  <?php
  session_start();

  // 漏洞：垂直越权 —— 同样只判断登录态，不检查 role
  if (!isset($_SESSION['user_id'])) {
      header('Location: ../login.php');
      exit;
  }

  require_once '../conf/db.php';
  $conn = db_conn();

  $message = '';

  // 处理搜索
  // 漏洞：SQL 注入 —— $search 直接拼入查询
  // payload：' UNION SELECT 1,2,3,4,5,6,7--
  $search = $_GET['search'] ?? '';
  if ($search !== '') {
      $sql = "SELECT id, username, email, role, created_at FROM users
              WHERE username LIKE '%$search%' OR email LIKE '%$search%'";
  } else {
      $sql = "SELECT id, username, email, role, created_at FROM users ORDER BY id ASC";
  }

  // 漏洞：报错直接输出，泄露表结构
  $result = $conn->query($sql) or die('查询出错：' . $conn->error);
  $users  = $result->fetch_all(MYSQLI_ASSOC);

  // 处理强制修改密码（POST）
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $target_id  = $_POST['user_id'];
      $new_passwd = $_POST['new_password'];

      // 漏洞：SQL 注入 + 密码明文写入 + 无 CSRF token
      $conn->query("UPDATE users SET password='$new_passwd' WHERE id=$target_id")
          or die('操作失败：' . $conn->error);

      $message = "用户 ID {$target_id} 密码已重置";
  }

  $conn->close();
  ?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>用户管理</title>
  </head>
  <body>

  <p><a href="index.php">← 返回后台首页</a></p>

  <h2>用户管理</h2>

  <?php if ($message): ?>
      <p style="color:green;"><?php echo $message; ?></p>
  <?php endif; ?>

  <form method="GET">
      <!-- 漏洞：search 参数反射到 value，反射型 XSS -->
      <input type="text" name="search" value="<?php echo $search; ?>" placeholder="搜索用户名/邮箱">
      <button type="submit">搜索</button>
  </form>

  <hr>

  <table border="1" cellpadding="6">
      <tr>
          <th>ID</th><th>用户名</th><th>邮箱</th><th>角色</th>
          <th>注册时间</th><th>操作</th>
      </tr>
      <?php foreach ($users as $u): ?>
      <tr>
          <!-- 漏洞：所有字段直接输出，未转义，存储型 XSS -->
          <td><?php echo $u['id']; ?></td>
          <td><?php echo $u['username']; ?></td>
          <td><?php echo $u['email']; ?></td>
          <td><?php echo $u['role'] == 1 ? '管理员' : '普通用户'; ?></td>
          <td><?php echo $u['created_at']; ?></td>
          <td>
              <!-- 漏洞：删除为 GET 请求，无 token，CSRF 可直接触发 -->
              <a href="delete.php?type=user&id=<?php echo $u['id']; ?>"
                 onclick="return confirm('确认删除？')">删除</a>
              &nbsp;|&nbsp;
              <!-- 重置密码表单 -->
              <form method="POST" style="display:inline;">
                  <!-- 漏洞：无 CSRF token -->
                  <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                  <input type="text" name="new_password" placeholder="新密码" style="width:80px;">
                  <button type="submit">重置密码</button>
              </form>
          </td>
      </tr>
      <?php endforeach; ?>
  </table>

  </body>
  </html>