-- ============================================================
-- InfoHarga Komoditi - Database Setup
-- Jalankan file ini di database cloud Anda (PlanetScale/Railway/dll)
-- ============================================================

CREATE DATABASE IF NOT EXISTS infoharga_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE infoharga_db;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin_master','admin','kontributor','user') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    foto VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    provinsi VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel komoditas
CREATE TABLE IF NOT EXISTS komoditas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    lokasi VARCHAR(100) NOT NULL,
    provinsi VARCHAR(100) NOT NULL,
    harga_kemarin INT NOT NULL DEFAULT 0,
    harga_sekarang INT NOT NULL DEFAULT 0,
    satuan VARCHAR(30) DEFAULT 'kg',
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    user_id INT DEFAULT NULL,
    catatan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel artikel
CREATE TABLE IF NOT EXISTS artikel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    slug VARCHAR(255) DEFAULT '',
    ringkasan TEXT,
    isi LONGTEXT,
    kategori VARCHAR(100) DEFAULT 'Umum',
    emoji VARCHAR(10) DEFAULT '📰',
    menit_baca INT DEFAULT 3,
    sumber_url VARCHAR(500) DEFAULT NULL,
    sumber_nama VARCHAR(100) DEFAULT NULL,
    is_publish TINYINT(1) DEFAULT 0,
    is_bps TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel diskusi
CREATE TABLE IF NOT EXISTS diskusi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    isi TEXT NOT NULL,
    user_id INT NOT NULL,
    views INT DEFAULT 0,
    is_pinned TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel diskusi_reaksi
CREATE TABLE IF NOT EXISTS diskusi_reaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    diskusi_id INT NOT NULL,
    user_id INT NOT NULL,
    reaksi ENUM('like','helpful','insightful') DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaksi (diskusi_id, user_id)
) ENGINE=InnoDB;

-- Tabel pengumuman
CREATE TABLE IF NOT EXISTS pengumuman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    isi TEXT NOT NULL,
    tipe ENUM('info','warning','success','danger') DEFAULT 'info',
    is_active TINYINT(1) DEFAULT 1,
    berlaku_hingga DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel pengaturan_sistem
CREATE TABLE IF NOT EXISTS pengaturan_sistem (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kunci VARCHAR(100) NOT NULL UNIQUE,
    nilai TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel activity_log
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    aksi VARCHAR(255) NOT NULL,
    detail TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel pusat_informasi
CREATE TABLE IF NOT EXISTS pusat_informasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    isi LONGTEXT,
    kategori VARCHAR(100) DEFAULT 'Panduan',
    urutan INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Data awal: Admin Master ──────────────────────────────────
-- Password default: admin123 (GANTI SEGERA setelah login pertama!)
INSERT IGNORE INTO users (nama, username, email, password, role, is_active)
VALUES ('Admin Master', 'admin', 'admin@infoharga.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin_master', 1);

-- ── Data awal: Pengaturan sistem ─────────────────────────────
INSERT IGNORE INTO pengaturan_sistem (kunci, nilai) VALUES
('nama_situs', 'InfoHarga Komoditi'),
('deskripsi_situs', 'Sistem Informasi Harga Komoditas Indonesia'),
('email_kontak', 'admin@infoharga.id');

