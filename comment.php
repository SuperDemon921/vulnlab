  <?php
//   session_start();
  require_once 'conf/db.php';
  require_login();        //先登录才能评论

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }    
  csrf_check();          

  $article_id = (int)($_POST['article_id'] ?? 0);
  $content    = trim((string)($_POST['content'] ?? ''));
  if ($article_id <= 0 || $content === '' || mb_strlen($content) > 2000) {
      http_response_code(400); exit('参数错误');
  }

  $pdo = db();
  $exists = $pdo->prepare('SELECT 1 FROM articles WHERE id = ?');   //校验要评论的id是否存在
  $exists->execute([$article_id]);
  if (!$exists->fetchColumn()) {
      http_response_code(404);
      exit('文章不存在');
  }

  $ins = $pdo->prepare('INSERT INTO comments (article_id, user_id, content) VALUES (?, ?, ?)');   //插入评论
  $ins->execute([$article_id, current_user_id(), $content]);
  
  header("Location: article.php?id={$article_id}"); exit;  //重定向并退出



  // 漏洞：仅简单判断 session，未验证 session 是否被伪造
//   if (!isset($_SESSION['user_id'])) {
//       header('Location: login.php');
//       exit;
//   }

//   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//       header('Location: index.php');
//       exit;
//   }

//   $article_id = $_POST['article_id'];
//   $content    = $_POST['content'];
//   $user_id    = $_SESSION['user_id'];

//   $conn = db_conn();

//   // 漏洞1：SQL 注入，content 和 article_id 均未过滤直接拼接
//   // 漏洞2：content 未做 XSS 过滤，原样存入数据库，展示时直接触发
//   // 漏洞3：无 CSRF token 校验，任意第三方页面可伪造提交
//   $sql = "INSERT INTO comments (article_id, user_id, content)
//           VALUES ($article_id, $user_id, '$content')";

//   if ($conn->query($sql)) {
//       header("Location: article.php?id=$article_id");
//   } else {
//       // 漏洞：报错直接输出，暴露数据库细节
//       die('评论失败：' . $conn->error);
//   }

//   $conn->close();