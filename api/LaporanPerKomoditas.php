<?php
include "koneksi.php";

$data = mysqli_query($conn, "SELECT * FROM panen");
$rows = [];
while($d = mysqli_fetch_assoc($data)){
    $rows[] = $d;
}

$grafik = mysqli_query($conn, "
    SELECT komoditas, SUM(
        CASE 
            WHEN satuan='Ton' THEN jumlah*1000 
            ELSE jumlah 
        END
    ) as total 
    FROM panen 
    GROUP BY komoditas
");

$labels = [];
$dataChart = [];
while($row = mysqli_fetch_assoc($grafik)){
    $labels[] = $row['komoditas'];
    $dataChart[] = $row['total'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Panen Per Komoditas</title>
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

        .section-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 24px 28px;
            margin-bottom: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            border: 1px solid #e0f2e9;
        }
        .section-title {
            font-size: 1rem; font-weight: 600; color: #1b5e20;
            margin-bottom: 16px; padding-bottom: 10px;
            border-bottom: 2px solid #e8f5e9;
        }

        .form-label-custom {
            font-size: 0.82rem; font-weight: 600;
            color: #2e7d32; margin-bottom: 6px; display: block;
        }
        .form-control-custom {
            border: 1.5px solid #c8e6c9;
            border-radius: 10px;
            padding: 10px 13px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            color: #333;
            background-color: #fafffb;
            width: 100%;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control-custom:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46,125,50,0.11);
            outline: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e0f2e9;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,0.13); }
        .stat-card:nth-child(1) { border-top: 3px solid #2e7d32; }
        .stat-card:nth-child(2) { border-top: 3px solid #00897b; }
        .stat-card:nth-child(3) { border-top: 3px solid #1565c0; }
        .stat-label {
            font-size: 0.72rem; color: #388e3c; font-weight: 600;
            letter-spacing: 0.5px; margin-bottom: 8px; text-transform: uppercase;
        }
        .stat-value {
            font-size: 1.5rem; font-weight: 700; color: #1b5e20; line-height: 1.2;
        }
        .stat-card:nth-child(3) .stat-value { color: #1565c0; font-size: 1.2rem; }

        /* ── CHART CARD — KUNCI UTAMA: overflow visible ── */
        .chart-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 24px 28px;
            margin-bottom: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            border: 1px solid #e0f2e9;
            /* TIDAK ada overflow:hidden, filter, transform, backdrop-filter */
        }
        .chart-title {
            font-size: 1rem; font-weight: 600; color: #1b5e20;
            margin-bottom: 16px; padding-bottom: 10px;
            border-bottom: 2px solid #e8f5e9;
        }

        /* Container dengan ukuran eksplisit */
        .chart-container {
            position: relative;
            width: 100%;
            height: 280px;
            /* Paksa GPU rendering yang bersih */
            transform: translateZ(0);
            -webkit-transform: translateZ(0);
            will-change: auto;
        }

        /* Canvas sendiri harus bebas dari scaling CSS */
        .chart-container canvas {
            image-rendering: auto;
            image-rendering: crisp-edges;
            image-rendering: -webkit-optimize-contrast;
        }

        .btn-back {
            display: inline-block; padding: 11px 28px;
            background: linear-gradient(135deg, #2e7d32, #00897b);
            color: white; border: none; border-radius: 11px;
            font-size: 0.88rem; font-weight: 600; font-family: 'Poppins', sans-serif;
            text-decoration: none; cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(46,125,50,0.32); letter-spacing: 0.4px;
            margin-bottom: 20px;
            display: block;
            width: fit-content;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46,125,50,0.42); color: white;
        }

        @media (max-width: 576px) {
            .stats-grid { grid-template-columns: 1fr; }
            .site-header h1 { font-size: 1.15rem; }
            .site-header { padding: 20px; }
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
        <h1>LAPORAN PANEN PER KOMODITAS</h1>
        <p>Dashboard Data Panen</p>
    </div>

    <a href="PencatatanPanen.php" class="btn-back">Kembali ke Input Data</a>

    <div class="section-card">
        <div class="section-title">Filter Komoditas</div>
        <label class="form-label-custom">Pilih Komoditas</label>
        <select id="filterKomoditas" class="form-control-custom">
            <option value="semua">Semua</option>
            <option>Padi</option>
            <option>Jagung</option>
            <option>Cabai</option>
            <option>Kedelai</option>
        </select>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Data</div>
            <div class="stat-value" id="totalData">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Panen</div>
            <div class="stat-value" id="totalPanen">0</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Komoditas Terbanyak</div>
            <div class="stat-value" id="komoditasTerbanyak">-</div>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-title">Grafik Total per Komoditas</div>
        <div class="chart-container">
            <canvas id="grafikKomoditas"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-title">Grafik Data Panen</div>
        <div class="chart-container">
            <canvas id="grafikPanen"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-title">Grafik Total Panen (Database)</div>
        <div class="chart-container">
            <canvas id="grafikDatabase"></canvas>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
// ── SOLUSI UTAMA: fungsi untuk set pixel ratio pada canvas secara manual ──
function prepareCanvas(canvasId) {
    const canvas = document.getElementById(canvasId);
    const container = canvas.parentElement;
    const dpr = Math.max(window.devicePixelRatio || 1, 2); // minimal 2x
    const width  = container.clientWidth;
    const height = container.clientHeight;

    canvas.width  = width  * dpr;
    canvas.height = height * dpr;
    canvas.style.width  = width  + 'px';
    canvas.style.height = height + 'px';

    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);
    return ctx;
}

const COLORS_BG = [
    'rgba(46,125,50,0.85)',
    'rgba(0,137,123,0.85)',
    'rgba(21,101,192,0.85)',
    'rgba(230,81,0,0.85)',
];
const COLORS_BORDER = [
    'rgb(46,125,50)',
    'rgb(0,137,123)',
    'rgb(21,101,192)',
    'rgb(230,81,0)',
];

// baseOptions: responsive=false karena kita sudah set ukuran manual
function makeOptions(extra = {}) {
    return Object.assign({
        responsive: false,
        maintainAspectRatio: false,
        animation: { duration: 400 },
        plugins: {
            legend: {
                labels: { font: { family: 'Poppins', size: 12 }, color: '#444' }
            },
            tooltip: {
                backgroundColor: 'rgba(27,94,32,0.92)',
                titleFont: { family: 'Poppins', size: 12 },
                bodyFont:  { family: 'Poppins', size: 12 },
                padding: 10, cornerRadius: 8,
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: { font: { family: 'Poppins', size: 11 }, color: '#555' }
            },
            x: {
                grid: { display: false },
                ticks: { font: { family: 'Poppins', size: 11 }, color: '#333' }
            }
        }
    }, extra);
}

let dataPanen = <?= json_encode($rows); ?>;
let chart1, chart2;

function konversiKeKg(item) {
    let jumlah = Number(item.jumlah);
    if (item.satuan === "Ton") jumlah *= 1000;
    return jumlah;
}

function renderData(filter = "semua") {
    let filtered = filter === "semua"
        ? dataPanen
        : dataPanen.filter(d => d.komoditas === filter);

    let totalData = filtered.length;
    let totalPanen = 0;
    let komoditasCount = {};
    let totalPerKomoditas = {};

    filtered.forEach(item => {
        let kg = konversiKeKg(item);
        totalPanen += kg;
        komoditasCount[item.komoditas]    = (komoditasCount[item.komoditas] || 0) + 1;
        totalPerKomoditas[item.komoditas] = (totalPerKomoditas[item.komoditas] || 0) + kg;
    });

    document.getElementById("totalData").innerText = totalData;
    document.getElementById("totalPanen").innerText =
        totalPanen >= 1000
            ? (totalPanen / 1000).toFixed(2) + " Ton"
            : totalPanen + " Kg";

    let max = 0, komoditasTerbanyak = "-";
    for (let k in komoditasCount) {
        if (komoditasCount[k] > max) { max = komoditasCount[k]; komoditasTerbanyak = k; }
    }
    document.getElementById("komoditasTerbanyak").innerText = komoditasTerbanyak;

    // ── GRAFIK 1 ──
    if (chart1) chart1.destroy();
    const ctx1 = prepareCanvas('grafikKomoditas');
    chart1 = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: Object.keys(totalPerKomoditas),
            datasets: [{
                label: "Total Panen (Kg)",
                data: Object.values(totalPerKomoditas),
                backgroundColor: COLORS_BG,
                borderColor: COLORS_BORDER,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: makeOptions()
    });

    // ── GRAFIK 2 ──
    if (chart2) chart2.destroy();
    const ctx2 = prepareCanvas('grafikPanen');
    chart2 = new Chart(ctx2, {
        type: 'line',
        data: {
            labels: filtered.map((d, i) => "Data " + (i + 1)),
            datasets: [{
                label: "Panen (Kg)",
                data: filtered.map(item => konversiKeKg(item)),
                borderColor: 'rgb(46,125,50)',
                backgroundColor: 'rgba(46,125,50,0.1)',
                borderWidth: 2.5,
                pointBackgroundColor: 'rgb(46,125,50)',
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.3,
            }]
        },
        options: makeOptions()
    });
}

document.getElementById("filterKomoditas").addEventListener("change", function () {
    renderData(this.value);
});

// ── GRAFIK 3 (DATABASE) — render setelah semua layout selesai ──
window.addEventListener('load', function () {
    renderData();

    const ctx3 = prepareCanvas('grafikDatabase');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels); ?>,
            datasets: [{
                label: "Total Panen (Kg)",
                data: <?= json_encode($dataChart); ?>,
                backgroundColor: COLORS_BG,
                borderColor: COLORS_BORDER,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: makeOptions()
    });
});
</script>

</body>
</html>