  <?php
//   session_start();
require_once 'conf/db.php';
require_login();      //先校验有没有登录（有无session值）

  $uid     = current_user_id();
  $pdo     = db();
  $message = '';
  $error   = '';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      csrf_check();         //校验csrf_token是否存在

      $email    = trim((string)($_POST['email'] ?? ''));
      $password = (string)($_POST['password'] ?? '');

      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $error = '邮箱不合法';
      } elseif ($password !== '' && strlen($password) < 8) {
          $error = '密码至少 8 位';
      } else {
          if ($password !== '') {
              $hash = password_hash($password, PASSWORD_DEFAULT);
              $pdo->prepare('UPDATE users SET email = ?, password = ? WHERE id = ?')
                  ->execute([$email, $hash, $uid]);
          } else {
              $pdo->prepare('UPDATE users SET email = ? WHERE id = ?')
                  ->execute([$email, $uid]);
          }
          $message = '资料更新成功';
      }
  }

  $stmt = $pdo->prepare('SELECT id, username, email, role, avatar, created_at FROM users WHERE id = ?');
  $stmt->execute([$uid]);
  $user = $stmt->fetch();
  if (!$user) { http_response_code(404); exit('用户不存在'); }

  //头像路径安全校验
  $avatarSafe = preg_match('#^uploads/[A-Za-z0-9_.\-]+$#', (string)$user['avatar'])   //路径必须以uploads/开头，白名单形式
      ? $user['avatar']
      : 'uploads/default.png';     //路径不符合规则强制使用默认头像


//   if (!isset($_SESSION['user_id'])) {
//       header('Location: login.php');
//       exit;
//   }

//   // 漏洞1：水平越权 —— 直接取 GET 参数 id，不校验是否是当前登录用户
//   // 任意登录用户访问 ?id=1 即可查看管理员资料
//   // ?id=2 可查看其他用户资料
//   $id   = $_GET['id'] ?? $_SESSION['user_id'];
//   $conn = db_conn();

//   $message = '';

//   // 处理修改资料（POST）
//   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//       $email    = $_POST['email'];
//       $password = $_POST['password'];

//       // 漏洞2：水平越权 —— 任意登录用户可修改任意 id 用户的资料
//       // 漏洞3：SQL 注入 —— 字段未过滤直接拼接
//       // 漏洞4：密码明文更新
//       if ($password !== '') {
//           $sql = "UPDATE users SET email='$email', password='$password' WHERE id=$id";
//       } else {
//           $sql = "UPDATE users SET email='$email' WHERE id=$id";
//       }

//       // 漏洞5：报错直接输出
//       $conn->query($sql) or die('更新失败：' . $conn->error);
//       $message = '资料更新成功';
//   }

//   // 漏洞6：不安全的反序列化 ——
//   // 若 cookie 中 user_info 存在，直接反序列化取值，
//   // 攻击者可伪造 cookie：将 role 改为 1 获得管理员标识
//   // 序列化格式示例：a:3:{s:2:"id";i:2;s:8:"username";s:5:"alice";s:4:"role";i:1;}
//   if (isset($_COOKIE['user_info'])) {
//       $cookie_user = unserialize($_COOKIE['user_info']);
//       // 漏洞：用 cookie 中的 role 覆盖 session，身份可被伪造
//       if (isset($cookie_user['role'])) {
//           $_SESSION['role'] = $cookie_user['role'];
//       }
//   }

//   // 查询目标用户信息（直接拼接 $id，SQL 注入）
//   $result = $conn->query("SELECT id, username, email, role, avatar, created_at FROM users WHERE id = $id")
//       or die('查询失败：' . $conn->error);

//   $user = $result->fetch_assoc();
//   $conn->close();

//   if (!$user) {
//       die('用户不存在');
//   }
  ?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>个人中心</title>
  </head>
  <body>

  <p><a href="index.php">← 返回首页</a></p>

  <h2>个人中心</h2>

  <?php if ($message): ?>
      <p style="color:green;"><?= e($message) ?></p>
  <?php endif; ?>

  
   <?php if ($error): ?>
      <p style="color:red;"><?= e($error) ?></p>
  <?php endif; ?>

  

  <table border="1" cellpadding="6">
      <tr><th>字段</th><th>值</th></tr>
      <tr><td>用户名</td><td><?= e($user['username']) ?></td></tr>
      <tr><td>邮　箱</td><td><?= e($user['email']) ?></td></tr>
      <tr><td>角　色</td><td><?= ((int)$user['role'] === 1) ? '管理员' : '普通用户' ?></td></tr>
      <tr><td>注册时间</td><td><?= e($user['created_at']) ?></td></tr>
      <tr>
          <td>头　像</td>
          <td><img src="<?= e($avatarSafe) ?>" width="60" height="60" alt=""></td>       <!--转义头像链接-->
      </tr>
  </table>

  <hr>
  <h3>修改资料</h3>
  <form method="POST" action="profile.php">
    <!-- 加载csrf_token -->
    <?= csrf_field() ?>     
      <label>新邮箱：<input type="text" name="email" value="<?= e($user['email']) ?>" maxlength="100"></label><br><br>   <!--转义数据库中的用户邮箱，限制长度-->
      <label>新密码：<input type="password" name="password" placeholder="不修改请留空"></label><br><br>
      <button type="submit">保存修改</button>
  </form>

  <hr>
  <p><a href="upload.php">上传头像</a></p>

  </body>
  </html>