  <?php
  session_start();
  require_once 'conf/db.php';

  // 漏洞1：反射型 XSS —— $q 直接输出到页面，未做任何转义
  // 漏洞2：SQL 注入 —— $q 直接拼入查询语句
  $q = $_GET['q'] ?? '';

  $results = [];

  if ($q !== '') {
      $conn = db_conn();

      // 漏洞：SQL 注入
      // payload：' UNION SELECT id,username,password,email,created_at FROM users--
      $sql    = "SELECT a.id, a.title, a.content, a.created_at, u.username
                 FROM articles a
                 JOIN users u ON a.author_id = u.id
                 WHERE a.title LIKE '%$q%' OR a.content LIKE '%$q%'";

      // 漏洞：报错直接输出
      $result = $conn->query($sql) or die('查询出错：' . $conn->error);

      while ($row = $result->fetch_assoc()) {
          $results[] = $row;
      }

      $conn->close();
  }
  ?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>搜索结果</title>
  </head>
  <body>

  <p><a href="index.php">← 返回首页</a></p>

  <h2>搜索结果</h2>

  <form method="GET" action="search.php">
      <!-- 漏洞：value 直接输出 $q，反射型 XSS
           payload：?q=<script>alert(document.cookie)</script>  -->
      <input type="text" name="q" value="<?php echo $q; ?>" style="width:300px;">
      <button type="submit">搜索</button>
  </form>

  <hr>

  <?php if ($q !== ''): ?>
      <!-- 漏洞：$q 直接拼入字符串输出，反射型 XSS 二次触发点 -->
      <p>关键词 "<b><?php echo $q; ?></b>" 的搜索结果，共 <?php echo count($results); ?> 条：</p>
  <?php endif; ?>

  <?php if (!empty($results)): ?>
      <?php foreach ($results as $row): ?>
          <div style="border:1px solid #ccc; margin:10px; padding:10px;">
              <h4>
                  <!-- 漏洞：标题未转义，若数据库内容含 XSS payload 则触发存储型 XSS -->
                  <a href="article.php?id=<?php echo $row['id']; ?>">
                      <?php echo $row['title']; ?>
                  </a>
              </h4>
              <p><?php echo mb_substr($row['content'], 0, 100); ?>...</p>
              <small>作者：<?php echo $row['username']; ?> | <?php echo $row['created_at']; ?></small>
          </div>
      <?php endforeach; ?>
  <?php elseif ($q !== ''): ?>
      <p>未找到相关文章</p>
  <?php endif; ?>

  </body>
  </html>