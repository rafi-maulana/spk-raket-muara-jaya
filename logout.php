<?php
// ============================================================
//  logout.php — Secure Logout | Muara Jaya Sport SPK
//  Security: Anti-cache headers, full session destruction,
//            session cookie deletion
// ============================================================

// -----------------------------------------------------------
// 0. HEADER ANTI-CACHE MUTLAK
//    Harus dipasang SEBELUM session_start() agar browser
//    tidak menyimpan halaman admin di cache setelah logout.
//    Tombol Back browser akan memunculkan "halaman kadaluarsa"
//    bukan menampilkan kembali isi halaman admin.
// -----------------------------------------------------------
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// -----------------------------------------------------------
// 1. Mulai sesi (wajib sebelum bisa memanipulasinya)
// -----------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------
// 2. Hapus SEMUA variabel sesi dari array $_SESSION
//    session_unset() = setara $_SESSION = []
//    (lebih eksplisit dan standar OWASP)
// -----------------------------------------------------------
$_SESSION = [];
session_unset();

// -----------------------------------------------------------
// 3. Hapus cookie sesi dari browser pengguna
//    Ini memastikan browser menghapus cookie PHPSESSID-nya
//    sehingga ID sesi lama tidak bisa dipakai lagi sama sekali.
// -----------------------------------------------------------
if (ini_get('session.use_cookies')) {
    $cookieParams = session_get_cookie_params();
    setcookie(
        session_name(),          // nama cookie (default: PHPSESSID)
        '',                      // nilai dikosongkan
        time() - 42000,          // expire di masa lalu → browser hapus cookie
        $cookieParams['path'],
        $cookieParams['domain'],
        $cookieParams['secure'],
        $cookieParams['httponly']
    );
}

// -----------------------------------------------------------
// 4. Hancurkan data sesi di server (file / database sesi) & Cookie Stateless
// -----------------------------------------------------------
session_destroy();

require_once __DIR__ . '/includes/auth_stateless.php';
clearAdminAuthCookie();

// -----------------------------------------------------------
// 5. Redirect ke halaman login dengan pesan logout_success
//    Header no-cache dipasang lagi agar respons redirect ini
//    pun tidak di-cache browser.
// -----------------------------------------------------------
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: login.php?msg=logout_success');
exit;
