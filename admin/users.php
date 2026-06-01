  <?php
//   session_start();
  require_once '../conf/db.php';
  require_admin();  

  $pdo     = db();
  $message = '';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      csrf_check();
      $target_id  = (int)($_POST['user_id'] ?? 0);
      $new_passwd = (string)($_POST['new_password'] ?? '');

      if ($target_id <= 0 || strlen($new_passwd) < 8) {
          $message = '新密码至少 8 位';
      } else {
          $hash = password_hash($new_passwd, PASSWORD_DEFAULT);
          $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
              ->execute([$hash, $target_id]);
          $message = "用户 ID {$target_id} 密码已重置";
      }
  }

  $search = trim((string)($_GET['search'] ?? ''));
  if ($search !== '') {
      $like = '%' . $search . '%';
      $stmt = $pdo->prepare('
          SELECT id, username, email, role, created_at FROM users
          WHERE username LIKE ? OR email LIKE ?
          ORDER BY id ASC');
      $stmt->execute([$like, $like]);
  } else {
      $stmt = $pdo->query('SELECT id, username, email, role, created_at FROM users ORDER BY id ASC');
  }
  $users = $stmt->fetchAll();
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
       <p style="color:green;"><?= e($message) ?></p>
  <?php endif; ?>

  <form method="GET">
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="搜索用户名/邮箱">
      <button type="submit">搜索</button>
  </form>

  <hr>

  <table border="1" cellpadding="6">
    <tr><th>ID</th><th>用户名</th><th>邮箱</th><th>角色</th><th>注册时间</th><th>操作</th></tr>
             <!--用户列表展示-->
      <?php foreach ($users as $u): ?>           
      <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><?= e($u['username']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><?= ((int)$u['role'] === 1) ? '管理员' : '普通用户' ?></td>
          <td><?= e($u['created_at']) ?></td>
          <td>
                        <!--删除用户-->
               <form method="POST" action="delete.php" style="display:inline" onsubmit="return confirm('确认删除？')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="type" value="user">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button type="submit">删除</button>
              </form>
              &nbsp;|&nbsp;
              
              <!-- 重置密码表单 -->
              <form method="POST" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                  <input type="password" name="new_password" placeholder="新密码 ≥8位" style="width:120px;">
                  <button type="submit">重置密码</button>
              </form>
          </td>
      </tr>
      <?php endforeach; ?>
  </table>

  </body>
  </html>