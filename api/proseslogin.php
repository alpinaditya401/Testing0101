<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "koneksi.php";

if (isset($_POST['login'])) {

    if (!isset($conn) || !$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    $username = $_POST['username'];
    $pass     = $_POST['password'];

    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE username = ?");
    if (!$stmt) {
        die("Prepare gagal: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $data   = $result->fetch_assoc();
    $stmt->close();

    if ($data && password_verify($pass, $data['password'])) {

        // Cookie dengan flag HttpOnly dan Secure
        setcookie('username', $data['username'], time() + 3600, '/', '', true, true);
        setcookie('role',     $data['role'],     time() + 3600, '/', '', true, true);

        if ($data['role'] == 'admin') {
            header("Location: /api/dashboardadmin.php");
        } else {
            header("Location: /api/PencatatanPanen.php");
        }
        exit;

    } else {
        header("Location: /api/login.php?error=1");
        exit;
    }
}
?>
