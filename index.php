<?php
// session_start();
require_once 'conf/db.php';

  $stmt = db()->query('
      SELECT a.id, a.title, a.content, a.created_at, u.username
      FROM articles a
      JOIN users u ON a.author_id = u.id
      ORDER BY a.created_at DESC');
  $articles = $stmt->fetchAll();

// $conn = db_conn();

// $sql = "SELECT a.id, a.title, a.content, a.created_at, u.username
//         FROM articles a
//         JOIN users u ON a.author_id = u.id
//         ORDER BY a.created_at DESC";
// $result = $conn->query($sql);
// $conn->close();
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
      <?php if (current_user_id() > 0): ?>
          欢迎，<?= e($_SESSION['username'] ?? '') ?> |
          <a href="profile.php">个人中心</a> |
          <a href="upload.php">上传文件</a> |
          <a href="browse.php">网页浏览</a> |
          <?php if (current_user_role() === 1): ?>
              <a href="admin/index.php">管理后台</a> |
          <?php endif; ?>
          <a href="logout.php">退出</a>
      <?php else: ?>
          <a href="login.php">登录</a> | <a href="register.php">注册</a>
      <?php endif; ?>
      &nbsp;|&nbsp;
      <form method="GET" action="search.php" style="display:inline;">
          <input type="text" name="q" placeholder="搜索文章...">
          <button type="submit">搜索</button>
      </form>
  </nav>

  <hr>

  <h3>最新文章</h3>

  <?php if ($articles): ?>
      <?php foreach ($articles as $row): ?>
          <div style="border:1px solid #ccc; margin:10px; padding:10px;">
              <h4>
                  <a href="article.php?id=<?= (int)$row['id'] ?>">
                      <?= e($row['title']) ?>
                  </a>
              </h4>
              <p><?= e(mb_substr($row['content'], 0, 80)) ?>...</p>
              <small>作者：<?= e($row['username']) ?> | 时间：<?= e($row['created_at']) ?></small>
          </div>
      <?php endforeach; ?>
  <?php else: ?>
      <p>暂无文章</p>
  <?php endif; ?>

  </body>
  </html>

