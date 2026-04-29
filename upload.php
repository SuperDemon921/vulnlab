  <?php
  session_start();
  require_once 'conf/db.php';

  if (!isset($_SESSION['user_id'])) {
      header('Location: login.php');
      exit;
  }

  $message = '';
  $uploaded_url = '';

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
      $file     = $_FILES['file'];
      $filename = $file['name'];
      $tmp_path = $file['tmp_name'];
      $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

      // 漏洞1：黑名单过滤不完整
      // 仅拦截 php，但 php3/php4/php5/phtml/phar 均可绕过
      $black_list = ['php'];

      if (in_array($ext, $black_list)) {
          $message = '不允许上传该类型文件';
      } else {
          // 漏洞2：文件名未重命名，保留原始文件名，可覆盖已有文件
          // 漏洞3：上传目录直接在 Web 根目录下，上传后可直接访问执行
          // 漏洞4：未校验文件内容（MIME/魔数），图片马可绕过
          $save_path = 'uploads/' . $filename;

          if (move_uploaded_file($tmp_path, $save_path)) {
              // 漏洞5：将文件路径直接写入数据库（SQL 注入 + 路径未校验）
              $user_id = $_SESSION['user_id'];
              $conn    = db_conn();
              $conn->query("UPDATE users SET avatar='$save_path' WHERE id=$user_id");
              $conn->close();

              $uploaded_url = $save_path;
              $message      = '上传成功';
          } else {
              $message = '上传失败，请检查目录权限';
          }
      }
  }
  ?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>文件上传</title>
  </head>
  <body>

  <p><a href="index.php">← 返回首页</a></p>

  <h2>上传头像 / 附件</h2>

  <?php if ($message): ?>
      <p style="color:<?php echo $uploaded_url ? 'green' : 'red'; ?>;">
          <?php echo $message; ?>
      </p>
  <?php endif; ?>

  <?php if ($uploaded_url): ?>
      <p>
          文件地址：
          <!-- 漏洞6：路径直接输出，未转义，XSS + 路径信息泄露 -->
          <a href="<?php echo $uploaded_url; ?>" target="_blank">
              <?php echo $uploaded_url; ?>
          </a>
      </p>
      <img src="<?php echo $uploaded_url; ?>" width="120" onerror="this.style.display='none'">
  <?php endif; ?>

  <hr>

  <form method="POST" enctype="multipart/form-data">
      <!-- 漏洞7：前端 accept 限制毫无意义，可直接用 Burp 改包绕过 -->
      <input type="file" name="file" accept="image/*"><br><br>
      <button type="submit">上传</button>
  </form>

  <hr>
  <h4>上传提示（靶场说明）</h4>
  <ul>
      <li>仅过滤了 <code>.php</code> 后缀，可尝试 <code>.php5</code> / <code>.phtml</code> / <code>.phar</code> 绕过</li>
      <li>上传目录：<code>uploads/</code>，文件上传后可直接浏览器访问</li>
      <li>图片马：在图片末尾追加 PHP 代码，配合文件包含漏洞利用</li>
  </ul>

  </body>
  </html>