<?php
session_start();

// Hapus session
$_SESSION = [];
session_destroy();

// Hapus cookie
setcookie('username', '', time() - 3600, '/');
setcookie('role',     '', time() - 3600, '/');

header("Location: /api/login.php");
exit;
?>
