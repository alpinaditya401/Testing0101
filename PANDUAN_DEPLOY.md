# 🚀 Panduan Deploy InfoHarga ke Vercel

## Masalah yang sudah diperbaiki:
1. ✅ `index.html` → dipindah ke `api/index.php` (PHP tidak jalan di file .html)
2. ✅ `Assets/` → disalin ke `api/Assets/` (path include sekarang benar)
3. ✅ `vercel.json` → routing diperbaiki agar semua PHP file terjangkau
4. ✅ `koneksi.php` → sekarang support environment variables untuk database cloud

---

## LANGKAH 1 — Setup Database Cloud (Railway - GRATIS)

1. Buka https://railway.app dan daftar/login
2. Klik **"New Project"** → **"Provision MySQL"**
3. Setelah MySQL siap, klik pada database tersebut
4. Buka tab **"Connect"** → salin nilai:
   - `MYSQLHOST` → ini adalah **DB_HOST**
   - `MYSQLUSER` → ini adalah **DB_USER**
   - `MYSQLPASSWORD` → ini adalah **DB_PASS**
   - `MYSQLDATABASE` → ini adalah **DB_NAME**
   - `MYSQLPORT` → ini adalah **DB_PORT**
5. Buka tab **"Query"** → paste seluruh isi file `database_setup.sql` → klik **Run**

---

## LANGKAH 2 — Upload ke GitHub

1. Buka https://github.com → buat repository baru (misal: `infoharga`)
2. Upload seluruh isi folder ini (semua file & folder)
   - Pastikan struktur folder:
     ```
     ├── api/
     │   ├── Assets/
     │   ├── Server/
     │   ├── Proses/
     │   ├── index.php  ← file baru
     │   ├── login.php
     │   └── ... semua .php lainnya
     ├── vercel.json    ← sudah diperbaiki
     └── database_setup.sql
     ```
3. Commit & push

---

## LANGKAH 3 — Deploy ke Vercel

1. Buka https://vercel.com → login dengan GitHub
2. Klik **"Add New Project"** → pilih repository `infoharga`
3. Di bagian **"Environment Variables"**, tambahkan:

   | Name | Value |
   |------|-------|
   | `DB_HOST` | (dari Railway: MYSQLHOST) |
   | `DB_USER` | (dari Railway: MYSQLUSER) |
   | `DB_PASS` | (dari Railway: MYSQLPASSWORD) |
   | `DB_NAME` | (dari Railway: MYSQLDATABASE) |
   | `DB_PORT` | (dari Railway: MYSQLPORT) |

4. Klik **"Deploy"**

---

## LANGKAH 4 — Login Pertama

- URL: `https://nama-project-anda.vercel.app/login.php`
- Username: `admin`
- Password: `admin123`
- ⚠️ **SEGERA ganti password setelah login!**

---

## Troubleshooting

**Error 403 Forbidden** → Pastikan `vercel.json` sudah diupdate (versi baru)

**Source code PHP tampil** → Pastikan `index.php` ada di dalam folder `api/`, bukan di root

**Koneksi database gagal** → Cek environment variables di Vercel Dashboard → Settings → Environment Variables

