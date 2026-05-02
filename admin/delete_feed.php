<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../conf/db.php';
$id = $_GET['id'] ?? '';

if ($id === '') {
    die('参数缺失');
}

$conn = db_conn();
$conn->query("DELETE FROM feeds WHERE id = $id") or die('删除失败：' . $conn->error);
$conn->close();

header('Location: feed.php');
exit;
