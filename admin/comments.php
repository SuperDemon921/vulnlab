 <?php
  session_start();

  // 漏洞：垂直越权，仅判断登录态
  if (!isset($_SESSION['user_id'])) {
      header('Location: ../login.php');
      exit;
  }

  require_once '../conf/db.php';
  $conn = db_conn();

  // 漏洞：SQL 注入，$search 直接拼接
  $search = $_GET['search'] ?? '';
  if ($search !== '') {
      $sql = "SELECT c.id, c.content, c.created_at, u.username, a.title AS article_title, a.id AS article_id
              FROM comments c
              JOIN users u ON c.user_id = u.id
              JOIN articles a ON c.article_id = a.id
              WHERE c.content LIKE '%$search%' OR u.username LIKE '%$search%'
              ORDER BY c.id DESC";
  } else {
      $sql = "SELECT c.id, c.content, c.created_at, u.username, a.title AS article_title, a.id AS article_id
              FROM comments c
              JOIN users u ON c.user_id = u.id
              JOIN articles a ON c.article_id = a.id
              ORDER BY c.id DESC";
  }

  // 漏洞：报错直接输出
  $result   = $conn->query($sql) or die('查询出错：' . $conn->error);
  $comments = $result->fetch_all(MYSQLI_ASSOC);
  $conn->close();
  ?>
  <!DOCTYPE html>
  <html lang="zh">
  <head>
      <meta charset="UTF-8">
      <title>评论管理</title>
  </head>
  <body>

  <p><a href="index.php">← 返回后台首页</a></p>
  <h2>评论管理</h2>

  <form method="GET">
      <!-- 漏洞：search 参数反射到 value，反射型 XSS -->
      <input type="text" name="search" value="<?php echo $search; ?>" placeholder="搜索评论内容/用户名">
      <button type="submit">搜索</button>
  </form>

  <hr>

  <table border="1" cellpadding="6">
      <tr>
          <th>ID</th><th>评论者</th><th>所属文章</th><th>内容</th><th>时间</th><th>操作</th>
      </tr>
      <?php foreach ($comments as $c): ?>
      <tr>
          <!-- 漏洞：字段直接输出，未转义，存储型 XSS（评论内容尤为危险） -->
          <td><?php echo $c['id']; ?></td>
          <td><?php echo $c['username']; ?></td>
          <td>
              <a href="../article.php?id=<?php echo $c['article_id']; ?>" target="_blank">
                  <?php echo $c['article_title']; ?>
              </a>
          </td>
          <td><?php echo $c['content']; ?></td>
          <td><?php echo $c['created_at']; ?></td>
          <td>
              <!-- 漏洞：GET 删除 + 无 CSRF token -->
              <a href="delete.php?type=comment&id=<?php echo $c['id']; ?>"
                 onclick="return confirm('确认删除该评论？')">删除</a>
          </td>
      </tr>
      <?php endforeach; ?>
  </table>

  </body>
  </html>