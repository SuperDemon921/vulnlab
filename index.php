<?php
session_start();
require_once 'conf/db.php';

$conn = db_conn();

$sql = "SELECT a.id, a.title, a.content, a.created_at, u.username
        FROM articles a
        JOIN users u ON a.author_id = u.id
        ORDER BY a.created_at DESC";
$result = $conn->query($sql);
$conn->close();
?>
<!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>靶场博客系统</title>
  </head>
  <body>

  <h2>靶场博客系统</h2>

  <nav>
      <?php if (isset($_SESSION['user_id'])): ?>
          欢迎，<?php echo $_SESSION['username']; ?> |
          <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>">个人中心</a> |
          <a href="upload.php">上传文件</a> |
          <?php if ($_SESSION['role'] == 1): ?>
              <a href="admin/index.php">管理后台</a> |
          <?php endif; ?>
          <a href="logout.php">退出</a>
      <?php else: ?>
          <a href="login.php">登录</a> |
          <a href="register.php">注册</a>
      <?php endif; ?>
      &nbsp;|&nbsp;
      <form method="GET" action="search.php" style="display:inline;">
          <input type="text" name="q" placeholder="搜索文章...">
          <button type="submit">搜索</button>
      </form>
  </nav>

  <hr>

  <h3>最新文章</h3>

  <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
          <div style="border:1px solid #ccc; margin:10px; padding:10px;">
              <h4>
                  <!-- 漏洞：文章标题未转义，存储型 XSS -->
                  <a href="article.php?id=<?php echo $row['id']; ?>">
                      <?php echo $row['title']; ?>
                  </a>
              </h4>
              <p>
                  <!-- 漏洞：内容截断展示但仍未转义 -->
                  <?php echo mb_substr($row['content'], 0, 80); ?>...
              </p>
              <small>作者：<?php echo $row['username']; ?> | 时间：<?php echo $row['created_at']; ?></small>
          </div>
      <?php endwhile; ?>
  <?php else: ?>
      <p>暂无文章</p>
  <?php endif; ?>

  </body>
  </html>

