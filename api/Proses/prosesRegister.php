<?php
/**
 * Proses/prosesRegister.php
 */
require_once __DIR__ . '/../Server/koneksi.php';

if ($conn === null) {
    redirect('/register.php?pesan=nodb');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/register.php');

// Ambil & sanitasi semua input
$email      = esc($conn, $_POST['email']        ?? '');
$username   = esc($conn, $_POST['username']     ?? '');
$password   = trim($_POST['password']   ?? '');
$konfirmasi = trim($_POST['konfirmasi'] ?? '');
// ✅ FIX: nama_lengkap dari form → kolom 'nama' di database
$nama       = esc($conn, $_POST['nama_lengkap'] ?? $_POST['nama'] ?? '');
$provinsi   = esc($conn, $_POST['provinsi']     ?? '');

// Role hanya boleh 'user' atau 'kontributor'
$roleRaw = $_POST['role'] ?? 'user';
$role    = in_array($roleRaw, ['user', 'kontributor'], true) ? $roleRaw : 'user';

// Validasi field wajib
if (!$email || !$username || !$password || !$konfirmasi)
    redirect('/register.php?error=empty');

if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    redirect('/register.php?error=email_invalid');

if (mb_strlen($username) < 4)
    redirect('/register.php?error=username_short');

if (mb_strlen($password) < 6)
    redirect('/register.php?error=password_short');

if ($password !== $konfirmasi)
    redirect('/register.php?error=mismatch');

// Cek duplikat email
if ($conn->query("SELECT id FROM users WHERE email='$email' LIMIT 1")?->num_rows > 0)
    redirect('/register.php?error=email_taken');

// Cek duplikat username
if ($conn->query("SELECT id FROM users WHERE username='$username' LIMIT 1")?->num_rows > 0)
    redirect('/register.php?error=username_taken');

// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// ✅ FIX: INSERT hanya kolom yang BENAR-BENAR ADA di tabel users
$insertOk = $conn->query(
    "INSERT INTO users (email, username, password, nama, role, is_active)
     VALUES ('$email','$username','$hash','$nama','$role', 1)"
);

// Tangkap error insert
if (!$insertOk || $conn->insert_id === 0) {
    $errDetail = urlencode($conn->error ?: 'insert_id=0');
    redirect('/register.php?error=db_error&detail=' . $errDetail);
}

// Update provinsi kalau kolom ada
if ($provinsi) {
    $newId = (int)$conn->insert_id;
    $hasProvinsi = $conn->query("SHOW COLUMNS FROM users LIKE 'provinsi'");
    if ($hasProvinsi && $hasProvinsi->num_rows > 0) {
        $conn->query("UPDATE users SET provinsi='$provinsi' WHERE id=$newId");
    }
}

// ✅ Setelah daftar → ke halaman login dengan pesan sukses (tidak auto-login)
redirect('/login.php?pesan=register_sukses');
