<?php
if (ob_get_level() === 0) {
    ob_start();
}
/**
 * includes/auth_check.php
 * Session Guard — wajib di-include di semua halaman /admin/
 *
 * Alur:
 * 1. Pastikan session aktif
 * 2. Cek $_SESSION['admin_logged_in']
 * 3. Jika belum login → simpan URL tujuan di session → redirect ke login.php
 * 4. Jika sudah login → lanjut (tidak ada aksi)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Stateless HMAC Cookie Session Recovery (Vercel Compatibility)
require_once __DIR__ . '/auth_stateless.php';
ensureAdminSessionFromCookie();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Simpan halaman yang ingin dikunjungi supaya setelah login
    // bisa langsung diarahkan ke sana (redirect-after-login pattern)
    $requestedPage = basename($_SERVER['SCRIPT_NAME']);
    $_SESSION['redirect_after_login'] = 'admin/' . $requestedPage;

    // Redirect ke login dengan keterangan alasan (Aman headers_sent)
    if (!headers_sent()) {
        header('Location: ../login.php?reason=unauthorized');
        exit;
    }
    echo '<script>window.location.href="../login.php?reason=unauthorized";</script>';
    exit;
}
