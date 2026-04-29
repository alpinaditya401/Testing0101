<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com';
$port = 4000;
$user = '4SAcQGX7jXvf57V.root';
$pass = 'IIGRkIjPmovOfDu4';
$db   = 'db_panen';

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);

$real_connect = @mysqli_real_connect(
    $conn,
    $host,
    $user,
    $pass,
    $db,
    $port,
    NULL,
    MYSQLI_CLIENT_SSL
);

if (!$real_connect) {
    die("Koneksi gagal: " . mysqli_connect_error() . " (Error #" . mysqli_connect_errno() . ")");
}
?>
