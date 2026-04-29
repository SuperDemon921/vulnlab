 <?php
  session_start();
  require_once 'conf/db.php';

  $conn = db_conn();

  // 漏洞：id 参数直接拼接，未做任何过滤，存在 SQL 注入
  // 示例 payload：?id=1 UNION SELECT 1,username,password,4,5 FROM users--
  $id  = $_GET['id'];
  $sql = "SELECT a.*, u.username FROM articles a JOIN users u ON a.author_id = u.id WHERE a.id = $id";

  // 漏洞：数据库报错直接输出，泄露表结构信息
  $result = $conn->query($sql) or die('查询错误：' . $conn->error);
  $article = $result->fetch_assoc();

  if (!$article) {
      die('文章不存在');
  }

  // 获取评论列表
  $cResult  = $conn->query("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.article_id =
  $id ORDER BY c.created_at ASC");
  $comments = [];
  while ($c = $cResult->fetch_assoc()) {
      $comments[] = $c;
  }

  $conn->close();
  ?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title><?php echo $article['title']; ?></title>
  </head>
  <body>

  <p><a href="index.php">← 返回首页</a></p>

  <article>
      <h2>
          <!-- 漏洞：标题直接输出，未 htmlspecialchars，存储型 XSS -->
          <?php echo $article['title']; ?>
      </h2>
      <small>作者：<?php echo $article['username']; ?> | <?php echo $article['created_at']; ?></small>
      <hr>
      <div>
          <!-- 漏洞：正文直接输出，存储型 XSS -->
          <?php echo $article['content']; ?>
      </div>
  </article>

  <hr>
  <h3>评论区</h3>

  <?php if (empty($comments)): ?>
      <p>暂无评论</p>
  <?php else: ?>
      <?php foreach ($comments as $c): ?>
          <div style="border:1px solid #eee; margin:6px; padding:8px;">
              <strong><?php echo $c['username']; ?></strong>
              <small><?php echo $c['created_at']; ?></small>
              <p>
                  <!-- 漏洞：评论内容未转义，存储型 XSS 直接触发 -->
                  <?php echo $c['content']; ?>
              </p>
          </div>
      <?php endforeach; ?>
  <?php endif; ?>

  <hr>
  <h4>发表评论</h4>

  <?php if (isset($_SESSION['user_id'])): ?>
      <form method="POST" action="comment.php">
          <!-- 漏洞：无 CSRF token -->
          <input type="hidden" name="article_id" value="<?php echo $id; ?>">
          <textarea name="content" rows="4" cols="50" placeholder="输入评论内容..."></textarea><br><br>
          <button type="submit">提交评论</button>
      </form>
  <?php else: ?>
      <p><a href="login.php">登录后</a>才能发表评论</p>
  <?php endif; ?>

  </body>
  </html>