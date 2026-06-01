<?php
// session_start();
require_once 'conf/db.php';
require_login();

  $target = trim((string)($_GET['url'] ?? ''));  //简单处理用户传递的url，默认为空字符串
  $html   = '';
  $error  = '';

  if ($target !== '') {
      try {
          $raw  = safe_fetch($target);                              //fetch页面
          $html = '<h3>页面内容：</h3><pre>' . e($raw) . '</pre>';
      } catch (Throwable $ex) {
          $error = $ex->getMessage();
      }
  }

// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

// $html   = '';
// $error  = '';
// $target = $_GET['url'] ?? '';

// // SSRF：直接使用 file_get_contents 获取任意 URL 内容，未做任何过滤
// if ($target !== '') {
//     // 漏洞：未限制协议（支持 file://）、未限制内网地址（127.0.0.1、10.x.x.x）
//     // 漏洞：未校验 URL 格式，可注入 IP 或协议
//     $result = @file_get_contents($target);

//     if ($result !== false) {
//         // 漏洞：将获取的内容直接输出到页面
//         $html = '<h3>页面内容：</h3><pre>' . htmlspecialchars($result) . '</pre>';
//     } else {
//         $error = '无法访问该地址，请检查 URL 是否有效';
//     }
// }
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>网页浏览</title>
</head>
<body>

<p><a href="index.php">← 返回首页</a></p>

<h2>网页浏览</h2>

<form method="GET">
    <input type="text" name="url" value="<?= e($target) ?>" placeholder="http(s):// 公网地址" style="width:400px;">
    <button type="submit">浏览</button>
</form>

<hr>

<?php if ($error): ?>
    <p style="color:red;"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($html): ?>
    <p>正在浏览：<?= e($target) ?></p>
    <?= $html /* 已是经过 e() 处理过的内容 */ ?>
<?php endif; ?>

</body>
</html>
