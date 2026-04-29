<?php
include "koneksi.php";

if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
    echo "Username, Email dan Password wajib diisi!";
    exit;
}

$username = $_POST['username'];
$email    = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO tbl_user (username, email, password, role) VALUES (?, ?, ?, 'user')");
$stmt->bind_param("sss", $username, $email, $password);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: /api/login.php");
    exit;
} else {
    echo "Register Gagal: " . $conn->error;
    $stmt->close();
}
?>