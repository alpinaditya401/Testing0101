<?php
/**
 * koneksi.php — Koneksi ke TiDB Cloud dengan SSL
 */

define('DB_HOST', getenv('DB_HOST'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME'));
define('DB_PORT', (int) (getenv('DB_PORT') ?: 4000));

mysqli_report(MYSQLI_REPORT_OFF);

// TiDB Cloud WAJIB pakai SSL
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect(
    $conn,
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME,
    DB_PORT,
    NULL,
    MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT
);

if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

define('APP_NAME', 'InfoHarga Komoditi');
define('APP_VERSION', '4.0.0');

if (!defined('PROVINSI_LIST')) {
    define('PROVINSI_LIST', [
        'Aceh',
        'Sumatera Utara',
        'Sumatera Barat',
        'Riau',
        'Kepulauan Riau',
        'Jambi',
        'Bengkulu',
        'Sumatera Selatan',
        'Kepulauan Bangka Belitung',
        'Lampung',
        'Banten',
        'DKI Jakarta',
        'Jawa Barat',
        'Jawa Tengah',
        'DI Yogyakarta',
        'Jawa Timur',
        'Bali',
        'Nusa Tenggara Barat',
        'Nusa Tenggara Timur',
        'Kalimantan Barat',
        'Kalimantan Tengah',
        'Kalimantan Selatan',
        'Kalimantan Timur',
        'Kalimantan Utara',
        'Sulawesi Utara',
        'Gorontalo',
        'Sulawesi Tengah',
        'Sulawesi Barat',
        'Sulawesi Selatan',
        'Sulawesi Tenggara',
        'Maluku',
        'Maluku Utara',
        'Papua',
        'Papua Barat',
        'Papua Selatan',
        'Papua Tengah',
        'Papua Pegunungan',
        'Papua Barat Daya',
    ]);
}

function esc(mysqli $conn, string $val): string
{
    return $conn->real_escape_string(trim($val));
}
function rupiah(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function redirect(string $url): never
{
    header("Location: $url");
    exit();
}
function cekLogin(): void
{
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true)
        redirect('login.php');
}
function cekRole(array $roles): void
{
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        $role = $_SESSION['role'] ?? '';
        if ($role === 'admin_master' || $role === 'admin')
            redirect('dashboard.php');
        elseif ($role === 'kontributor')
            redirect('dashboard-user.php?tab=laporan');
        else
            redirect('dashboard-user.php');
    }
}
function getSetting(mysqli $conn, string $kunci, string $default = ''): string
{
    $k = esc($conn, $kunci);
    $res = $conn->query("SELECT nilai FROM pengaturan_sistem WHERE kunci='$k' LIMIT 1");
    if ($res && $res->num_rows > 0)
        return $res->fetch_assoc()['nilai'] ?? $default;
    return $default;
}
function getPengumuman(mysqli $conn): array
{
    $res = $conn->query("SELECT * FROM pengumuman WHERE is_active=1 AND (berlaku_hingga IS NULL OR berlaku_hingga >= CURDATE()) ORDER BY tipe DESC, created_at DESC LIMIT 5");
    $list = [];
    if ($res)
        while ($r = $res->fetch_assoc())
            $list[] = $r;
    return $list;
}
function slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}
require_once __DIR__ . '/session_db.php';
startDbSession($conn);
require_once __DIR__ . '/bps_api.php';