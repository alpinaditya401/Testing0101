<?php
header('Content-Type: application/json'); 

// URL API BPS spesifik yang sudah dites di Postman
$url = "https://webapi.bps.go.id/v1/api/list/model/data/lang/ind/domain/0000/var/2310/th/126/key/963fdf21a693d5cd78ed231fcc7055df/";

// ambil data
$response = file_get_contents($url);

// cek error
if ($response === FALSE) {
    echo json_encode(["error" => "Gagal mengambil data"]);
    exit;
}

// kirim ke frontend
echo $response;
?>