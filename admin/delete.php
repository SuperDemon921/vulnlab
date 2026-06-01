 <?php
  require_once '../conf/db.php';
  require_admin();

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {  //判断请求类型，拒绝除POST以外的请求
      http_response_code(405);
      exit('Method Not Allowed');
  }
  csrf_check();

  $type = (string)($_POST['type'] ?? '');
  $id   = (int)($_POST['id'] ?? 0);

  //删除逻辑路由
  $map = [
      'user'    => ['DELETE FROM users    WHERE id = ?', 'users.php'],
      'article' => ['DELETE FROM articles WHERE id = ?', 'articles.php'],
      'comment' => ['DELETE FROM comments WHERE id = ?', 'comments.php'],
  ];

  if (!isset($map[$type]) || $id <= 0) {
      http_response_code(400);
      exit('参数错误');
  }

  if ($type === 'user' && $id === current_user_id()) {
      http_response_code(400);
      exit('不能删除自己');
  }
 //数组解构赋值，同时对两个变量进行赋值
  [$sql, $back] = $map[$type];
  db()->prepare($sql)->execute([$id]);

  header('Location: ' . $back);
  exit;