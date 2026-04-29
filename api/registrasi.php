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
        <h2>Register Sistem Panen</h2>
        <form action="/api/prosesregistrasi.php" method="POST"  id="loginForm">
            <input type="text" id="username" name="username" placeholder="Username" required>
            <input type="email" id="email" name="email" placeholder="Email" required>
            <input type="password" id="password" name="password" placeholder="Password" required>

            <button type="submit">Register</button>
        </form>
        <div class="footer">
            Sudah punya akun? <a href="/api/login.php">Masuk sekarang</a>
        </div>
        <p id="pesan"></p>
    </div>
</div>

<script>
    const form = document.getElementById("loginForm");
const pesan = document.getElementById("pesan");

form.addEventListener("submit", function(e){

    let username = document.getElementById("username").value;
    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;

    // validasi sederhana
    if(username === "" || email === "" || password === ""){
        e.preventDefault();
        pesan.style.color = "red";
        pesan.innerText = "Semua field harus diisi!";
        return;
    }

    // Biarkan form submit normal ke prosesregistrasi.php
});

</script>

</body>
</html>