<?php
include "koneksi.php";

if(!isset($_COOKIE['username'])){
    header("Location: /api/login.php");
    exit;
}

$username = $_COOKIE['username'];
$role     = $_COOKIE['role'] ?? 'user';

// SIMPAN DATA
if(isset($_POST['simpan'])){
    $tanggal   = mysqli_real_escape_string($conn, $_POST['tanggal_panen']);
    $komoditas = mysqli_real_escape_string($conn, $_POST['komoditas_panen']);
    $jumlah    = (int)$_POST['jumlah_panen'];
    $satuan    = mysqli_real_escape_string($conn, $_POST['satuan_panen']);
    $lokasi    = mysqli_real_escape_string($conn, $_POST['lokasi_panen'] ?? '');

    $query = "INSERT INTO tbl_panen (tanggal, komoditas, jumlah, satuan, lokasi) 
              VALUES ('$tanggal','$komoditas','$jumlah','$satuan','$lokasi')";

    if(mysqli_query($conn, $query)){
        header("Location: /api/PencatatanPanen.php");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

// HAPUS DATA
if(isset($_GET['hapus'])){
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM tbl_panen WHERE id='$id'");
    header("Location: /api/PencatatanPanen.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pencatatan Hasil Panen</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── BACKGROUND ── */
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Layer 1 — gradasi dasar hijau tanah */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(160deg,
                #1b4332 0%,
                #2d6a4f 25%,
                #40916c 50%,
                #52b788 75%,
                #95d5b2 100%);
            z-index: -3;
        }

        /* Layer 2 — pola daun geometris SVG */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cg fill='none' stroke='rgba(255,255,255,0.045)' stroke-width='1'%3E%3Cellipse cx='40' cy='40' rx='18' ry='32' transform='rotate(30 40 40)'/%3E%3Cellipse cx='40' cy='40' rx='18' ry='32' transform='rotate(90 40 40)'/%3E%3Cellipse cx='40' cy='40' rx='18' ry='32' transform='rotate(150 40 40)'/%3E%3C/g%3E%3C/svg%3E"),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='40' fill='none' stroke='rgba(255,255,255,0.025)' stroke-width='1'/%3E%3Ccircle cx='60' cy='60' r='20' fill='none' stroke='rgba(255,255,255,0.025)' stroke-width='1'/%3E%3C/svg%3E");
            background-size: 80px 80px, 120px 120px;
            z-index: -2;
        }

        /* Layer 3 — spotlight tengah terang */
        .bg-spotlight {
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse 70% 60% at 50% 40%,
                rgba(149,213,178,0.18) 0%,
                transparent 70%);
            z-index: -1;
            pointer-events: none;
        }

        /* ── WRAPPER ── */
        .page-wrapper {
            max-width: 860px;
            margin: 36px auto 56px;
            padding: 0 20px;
        }

        /* ── HEADER ── */
        .site-header {
            width: 100%;
            background: linear-gradient(135deg,
                rgba(27,94,32,0.92) 0%,
                rgba(46,125,50,0.95) 50%,
                rgba(0,137,123,0.92) 100%);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: white;
            padding: 28px 36px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border-radius: 16px 16px 0 0;
            border: 1px solid rgba(255,255,255,0.12);
            border-bottom: none;
        }
        .site-header .logout-btn {
            position: absolute;
            top: 22px;
            right: 22px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.18);
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: background .2s ease;
        }
        .site-header .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .site-header::before,
        .site-header::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
        }
        .site-header::before { width: 220px; height: 220px; top: -80px; right: -50px; }
        .site-header::after  { width: 160px; height: 160px; bottom: -60px; left: -40px; }
        .site-header h1 {
            font-size: 1.55rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            position: relative; z-index: 1;
        }
        .site-header p {
            font-size: 0.85rem;
            opacity: 0.85;
            margin-top: 5px;
            font-weight: 300;
            position: relative; z-index: 1;
        }

        /* ── BANNER ── */
        .banner-wrap {
            border-radius: 0 0 16px 16px;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0,0,0,0.3);
            margin-bottom: 28px;
            line-height: 0;
        }
        .banner-wrap img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* ── SECTION CARD ── */
        .section-card {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 18px;
            padding: 30px;
            margin-bottom: 24px;
            box-shadow:
                0 4px 24px rgba(0,0,0,0.12),
                0 1px 4px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.7);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1b5e20;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e8f5e9;
        }

        /* ── FORM ── */
        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 5px;
            display: block;
        }
        .form-control,
        .form-select {
            border: 1.5px solid #c8e6c9;
            border-radius: 10px;
            padding: 10px 13px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            color: #333;
            background-color: rgba(250,255,251,0.9);
            width: 100%;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 3px rgba(46,125,50,0.13);
            outline: none;
            background-color: #fff;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-grid .full-width { grid-column: 1 / -1; }

        .btn-simpan {
            background: linear-gradient(135deg, #2e7d32, #00897b);
            color: white;
            border: none;
            border-radius: 11px;
            padding: 13px;
            font-size: 0.93rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            width: 100%;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 14px rgba(46,125,50,0.35);
            letter-spacing: 0.5px;
        }
        .btn-simpan:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46,125,50,0.45);
        }
        .btn-simpan:active { transform: translateY(0); }

        /* ── TABLE ── */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #dcedc8;
        }
        .table { margin-bottom: 0; font-size: 0.88rem; }

        .table thead tr {
            background: linear-gradient(135deg, #2e7d32, #00897b);
            color: white;
        }
        .table thead th {
            font-weight: 600;
            font-size: 0.83rem;
            letter-spacing: 0.5px;
            padding: 13px 16px;
            border: none;
        }
        .table tbody td {
            padding: 12px 16px;
            vertical-align: middle;
            color: #444;
            border: none;
            border-bottom: 1px solid #f1f8e9;
        }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:nth-child(odd)  { background: rgba(255,255,255,0.95); }
        .table tbody tr:nth-child(even) { background: rgba(249,253,249,0.95); }
        .table tbody tr:hover { background: #f1f8e9 !important; }

        .badge-komoditas {
            background: #e8f5e9;
            color: #1b5e20;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid #c8e6c9;
            display: inline-block;
        }
        .badge-jumlah {
            background: #e3f2fd;
            color: #1565c0;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .no-data {
            text-align: center;
            padding: 36px;
            color: #aaa;
            font-size: 0.9rem;
        }
        .row-num {
            background: #e8f5e9;
            color: #2e7d32;
            width: 27px; height: 27px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }

        /* ── NAV BUTTONS ── */
        .nav-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .btn-nav {
            flex: 1;
            min-width: 160px;
            padding: 11px 18px;
            border-radius: 11px;
            font-size: 0.86rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            border: none;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.15s;
            display: inline-block;
        }
        .btn-nav:hover { transform: translateY(-2px); text-decoration: none; }
        .btn-nav-green {
            background: linear-gradient(135deg, #2e7d32, #388e3c);
            color: white;
            box-shadow: 0 4px 12px rgba(46,125,50,0.28);
        }
        .btn-nav-blue {
            background: linear-gradient(135deg, #1565c0, #1976d2);
            color: white;
            box-shadow: 0 4px 12px rgba(21,101,192,0.28);
        }
        .btn-nav-green:hover { color: white; box-shadow: 0 6px 16px rgba(46,125,50,0.4); }
        .btn-nav-blue:hover  { color: white; box-shadow: 0 6px 16px rgba(21,101,192,0.4); }

        /* ── RESPONSIVE ── */
        @media (max-width: 576px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .full-width { grid-column: 1; }
            .site-header h1 { font-size: 1.15rem; }
            .site-header { padding: 20px; }
            .section-card { padding: 20px; }
            .page-wrapper { margin-top: 20px; }
        }
    </style>
</head>

<body>

<!-- Layer spotlight background -->
<div class="bg-spotlight"></div>

<div class="page-wrapper">

    <!-- HEADER -->
    <div class="site-header">
        <?php if ($role === 'admin'): ?>
            <a href="/api/dashboardadmin.php" class="logout-btn">🏠 Beranda</a>
        <?php else: ?>
            <a href="/api/logout.php" class="logout-btn">⬅ Logout</a>
        <?php endif; ?>
        <h1>SISTEM PENCATATAN HASIL PANEN</h1>
        <p>Kelola data panen Anda dengan mudah dan efisien</p>
    </div>

    <!-- BANNER -->
    <div class="banner-wrap">
        <img src="/BANNER.png" alt="Banner Panen">
    </div>

    <!-- FORM INPUT -->
    <div class="section-card">
        <div class="section-title">Input Data Panen</div>

        <form method="POST">
            <div class="form-grid">

                <div class="full-width">
                    <label class="form-label">Tanggal Panen</label>
                    <input type="date" name="tanggal_panen" class="form-control" required>
                </div>

                <div>
                    <label class="form-label">Komoditas</label>
                    <select name="komoditas_panen" class="form-control">
                        <option>Padi</option>
                        <option>Jagung</option>
                        <option>Cabai</option>
                        <option>Kedelai</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Satuan</label>
                    <select name="satuan_panen" class="form-control">
                        <option>Kg</option>
                        <option>Ton</option>
                    </select>
                </div>

                <div class="full-width">
                    <label class="form-label">Jumlah</label>
                    <input type="number" name="jumlah_panen" class="form-control"
                           placeholder="Masukkan jumlah panen..." required>
                </div>

                <div class="full-width">
                    <button type="submit" name="simpan" class="btn-simpan">Simpan Data</button>
                </div>

            </div>
        </form>
    </div>

    <!-- TABEL RIWAYAT -->
    <div class="section-card">
        <div class="section-title">Riwayat Panen</div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Komoditas</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                $tampil = mysqli_query($conn, "SELECT * FROM tbl_panen ORDER BY id DESC");

                if(mysqli_num_rows($tampil) == 0){
                    echo '<tr><td colspan="4" class="no-data">Belum ada data panen</td></tr>';
                } else {
                    while($data = mysqli_fetch_assoc($tampil)){
                ?>
                    <tr>
                        <td><span class="row-num"><?= $no++; ?></span></td>
                        <td><?= date('d M Y', strtotime($data['tanggal'])); ?></td>
                        <td><span class="badge-komoditas"><?= $data['komoditas']; ?></span></td>
                        <td><span class="badge-jumlah"><?= $data['jumlah'] . " " . $data['satuan']; ?></span></td>
                    </tr>
                <?php } } ?>
                </tbody>
            </table>
        </div>

        <div class="nav-buttons">
            <a href="/api/LaporanUmum.php" class="btn-nav btn-nav-green">Dashboard Panen Terbanyak</a>
            <a href="/api/LaporanPerKomoditas.php" class="btn-nav btn-nav-blue">Laporan Per Komoditas</a>
        </div>
    </div>

</div>

</body>
</html>