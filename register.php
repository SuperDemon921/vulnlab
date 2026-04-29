<?php
session_start();
require_once 'conf/db.php';

$error   = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email    = $_POST['email'];

    $conn = db_conn();

    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check && $check->num_rows > 0) {
        $error = '用户名已存在';
    } else {

        $sql = "INSERT INTO users (username, password, email, role)
                VALUES ('$username','$password','$email',0)";

        if ($conn->query($sql)) {
            $success = '注册成功， <a href="login.php">点击登录</a>';
        } else {
            $error = '注册失败：' . $conn->error;

        }
    }
    $conn->close();
}
?>
 <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>注册</title>
  </head>
  <body>
      <h2>用户注册</h2>

      <?php if ($error): ?>
          <p style="color:red;"><?php echo $error; ?></p>
      <?php endif; ?>

      <?php if ($success): ?>
          <!-- 漏洞：success 信息未转义，若含 HTML 会直接渲染（存储型 XSS 入口） -->
          <p style="color:green;"><?php echo $success; ?></p>
      <?php endif; ?>

      <form method="POST">
          <label>用户名：<input type="text" name="username"></label><br><br>
          <label>密　码：<input type="password" name="password"></label><br><br>
          <label>邮　箱：<input type="text" name="email"></label><br><br>
          <button type="submit">注册</button>
      </form>
      <p>已有账号？<a href="login.php">立即登录</a></p>
  </body>
  </html>