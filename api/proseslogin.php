<?php
session_start();
include_once __DIR__ . '/koneksi.php';

if (mysqli_connect_errno()) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

if (!isset($_POST['login'])) {
    header("Location: /api/login.php");
    exit;
}

$username = trim($_POST['username']);
$pass     = trim($_POST['password']);

if (empty($username) || empty($pass)) {
    header("Location: /api/login.php?error=2");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tbl_user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$passwordMatch = false;
if ($data) {
    if (password_verify($pass, $data['password'])) {
        $passwordMatch = true;
    } elseif ($pass === $data['password']) {
        $passwordMatch = true;
        $newHash = password_hash($pass, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE tbl_user SET password = ? WHERE username = ?");
        $upd->bind_param("ss", $newHash, $username);
        $upd->execute();
        $upd->close();
    }
}

if ($passwordMatch) {
    session_regenerate_id(true);

    // Set SESSION
    $_SESSION['login']    = true;
    $_SESSION['username'] = $data['username'];
    $_SESSION['role']     = $data['role'];

    // Set COOKIE (dibutuhkan PencatatanPanen.php)
    setcookie('username', $data['username'], time() + (86400 * 7), '/');
    setcookie('role',     $data['role'],     time() + (86400 * 7), '/');

    if ($data['role'] == 'admin') {
        header("Location: /api/dashboardadmin.php");
    } else {
        header("Location: /api/PencatatanPanen.php");
    }
    exit;
}

header("Location: /api/login.php?error=1");
exit;
?>