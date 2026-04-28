<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include __DIR__ . '/koneksi.php';

if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
    header("Location: registrasi.php?error=2");
    exit;
}

$username = trim($_POST['username']);
$email    = trim($_POST['email']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Cek apakah username sudah ada
$cek = $conn->prepare("SELECT id FROM tbl_user WHERE username = ?");
$cek->bind_param("s", $username);
$cek->execute();
$cek->store_result();
if ($cek->num_rows > 0) {
    $cek->close();
    header("Location: registrasi.php?error=3");
    exit;
}
$cek->close();

$stmt = $conn->prepare("INSERT INTO tbl_user (username, email, password, role) VALUES (?, ?, ?, 'user')");
$stmt->bind_param("sss", $username, $email, $password);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: login.php?success=1");
    exit;
} else {
    $err = $conn->error;
    $stmt->close();
    header("Location: registrasi.php?error=1&msg=" . urlencode($err));
    exit;
}
?>