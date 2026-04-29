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
    header("Location: /api/registrasi.php?error=2");
    exit;
}

$username = trim($_POST['username']);
$email    = trim($_POST['email']);
$password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

// Cek username sudah ada
$cek = $conn->prepare("SELECT id FROM tbl_user WHERE username = ?");
if (!$cek) {
    die("Prepare gagal: " . $conn->error);
}
$cek->bind_param("s", $username);
$cek->execute();
$cek->store_result();
if ($cek->num_rows > 0) {
    $cek->close();
    header("Location: /api/registrasi.php?error=3");
    exit;
}
$cek->close();

// Ambil id max untuk generate id manual (workaround jika id tidak AUTO_INCREMENT)
$res = $conn->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM tbl_user");
$row = $res->fetch_assoc();
$next_id = $row['next_id'];

// Coba insert tanpa id dulu (kalau AUTO_INCREMENT sudah benar)
$stmt = $conn->prepare("INSERT INTO tbl_user (username, email, password, role) VALUES (?, ?, ?, 'user')");
if (!$stmt) {
    // Kalau gagal, coba dengan id manual
    $stmt = $conn->prepare("INSERT INTO tbl_user (id, username, email, password, role) VALUES (?, ?, ?, ?, 'user')");
    if (!$stmt) {
        die("Prepare gagal: " . $conn->error);
    }
    $stmt->bind_param("isss", $next_id, $username, $email, $password);
} else {
    $stmt->bind_param("sss", $username, $email, $password);
}

if ($stmt->execute()) {
    $stmt->close();
    header("Location: /api/login.php?success=1");
    exit;
} else {
    // Jika gagal karena id, coba insert dengan id manual
    $stmt->close();
    $stmt2 = $conn->prepare("INSERT INTO tbl_user (id, username, email, password, role) VALUES (?, ?, ?, ?, 'user')");
    if ($stmt2 && $stmt2->bind_param("isss", $next_id, $username, $email, $password) && $stmt2->execute()) {
        $stmt2->close();
        header("Location: /api/login.php?success=1");
        exit;
    }
    $err = $conn->error;
    if ($stmt2) $stmt2->close();
    header("Location: /api/registrasi.php?error=1&msg=" . urlencode($err));
    exit;
}
?>