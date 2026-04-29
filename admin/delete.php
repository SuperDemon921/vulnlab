 <?php
  session_start();

  // 漏洞：垂直越权 —— 无 role 校验
  if (!isset($_SESSION['user_id'])) {
      header('Location: ../login.php');
      exit;
  }

  require_once '../conf/db.php';

  // 漏洞1：CSRF —— GET 请求直接执行删除，无任何 token 校验
  // 构造 CSRF 示例：
  // <img src="http://target/admin/delete.php?type=user&id=2">
  // 管理员访问含此标签的页面即触发删除

  // 漏洞2：SQL 注入 —— $id 直接拼入语句
  // 漏洞3：type 参数未做白名单校验，可构造任意表名（宽字节/二次注入场景）
  $type = $_GET['type'] ?? '';
  $id   = $_GET['id']   ?? '';

  if ($id === '' || $type === '') {
      die('参数缺失');
  }

  $conn = db_conn();

  if ($type === 'user') {
      // 漏洞：id 未做整型转换，可注入
      $sql     = "DELETE FROM users WHERE id = $id";
      $back    = '../admin/users.php';
  } elseif ($type === 'article') {
      $sql     = "DELETE FROM articles WHERE id = $id";
      $back    = '../admin/articles.php';
  } elseif ($type === 'comment') {
      $sql     = "DELETE FROM comments WHERE id = $id";
      $back    = '../admin/comments.php';
  } else {
      // 漏洞：type 值直接输出，反射型 XSS
      die('未知操作类型：' . $type);
  }

  // 漏洞：报错直接输出
  $conn->query($sql) or die('删除失败：' . $conn->error);
  $conn->close();

  header('Location: ' . $back);
  exit;