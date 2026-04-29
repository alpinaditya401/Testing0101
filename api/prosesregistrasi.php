<?php
session_start();
include __DIR__ . '/koneksi.php';

if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
    header("Location: /api/registrasi.php?error=2");
    exit;
}

$username = trim($_POST['username']);
$email    = trim($_POST['email']);
$password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

// Cek username sudah ada
$cek = $conn->prepare("SELECT id FROM tbl_user WHERE username = ?");
$cek->bind_param("s", $username);
$cek->execute();
$cek->store_result();
if ($cek->num_rows > 0) {
    $cek->close();
    header("Location: /api/registrasi.php?error=3");
    exit;
}
$cek->close();

// Generate id manual karena kolom id belum AUTO_INCREMENT
$res     = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM tbl_user");
$next_id = (int) $res->fetch_assoc()['next_id'];

$stmt = $conn->prepare("INSERT INTO tbl_user (id, username, email, password, role) VALUES (?, ?, ?, ?, 'user')");
$stmt->bind_param("isss", $next_id, $username, $email, $password);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: /api/login.php?success=1");
    exit;
} else {
    $err = $conn->error;
    $stmt->close();
    header("Location: /api/registrasi.php?error=1&msg=" . urlencode($err));
    exit;
}
?>