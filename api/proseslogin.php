<?php
session_start();
include_once __DIR__ . '/koneksi.php';

if (isset($_POST['login'])) {

    if (mysqli_connect_errno()) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    $username = trim($_POST['username']);
    $pass     = trim($_POST['password']);

    if (empty($username) || empty($pass)) {
        header("Location: login.php?error=2");
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($data) {
        $passwordMatch = false;

        // Cek bcrypt hash
        if (password_verify($pass, $data['password'])) {
            $passwordMatch = true;
        }
        // Cek plain text (fallback, lalu otomatis upgrade ke hash)
        elseif ($pass === $data['password']) {
            $passwordMatch = true;
            // Upgrade password ke bcrypt
            $newHash = password_hash($pass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE tbl_user SET password = ? WHERE username = ?");
            $upd->bind_param("ss", $newHash, $username);
            $upd->execute();
            $upd->close();
        }
        // Cek MD5 (fallback, lalu otomatis upgrade ke hash)
        elseif (md5($pass) === $data['password']) {
            $passwordMatch = true;
            $newHash = password_hash($pass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE tbl_user SET password = ? WHERE username = ?");
            $upd->bind_param("ss", $newHash, $username);
            $upd->execute();
            $upd->close();
        }

        if ($passwordMatch) {
            session_regenerate_id(true);
            $_SESSION['login']    = true;
            $_SESSION['username'] = $data['username'];
            $_SESSION['role']     = $data['role'];

            if ($data['role'] == 'admin') {
                header("Location: dashboardadmin.php");
            } else {
                header("Location: PencatatanPanen.php");
            }
            exit;
        }
    }

    header("Location: login.php?error=1");
    exit;
}
?>