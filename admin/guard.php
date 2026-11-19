<?php
/**
 * admin/guard.php — Pengaman Terpusat Semua Halaman Admin
 * ─────────────────────────────────────────────────────────────
 * Cara pakai (taruh di baris PALING ATAS setiap file admin/):
 *
 *   <?php require_once __DIR__ . '/guard.php'; ?>
 *
 * File ini melakukan 3 hal sekaligus:
 *   1. Header HTTP anti-cache mutlak (wajib SEBELUM session_start)
 *   2. Buka sesi PHP (hanya jika belum aktif)
 *   3. Validasi login — tolak & redirect jika tidak terautentikasi
 */

// ═══════════════════════════════════════════════════════════════
// 1. HEADER ANTI-CACHE MUTLAK
//    Wajib dikirim SEBELUM session_start() agar tidak ada output
//    HTML yang sudah terkirim lebih dahulu.
//
//    Tujuan:
//    • Mencegah browser/proxy menyimpan halaman admin di cache
//    • Tombol Back browser setelah logout → tampilkan "halaman
//      kadaluarsa" (bukan isi halaman admin dari cache)
//    • Mencegah shared-computer attack (data admin tidak terbaca
//      oleh pengguna komputer berikutnya)
// ═══════════════════════════════════════════════════════════════
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);   // IE legacy
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');            // tanggal di masa lalu

// ═══════════════════════════════════════════════════════════════
// 2. BUKA SESI PHP
//    Panggil session_start() hanya jika belum aktif.
//    Ini aman untuk dipanggil berkali-kali (idempotent).
// ═══════════════════════════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ═══════════════════════════════════════════════════════════════
// 2b. RECOVERY SESI DARI COOKIE STATELESS HMAC (VERCEL SERVERLESS)
// ═══════════════════════════════════════════════════════════════
require_once __DIR__ . '/../includes/auth_stateless.php';
ensureAdminSessionFromCookie();

// ═══════════════════════════════════════════════════════════════
// 3. VALIDASI AUTENTIKASI ADMIN
//
//    Cek TIGA kondisi secara bersamaan (AND):
//    (a) Kunci 'admin_logged_in' harus ada di $_SESSION
//    (b) Nilainya harus persis === true (strict comparison)
//    (c) [Opsional] Kunci 'admin_id' harus ada (anti-session-ghost)
//
//    Jika salah satu gagal → simpan URL tujuan, redirect ke login.
// ═══════════════════════════════════════════════════════════════
$_isLoggedIn = (
    (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)
    || !empty($_SESSION['admin'])
);

if (!$_isLoggedIn) {
    // Simpan URL yang dituju agar setelah login bisa diarahkan balik
    // Ambil path relatif dari root proyek (hanya bagian admin/xxx.php)
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $adminPath  = ltrim(parse_url($requestUri, PHP_URL_PATH), '/');
    if (str_starts_with($adminPath, 'spk-raket/')) {
        $adminPath = substr($adminPath, strlen('spk-raket/'));
    }
    if (str_starts_with($adminPath, 'admin/')) {
        $_SESSION['redirect_after_login'] = $adminPath;
    }

    // Kirim header anti-cache lagi pada respons redirect ini
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    // Redirect ke login dengan parameter reason
    header('Location: ../login.php?reason=unauthorized');
    exit();
}

// ═══════════════════════════════════════════════════════════════
// 4. [OPSIONAL] SESSION TIMEOUT — 2 jam tidak aktif = logout paksa
//    Aktifkan dengan uncomment blok di bawah jika diperlukan.
// ═══════════════════════════════════════════════════════════════
/*
define('SESSION_TIMEOUT_SECONDS', 7200); // 2 jam
if (isset($_SESSION['admin_last_activity'])) {
    $idle = time() - $_SESSION['admin_last_activity'];
    if ($idle > SESSION_TIMEOUT_SECONDS) {
        session_unset();
        session_destroy();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Location: ../login.php?reason=timeout');
        exit();
    }
}
$_SESSION['admin_last_activity'] = time(); // perbarui setiap request
*/

// ═══════════════════════════════════════════════════════════════
// 5. EXPOSE VARIABEL SIAP PAKAI KE FILE YANG INCLUDE GUARD INI
// ═══════════════════════════════════════════════════════════════
$adminUsername = htmlspecialchars($_SESSION['admin_username'] ?? $_SESSION['admin'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$adminId       = (int) ($_SESSION['admin_id'] ?? 1);

// guard.php selesai — eksekusi dilanjutkan ke file yang meng-include ini
