<?php
include "koneksi.php";

if(!isset($_COOKIE['username'])){
    header("Location: /api/login.php");
    exit;
}

$total_data    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_panen"));
$total_jumlah  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(jumlah) as total, satuan FROM tbl_panen GROUP BY satuan ORDER BY total DESC LIMIT 1"));
$top_komoditas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT komoditas, SUM(jumlah) as total FROM tbl_panen GROUP BY komoditas ORDER BY total DESC LIMIT 1"));

$chart_query = mysqli_query($conn, "SELECT komoditas, SUM(jumlah) as total FROM tbl_panen GROUP BY komoditas ORDER BY total DESC");
$labels = [];
$data_chart = [];
while($row = mysqli_fetch_assoc($chart_query)){
    $labels[]     = $row['komoditas'];
    $data_chart[] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Panen Terbanyak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .bg-layer {
            position: fixed; inset: 0; z-index: -3;
            background: linear-gradient(160deg, #1b4332 0%, #2d6a4f 25%, #40916c 50%, #52b788 75%, #95d5b2 100%);
        }
        .bg-pattern {
            position: fixed; inset: 0; z-index: -2;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cg fill='none' stroke='rgba(255,255,255,0.04)' stroke-width='1'%3E%3Cellipse cx='40' cy='40' rx='18' ry='32' transform='rotate(30 40 40)'/%3E%3Cellipse cx='40' cy='40' rx='18' ry='32' transform='rotate(90 40 40)'/%3E%3Cellipse cx='40' cy='40' rx='18' ry='32' transform='rotate(150 40 40)'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 80px 80px;
        }
        .bg-spotlight {
            position: fixed; inset: 0; z-index: -1;
            background: radial-gradient(ellipse 70% 60% at 50% 40%, rgba(149,213,178,0.15) 0%, transparent 70%);
            pointer-events: none;
        }

        .page-wrapper {
            max-width: 860px;
            margin: 36px auto 56px;
            padding: 0 20px;
        }

        .site-header {
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 50%, #00897b 100%);
            color: white;
            padding: 28px 36px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.12);
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
        }
        .site-header::before,
        .site-header::after {
            content: ''; position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.07); pointer-events: none;
        }
        .site-header::before { width: 220px; height: 220px; top: -80px; right: -50px; }
        .site-header::after  { width: 160px; height: 160px; bottom: -60px; left: -40px; }
        .site-header h1 {
            font-size: 1.55rem; font-weight: 700; letter-spacing: 1.5px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3); position: relative; z-index: 1;
        }
        .site-header p {
            font-size: 0.85rem; opacity: 0.85; margin-top: 5px;
            font-weight: 300; position: relative; z-index: 1;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 22px 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 1px solid #e0f2e9;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,0.15); }
        .stat-card:nth-child(1) { border-top: 3px solid #2e7d32; }
        .stat-card:nth-child(2) { border-top: 3px solid #00897b; }
        .stat-card:nth-child(3) { border-top: 3px solid #1565c0; }
        .stat-label {
            font-size: 0.75rem; color: #388e3c; font-weight: 600;
            letter-spacing: 0.5px; margin-bottom: 8px; text-transform: uppercase;
        }
        .stat-value { font-size: 1.6rem; font-weight: 700; color: #1b5e20; line-height: 1.2; }
        .stat-card:nth-child(3) .stat-value { color: #1565c0; font-size: 1.3rem; }

        /* CHART CARD — solid putih murni, zero filter */
        .section-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 30px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
            border: 1px solid #e0f2e9;
        }
        .section-title {
            font-size: 1.1rem; font-weight: 600; color: #1b5e20;
            margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #e8f5e9;
        }

        /* Chart container — tinggi fixed, lebar 100% */
        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
        }

        .btn-back {
            display: inline-block; padding: 11px 28px;
            background: linear-gradient(135deg, #2e7d32, #00897b);
            color: white; border: none; border-radius: 11px;
            font-size: 0.88rem; font-weight: 600; font-family: 'Poppins', sans-serif;
            text-decoration: none; cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(46,125,50,0.32); letter-spacing: 0.4px;
        }
        .btn-back:hover {
            transform: translateY(-2px); box-shadow: 0 6px 20px rgba(46,125,50,0.42); color: white;
        }

        @media (max-width: 576px) {
            .stats-grid { grid-template-columns: 1fr; }
            .site-header h1 { font-size: 1.15rem; }
            .site-header { padding: 20px; }
            .section-card { padding: 20px; }
            .page-wrapper { margin-top: 20px; }
        }
    </style>
</head>
<body>

<div class="bg-layer"></div>
<div class="bg-pattern"></div>
<div class="bg-spotlight"></div>

<div class="page-wrapper">

    <div class="site-header">
        <h1>DASHBOARD PANEN TERBANYAK</h1>
        <p>Analisis hasil panen petani</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Data Panen</div>
            <div class="stat-value"><?= $total_data['total']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Jumlah Panen</div>
            <div class="stat-value"><?= $total_jumlah['total'] . ' ' . $total_jumlah['satuan']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Komoditas Terbanyak</div>
            <div class="stat-value"><?= $top_komoditas['komoditas']; ?></div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title">Grafik Hasil Panen</div>
        <div class="chart-container">
            <canvas id="grafikPanen"></canvas>
        </div>
    </div>

    <a href="PencatatanPanen.php" class="btn-back">Kembali ke Input Data</a>

</div>

<!-- Chart.js versi terbaru langsung dari CDN resmi -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('grafikPanen');

    // Paksa canvas render dengan pixel ratio layar
    Chart.defaults.devicePixelRatio = window.devicePixelRatio || 2;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Jumlah Panen',
                data: <?= json_encode($data_chart) ?>,
                backgroundColor: [
                    'rgba(46,125,50,0.85)',
                    'rgba(0,137,123,0.85)',
                    'rgba(21,101,192,0.85)',
                    'rgba(230,81,0,0.85)',
                ],
                borderColor: [
                    'rgb(46,125,50)',
                    'rgb(0,137,123)',
                    'rgb(21,101,192)',
                    'rgb(230,81,0)',
                ],
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 500 },
            plugins: {
                legend: {
                    labels: {
                        font: { family: 'Poppins', size: 13 },
                        color: '#333'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(27,94,32,0.92)',
                    titleFont: { family: 'Poppins', size: 13 },
                    bodyFont:  { family: 'Poppins', size: 13 },
                    padding: 10,
                    cornerRadius: 8,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.06)' },
                    ticks: { font: { family: 'Poppins', size: 12 }, color: '#555' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Poppins', size: 12 }, color: '#333' }
                }
            }
        }
    });
});
</script>

</body>
</html>