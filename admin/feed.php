<?php
session_start();

// 漏洞：垂直越权，仅判断登录态，不检查 role
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../conf/db.php';
$conn = db_conn();

$message  = '';
$error    = '';
$feeds    = [];
$feed_url = '';

// 获取所有已添加的 RSS 源
$feeds_result = $conn->query("SELECT * FROM feeds ORDER BY id DESC");
if ($feeds_result) {
    while ($row = $feeds_result->fetch_assoc()) {
        $feeds[] = $row;
    }
}

// 处理添加订阅（POST）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feed_url = trim($_POST['feed_url']);
    $name     = trim($_POST['name']);

    if ($feed_url === '') {
        $error = '请输入订阅地址';
    } else {
        // SSRF：直接使用 file_get_contents 获取任意 URL 内容，未做任何协议/内网限制
        // payload：http://attacker.com/evil.xml（远程恶意 XML）
        $xml = @file_get_contents($feed_url);

        if ($xml === false) {
            $error = '无法访问该地址，请检查 URL 是否有效';
        } else {
            // XXE：没有禁用外部实体，且使用 LIBXML_NOENT 允许展开外部实体
            // payload 恶意 XML 中的 &xxe; 会被替换为服务器本地文件内容
            $xmlData = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOENT);

            if ($xmlData === false) {
                $error = 'RSS 解析失败，请检查格式';
            } else {
                // 保存订阅源
                $safe_name = empty($name) ? $feed_url : $name;
                $conn->query("INSERT INTO feeds (name, url, article_count) VALUES ('$safe_name', '$feed_url', 0)");

                // 从 RSS 中提取文章
                $count = 0;
                foreach ($xmlData->channel->item as $item) {
                    $title = (string) $item->title;
                    $link  = (string) $item->link;
                    $desc  = (string) $item->description;
                    $pub   = isset($item->pubDate) ? (string) $item->pubDate : '';

                    // 避免重复添加
                    $checkStmt = $conn->prepare("SELECT id FROM articles WHERE title = ? LIMIT 1");
                    $checkStmt->bind_param('s', $title);
                    $checkStmt->execute();
                    $check = $checkStmt->get_result();
                    if ($check->num_rows === 0) {
                        $author_id = $_SESSION['user_id'];
                        $insertStmt = $conn->prepare("INSERT INTO articles (title, content, author_id) VALUES (?, ?, ?)");
                        $link = (string) $item->link;
                        $insertStmt->bind_param('ssi', $title, $desc, $author_id);
                        $insertStmt->execute();
                        $count++;
                    }
                    $checkStmt->close();
                    $insertStmt->close();
                }

                $message = "RSS 解析成功，导入 {$count} 篇文章";
            }
        }
    }
}

$conn->close();
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
    <p style="color:green;"><?php echo $message; ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<hr>
<h3>添加订阅</h3>
<form method="POST">
    <label>名称：<input type="text" name="name" placeholder="可选，留空则使用 URL" style="width:200px;"></label><br><br>
    <label>RSS 地址：<input type="text" name="feed_url" value="<?php echo htmlspecialchars($feed_url); ?>" placeholder="http://example.com/feed.xml" style="width:400px;"></label><br><br>
    <button type="submit">添加订阅</button>
</form>

<hr>
<h3>已添加的订阅</h3>

<?php if (empty($feeds)): ?>
    <p>暂无订阅</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <tr>
            <th>ID</th><th>名称</th><th>订阅地址</th><th>文章数</th><th>添加时间</th><th>操作</th>
        </tr>
        <?php foreach ($feeds as $f): ?>
        <tr>
            <td><?php echo $f['id']; ?></td>
            <td><?php echo $f['name']; ?></td>
            <td>
                <!-- 漏洞：URL 直接输出，未转义，反射型 XSS -->
                <a href="<?php echo $f['url']; ?>" target="_blank">
                    <?php echo $f['url']; ?>
                </a>
            </td>
            <td><?php echo $f['article_count']; ?></td>
            <td><?php echo $f['created_at']; ?></td>
            <td>
                <!-- 漏洞：无 CSRF token -->
                <a href="delete_feed.php?id=<?php echo $f['id']; ?>" onclick="return confirm('确认删除？')">删除</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<hr>
<h4>安全说明（靶场）</h4>
<ul>
    <li>此功能存在 <strong>SSRF</strong>：服务器会主动请求你输入的任意 URL</li>
    <li>此功能存在 <strong>XXE</strong>：解析 RSS XML 时允许外部实体展开</li>
    <li>可利用此漏洞读取服务器本地文件、探测内网服务</li>
    <li>详细利用方法见 <a href="../README.md">README.md</a></li>
</ul>

</body>
</html>
