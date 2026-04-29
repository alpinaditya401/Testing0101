<?php
header('Content-Type: application/json'); 

// URL API BPS spesifik yang sudah dites di Postman
$url = "https://webapi.bps.go.id/v1/api/list/model/data/lang/ind/domain/0000/var/2310/th/126/key/963fdf21a693d5cd78ed231fcc7055df/";

// Gunakan cURL (lebih stabil di Vercel daripada file_get_contents)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$response = curl_exec($ch);
$error    = curl_error($ch);
curl_close($ch);

if ($response === false || $response === '') {
    echo json_encode(["error" => "Gagal mengambil data: " . $error]);
    exit;
}

// kirim ke frontend
echo $response;
?>