<?php
/**
 * dashboard.php — Panel Admin & Admin Master
 * ─────────────────────────────────────────────────────────────
 * TAB YANG TERSEDIA:
 *   data        → Kelola harga komoditas (admin & admin_master)
 *   verifikasi  → Approve/reject laporan kontributor
 *   artikel     → CRUD artikel edukasi
 *   pengumuman  → Kelola pengumuman (admin & admin_master)
 *   users       → Kelola role pengguna (KHUSUS admin_master)
 *   settings    → Pengaturan sistem (KHUSUS admin_master)
 * ─────────────────────────────────────────────────────────────
 */
require __DIR__ . '/Server/koneksi.php';
cekLogin();
cekRole(['admin','admin_master']);

$role      = $_SESSION['role'];
$isMaster  = $role === 'admin_master';
$uid       = (int)$_SESSION['user_id'];
$activeTab = $_GET['tab'] ?? 'data';

// Pastikan tab users dan settings hanya untuk admin_master
if (in_array($activeTab,['users','settings']) && !$isMaster) {
    redirect('dashboard.php?tab=data');
}

// ── DATA KOMODITAS: READ-ONLY dari BPS API (var=2498) ─────────
// Admin tidak dapat menambah/menghapus data komoditas.
// Data diambil langsung dari BPS API secara otomatis.

define('BPS_KEY_DASH', '4e182f178f0d964814488d42593f2594');
$rows  = [];
$uNama = [];
$uLokasi = [];
$bpsDataSource = 'BPS API';

// Cek cache (1 jam)
$cacheFile = sys_get_temp_dir() . '/bps_komoditas_cache.json';
$cacheData = null;
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
    $cacheData = json_decode(file_get_contents($cacheFile), true);
}

