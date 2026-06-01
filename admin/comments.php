 <?php
  require_once '../conf/db.php';
  require_admin();

  $pdo    = db();
  $search = trim((string)($_GET['search'] ?? ''));
  //评论查询基础语句
  $base = '
      SELECT c.id, c.content, c.created_at, u.username,
             a.title AS article_title, a.id AS article_id
      FROM comments c
      JOIN users u    ON c.user_id   = u.id
      JOIN articles a ON c.article_id = a.id ';

  if ($search !== '') {
      $like = '%' . $search . '%';
      $stmt = $pdo->prepare($base . 'WHERE c.content LIKE ? OR u.username LIKE ? ORDER BY c.id DESC');  //拼接查询where条件
      $stmt->execute([$like, $like]);
  } else {
      $stmt = $pdo->query($base . 'ORDER BY c.id DESC');        //默认查询所有评论
  }
  $comments = $stmt->fetchAll();
?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>评论管理</title>
  </head>
  <body>

  <p><a href="index.php">← 返回后台首页</a></p>
  <h2>评论管理</h2>

  <form method="GET">
      
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="搜索评论内容/用户名">
      <button type="submit">搜索</button>
  </form>

  <hr>

  <table border="1" cellpadding="6">
      <tr>
          <th>ID</th><th>评论者</th><th>所属文章</th><th>内容</th><th>时间</th><th>操作</th>
      </tr>
      <?php foreach ($comments as $c): ?>
      <tr>
          
          <td><?= (int)$c['id'] ?></td>
          <td><?= e($c['username']) ?></td>
          <td>
              <a href="../article.php?id=<?= (int)$c['article_id'] ?>" target="_blank">
                  <?= e($c['article_title']) ?>
              </a>
          </td>
          <td><?= e($c['content']) ?></td>
          <td><?= e($c['created_at']) ?></td>
          <td>
                 <!--删除评论-->
              <form method="POST" action="delete.php" style="display:inline" onsubmit="return confirm('确认删除？')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="type" value="comment">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button type="submit">删除</button>
              </form>
          </td>
      </tr>
      <?php endforeach; ?>
  </table>

  </body>
  </html>