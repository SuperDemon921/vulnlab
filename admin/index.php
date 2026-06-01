<?php
// session_start();
  require_once '../conf/db.php';
  require_admin();   //判断role是否为1

  $pdo           = db();
  $user_count    = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
  $article_count = (int)$pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn();
  $comment_count = (int)$pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();

// if (!isset($_SESSION['user_id'])) {
//     header('Location: ../index.php');
//     exit;
// }
?>

  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>管理后台</title>
  </head>
  <body>

  <h2>管理后台</h2>

 
  <p>当前登录：<?= e($_SESSION['username'] ?? '') ?>（角色 ID：<?= (int)current_user_role() ?>）</p>

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

  <table border="1" cellpadding="8">
      <tr><th>用户总数</th><th>文章总数</th><th>评论总数</th></tr>
      <tr>
          <td><?= $user_count ?></td>
          <td><?= $article_count ?></td>
          <td><?= $comment_count ?></td>
      </tr>
  </table>

  </body>
  </html> 