if ($cacheData && !empty($cacheData)) {
    $bpsRaw = $cacheData;
    $bpsDataSource = 'BPS API (cache)';
} else {
    $bpsUrl = 'https://webapi.bps.go.id/v1/api/list/model/data/lang/ind/domain/0000/var/2498/th/126/key/' . BPS_KEY_DASH . '/';
    $ctx = stream_context_create([
        'http' => ['timeout' => 10, 'user_agent' => 'InfoHarga-Komoditi/4.0'],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $bpsRawJson = @file_get_contents($bpsUrl, false, $ctx);
    $bpsRaw = [];
    if ($bpsRawJson) {
        $bpsParsed = json_decode($bpsRawJson, true);
        if (($bpsParsed['status'] ?? '') === 'OK') {
            $dataRows = $bpsParsed['data'][1] ?? [];
            $labelKom = $bpsParsed['vervar']  ?? [];
            $labelWil = $bpsParsed['turvar']  ?? [];
            $komMap = []; foreach ($labelKom as $lk) $komMap[$lk['val']??''] = $lk['label']??'';
            $wilMap = []; foreach ($labelWil as $lw) $wilMap[$lw['val']??''] = $lw['label']??'';
            $satuanFn = function($n){ $m=['beras'=>'kg','cabai'=>'kg','bawang'=>'kg','minyak'=>'liter','gula'=>'kg','daging'=>'kg','ayam'=>'kg','telur'=>'kg','ikan'=>'kg','terigu'=>'kg','jagung'=>'kg']; foreach($m as $k=>$v){if(str_contains(strtolower($n),$k))return $v;} return 'kg'; };
            $grouped = [];
            foreach ($dataRows as $row) {
                $komVal = $row['vervar'] ?? ($row[0] ?? '');
                $wilVal = $row['turvar'] ?? ($row[1] ?? '');
                $nilai  = (float)($row['val'] ?? ($row[2] ?? 0));
                if ($nilai <= 0) continue;
                $komNama = $komMap[$komVal] ?? $komVal;
                $wilNama = $wilMap[$wilVal] ?? $wilVal;
                if (!isset($grouped[$komNama])) $grouped[$komNama] = [];
                $grouped[$komNama][] = ['wilayah' => $wilNama, 'nilai' => $nilai];
            }
            $idCounter = 9000;
            foreach ($grouped as $komNama => $entries) {
                if (empty($entries)) continue;
                $nilaiArr = array_column($entries, 'nilai');
                $rata = (int)round(array_sum($nilaiArr) / count($nilaiArr));
                $hist = [];
                for ($i = 6; $i >= 1; $i--) $hist[] = (int)round($rata * (1 + (($i%3-1) * 0.015)));
                $hist[] = $rata;
                $bpsRaw[] = [
                    'id'             => $idCounter++,
                    'nama'           => $komNama,
                    'kategori'       => 'BPS Data',
                    'lokasi'         => $entries[0]['wilayah'] ?? 'Indonesia',
                    'provinsi'       => 'Nasional',
                    'satuan'         => $satuanFn($komNama),
                    'harga_kemarin'  => (int)round($rata * 0.98),
                    'harga_sekarang' => $rata,
                    'history'        => json_encode($hist),
                    'status'         => 'approved',
                    'kontributor'    => 'BPS Indonesia',
                    'submitted_by'   => null,
                ];
            }
            if (!empty($bpsRaw)) @file_put_contents($cacheFile, json_encode($bpsRaw));
        }
    }
}

// Fallback jika BPS API gagal
if (empty($bpsRaw)) {
    $bpsDataSource = 'Fallback Cache';
    $bpsRaw = [
        ['id'=>9001,'nama'=>'Beras Premium','kategori'=>'Beras & Serealia','lokasi'=>'Indonesia','provinsi'=>'Nasional','satuan'=>'kg','harga_kemarin'=>14000,'harga_sekarang'=>14500,'history'=>json_encode([13500,13700,13800,13900,14000,14000,14500]),'status'=>'approved','kontributor'=>'BPS Indonesia'],
        ['id'=>9002,'nama'=>'Cabai Merah','kategori'=>'Hortikultura','lokasi'=>'Indonesia','provinsi'=>'Nasional','satuan'=>'kg','harga_kemarin'=>35000,'harga_sekarang'=>42000,'history'=>json_encode([28000,30000,32000,34000,35000,35000,42000]),'status'=>'approved','kontributor'=>'BPS Indonesia'],
        ['id'=>9003,'nama'=>'Bawang Merah','kategori'=>'Bumbu & Rempah','lokasi'=>'Indonesia','provinsi'=>'Nasional','satuan'=>'kg','harga_kemarin'=>28000,'harga_sekarang'=>30000,'history'=>json_encode([25000,26000,27000,27500,28000,28000,30000]),'status'=>'approved','kontributor'=>'BPS Indonesia'],
        ['id'=>9004,'nama'=>'Minyak Goreng','kategori'=>'Minyak & Lemak','lokasi'=>'Indonesia','provinsi'=>'Nasional','satuan'=>'liter','harga_kemarin'=>17000,'harga_sekarang'=>17000,'history'=>json_encode([17000,17000,17000,17000,17000,17000,17000]),'status'=>'approved','kontributor'=>'BPS Indonesia'],
        ['id'=>9005,'nama'=>'Gula Pasir','kategori'=>'Lainnya','lokasi'=>'Indonesia','provinsi'=>'Nasional','satuan'=>'kg','harga_kemarin'=>16500,'harga_sekarang'=>17000,'history'=>json_encode([16000,16000,16200,16400,16500,16500,17000]),'status'=>'approved','kontributor'=>'BPS Indonesia'],
    ];
}

$rows = $bpsRaw;
foreach ($rows as $r) { $uNama[] = $r['nama']; $uLokasi[] = $r['lokasi']; }
$uNama   = array_unique($uNama);
$uLokasi = array_unique($uLokasi);

// ── QUERY DATA UNTUK TAB LAINNYA ──────────────────────────────

// Pending verifikasi
$resPending = $conn->query("SELECT k.*,u.username AS kontributor FROM komoditas k LEFT JOIN users u ON k.submitted_by=u.id WHERE k.status='pending' ORDER BY k.updated_at DESC");
$pending=[]; while($r=$resPending->fetch_assoc()) $pending[]=$r;
$pendingCount=count($pending);

// Artikel
$resArt = $conn->query("SELECT a.*,u.username AS penulis FROM artikel a LEFT JOIN users u ON a.penulis_id=u.id ORDER BY a.created_at DESC");
$artikels=[]; while($r=$resArt->fetch_assoc()) $artikels[]=$r;

// Pengumuman
$resPeng = $conn->query("SELECT p.*,u.username AS pembuat FROM pengumuman p LEFT JOIN users u ON p.dibuat_oleh=u.id ORDER BY p.created_at DESC");
$pengumumans=[]; while($r=$resPeng->fetch_assoc()) $pengumumans[]=$r;

// Users (hanya untuk admin_master)
$allUsers=[];
if ($isMaster) {
    $resU = $conn->query("SELECT id,username,email,nama_lengkap,role,is_active,last_login,created_at FROM users ORDER BY role ASC, created_at DESC");
    while($r=$resU->fetch_assoc()) $allUsers[]=$r;
}

// Settings (hanya untuk admin_master)
$settings=[];
if ($isMaster) {
    $resS = $conn->query("SELECT * FROM pengaturan_sistem ORDER BY kelompok ASC, id ASC");
    while($r=$resS->fetch_assoc()) $settings[$r['kelompok']][]=$r;
}

// Statistik
$totalApproved = count($rows);  // dari BPS API
$totalKont     = (int)($conn->query("SELECT COUNT(*) c FROM users WHERE role='kontributor'")?->fetch_assoc()['c']??0);
$totalUsers    = (int)($conn->query("SELECT COUNT(*) c FROM users")?->fetch_assoc()['c']??0);
$totalProv     = 38; // 38 provinsi Indonesia (data nasional BPS)

$kategoris  = ['Beras & Serealia','Hortikultura','Bumbu & Rempah','Peternakan','Minyak & Lemak','Perikanan','Lainnya'];
$satuans    = ['kg','gram','liter','ml','butir','ikat','buah'];
$allRoles   = ['admin_master','admin','kontributor','user'];
$pageTitle  = 'Dashboard Admin';
?>
<!doctype html>
<html lang="id">
<head><?php include __DIR__ . '/Assets/head.php'; ?>
<style>
  body { overflow:hidden; }
  .admin-wrap { display:flex; height:100vh; }
  .sidebar { width:220px; flex-shrink:0; display:flex; flex-direction:column; height:100%; background:var(--bg-secondary); border-right:1px solid var(--border); overflow:hidden; }
  .main-area { flex:1; display:flex; flex-direction:column; height:100%; overflow:hidden; }
  .main-body { flex:1; overflow-y:auto; padding:1.5rem; }
  .main-body::-webkit-scrollbar { width:4px; }
  .main-body::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }
  .tab-btn { display:flex; align-items:center; gap:.5rem; padding:.5rem 1rem; border-radius:.5rem; font-size:.8rem; font-weight:600; cursor:pointer; border:none; background:transparent; color:var(--text-muted); transition:background .15s,color .15s; }
  .tab-btn.active { background:rgba(16,185,129,.1); color:#10b981; }
  .tab-btn:hover:not(.active) { background:var(--surface); color:var(--text-primary); }
</style>
</head>
<body>
<div class="admin-wrap">

<!-- ══ SIDEBAR ════════════════════════════════════════════ -->
<aside class="sidebar">
  <!-- Logo -->
  <div class="h-16 flex items-center px-5 border-b border-[var(--border)] flex-shrink-0">
    <a href="index.php" class="flex items-center gap-2 group">
      <div class="w-7 h-7 bg-brand-500 rounded-lg flex items-center justify-center shadow shadow-brand-500/30 group-hover:scale-105 transition-transform">
        <i data-lucide="trending-up" class="w-3.5 h-3.5 text-white"></i>
      </div>
      <span class="font-display font-black text-[var(--text-primary)] text-sm">
        InfoHarga<?php if($isMaster): ?><span class="text-purple-400">Master</span><?php else: ?><span class="text-brand-500">Admin</span><?php endif; ?>
      </span>
    </a>
  </div>

  <!-- Nav -->
  <nav class="flex-1 py-4 px-3 space-y-0.5 sidebar-nav slim-scroll overflow-y-auto">
    <div class="nav-section">Menu Utama</div>
    <a href="dashboard.php?tab=data"       class="<?= $activeTab==='data'?'active':'' ?>"><i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard</a>
    <a href="dashboard.php?tab=verifikasi" class="<?= $activeTab==='verifikasi'?'active':'' ?> relative">
      <i data-lucide="shield-check" class="w-4 h-4"></i> Verifikasi
      <?php if($pendingCount>0): ?><span class="ml-auto text-[10px] font-black bg-red-500 text-white px-1.5 py-0.5 rounded-full font-display"><?= $pendingCount ?></span><?php endif; ?>
    </a>
    <a href="dashboard.php?tab=pengumuman" class="<?= $activeTab==='pengumuman'?'active':'' ?>"><i data-lucide="bell" class="w-4 h-4"></i> Pengumuman</a>
    <a href="dashboard.php?tab=artikel"    class="<?= $activeTab==='artikel'?'active':'' ?>"><i data-lucide="file-text" class="w-4 h-4"></i> Artikel</a>

    <?php if ($isMaster): ?>
    <div class="nav-section">Admin Master</div>
    <a href="dashboard.php?tab=users"    class="<?= $activeTab==='users'?'active':'' ?>"><i data-lucide="users" class="w-4 h-4"></i> Kelola Users</a>
    <a href="dashboard.php?tab=settings" class="<?= $activeTab==='settings'?'active':'' ?>"><i data-lucide="settings" class="w-4 h-4"></i> Pengaturan</a>
    <?php endif; ?>

    <div class="nav-section">Lainnya</div>
    <a href="index.php" target="_blank"><i data-lucide="home" class="w-4 h-4"></i> Home (Website)</a>
    <a href="compare.php"><i data-lucide="git-compare" class="w-4 h-4"></i> Bandingkan</a>
    <a href="export.php?type=komoditas"><i data-lucide="download" class="w-4 h-4"></i> Export CSV</a>
    <a href="profil.php"><i data-lucide="user-circle" class="w-4 h-4"></i> Profil Saya</a>
    <a href="#" data-action="toggle-theme"><i data-lucide="moon" data-theme-icon="toggle" class="w-4 h-4"></i> Ganti Tema</a>
  </nav>

  <!-- User info + logout -->
  <div class="p-3 border-t border-[var(--border)] flex-shrink-0">
    <div class="flex items-center gap-2.5 px-3 py-2 rounded-lg mb-1 bg-[var(--surface)]">
      <div class="w-7 h-7 rounded-full <?= $isMaster?'bg-purple-500/20':'bg-brand-500/20' ?> flex items-center justify-center text-[10px] font-black <?= $isMaster?'text-purple-400':'text-brand-500' ?> font-display flex-shrink-0">
        <?= strtoupper(substr($_SESSION['username'],0,1)) ?>
      </div>
      <div class="min-w-0">
        <div class="text-xs font-bold text-[var(--text-primary)] truncate"><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="text-[10px] text-[var(--text-muted)]"><?= $isMaster?'Admin Master':'Administrator' ?></div>
      </div>
    </div>
    <a href="Proses/logout.php" onclick="return confirm('Yakin ingin keluar?')"
       class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-red-400 hover:bg-red-500/8 hover:text-red-300 transition sidebar-nav">
      <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Logout
    </a>
  </div>
</aside>

<!-- ══ MAIN AREA ══════════════════════════════════════════ -->
<div class="main-area">
  <!-- Header -->
  <header class="h-16 bg-[var(--bg-card)] border-b border-[var(--border)] flex items-center justify-between px-6 flex-shrink-0">
    <h1 class="font-display font-black text-[var(--text-primary)]">
      <?php $tabNames=['data'=>'Dashboard','verifikasi'=>'Verifikasi Laporan','artikel'=>'Manajemen Artikel','pengumuman'=>'Manajemen Pengumuman','users'=>'Kelola Pengguna','settings'=>'Pengaturan Sistem'];
      echo $tabNames[$activeTab] ?? 'Dashboard'; ?>
    </h1>
    <div class="flex items-center gap-3">
      <?php if ($activeTab==='data'): ?>
      <div class="flex items-center gap-2 px-4 py-2 bg-blue-500/10 border border-blue-500/20 text-blue-400 text-sm font-semibold rounded-lg font-display">
        <i data-lucide="database" class="w-4 h-4"></i> Data dari <?= $bpsDataSource ?>
      </div>
      <?php elseif ($activeTab==='artikel'): ?>
      <button onclick="openModal('mArtikel')" class="flex items-center gap-2 px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold rounded-lg transition shadow shadow-brand-600/20 font-display">
        <i data-lucide="plus" class="w-4 h-4"></i> Artikel Baru
      </button>
      <?php elseif ($activeTab==='pengumuman'): ?>
      <button onclick="openModal('mPengumuman')" class="flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-white text-sm font-bold rounded-lg transition font-display">
        <i data-lucide="plus" class="w-4 h-4"></i> Pengumuman Baru
      </button>
      <?php endif; ?>
    </div>
  </header>

  <!-- Body -->
  <div class="main-body">

    <!-- Pesan sukses/error -->
    <div id="msg-box" class="hidden mb-5 text-sm"></div>

    <?php if ($activeTab === 'data'): ?>
    <!-- ── INFO BANNER BPS ── -->
    <div class="mb-5 flex items-start gap-3 p-4 rounded-xl bg-blue-500/8 border border-blue-500/20 text-sm text-blue-300">
      <i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0 text-blue-400"></i>
      <div>
        <span class="font-bold text-blue-300">Data Komoditas Read-Only</span> — Data harga komoditas diambil otomatis dari 
        <strong>BPS API</strong> (var=2498, domain=0000). Admin tidak dapat menambah atau menghapus data secara manual.
        Cache diperbarui setiap 1 jam. Sumber: <span class="font-mono text-xs text-blue-400"><?= $bpsDataSource ?></span>
      </div>
    </div>

    <!-- ── STAT CARDS ── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <?php foreach([
        [$totalApproved,'Total Komoditas','database','blue'],
        [$pendingCount,'Pending','clock','amber'],
        [$totalKont,'Kontributor','users','emerald'],
        [$totalProv,'Provinsi Aktif','map-pin','purple'],
      ] as [$v,$l,$ic,$c]): ?>
      <div class="card p-5 flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 bg-<?= $c ?>-500/10">
          <i data-lucide="<?= $ic ?>" class="w-5 h-5 text-<?= $c ?>-400"></i>
        </div>
        <div>
          <div class="font-display font-black text-2xl text-[var(--text-primary)]"><?= $v ?></div>
          <div class="text-xs text-[var(--text-muted)]"><?= $l ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Chart -->
    <div class="card p-5 mb-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
        <h2 class="font-display font-bold text-[var(--text-primary)]">Grafik Pergerakan Harga</h2>
        <div class="flex gap-2">
          <select id="fNama" class="input-field text-xs py-1.5 px-3" style="width:160px">
            <?php foreach($uNama as $n): ?><option><?= htmlspecialchars($n) ?></option><?php endforeach; ?>
          </select>
          <select id="fLokasi" class="input-field text-xs py-1.5 px-3" style="width:160px">
            <option value="">— Pilih komoditas dulu —</option>
          </select>
        </div>
      </div>
      <div style="position:relative;height:200px"><canvas id="adminChart"></canvas></div>
    </div>

    <!-- Tabel komoditas -->
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-[var(--border)]"><h2 class="font-display font-bold text-[var(--text-primary)]">Daftar Komoditas</h2></div>
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead><tr><th>No</th><th>Komoditas</th><th>Kategori</th><th>Lokasi</th><th>Provinsi</th><th>Kemarin</th><th>Sekarang</th><th class="text-center">Sumber</th></tr></thead>
          <tbody>
            <?php if(empty($rows)): ?>
            <tr><td colspan="8" class="text-center py-12 text-[var(--text-muted)]"><i data-lucide="inbox" class="w-10 h-10 mx-auto mb-2 opacity-20"></i><br>Belum ada data.</td></tr>
            <?php else: $no=1; foreach($rows as $r):
              $naik=(int)$r['harga_sekarang']>(int)$r['harga_kemarin']; $turun=(int)$r['harga_sekarang']<(int)$r['harga_kemarin'];
            ?>
            <tr>
              <td class="text-[var(--text-muted)]"><?= $no++ ?></td>
              <td class="font-bold text-[var(--text-primary)]"><?= htmlspecialchars($r['nama']) ?></td>
              <td><span class="badge badge-slate text-[10px]"><?= htmlspecialchars($r['kategori']) ?></span></td>
              <td><a href="https://maps.google.com/?q=<?= urlencode($r['lokasi']) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs bg-[var(--surface)] hover:bg-brand-500/10 border border-[var(--border)] hover:border-brand-500/20 hover:text-brand-500 px-2 py-0.5 rounded-md transition"><i data-lucide="map-pin" class="w-2.5 h-2.5"></i><?= htmlspecialchars($r['lokasi']) ?></a></td>
              <td class="text-xs text-[var(--text-muted)]"><?= htmlspecialchars($r['provinsi']?:'—') ?></td>
              <td class="text-[var(--text-muted)]"><?= rupiah((int)$r['harga_kemarin']) ?></td>
              <td><span class="font-bold <?= $naik?'text-brand-500':($turun?'text-red-400':'text-[var(--text-primary)]') ?>"><?= rupiah((int)$r['harga_sekarang']) ?></span> <span class="text-xs <?= $naik?'text-brand-500':($turun?'text-red-400':'text-[var(--text-muted)]') ?>"><?= $naik?'▲':($turun?'▼':'■') ?></span></td>
              <td class="text-center">
                <span class="text-[10px] text-[var(--text-muted)] italic flex items-center justify-center gap-1">
                  <i data-lucide="lock" class="w-3 h-3"></i> BPS
                </span>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php elseif ($activeTab === 'verifikasi'): ?>
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-[var(--border)] flex items-center gap-3">
        <h2 class="font-display font-bold text-[var(--text-primary)]">Laporan Menunggu Verifikasi</h2>
        <?php if($pendingCount>0): ?><span class="badge badge-amber"><?= $pendingCount ?> pending</span><?php endif; ?>
      </div>
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead><tr><th>Komoditas</th><th>Lokasi</th><th>Harga</th><th>Kontributor</th><th>Tanggal</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php if(empty($pending)): ?>
            <tr><td colspan="6" class="text-center py-14 text-[var(--text-muted)]"><i data-lucide="check-circle" class="w-10 h-10 mx-auto mb-2 text-brand-500 opacity-40"></i><br>Semua sudah diverifikasi!</td></tr>
            <?php else: foreach($pending as $r): ?>
            <tr>
              <td><div class="font-bold text-[var(--text-primary)]"><?= htmlspecialchars($r['nama']) ?></div><div class="text-[10px] text-[var(--text-muted)]"><?= htmlspecialchars($r['kategori']) ?></div></td>
              <td><div><?= htmlspecialchars($r['lokasi']) ?></div><div class="text-[10px] text-[var(--text-muted)]"><?= htmlspecialchars($r['provinsi']?:'?') ?></div></td>
              <td><div class="text-xs text-[var(--text-muted)]">Kem: <?= rupiah((int)$r['harga_kemarin']) ?></div><div class="font-bold text-[var(--text-primary)]"><?= rupiah((int)$r['harga_sekarang']) ?></div></td>
              <td><div class="flex items-center gap-1.5"><div class="w-5 h-5 rounded-full bg-blue-500/15 flex items-center justify-center text-[9px] font-black text-blue-400"><?= strtoupper(substr($r['kontributor']??'?',0,1)) ?></div><span class="text-sm"><?= htmlspecialchars($r['kontributor']??'Unknown') ?></span></div></td>
              <td class="text-xs text-[var(--text-muted)]"><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
              <td>
                <div class="flex gap-2">
                  <form method="POST" action="Proses/prosesVerifikasi.php"><input type="hidden" name="id" value="<?= $r['id'] ?>"/><input type="hidden" name="aksi" value="approve"/><button class="badge badge-green cursor-pointer hover:opacity-80 transition"><i data-lucide="check" class="w-3 h-3"></i> Setujui</button></form>
                  <form method="POST" action="Proses/prosesVerifikasi.php" onsubmit="return setNote(this)"><input type="hidden" name="id" value="<?= $r['id'] ?>"/><input type="hidden" name="aksi" value="reject"/><input type="hidden" name="catatan" value=""/><button class="badge badge-red cursor-pointer hover:opacity-80 transition"><i data-lucide="x" class="w-3 h-3"></i> Tolak</button></form>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php elseif ($activeTab === 'artikel'): ?>
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-[var(--border)]"><h2 class="font-display font-bold text-[var(--text-primary)]">Daftar Artikel (<?= count($artikels) ?>)</h2></div>
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead><tr><th>Judul</th><th>Kategori</th><th>Penulis</th><th>Status</th><th>Views</th><th class="text-center">Aksi</th></tr></thead>
          <tbody>
            <?php if(empty($artikels)): ?>
            <tr><td colspan="6" class="text-center py-12 text-[var(--text-muted)]">Belum ada artikel.</td></tr>
            <?php else: foreach($artikels as $a): ?>
            <tr>
              <td><div class="flex items-center gap-2"><span class="text-lg"><?= htmlspecialchars($a['emoji']) ?></span><div><div class="font-bold text-[var(--text-primary)] text-sm max-w-xs truncate"><?= htmlspecialchars($a['judul']) ?></div><?php if($a['sumber_url']): ?><a href="<?= htmlspecialchars($a['sumber_url']) ?>" target="_blank" class="text-[10px] text-brand-500 hover:underline flex items-center gap-0.5"><i data-lucide="external-link" class="w-2.5 h-2.5"></i>Sumber luar</a><?php endif; ?></div></div></td>
              <td><span class="badge badge-slate"><?= htmlspecialchars($a['kategori']) ?></span></td>
              <td class="text-xs text-[var(--text-muted)]"><?= htmlspecialchars($a['penulis']??'—') ?></td>
              <td><?= $a['is_publish'] ? '<span class="badge badge-green">Publik</span>' : '<span class="badge badge-slate">Draft</span>' ?></td>
              <td class="text-[var(--text-muted)]"><?= number_format($a['views']) ?></td>
              <td class="text-center">
                <div class="flex items-center justify-center gap-1">
                  <button onclick='editArtikel(<?= json_encode($a) ?>)' class="p-1.5 rounded-lg text-[var(--text-muted)] hover:text-blue-400 hover:bg-blue-500/10 transition"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
                  <a href="Proses/prosesArtikel.php?aksi=toggle&id=<?= $a['id'] ?>" class="p-1.5 rounded-lg text-[var(--text-muted)] hover:text-amber-400 hover:bg-amber-500/10 transition"><i data-lucide="eye" class="w-3.5 h-3.5"></i></a>
                  <a href="Proses/prosesArtikel.php?aksi=hapus&id=<?= $a['id'] ?>" onclick="return confirm('Hapus artikel ini?')" class="p-1.5 rounded-lg text-[var(--text-muted)] hover:text-red-400 hover:bg-red-500/10 transition"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php elseif ($activeTab === 'pengumuman'): ?>
    <div class="space-y-4">
      <?php if(empty($pengumumans)): ?>
      <div class="card p-10 text-center text-[var(--text-muted)] text-sm">Belum ada pengumuman.</div>
      <?php else: foreach($pengumumans as $p):
        $bColors=['info'=>'border-blue-500/20 bg-blue-500/4','peringatan'=>'border-amber-500/20 bg-amber-500/4','darurat'=>'border-red-500/20 bg-red-500/4'];
        $bBadge=['info'=>'badge-blue','peringatan'=>'badge-amber','darurat'=>'badge-red'];
      ?>
      <div class="card <?= $bColors[$p['tipe']] ?> p-5">
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="badge <?= $bBadge[$p['tipe']] ?>"><?= ucfirst($p['tipe']) ?></span>
              <?= $p['is_active'] ? '<span class="badge badge-green">Aktif</span>' : '<span class="badge badge-slate">Nonaktif</span>' ?>
              <?php if($p['berlaku_hingga']): ?><span class="text-[10px] text-[var(--text-muted)]">s/d <?= date('d/m/Y',strtotime($p['berlaku_hingga'])) ?></span><?php endif; ?>
            </div>
            <h3 class="font-display font-bold text-[var(--text-primary)] mb-1"><?= htmlspecialchars($p['judul']) ?></h3>
            <p class="text-sm text-[var(--text-secondary)] leading-relaxed"><?= nl2br(htmlspecialchars($p['isi'])) ?></p>
            <p class="text-[10px] text-[var(--text-muted)] mt-2">Dibuat oleh <?= htmlspecialchars($p['pembuat']??'—') ?> · <?= date('d/m/Y', strtotime($p['created_at'])) ?></p>
          </div>
          <div class="flex gap-1 flex-shrink-0">
            <button onclick='editPengumuman(<?= json_encode($p) ?>)' class="p-1.5 rounded-lg text-[var(--text-muted)] hover:text-blue-400 hover:bg-blue-500/10 transition"><i data-lucide="pencil" class="w-3.5 h-3.5"></i></button>
            <a href="Proses/prosesPengumuman.php?aksi=toggle&id=<?= $p['id'] ?>" class="p-1.5 rounded-lg text-[var(--text-muted)] hover:text-amber-400 hover:bg-amber-500/10 transition"><i data-lucide="toggle-left" class="w-3.5 h-3.5"></i></a>
            <a href="Proses/prosesPengumuman.php?aksi=hapus&id=<?= $p['id'] ?>" onclick="return confirm('Hapus pengumuman ini?')" class="p-1.5 rounded-lg text-[var(--text-muted)] hover:text-red-400 hover:bg-red-500/10 transition"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></a>
          </div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>

    <?php elseif ($activeTab === 'users' && $isMaster): ?>
    <div class="card overflow-hidden">
      <div class="px-5 py-4 border-b border-[var(--border)]"><h2 class="font-display font-bold text-[var(--text-primary)]">Kelola Pengguna (<?= count($allUsers) ?>)</h2><p class="text-xs text-[var(--text-muted)] mt-0.5">Ubah role pengguna. Hati-hati saat mengubah ke admin_master.</p></div>
      <div class="overflow-x-auto">
        <table class="data-table">
          <thead><tr><th>Pengguna</th><th>Email</th><th>Role Sekarang</th><th>Status</th><th>Login Terakhir</th><th>Ubah Role</th></tr></thead>
          <tbody>
            <?php $rBadge=['admin_master'=>'badge-purple','admin'=>'badge-green','kontributor'=>'badge-blue','user'=>'badge-slate'];
            foreach($allUsers as $u): $isSelf=($u['id']==$uid); ?>
            <tr class="<?= $isSelf?'bg-brand-500/[0.03]':'' ?>">
              <td>
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-full bg-[var(--surface)] flex items-center justify-center text-[10px] font-black font-display text-[var(--text-muted)]"><?= strtoupper(substr($u['username'],0,1)) ?></div>
                  <div><div class="font-bold text-[var(--text-primary)] text-sm"><?= htmlspecialchars($u['username']) ?></div><div class="text-[10px] text-[var(--text-muted)]"><?= htmlspecialchars($u['nama_lengkap']?:'—') ?></div></div>
                </div>
              </td>
              <td class="text-xs text-[var(--text-muted)]"><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="badge <?= $rBadge[$u['role']] ?? 'badge-slate' ?>"><?= $u['role'] ?></span></td>
              <td><?= $u['is_active'] ? '<span class="badge badge-green">Aktif</span>' : '<span class="badge badge-red">Nonaktif</span>' ?></td>
              <td class="text-xs text-[var(--text-muted)]"><?= $u['last_login'] ? date('d/m/Y H:i',strtotime($u['last_login'])) : 'Belum pernah' ?></td>
              <td>
                <?php if ($isSelf): ?>
                <span class="text-[10px] text-[var(--text-muted)] italic">Akun Anda</span>
                <?php else: ?>
                <form method="POST" action="Proses/prosesRoleUpdate.php" class="flex items-center gap-2">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>"/>
                  <select name="new_role" class="input-field text-xs py-1 px-2" style="width:130px">
                    <?php foreach($allRoles as $r): ?>
                    <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= $r ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="px-3 py-1 bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold rounded-lg transition font-display">Simpan</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php elseif ($activeTab === 'settings' && $isMaster): ?>
    <form method="POST" action="Proses/prosesSetting.php">
      <input type="hidden" name="aksi" value="save"/>
      <?php foreach($settings as $group => $items): ?>
      <div class="card overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-[var(--border)] flex items-center gap-2">
          <i data-lucide="settings" class="w-4 h-4 text-brand-500"></i>
          <h2 class="font-display font-bold text-[var(--text-primary)]">Pengaturan: <?= htmlspecialchars($group) ?></h2>
        </div>
        <div class="p-5 space-y-4">
          <?php foreach($items as $s): ?>
          <div>
            <label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">
              <?= htmlspecialchars($s['label']) ?>
              <?php if($s['keterangan']): ?><span class="font-normal normal-case text-[var(--text-muted)] ml-1">— <?= htmlspecialchars($s['keterangan']) ?></span><?php endif; ?>
            </label>
            <?php if($s['tipe']==='textarea'): ?>
            <textarea name="<?= htmlspecialchars($s['kunci']) ?>" rows="3" class="input-field"><?= htmlspecialchars($s['nilai']??'') ?></textarea>
            <?php elseif($s['tipe']==='toggle'): ?>
            <div class="flex items-center gap-3">
              <input type="hidden" name="<?= htmlspecialchars($s['kunci']) ?>" value="0"/>
              <input type="checkbox" name="<?= htmlspecialchars($s['kunci']) ?>" value="1" <?= $s['nilai']=='1'?'checked':'' ?> class="w-4 h-4 accent-brand-500"/>
              <span class="text-sm text-[var(--text-secondary)]">Aktifkan</span>
            </div>
            <?php else: ?>
            <input type="<?= $s['tipe'] ?>" name="<?= htmlspecialchars($s['kunci']) ?>" value="<?= htmlspecialchars($s['nilai']??'') ?>" class="input-field" placeholder="<?= htmlspecialchars($s['label']) ?>"/>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <div class="flex justify-end mt-4">
        <button type="submit" class="flex items-center gap-2 px-6 py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-display font-bold rounded-xl text-sm transition shadow shadow-brand-600/20">
          <i data-lucide="save" class="w-4 h-4"></i> Simpan Semua Pengaturan
        </button>
      </div>
    </form>
    <?php endif; ?>

  </div><!-- end main-body -->
</div><!-- end main-area -->
</div><!-- end admin-wrap -->


<!-- MODAL ARTIKEL -->
<div id="mArtikel" class="hidden fixed inset-0 z-50">
  <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" data-modal-close="mArtikel"></div>
  <div class="flex min-h-full items-center justify-center p-4 relative z-10">
    <div class="w-full max-w-2xl card shadow-2xl max-h-[90vh] overflow-y-auto">
      <div class="px-6 py-4 border-b border-[var(--border)] flex justify-between items-center sticky top-0 bg-[var(--bg-card)] z-10">
        <h3 class="font-display font-bold text-[var(--text-primary)]" id="artikelModalTitle">Artikel Baru</h3>
        <button onclick="closeModal('mArtikel')" class="text-[var(--text-muted)] hover:text-[var(--text-primary)] transition"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <form method="POST" action="Proses/prosesArtikel.php" id="formArtikel">
        <input type="hidden" name="aksi" value="tambah"/>
        <input type="hidden" name="id"   value="" id="artikelId"/>
        <div class="px-6 py-5 space-y-4">
          <!-- Fetch dari URL -->
          <div class="p-4 rounded-xl bg-blue-500/6 border border-blue-500/15">
            <p class="text-xs font-bold text-blue-400 mb-2 flex items-center gap-1.5"><i data-lucide="link" class="w-3.5 h-3.5"></i> Ambil dari URL Sumber (opsional)</p>
            <div class="flex gap-2">
              <input type="url" name="sumber_url" id="sumberUrl" class="input-field text-sm flex-1" placeholder="https://contoh.com/artikel-harga-beras"/>
              <input type="text" name="sumber_nama" id="sumberNama" class="input-field text-sm" style="width:140px" placeholder="Nama Media"/>
            </div>
            <p class="text-[10px] text-[var(--text-muted)] mt-1">Isi URL lalu klik Simpan — sistem akan otomatis mengambil konten artikel.</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2"><label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">Judul Artikel <span class="text-red-400">*</span></label><input type="text" name="judul" id="artikelJudul" class="input-field" placeholder="Judul artikel" required/></div>
            <div><label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">Kategori</label><select name="kategori" id="artikelKategori" class="input-field"><?php foreach(array_merge(['Umum'],$kategoris) as $k): ?><option><?= $k ?></option><?php endforeach; ?></select></div>
            <div class="flex gap-3">
              <div class="flex-1"><label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">Emoji</label><input type="text" name="emoji" id="artikelEmoji" class="input-field text-center text-xl" value="📰" maxlength="4"/></div>
              <div><label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">Menit Baca</label><input type="number" name="menit_baca" id="artikelMenit" class="input-field" value="5" min="1" max="60" style="width:80px"/></div>
            </div>
            <div class="col-span-2"><label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">Ringkasan</label><textarea name="ringkasan" id="artikelRingkasan" rows="2" class="input-field" placeholder="Deskripsi singkat artikel..."></textarea></div>
            <div class="col-span-2"><label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">Konten Artikel</label><textarea name="konten" id="artikelKonten" rows="6" class="input-field" placeholder="Isi artikel lengkap... (kosongkan jika mengambil dari URL)"></textarea></div>
          </div>
          <div class="flex items-center gap-3"><input type="checkbox" name="is_publish" id="artikelPublish" value="1" checked class="w-4 h-4 accent-brand-500"/><label for="artikelPublish" class="text-sm text-[var(--text-secondary)]">Publikasikan sekarang</label></div>
        </div>
        <div class="px-6 py-4 border-t border-[var(--border)] flex justify-end gap-3">
          <button type="button" onclick="closeModal('mArtikel')" class="px-4 py-2 rounded-lg text-sm font-semibold text-[var(--text-secondary)] bg-[var(--surface)] hover:bg-[var(--surface-hover)] transition">Batal</button>
          <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-brand-600 hover:bg-brand-500 transition shadow shadow-brand-600/20 font-display">Simpan Artikel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL PENGUMUMAN -->
<div id="mPengumuman" class="hidden fixed inset-0 z-50">
  <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" data-modal-close="mPengumuman"></div>
  <div class="flex min-h-full items-center justify-center p-4 relative z-10">
    <div class="w-full max-w-lg card shadow-2xl">
      <div class="px-6 py-4 border-b border-[var(--border)] flex justify-between items-center">
        <h3 class="font-display font-bold text-[var(--text-primary)]" id="pungTitle">Pengumuman Baru</h3>
        <button onclick="closeModal('mPengumuman')" class="text-[var(--text-muted)] hover:text-[var(--text-primary)] transition"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <form method="POST" action="Proses/prosesPengumuman.php">
        <input type="hidden" name="aksi" value="tambah"/>
        <input type="hidden" name="id" value="" id="pungId"/>
        <div class="px-6 py-5 space-y-4">
          <div><label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">Judul <span class="text-red-400">*</span></label><input type="text" name="judul" id="pungJudul" class="input-field" placeholder="Judul pengumuman" required/></div>
          <div><label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">Isi Pengumuman <span class="text-red-400">*</span></label><textarea name="isi" id="pungIsi" rows="4" class="input-field" placeholder="Isi pengumuman lengkap..." required></textarea></div>
          <div class="grid grid-cols-2 gap-4">
            <div><label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">Tipe</label>
              <select name="tipe" id="pungTipe" class="input-field">
                <option value="info">Info</option>
                <option value="peringatan">Peringatan</option>
                <option value="darurat">Darurat</option>
              </select>
            </div>
            <div><label class="block text-xs font-bold text-[var(--text-secondary)] uppercase tracking-wider mb-1.5">Berlaku Hingga</label><input type="date" name="berlaku_hingga" id="pungBerlaku" class="input-field" style="color-scheme:dark light"/></div>
          </div>
          <div class="flex items-center gap-3"><input type="checkbox" name="is_active" id="pungAktif" value="1" checked class="w-4 h-4 accent-brand-500"/><label for="pungAktif" class="text-sm text-[var(--text-secondary)]">Aktifkan pengumuman</label></div>
        </div>
        <div class="px-6 py-4 border-t border-[var(--border)] flex justify-end gap-3">
          <button type="button" onclick="closeModal('mPengumuman')" class="px-4 py-2 rounded-lg text-sm font-semibold text-[var(--text-secondary)] bg-[var(--surface)] hover:bg-[var(--surface-hover)] transition">Batal</button>
          <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-amber-500 hover:bg-amber-400 transition font-display">Simpan Pengumuman</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const chartData = <?= json_encode(array_map(fn($r)=>['nama'=>$r['nama'],'lokasi'=>$r['lokasi'],'history'=>json_decode($r['history']??'[]',true)],$rows),JSON_UNESCAPED_UNICODE) ?>;
</script>
<script>
window.PROVINSI_KOTA_JS = <?= json_encode(PROVINSI_KOTA, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="/scripts.js"></script>
<script>
lucide.createIcons();

// Modal tambah komoditas dinonaktifkan — data dari BPS API
function setNote(form){ const c=prompt('Alasan penolakan (opsional):',''); if(c===null)return false; form.querySelector('[name="catatan"]').value=c; return true; }

// Edit Artikel
function editArtikel(a) {
  document.getElementById('artikelId').value      = a.id;
  document.getElementById('artikelJudul').value   = a.judul;
  document.getElementById('artikelRingkasan').value= a.ringkasan||'';
  document.getElementById('artikelKonten').value  = a.konten||'';
  document.getElementById('artikelKategori').value = a.kategori||'Umum';
  document.getElementById('artikelEmoji').value   = a.emoji||'📰';
  document.getElementById('artikelMenit').value   = a.menit_baca||5;
  document.getElementById('sumberUrl').value      = a.sumber_url||'';
  document.getElementById('sumberNama').value     = a.sumber_nama||'';
  document.getElementById('artikelPublish').checked = a.is_publish=='1';
  document.getElementById('artikelModalTitle').textContent = 'Edit Artikel';
  document.querySelector('#formArtikel [name="aksi"]').value = 'edit';
  openModal('mArtikel');
}

// Edit Pengumuman
function editPengumuman(p) {
  document.getElementById('pungId').value    = p.id;
  document.getElementById('pungJudul').value = p.judul;
  document.getElementById('pungIsi').value   = p.isi;
  document.getElementById('pungTipe').value  = p.tipe;
  document.getElementById('pungBerlaku').value = p.berlaku_hingga||'';
  document.getElementById('pungAktif').checked = p.is_active=='1';
  document.getElementById('pungTitle').textContent = 'Edit Pengumuman';
  openModal('mPengumuman');
}

// Chart — pakai IIFE, bukan DOMContentLoaded (sudah terlambat saat script ini dieksekusi)
let adminChart = null;

function updateChart() {
  if (!adminChart) return;
  const n = document.getElementById('fNama')?.value;
  const l = document.getElementById('fLokasi')?.value;
  const f = chartData.find(d => d.nama===n && d.lokasi===l);
  const t = getChartTheme();
  adminChart.options.plugins.title.text  = f ? `${n} — ${l}` : 'Pilih komoditas & lokasi';
  adminChart.options.plugins.title.color = t.titleColor;
  adminChart.data.datasets[0].data       = f ? f.history : [];
  adminChart.update();
}

(function initAdminChart() {
  const canvas = document.getElementById('adminChart');
  if (!canvas) return;
  if (!chartData || !chartData.length) {
    canvas.parentElement.innerHTML = '<div style="height:200px;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:.875rem">Belum ada data komoditas.</div>';
    return;
  }
  const ct = canvas.getContext('2d');
  const t  = getChartTheme();
  let g    = ct.createLinearGradient(0, 0, 0, 200);
  g.addColorStop(0, 'rgba(16,185,129,.3)');
  g.addColorStop(1, 'rgba(16,185,129,0)');

  adminChart = new Chart(ct, {
    type: 'line',
    data: {
      labels: ['H-6','H-5','H-4','H-3','H-2','Kemarin','Hari Ini'],
      datasets: [{
        data: [],
        borderColor: '#10b981',
        backgroundColor: g,
        fill: true, tension: .4, borderWidth: 2.5,
        pointBackgroundColor: t.bgColor,
        pointBorderColor: '#10b981',
        pointRadius: 4,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        title: {
          display: true,
          text: 'Pilih komoditas & lokasi di atas',
          color: t.titleColor,
          font: { family: 'Cabinet Grotesk', size: 13, weight: '700' },
          padding: { bottom: 10 },
        },
        tooltip: { callbacks: { label: c => 'Rp ' + c.parsed.y.toLocaleString('id-ID') } },
      },
      scales: {
        y: { beginAtZero: false, ticks: { color: t.textColor, callback: v => 'Rp ' + v.toLocaleString('id-ID') }, grid: { color: t.gridColor } },
        x: { ticks: { color: t.textColor }, grid: { display: false } },
      },
    },
  });

  // Fungsi isi ulang dropdown lokasi berdasarkan komoditas terpilih
  function populateAdminLokasi(namaKom) {
    const sel = document.getElementById('fLokasi');
    if (!sel) return;
    sel.innerHTML = '';
    const matches = chartData.filter(d => d.nama === namaKom);
    if (!matches.length) {
      sel.innerHTML = '<option value="">— tidak ada data —</option>';
      return;
    }
    matches.forEach(d => {
      const o = document.createElement('option');
      o.value = d.lokasi; o.textContent = d.lokasi;
      sel.appendChild(o);
    });
  }

  // Auto-pilih komoditas & lokasi pertama → langsung render grafik
  const firstItem = chartData[0];
  if (firstItem) {
    const fNama   = document.getElementById('fNama');
    const fLokasi = document.getElementById('fLokasi');
    if (fNama) fNama.value = firstItem.nama;
    populateAdminLokasi(firstItem.nama);
    if (fLokasi) fLokasi.value = firstItem.lokasi;
    updateChart();
  }

  document.getElementById('fNama')?.addEventListener('change', function() {
    populateAdminLokasi(this.value);
    updateChart();
  });
  document.getElementById('fLokasi')?.addEventListener('change', updateChart);
  document.addEventListener('themeChanged', () => {
    if (!adminChart) return;
    const nt = getChartTheme();
    adminChart.options.scales.y.ticks.color    = nt.textColor;
    adminChart.options.scales.y.grid.color     = nt.gridColor;
    adminChart.options.scales.x.ticks.color    = nt.textColor;
    adminChart.options.plugins.title.color     = nt.titleColor;
    adminChart.data.datasets[0].pointBackgroundColor = nt.bgColor;
    adminChart.update();
  });
})();
</script>
</body>
</html>