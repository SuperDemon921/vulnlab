<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>

  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>管理后台</title>
  </head>
  <body>

  <h2>管理后台</h2>

  <!-- 漏洞：直接输出 session 用户名，未转义 -->
  <p>当前登录：<?php echo $_SESSION['username']; ?>（角色 ID：<?php echo $_SESSION['role']; ?>）</p>

  <nav>
      <a href="users.php">用户管理</a> |
      <a href="articles.php">文章管理</a> |
      <a href="comments.php">评论管理</a> |
      <a href="feed.php">RSS订阅管理</a> |
      <a href="../index.php">返回前台</a> |
      <a href="../logout.php">退出</a>
  </nav>

  <hr>

  <h3>快速统计</h3>

  <?php
  require_once '../conf/db.php';
  $conn = db_conn();

  // 漏洞：直接拼接，虽无外部输入，但习惯性不用预处理
  $user_count    = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
  $article_count = $conn->query("SELECT COUNT(*) AS c FROM articles")->fetch_assoc()['c'];
  $comment_count = $conn->query("SELECT COUNT(*) AS c FROM comments")->fetch_assoc()['c'];
  $conn->close();
  ?>

  <table border="1" cellpadding="8">
      <tr><th>用户总数</th><th>文章总数</th><th>评论总数</th></tr>
      <tr>
          <td><?php echo $user_count; ?></td>
          <td><?php echo $article_count; ?></td>
          <td><?php echo $comment_count; ?></td>
      </tr>
  </table>

  </body>
  </html> 
