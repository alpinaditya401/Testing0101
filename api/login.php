<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Panen</title>
<link rel="stylesheet" href="/css/Login_Register_form.css">
</head>

<body>
<img src="/Cover.jpg">
<div class="container">
    <div class="login-box">
        <h2>Login Sistem Panen</h2>
        
        <form action="/api/proseslogin.php" method="POST" id="loginForm">
            <input type="text" id="username" name="username" placeholder="Username" required>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>

        <div class="footer">
            Belum punya akun? <a href="/api/registrasi.php">Daftar sekarang</a>
        </div>
        <p id="pesan" style="<?= isset($_GET['error']) ? 'color:red' : '' ?>">
            <?= isset($_GET['error']) ? 'Username atau password salah!' : '' ?>
        </p>
    </div>
</div>

<script>
const form = document.getElementById("loginForm");
const pesan = document.getElementById("pesan");

form.addEventListener("submit", function(e) {
    let username = document.getElementById("username").value.trim();
    let password = document.getElementById("password").value.trim();

    // Jika field kosong, cegah submit dan tampilkan pesan
    if (username === "" || password === "") {
        pesan.style.color = "red";
        pesan.innerText = "Semua field harus diisi!";
        return;
    }

    // Jika isi lengkap, biarkan form submit normal ke proseslogin.php
});
</script>

</body>
</html>