<?php
session_start();
include_once __DIR__ . '/koneksi.php';

if (mysqli_connect_errno()) {
    die("❌ Koneksi database gagal: " . mysqli_connect_error());
}

if (!isset($_POST['login'])) {
    die("⚠️ Akses langsung tidak diizinkan. Gunakan form login.");
}

$username = trim($_POST['username']);
$pass     = trim($_POST['password']);

if (empty($username) || empty($pass)) {
    header("Location: /api/login.php?error=2");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM tbl_user WHERE username = ?");
if (!$stmt) {
    die("❌ Prepare gagal: " . $conn->error);
}
$stmt->bind_param("s", $username);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("❌ User '<b>" . htmlspecialchars($username) . "</b>' tidak ditemukan di database.<br>
        Coba cek huruf besar/kecil. Username di DB: pastikan sama persis.");
}

$hash = $data['password'];
$verify = password_verify($pass, $hash);

if (!$verify) {
    die("❌ Password salah.<br>
        Username ditemukan ✅<br>
        password_verify() = false ❌<br>
        Panjang hash di DB: " . strlen($hash) . "<br>
        4 karakter awal hash: " . htmlspecialchars(substr($hash, 0, 4)));
}

// Login berhasil
session_regenerate_id(true);
$_SESSION['login']    = true;
$_SESSION['username'] = $data['username'];
$_SESSION['role']     = $data['role'];

if ($data['role'] == 'admin') {
    header("Location: /api/dashboardadmin.php");
} else {
    header("Location: /api/PencatatanPanen.php");
}
exit;
?>