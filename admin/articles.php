<?php
//   session_start();
require_once '../conf/db.php';
require_admin();

  $pdo    = db();
  $search = trim((string)($_GET['search'] ?? ''));

  if ($search !== '') {
      $like = '%' . $search . '%';
      $stmt = $pdo->prepare('
          SELECT a.id, a.title, a.created_at, u.username
          FROM articles a JOIN users u ON a.author_id = u.id
          WHERE a.title LIKE ? OR a.content LIKE ?
          ORDER BY a.id ASC');
      $stmt->execute([$like, $like]);
  } else {
      $stmt = $pdo->query('
          SELECT a.id, a.title, a.created_at, u.username
          FROM articles a JOIN users u ON a.author_id = u.id
          ORDER BY a.id ASC');
  }
  $articles = $stmt->fetchAll();
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
      
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="搜索标题/内容">
      <button type="submit">搜索</button>
  </form>

  <hr>

  <table border="1" cellpadding="6">
      <tr><th>ID</th><th>标题</th><th>作者</th><th>发布时间</th><th>操作</th></tr>
      <?php foreach ($articles as $a): ?>
      <tr>
          <td><?= (int)$a['id'] ?></td>
          <td>
              <a href="../article.php?id=<?= (int)$a['id'] ?>" target="_blank">
                  <?= e($a['title']) ?>
              </a>
          </td>
          <td><?= e($a['username']) ?></td>
          <td><?= e($a['created_at']) ?></td>
          <td>
              <form method="POST" action="delete.php" style="display:inline" onsubmit="return confirm('确认删除？')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="type" value="article">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <button type="submit">删除</button>
              </form>
          </td>
      </tr>
      <?php endforeach; ?>
  </table>

  </body>
  </html>