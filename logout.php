<?php
session_start();

$_SESSION = [];
session_destroy();

setcookie('user_info', '', time() - 3600);

header('Location: Login.php');
exit;