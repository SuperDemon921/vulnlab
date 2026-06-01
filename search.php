  <?php
//   session_start();
  require_once 'conf/db.php';

  $q = trim((string)($_GET['q'] ?? ''));     //将用户输入转为字符串，过滤空格，默认为空
  $results = [];
  if ($q !== '') {           
      $like = '%' . $q . '%';         //将用户输入的关键字前后加上%，以便模糊查询
      //预编译处理
      $stmt = db()->prepare('    
          SELECT a.id, a.title, a.content, a.created_at, u.username
          FROM articles a JOIN users u ON a.author_id = u.id
          WHERE a.title LIKE ? OR a.content LIKE ?');
      $stmt->execute([$like, $like]);      //模糊查询
      $results = $stmt->fetchAll();
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
      
      <input type="text" name="q" value="<?= e($q) ?>" style="width:300px;">  <!--转义用户输入-->
      <button type="submit">搜索</button>
  </form>

  <hr>

  <?php if ($q !== ''): ?>
      
      <p>关键词 "<b><?= e($q) ?></b>" 的搜索结果，共  <?= count($results) ?> 条：</p>
  <?php endif; ?>

  <?php if (!empty($results)): ?>
      <?php foreach ($results as $row): ?>
          <div style="border:1px solid #ccc; margin:10px; padding:10px;">
              <h4>
                  <!-- 漏洞：标题未转义，若数据库内容含 XSS payload 则触发存储型 XSS -->
                  <a href="article.php?id=<?= (int)$row['id'] ?>">
                      <?= e($row['title']) ?>
                  </a>
              </h4>
              <p><?= e(mb_substr($row['content'], 0, 100)) ?>...</p>
              <small>作者：<?= e($row['username']) ?> | <?= e($row['created_at']) ?></small>
          </div>
      <?php endforeach; ?>
  <?php elseif ($q !== ''): ?>
      <p>未找到相关文章</p>
  <?php endif; ?>

  </body>
  </html>