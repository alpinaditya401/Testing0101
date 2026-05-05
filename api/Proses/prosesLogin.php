<?php
/**
 * Proses/prosesLogin.php
 */
require_once __DIR__ . '/../Server/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/login.php');
}

if ($conn === null) {
    redirect('/login.php?pesan=nodb');
}

$username = esc($conn, trim($_POST['username'] ?? ''));
$password = trim($_POST['password'] ?? '');

if (!$username || !$password) redirect('/login.php?pesan=empty');

$res = $conn->query("SELECT id, username, password, role, is_active,
                            COALESCE(login_attempts,0) as login_attempts,
                            locked_until
                     FROM users WHERE username='$username' LIMIT 1");

if (!$res || $res->num_rows === 0) {
    redirect('/login.php?pesan=gagal');
}

$u = $res->fetch_assoc();

if (!$u['is_active']) redirect('/login.php?pesan=nonaktif');

if (!empty($u['locked_until'])) {
    $lockedUntil = strtotime($u['locked_until']);
    if ($lockedUntil > time()) {
        $menitSisa = ceil(($lockedUntil - time()) / 60);
        redirect('/login.php?pesan=locked&menit='.$menitSisa);
    } else {
        $conn->query("UPDATE users SET login_attempts=0, locked_until=NULL WHERE id={$u['id']}");
    }
}

if (password_verify($password, $u['password'])) {
    $conn->query("UPDATE users SET login_attempts=0, locked_until=NULL WHERE id={$u['id']}");

    $_SESSION['login']    = true;
    $_SESSION['user_id']  = (int)$u['id'];
    $_SESSION['username'] = $u['username'];
    $_SESSION['role']     = $u['role'];

    $dest = match($u['role']) {
        'admin_master', 'admin' => '/dashboard.php',
        'kontributor'           => '/dashboard-user.php?tab=laporan',
        default                 => '/dashboard-user.php',
    };
    redirect($dest);
} else {
    $attempts = (int)$u['login_attempts'] + 1;
    $maxTry   = 5;
    $lockMin  = 15;
    if ($attempts >= $maxTry) {
        $lockedUntil = date('Y-m-d H:i:s', time() + ($lockMin * 60));
        $conn->query("UPDATE users SET login_attempts=$attempts, locked_until='$lockedUntil' WHERE id={$u['id']}");
        redirect('/login.php?pesan=locked&menit='.$lockMin);
    } else {
        $sisaCoba = $maxTry - $attempts;
        $conn->query("UPDATE users SET login_attempts=$attempts WHERE id={$u['id']}");
        redirect('/login.php?pesan=gagal&sisa='.$sisaCoba);
    }
}