<?php
  session_start();

  // 漏洞：垂直越权，仅判断登录态
  if (!isset($_SESSION['user_id'])) {
      header('Location: ../login.php');
      exit;
  }

  require_once '../conf/db.php';
  $conn = db_conn();

  // 漏洞：SQL 注入，$search 直接拼接
  $search = $_GET['search'] ?? '';
  if ($search !== '') {
      $sql = "SELECT a.id, a.title, a.created_at, u.username
              FROM articles a JOIN users u ON a.author_id = u.id
              WHERE a.title LIKE '%$search%' OR a.content LIKE '%$search%'
              ORDER BY a.id ASC";
  } else {
      $sql = "SELECT a.id, a.title, a.created_at, u.username
              FROM articles a JOIN users u ON a.author_id = u.id
              ORDER BY a.id ASC";
  }

  // 漏洞：报错直接输出
  $result   = $conn->query($sql) or die('查询出错：' . $conn->error);
  $articles = $result->fetch_all(MYSQLI_ASSOC);
  $conn->close();
  ?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>文章管理</title>
  </head>
  <body>

  <p><a href="index.php">← 返回后台首页</a></p>
  <h2>文章管理</h2>

  <form method="GET">
      <!-- 漏洞：search 参数反射到 value，反射型 XSS -->
      <input type="text" name="search" value="<?php echo $search; ?>" placeholder="搜索标题/内容">
      <button type="submit">搜索</button>
  </form>

  <hr>

  <table border="1" cellpadding="6">
      <tr>
          <th>ID</th><th>标题</th><th>作者</th><th>发布时间</th><th>操作</th>
      </tr>
      <?php foreach ($articles as $a): ?>
      <tr>
          <!-- 漏洞：字段直接输出，未转义，存储型 XSS -->
          <td><?php echo $a['id']; ?></td>
          <td>
              <a href="../article.php?id=<?php echo $a['id']; ?>" target="_blank">
                  <?php echo $a['title']; ?>
              </a>
          </td>
          <td><?php echo $a['username']; ?></td>
          <td><?php echo $a['created_at']; ?></td>
          <td>
              <!-- 漏洞：GET 删除 + 无 CSRF token -->
              <a href="delete.php?type=article&id=<?php echo $a['id']; ?>"
                 onclick="return confirm('确认删除该文章？')">删除</a>
          </td>
      </tr>
      <?php endforeach; ?>
  </table>

  </body>
  </html>