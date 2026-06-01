<?php
require_once '../conf/db.php';
require_admin();

$pdo      = db();
$message  = '';
$error    = '';
$feed_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      csrf_check();

      $feed_url = trim((string)($_POST['feed_url'] ?? ''));
      $name     = trim((string)($_POST['name'] ?? ''));

      if ($feed_url === '') {
          $error = '请输入订阅地址';
      } else {
          try {
              $xml = safe_fetch($feed_url);

              // 关闭外部实体，禁止网络访问。php8.0默认禁用外部实体，所以加个版本判断
              if (\PHP_VERSION_ID < 80000) { libxml_disable_entity_loader(true); }
              //XML 解析错误不直接输出到页面
              libxml_use_internal_errors(true);

              $dom = new DOMDocument();
          //使用DOMDocument对象安全解析xml，解析过程中禁止网络请求，不产生警告，不产生错误输出
              $ok  = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);   
              if (!$ok) {
                  $error = 'RSS 解析失败';
              } else {
                  // 遍历根节点，发现有 DOCTYPE 节点直接抛异常，显式拒绝带 DOCTYPE 的输入（RSS 不需要 DTD）
                  foreach ($dom->childNodes as $child) {
                      if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
                          throw new RuntimeException('禁止包含 DOCTYPE 的 XML');
                      }
                  }
                  //将已经安全解析好的DOMDocument对象转成SimpleXMLElement方便读取数据
                  $xmlData = simplexml_import_dom($dom);
                  $safe_name = $name !== '' ? $name : $feed_url;                                    //若用户未传name，则使用url替代

                  $pdo->prepare('INSERT INTO feeds (name, url, article_count) VALUES (?, ?, 0)')    //预编译写入数据库
                      ->execute([$safe_name, $feed_url]);

                      //遍历文章并去重写入
                  $count = 0;
                  if (isset($xmlData->channel->item)) {
                    //遍历 RSS 的每一个 <item>，先查标题是否已存在（去重），不存在才插入
                      foreach ($xmlData->channel->item as $item) {      
                          $title = trim((string)$item->title);          //提取文章title
                          $desc  = (string)$item->description;          //提取文章描述
                          if ($title === '') continue;

                          $check = $pdo->prepare('SELECT 1 FROM articles WHERE title = ? LIMIT 1');   //检查文章标题是否存在，LIMIT 1 让查询尽早停止，性能上更合理。
                          $check->execute([$title]);
                          if (!$check->fetchColumn()) {
                              $ins = $pdo->prepare(
                                  'INSERT INTO articles (title, content, author_id) VALUES (?, ?, ?)'
                              );
                              $ins->execute([$title, $desc, current_user_id()]);  //将文章信息写入数据库
                              $count++;
                          }
                      }
                  }
                  $message = "RSS 解析成功，导入 {$count} 篇文章";
              }
          } catch (Throwable $ex) {
              $error = $ex->getMessage();
          }
      }
  }
  $feeds = $pdo->query('SELECT id, name, url, article_count, created_at FROM feeds ORDER BY id DESC')->fetchAll();  //查询已有订阅列表
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>RSS 订阅管理</title>
</head>
<body>

<p><a href="index.php">← 返回后台首页</a></p>
<h2>RSS 订阅管理</h2>

<?php if ($message): ?>
    <p style="color:green;"><?= e($message) ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color:red;"><?= e($error) ?></p>
<?php endif; ?>

<hr>
<h3>添加订阅</h3>
<form method="POST">
    <?= csrf_field() ?>
    <label>名称：<input type="text" name="name" placeholder="可选" style="width:200px;"></label><br><br>
      <label>RSS 地址：
          <input type="text" name="feed_url" value="<?= e($feed_url) ?>"
                 placeholder="http(s)://example.com/feed.xml" style="width:400px;">
      </label><br><br>
      <button type="submit">添加订阅</button>
</form>

<hr>
<h3>已添加的订阅</h3>

<?php if (empty($feeds)): ?>
    <p>暂无订阅</p>
<?php else: ?>
    <table border="1" cellpadding="6">
          <tr><th>ID</th><th>名称</th><th>订阅地址</th><th>文章数</th><th>添加时间</th><th>操作</th></tr>
          <?php foreach ($feeds as $f): ?>
          <tr>
              <td><?= (int)$f['id'] ?></td>
              <td><?= e($f['name']) ?></td>
              <td><?= e($f['url']) ?></td>
              <td><?= (int)$f['article_count'] ?></td>
              <td><?= e($f['created_at']) ?></td>
              <td>
                  <form method="POST" action="delete_feed.php" style="display:inline" onsubmit="return
  confirm('确认删除？')">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                      <button type="submit">删除</button>
                  </form>
              </td>
          </tr>
          <?php endforeach; ?>
      </table>
<?php endif; ?>

</body>
</html>
