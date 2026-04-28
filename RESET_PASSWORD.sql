-- =============================================
-- SCRIPT RESET PASSWORD - Sistem Panen
-- Jalankan di TiDB Cloud dashboard / SQL editor
-- =============================================

-- 1. Lihat dulu semua user yang ada
SELECT id, username, email, role, 
       LEFT(password, 10) AS password_preview,
       LENGTH(password) AS password_length
FROM tbl_user;

-- 2. Reset password user 'admin' menjadi 'admin123' (hash bcrypt)
--    Hash di bawah = password_hash('admin123', PASSWORD_DEFAULT)
UPDATE tbl_user 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'admin';

-- Catatan: Hash di atas adalah untuk password: admin123
-- Setelah login, segera ganti password dari dalam aplikasi!

-- 3. Kalau mau reset semua user ke password = 'password123'
--    Hash di bawah = password_hash('password123', PASSWORD_DEFAULT)
-- UPDATE tbl_user 
-- SET password = '$2y$10$TKh8H1.PfaNpFnTCNPPE.eWbxo.9GqG.bAjLHNrJcMqSq3DYN7Jjm'
-- WHERE role = 'user';
