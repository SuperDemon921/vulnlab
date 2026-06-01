 <?php
//   session_start();
  require_once 'conf/db.php';

//   $conn = db_conn();

//   // 漏洞：id 参数直接拼接，未做任何过滤，存在 SQL 注入
//   // 示例 payload：?id=1 UNION SELECT 1,username,password,4,5 FROM users--
//   $id  = $_GET['id'];
//   $sql = "SELECT a.*, u.username FROM articles a JOIN users u ON a.author_id = u.id WHERE a.id = $id";

//   // 漏洞：数据库报错直接输出，泄露表结构信息
//   $result = $conn->query($sql) or die('查询错误：' . $conn->error);
//   $article = $result->fetch_assoc();

//   if (!$article) {
//       die('文章不存在');
//   }

//   // 获取评论列表
//   $cResult  = $conn->query("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.article_id =
//   $id ORDER BY c.created_at ASC");
//   $comments = [];
//   while ($c = $cResult->fetch_assoc()) {
//       $comments[] = $c;
//   }

//   $conn->close();

$id = (int)($_GET['id'] ?? 0);    //将id转成int
  if ($id <= 0) { http_response_code(404); exit('文章不存在'); }

  $pdo = db();
  //预编译处理
  $stmt = $pdo->prepare('               
      SELECT a.id, a.title, a.content, a.created_at, u.username
      FROM articles a JOIN users u ON a.author_id = u.id
      WHERE a.id = ?');    
  $stmt->execute([$id]);
  $article = $stmt->fetch();
  if (!$article) { http_response_code(404); exit('文章不存在'); }

  //预编译处理
  $cs = $pdo->prepare('
      SELECT c.id, c.content, c.created_at, u.username
      FROM comments c JOIN users u ON c.user_id = u.id
      WHERE c.article_id = ? ORDER BY c.created_at ASC');
  $cs->execute([$id]);
  $comments = $cs->fetchAll();



  ?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title><?= e($article['title']) ?></title>
  </head>
  <body>

  <p><a href="index.php">← 返回首页</a></p>

  <article>
      <h2>
          <?= e($article['title']) ?>       
      </h2>
      <small>作者：<?= e($article['username']) ?> | <?= e($article['created_at']) ?></small>
      <hr>
      <div>
          <?= nl2br(e($article['content'])) ?>       <!--将文章换行内容自动加br标签并转义输出-->
      </div>
  </article>

  <hr>
  <h3>评论区</h3>

  <?php if (empty($comments)): ?>
      <p>暂无评论</p>
  <?php else: ?>
      <?php foreach ($comments as $c): ?>
          <div style="border:1px solid #eee; margin:6px; padding:8px;">
              <strong><?= e($c['username']) ?></strong>
              <small><?= e($c['created_at']) ?></small>
              <p>
                  <!-- 将评论换行自动加br标签 -->
                  <?= nl2br(e($c['content'])) ?>
              </p>
          </div>
      <?php endforeach; ?>
  <?php endif; ?>

  <hr>
  <h4>发表评论</h4>

  <?php if (current_user_id() > 0): ?>  <!--校验是否登录-->
      <form method="POST" action="comment.php">
          <!-- 生成CSRF隐藏表单自动提交 -->
           <?= csrf_field() ?>
          <input type="hidden" name="article_id" value="<?= (int)$id ?>">
          <textarea name="content" rows="4" cols="50" maxlength="2000" placeholder="输入评论内容..."></textarea><br><br>  <!--限制评论2000字-->
          <button type="submit">提交评论</button>
      </form>
  <?php else: ?>
      <p><a href="login.php">登录后</a>才能发表评论</p>
  <?php endif; ?>

  </body>
  </html>