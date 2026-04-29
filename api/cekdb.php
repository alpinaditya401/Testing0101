<?php
include __DIR__ . '/koneksi.php';

if (mysqli_connect_errno()) {
    die("Koneksi DB gagal: " . mysqli_connect_error());
}

$result = $conn->query("SELECT id, username, LENGTH(password) as pass_len, LEFT(password, 4) as pass_start, role FROM tbl_user");

if (!$result) {
    die("Query gagal: " . $conn->error);
}

echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Username</th><th>Panjang Password</th><th>Awalan Hash</th><th>Role</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>{$row['id']}</td>
        <td>{$row['username']}</td>
        <td>{$row['pass_len']}</td>
        <td>{$row['pass_start']}</td>
        <td>{$row['role']}</td>
    </tr>";
}

echo "</table>";
echo "<br><small>Kalau panjang password = 60, berarti sudah bcrypt ✅</small>";
?>