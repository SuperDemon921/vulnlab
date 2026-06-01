  <?php
//   session_start();
  require_once 'conf/db.php';
  require_login(); //检查是否登录 session值

  const UPLOAD_DIR  = __DIR__ . '/uploads/';
  const MAX_SIZE    = 2 * 1024 * 1024;         //限制大小2MB
  const ALLOW_EXT   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];   //定义白名单后缀
  const ALLOW_MIME  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

  $message = '';
  $uploaded_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      csrf_check();           //检查csrf token

      $f = $_FILES['file'] ?? null;              
      if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
          $message = '上传失败';
      } elseif ($f['size'] > MAX_SIZE) {
          $message = '文件过大（最大 2MB）';
      } else {
          $origName = (string)$f['name'];                                     //获取原始文件名
          $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));    //拆解文件后缀后转小写，方便做白名单校验

          if (!in_array($ext, ALLOW_EXT, true)) {      //白名单校验，严格模式，类型也必须匹配，防止类型转换绕过
              $message = '不允许的文件类型';
          } else {
              $finfo = new finfo(FILEINFO_MIME_TYPE);         //创建finfo对象，只返回文件的MIME类型字符串，用于白名单比对
              $mime  = (string)$finfo->file($f['tmp_name']);  //分析文件头部信息，获取MIME类型
              //尝试将文件作为图像来解析，返回图像的宽高、类型等信息。如果文件根本不是图片，返回 false。@抑制错误输出。
              $img   = @getimagesize($f['tmp_name']);         

              if (!in_array($mime, ALLOW_MIME, true) || !$img) {   //校验白名单，以及图片是否可解析
                  $message = '不是有效图片';
              } else {
                  if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0755, true); }  //校验uploads文件夹是否创建

                  $safeName = bin2hex(random_bytes(16)) . '.' . $ext;      //重命名上传文件
                  $dest     = UPLOAD_DIR . $safeName;                      //拼接路径

                  if (move_uploaded_file($f['tmp_name'], $dest)) {        //将文件从临时目录移动到uploads目录下
                      @chmod($dest, 0644);                                //给予上传文件权限644
                      $rel = 'uploads/' . $safeName;                      //文件路径
                      db()->prepare('UPDATE users SET avatar = ? WHERE id = ?')   //写入数据库
                          ->execute([$rel, current_user_id()]);
                      $uploaded_url = $rel;
                      $message      = '上传成功';
                  } else {
                      $message = '保存文件失败';
                  }
              }
          }
      }
  }

//   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
//       $file     = $_FILES['file'];
//       $filename = $file['name'];
//       $tmp_path = $file['tmp_name'];
//       $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

//       // 漏洞1：黑名单过滤不完整
//       // 仅拦截 php，但 php3/php4/php5/phtml/phar 均可绕过
//       $black_list = ['php'];

//       if (in_array($ext, $black_list)) {
//           $message = '不允许上传该类型文件';
//       } else {
//           // 漏洞2：文件名未重命名，保留原始文件名，可覆盖已有文件
//           // 漏洞3：上传目录直接在 Web 根目录下，上传后可直接访问执行
//           // 漏洞4：未校验文件内容（MIME/魔数），图片马可绕过
//           $save_path = 'uploads/' . $filename;

//           if (move_uploaded_file($tmp_path, $save_path)) {
//               // 漏洞5：将文件路径直接写入数据库（SQL 注入 + 路径未校验）
//               $user_id = $_SESSION['user_id'];
//               $conn    = db_conn();
//               $conn->query("UPDATE users SET avatar='$save_path' WHERE id=$user_id");
//               $conn->close();

//               $uploaded_url = $save_path;
//               $message      = '上传成功';
//           } else {
//               $message = '上传失败，请检查目录权限';
//           }
//       }
//   }
  ?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>文件上传</title>
  </head>
  <body>

  <p><a href="index.php">← 返回首页</a></p>

  <h2>上传头像</h2>

  <?php if ($message): ?>
      <p style="color:<?= $uploaded_url ? 'green' : 'red' ?>;"><?= e($message) ?></p>  <!--动态调整字体颜色-->
  <?php endif; ?>

  <?php if ($uploaded_url): ?>
      <p>文件地址：<?= e($uploaded_url) ?></p>
      <img src="<?= e($uploaded_url) ?>" width="120" alt="">
  <?php endif; ?>

  <hr>

  <form method="POST" enctype="multipart/form-data">
      <!-- 加载csrf_token -->
       <?= csrf_field() ?>
      <input type="file" name="file" accept="image/*"><br><br>
      <button type="submit">上传</button>
  </form>

  </body>
  </html>