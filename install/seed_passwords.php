 <?php
  require_once __DIR__ . '/../conf/db.php';

  $pairs = [
      'admin'   => 'admin123',
      'alice'   => 'alice123',
      'bob'     => 'bob123',
      'charlie' => 'charlie123',
  ];
  foreach ($pairs as $u => $p) {
      $hash = password_hash($p, PASSWORD_DEFAULT);
      db()->prepare('UPDATE users SET password = ? WHERE username = ?')
          ->execute([$hash, $u]);
      echo "$u updated\n";
  }