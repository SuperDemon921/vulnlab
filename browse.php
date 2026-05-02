<?php
session_start();
require_once 'conf/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$html   = '';
$error  = '';
$target = $_GET['url'] ?? '';

// SSRF：直接使用 file_get_contents 获取任意 URL 内容，未做任何过滤
if ($target !== '') {
    // 漏洞：未限制协议（支持 file://）、未限制内网地址（127.0.0.1、10.x.x.x）
    // 漏洞：未校验 URL 格式，可注入 IP 或协议
    $result = @file_get_contents($target);

    if ($result !== false) {
        // 漏洞：将获取的内容直接输出到页面
        $html = '<h3>页面内容：</h3><pre>' . htmlspecialchars($result) . '</pre>';
    } else {
        $error = '无法访问该地址，请检查 URL 是否有效';
    }
}
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
    <input type="text" name="url" value="<?php echo htmlspecialchars($target); ?>" placeholder="输入网址，如 http://localhost/vulnlab/" style="width:400px;">
    <button type="submit">浏览</button>
</form>

<hr>

<?php if ($error): ?>
    <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<?php if ($html): ?>
    <p>正在浏览：<?php echo htmlspecialchars($target); ?></p>
    <?php echo $html; ?>
<?php endif; ?>

<hr>
<h4>SSRF 利用提示（靶场说明）</h4>
<ul>
    <li>访问内网服务：http://127.0.0.1:3306（数据库端口）</li>
    <li>读取本地文件：file:///C:/Windows/win.ini</li>
    <li>探测内网端口：http://10.0.0.1:8080</li>
    <li>读取数据库配置：http://127.0.0.1/vulnlab/conf/db.php</li>
</ul>

</body>
</html>
