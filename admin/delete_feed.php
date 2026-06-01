<?php
  require_once '../conf/db.php';
  require_admin();

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {  //拒绝非POST请求
      http_response_code(405);
      exit('Method Not Allowed');
  }
  csrf_check();                                  //csrf_token检查

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {                    //判断id是否合法
      http_response_code(400);
      exit('参数错误');
  }

  db()->prepare('DELETE FROM feeds WHERE id = ?')->execute([$id]);       //删除订阅信息
  
  header('Location: feed.php');
  exit;
