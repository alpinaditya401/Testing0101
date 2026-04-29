<?php
include "koneksi.php";

if (!isset($_COOKIE['username'])) {
    header("Location: /api/login.php");
    exit();
}

$username = $_COOKIE['username'];

// ── HAPUS ──
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM tbl_panen WHERE id = $id");
    header("Location:/api/dashboardadmin.php");
    exit();
}

// ── EDIT — ambil data untuk modal ──
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM tbl_panen WHERE id = $id");
    if ($res && $res->num_rows > 0) {
        $editData = $res->fetch_assoc();
    }
}

// ── SIMPAN EDIT ──
if (isset($_POST['simpan_edit'])) {
    $id        = (int)$_POST['id'];
    $tanggal   = $conn->real_escape_string($_POST['tanggal']);
    $komoditas = $conn->real_escape_string($_POST['komoditas']);
    $jumlah    = (int)$_POST['jumlah'];
    $satuan    = $conn->real_escape_string($_POST['satuan']);
    $lokasi    = $conn->real_escape_string($_POST['lokasi']);

    $conn->query("UPDATE tbl_panen SET 
        tanggal='$tanggal', komoditas='$komoditas', 
        jumlah='$jumlah', satuan='$satuan', lokasi='$lokasi'
        WHERE id=$id");
    header("Location:/api/dashboardadmin.php");
    exit();
}

$user  = $conn->query("SELECT * FROM tbl_user")->num_rows;
$panen = $conn->query("SELECT * FROM tbl_panen")->num_rows;
$data  = $conn->query("SELECT * FROM tbl_panen ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Sistem Panen</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green-50:  #f0fdf4;
            --green-100: #dcfce7;
            --green-500: #22c55e;
            --green-600: #16a34a;
            --green-700: #15803d;
            --green-900: #14532d;
            --gray-50:   #f9fafb;
            --gray-100:  #f3f4f6;
            --gray-200:  #e5e7eb;
            --gray-400:  #9ca3af;
            --gray-500:  #6b7280;
            --gray-700:  #374151;
            --gray-900:  #111827;
            --white:     #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.04);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--gray-50);
            color: var(--gray-900);
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; top: 0; left: 0;
            width: 240px; height: 100vh;
            background: var(--green-900);
            display: flex; flex-direction: column; z-index: 100;
        }
        .sidebar-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-brand h1 { font-size: 13px; font-weight: 700; color: var(--white); line-height: 1.4; }
        .sidebar-brand p  { font-size: 11px; color: rgba(255,255,255,.4); margin-top: 2px; }

        .sidebar-nav { padding: 16px 12px; flex: 1; }
        .nav-label {
            font-size: 10px; font-weight: 600; letter-spacing: 1px;
            color: rgba(255,255,255,.3); text-transform: uppercase;
            padding: 0 12px; margin: 8px 0 6px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 8px;
            font-size: 13.5px; font-weight: 500;
            color: rgba(255,255,255,.6);
            cursor: pointer; transition: all .18s;
            text-decoration: none; margin-bottom: 2px;
        }
        .nav-item:hover  { background: rgba(255,255,255,.07); color: var(--white); }
        .nav-item.active { background: var(--green-600); color: var(--white); }
        .nav-item .icon  { font-size: 16px; width: 20px; text-align: center; }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .user-pill {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px;
            background: rgba(255,255,255,.07); border-radius: 10px;
        }
        .avatar {
            width: 32px; height: 32px; background: var(--green-500);
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; font-size: 13px; font-weight: 700;
            color: var(--white); flex-shrink: 0;
        }
        .user-pill .name { font-size: 13px; font-weight: 600; color: var(--white); }
        .user-pill .role { font-size: 11px; color: rgba(255,255,255,.4); }

        /* ── MAIN ── */
        .main { margin-left: 240px; padding: 32px; min-height: 100vh; }

        .topbar {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 28px;
        }
        .topbar h2 { font-size: 22px; font-weight: 700; color: var(--gray-900); }
        .topbar .date { font-size: 13px; color: var(--gray-400); margin-top: 2px; }

        .logout-btn {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 16px; background: var(--white);
            border: 1px solid var(--gray-200); border-radius: 8px;
            font-size: 13px; font-weight: 500; color: var(--gray-700);
            cursor: pointer; text-decoration: none;
            transition: all .18s; font-family: inherit;
        }
        .logout-btn:hover { background: #fef2f2; border-color: #fca5a5; color: #dc2626; }

        /* ── STATS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px; margin-bottom: 28px;
        }
        .stat-card {
            background: var(--white); border-radius: 14px;
            padding: 20px 22px; box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
            display: flex; align-items: center; gap: 16px;
            animation: fadeUp .4s ease both;
        }
        .stat-card:nth-child(1) { animation-delay: .05s; }
        .stat-card:nth-child(2) { animation-delay: .10s; }
        .stat-card:nth-child(3) { animation-delay: .15s; }
        .stat-label { font-size: 12.5px; color: var(--gray-500); font-weight: 500; margin-bottom: 4px; }
        .stat-value { font-size: 26px; font-weight: 700; color: var(--gray-900); line-height: 1; }

        /* ── TABLE ── */
        .table-card {
            background: var(--white); border-radius: 16px;
            box-shadow: var(--shadow-sm); border: 1px solid var(--gray-100);
            overflow: hidden; animation: fadeUp .4s .2s ease both;
        }
        .table-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 24px 16px; border-bottom: 1px solid var(--gray-100);
        }
        .table-header h3 { font-size: 15px; font-weight: 700; }
        .badge-count {
            background: var(--green-50); color: var(--green-700);
            font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px;
        }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: var(--gray-50); padding: 11px 16px;
            font-size: 11.5px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .6px; color: var(--gray-500);
            text-align: left; border-bottom: 1px solid var(--gray-100);
        }
        thead th:first-child { padding-left: 24px; }
        thead th:last-child  { padding-right: 24px; text-align: center; }

        tbody tr { transition: background .15s; border-bottom: 1px solid var(--gray-100); }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--gray-50); }

        tbody td {
            padding: 13px 16px; font-size: 13.5px;
            color: var(--gray-700); vertical-align: middle;
        }
        tbody td:first-child { padding-left: 24px; color: var(--gray-400); font-size: 12px; }
        tbody td:last-child  { padding-right: 24px; text-align: center; }

        .komoditas-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 6px;
            font-size: 12.5px; font-weight: 600;
        }
        .k-padi    { background: #fefce8; color: #854d0e; }
        .k-jagung  { background: #fff7ed; color: #9a3412; }
        .k-cabai   { background: #fef2f2; color: #991b1b; }
        .k-kedelai { background: #f0fdf4; color: #166534; }
        .k-default { background: var(--gray-100); color: var(--gray-700); }

        .jumlah-cell { font-weight: 600; color: var(--gray-900); }
        .lokasi-tag {
            display: inline-block; background: #eff6ff; color: #1d4ed8;
            font-size: 12px; font-weight: 500; padding: 3px 9px; border-radius: 5px;
        }

        .btn-group { display: flex; gap: 6px; justify-content: center; }
        .btn {
            padding: 6px 14px; border: none; border-radius: 7px;
            font-size: 12.5px; font-weight: 600;
            cursor: pointer; transition: all .15s; font-family: inherit;
            text-decoration: none; display: inline-block;
        }
        .btn-edit  { background: #eff6ff; color: #2563eb; }
        .btn-edit:hover  { background: #2563eb; color: var(--white); }
        .btn-hapus { background: #fef2f2; color: #dc2626; }
        .btn-hapus:hover { background: #dc2626; color: var(--white); }

        .empty-state { padding: 48px 24px; text-align: center; color: var(--gray-400); }
        .empty-state p { font-size: 14px; }

        /* ── MODAL ── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 999;
            background: rgba(0,0,0,0.45);
            align-items: center; justify-content: center;
        }
        .modal-overlay.show { display: flex; }

        .modal-box {
            background: var(--white);
            border-radius: 18px;
            padding: 32px;
            width: 100%; max-width: 460px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: modalIn .25s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(.95) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-title {
            font-size: 17px; font-weight: 700;
            color: var(--green-900); margin-bottom: 22px;
            padding-bottom: 14px;
            border-bottom: 2px solid var(--green-100);
        }

        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block; font-size: 12.5px; font-weight: 600;
            color: var(--green-700); margin-bottom: 6px;
        }
        .form-group input,
        .form-group select {
            width: 100%; padding: 10px 13px;
            border: 1.5px solid var(--gray-200);
            border-radius: 10px; font-size: 13.5px;
            font-family: inherit; color: var(--gray-900);
            background: var(--gray-50);
            transition: border-color .2s, box-shadow .2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--green-500);
            box-shadow: 0 0 0 3px rgba(34,197,94,.12);
            background: var(--white);
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .modal-actions {
            display: flex; gap: 10px; margin-top: 24px;
        }
        .btn-save {
            flex: 1; padding: 11px;
            background: linear-gradient(135deg, var(--green-700), var(--green-500));
            color: var(--white); border: none; border-radius: 10px;
            font-size: 13.5px; font-weight: 700; cursor: pointer;
            font-family: inherit; transition: opacity .15s;
        }
        .btn-save:hover { opacity: .88; }
        .btn-cancel {
            flex: 1; padding: 11px;
            background: var(--gray-100); color: var(--gray-700);
            border: none; border-radius: 10px;
            font-size: 13.5px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: background .15s;
        }
        .btn-cancel:hover { background: var(--gray-200); }

        /* ── KONFIRMASI HAPUS ── */
        .confirm-overlay {
            display: none; position: fixed; inset: 0; z-index: 1000;
            background: rgba(0,0,0,0.45);
            align-items: center; justify-content: center;
        }
        .confirm-overlay.show { display: flex; }
        .confirm-box {
            background: var(--white); border-radius: 16px;
            padding: 28px; width: 100%; max-width: 360px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: modalIn .25s ease;
        }
        .confirm-icon { font-size: 40px; margin-bottom: 12px; }
        .confirm-box h3 { font-size: 16px; font-weight: 700; margin-bottom: 8px; color: var(--gray-900); }
        .confirm-box p  { font-size: 13px; color: var(--gray-500); margin-bottom: 22px; }
        .confirm-actions { display: flex; gap: 10px; }
        .btn-confirm-hapus {
            flex: 1; padding: 10px; background: #dc2626;
            color: var(--white); border: none; border-radius: 9px;
            font-size: 13.5px; font-weight: 700; cursor: pointer;
            font-family: inherit;
        }
        .btn-confirm-hapus:hover { background: #b91c1c; }
        .btn-confirm-batal {
            flex: 1; padding: 10px; background: var(--gray-100);
            color: var(--gray-700); border: none; border-radius: 9px;
            font-size: 13.5px; font-weight: 600; cursor: pointer;
            font-family: inherit;
        }
        .btn-confirm-batal:hover { background: var(--gray-200); }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; padding: 20px 16px; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h1>Sistem Pencatatan<br>Hasil Panen</h1>
        <p>Panel Administrator</p>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Menu</div>
        <a href="/api/dashboardadmin.php"     class="nav-item active"><span class="icon">🏠</span> Dashboard</a>
        <a href="/api/PencatatanPanen.php"    class="nav-item"><span class="icon">📝</span> Pencatatan Panen</a>
        <a href="/api/LaporanUmum.php"        class="nav-item"><span class="icon">📊</span> Dashboard Panen</a>
        <a href="/api/LaporanPerKomoditas.php"class="nav-item"><span class="icon">🌾</span> Laporan Komoditas</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-pill">
            <div class="avatar"><?= strtoupper(substr($username, 0, 1)) ?></div>
            <div>
                <div class="name"><?= htmlspecialchars($username) ?></div>
                <div class="role">Administrator</div>
            </div>
        </div>
    </div>
</aside>

<!-- Main -->
<main class="main">

    <div class="topbar">
        <div>
            <h2>Dashboard</h2>
            <div class="date"><?= date('l, d F Y') ?></div>
        </div>
        <a href="/api/logout.php" class="logout-btn">⬅ Logout</a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div><div class="stat-label">Total User</div><div class="stat-value"><?= $user ?></div></div>
        </div>
        <div class="stat-card">
            <div><div class="stat-label">Total Data Panen</div><div class="stat-value"><?= $panen ?></div></div>
        </div>
        <div class="stat-card">
            <div><div class="stat-label">Tahun Berjalan</div><div class="stat-value"><?= date('Y') ?></div></div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-header">
            <h3>Data Panen Terbaru</h3>
            <span class="badge-count"><?= $panen ?> record</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>No</th><th>Tanggal</th><th>Komoditas</th>
                    <th>Jumlah</th><th>Lokasi</th><th style="text-align:center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $komoditasClass = ['Padi'=>'k-padi','Jagung'=>'k-jagung','Cabai'=>'k-cabai','Kedelai'=>'k-kedelai'];
            if ($data->num_rows === 0): ?>
                <tr><td colspan="6"><div class="empty-state"><p>Belum ada data panen.</p></div></td></tr>
            <?php else:
                $no = 1;
                while ($row = $data->fetch_assoc()):
                    $cls = $komoditasClass[$row['komoditas']] ?? 'k-default';
            ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                    <td><span class="komoditas-badge <?= $cls ?>"><?= htmlspecialchars($row['komoditas']) ?></span></td>
                    <td class="jumlah-cell"><?= htmlspecialchars($row['jumlah']).' '.htmlspecialchars($row['satuan']) ?></td>
                    <td><span class="lokasi-tag"><?= htmlspecialchars($row['lokasi'] ?? '-') ?></span></td>
                    <td>
                        <div class="btn-group">
                            <!-- Tombol Edit — buka modal lewat JS -->
                            <button class="btn btn-edit"
                                onclick="bukaEdit(
                                    <?= $row['id'] ?>,
                                    '<?= $row['tanggal'] ?>',
                                    '<?= addslashes($row['komoditas']) ?>',
                                    <?= $row['jumlah'] ?>,
                                    '<?= addslashes($row['satuan']) ?>',
                                    '<?= addslashes($row['lokasi'] ?? '') ?>'
                                )">Edit</button>
                            <!-- Tombol Hapus — buka konfirmasi -->
                            <button class="btn btn-hapus"
                                onclick="bukaHapus(<?= $row['id'] ?>)">Hapus</button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

</main>

<!-- ── MODAL EDIT ── -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal-box">
        <div class="modal-title">✏️ Edit Data Panen</div>
        <form method="POST">
            <input type="hidden" name="id" id="editId">

            <div class="form-group">
                <label>Tanggal Panen</label>
                <input type="date" name="tanggal" id="editTanggal" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Komoditas</label>
                    <select name="komoditas" id="editKomoditas">
                        <option>Padi</option>
                        <option>Jagung</option>
                        <option>Cabai</option>
                        <option>Kedelai</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Satuan</label>
                    <select name="satuan" id="editSatuan">
                        <option>Kg</option>
                        <option>Ton</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah" id="editJumlah" required min="1">
                </div>
                <div class="form-group">
                    <label>Lokasi</label>
                    <select name="lokasi" id="editLokasi">
                        <option>Lahan A</option>
                        <option>Lahan B</option>
                        <option>Lahan C</option>
                    </select>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="tutupEdit()">Batal</button>
                <button type="submit" name="simpan_edit" class="btn-save">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ── MODAL KONFIRMASI HAPUS ── -->
<div class="confirm-overlay" id="confirmHapus">
    <div class="confirm-box">
        <div class="confirm-icon">🗑️</div>
        <h3>Hapus Data?</h3>
        <p>Data yang dihapus tidak dapat dikembalikan.</p>
        <div class="confirm-actions">
            <button class="btn-confirm-batal" onclick="tutupHapus()">Batal</button>
            <a id="linkHapus" href="#" class="btn-confirm-hapus">Ya, Hapus</a>
        </div>
    </div>
</div>

<script>
// ── MODAL EDIT ──
function bukaEdit(id, tanggal, komoditas, jumlah, satuan, lokasi) {
    document.getElementById('editId').value       = id;
    document.getElementById('editTanggal').value  = tanggal;
    document.getElementById('editJumlah').value   = jumlah;

    // Set select komoditas
    const selKom = document.getElementById('editKomoditas');
    for (let o of selKom.options) o.selected = (o.value === komoditas);

    // Set select satuan
    const selSat = document.getElementById('editSatuan');
    for (let o of selSat.options) o.selected = (o.value === satuan);

    // Set select lokasi
    const selLok = document.getElementById('editLokasi');
    for (let o of selLok.options) o.selected = (o.value === lokasi);

    document.getElementById('modalEdit').classList.add('show');
}

function tutupEdit() {
    document.getElementById('modalEdit').classList.remove('show');
}

// Klik di luar modal → tutup
document.getElementById('modalEdit').addEventListener('click', function(e) {
    if (e.target === this) tutupEdit();
});

// ── KONFIRMASI HAPUS ──
function bukaHapus(id) {
    document.getElementById('linkHapus').href = '?hapus=' + id;
    document.getElementById('confirmHapus').classList.add('show');
}

function tutupHapus() {
    document.getElementById('confirmHapus').classList.remove('show');
}

document.getElementById('confirmHapus').addEventListener('click', function(e) {
    if (e.target === this) tutupHapus();
});
</script>

</body>
</html>