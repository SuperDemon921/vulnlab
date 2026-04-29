<?php
session_start();
require_once 'conf/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = db_conn();

    $sql = " SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($sql);

    if($result && $result->num_rows >0){
        $user = $result->fetch_assoc();

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        setcookie('user_info',serialize([
            'id'        => $user['id'],
            'username'  => $user['username'],
            'role'      => $user['role'],
        ]),time() + 86400);    //无 HttpOnly / Secure 标志
        header('Location: index.php');
        exit;
    }else {
        $error = '用户名或密码错误';
    }
    $conn->close();
}
?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>登录</title>
  </head>
  <body>
      <h2>用户登录</h2>

      <?php if ($error): ?>
          <!-- 漏洞：错误信息直接输出，可能泄露系统信息 -->
          <p style="color:red;"><?php echo $error; ?></p>
      <?php endif; ?>

      <form method="POST">
          <label>用户名：<input type="text" name="username"></label><br><br>
          <label>密　码：<input type="password" name="password"></label><br><br>
          <button type="submit">登录</button>
      </form>
      <p>没有账号？<a href="register.php">立即注册</a></p>
  </body>
  </html>