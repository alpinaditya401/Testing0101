<?php
// Hapus semua cookie
setcookie('username', '', time() - 3600, '/');
setcookie('role',     '', time() - 3600, '/');

header("Location:/api/login.php");
exit;
?>