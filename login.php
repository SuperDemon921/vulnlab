<?php
// session_start();
require_once 'conf/db.php';

  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';   //获取客户端ip
  login_throttle_check($ip);                    //检查ip是否被限制

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    csrf_check();     //添加csrf token检测
    // $username = $_POST['username'];
    // $password = $_POST['password'];
    $username = trim((string)($_POST['username'] ?? ''));    //将用户名去空格，并强转成字符串，避免用户传递数组导致报错，且默认为空字符串，不会报错
    $password = (string)($_POST['password'] ?? '');   //密码转字符串，默认为空字符串

    // $conn = db_conn();
    $stmt = db()->prepare('SELECT id,username,password,role FROM users WHERE username= ? LIMIT 1');   //预处理语句，不select * ，只查需要的字段，避免敏感信息泄露，最小化原则
    //只查username，因为密码是哈希值，无法明文比对
    $stmt ->execute([$username]);
    $user = $stmt->fetch();   //获取查询结果，db.php中定义了返回关联数组

    if ($user && password_verify($password,$user['password'])) {
        login_throttle_reset($ip);     //密码校验成功后，重置ip登录限制
        session_regenerate_id(true);   //登录成功后，session id 重新生成，且旧id删除，防止会话固定攻击
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = (int)$user['role'];

        if (password_needs_rehash((string)$user['password'], PASSWORD_DEFAULT)) {  //如果用户密码不是hash值，则自动生成hash值并更新数据库
              $newHash = password_hash($password, PASSWORD_DEFAULT);
              db()->prepare('UPDATE users SET password = ? WHERE id = ?')
                  ->execute([$newHash, (int)$user['id']]);
          }

        header('Location: index.php');
        exit;
    } else {
          login_throttle_fail($ip);       //登录失败，设置ip限制
          $error = '用户名或密码错误';
    }
    

    // $sql = " SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    // $result = $conn->query($sql);

    // if($result && $result->num_rows >0){
    //     $user = $result->fetch_assoc();

    //     $_SESSION['user_id'] = $user['id'];
    //     $_SESSION['username'] = $user['username'];
    //     $_SESSION['role'] = $user['role'];

    //     setcookie('user_info',serialize([
    //         'id'        => $user['id'],
    //         'username'  => $user['username'],
    //         'role'      => $user['role'],
    //     ]),time() + 86400);    //无 HttpOnly / Secure 标志
    //     header('Location: index.php');
    //     exit;
    // }else {
    //     $error = '用户名或密码错误';
    // }
    // $conn->close();
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
          <!-- 转义错误信息后输出 -->
          <p style="color:red;"><?= e($error) ?></p>    
      <?php endif; ?>
                 <!-- 前端限制用户名长度 -->
      <form method="POST">
        <?= csrf_field() ?>
          <label>用户名：<input type="text" name="username" maxlength="32"></label><br><br>    
          <label>密　码：<input type="password" name="password"></label><br><br>
          <button type="submit">登录</button>
      </form>
      <p>没有账号？<a href="register.php">立即注册</a></p>
  </body>
  </html>