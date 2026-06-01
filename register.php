<?php
// session_start();
require_once 'conf/db.php';

$error   = '';
// $success = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();    //校验csrf token
    // $username = $_POST['username'];
    // $password = $_POST['password'];
    // $email    = $_POST['email'];
    $username = trim((string)($_POST['username'] ?? ''));  //将用户输入的内容转字符串，且去空格
    $password = (string)($_POST['password'] ?? '');
    $email    = trim((string)($_POST['email'] ?? ''));

    // $conn = db_conn();

    if (!preg_match('/^[A-Za-z0-9_]{3,32}$/',$username)) {   //校验用户名，仅限大小写字母和数字下划线，长度3-32
        $error = '用户名不合法';
    } 
    elseif (strlen($password) < 8) {     //校验密码长度
            $error = '密码至少8位';
        }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {    //使用php内置函数校验邮箱合法性
        $error = '邮箱不合法';
    } 
    else {                                                //所有校验均通过后，进入注册流程
        $pdo = db();
        $check = $pdo->prepare('SELECT 1 FROM users WHERE username = ?');    //校验用户名是否存在
        $check->execute([$username]);
        if ($check->fetchColumn()) {
            $error = '用户已存在';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);       //哈希加密
            $ins = $pdo->prepare('INSERT INTO users (username, password, email, role) VALUES (?,?,?,0)');
            $ins->execute([$username, $hash, $email]);
            $success = '注册成功';
        }
    }




    // $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    // if ($check && $check->num_rows > 0) {
    //     $error = '用户名已存在';
    // } else {

    //     $sql = "INSERT INTO users (username, password, email, role)
    //             VALUES ('$username','$password','$email',0)";

    //     if ($conn->query($sql)) {
    //         $success = '注册成功， <a href="login.php">点击登录</a>';
    //     } else {
    //         $error = '注册失败：' . $conn->error;

    //     }
    // }
    // $conn->close();
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
          <p style="color:red;"><?= e($error) ?></p>
      <?php endif; ?>

      <?php if ($success): ?>
           <p style="color:green;">注册成功，<a href="login.php">点击登录</a></p>
      <?php endif; ?>

      <form method="POST">
        <!-- 插入隐式csrf token提交 -->
        <?= csrf_field() ?>                                                   
          <label>用户名：<input type="text" name="username" maxlength="32"></label><br><br>
          <label>密　码：<input type="password" name="password"></label><br><br>
          <label>邮　箱：<input type="text" name="email" maxlength="100"></label><br><br>
          <button type="submit">注册</button>
      </form>
      <p>已有账号？<a href="login.php">立即登录</a></p>
  </body>
  </html>