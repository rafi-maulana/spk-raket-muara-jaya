<?php
if (ob_get_level() === 0) {
    ob_start();
}
date_default_timezone_set('Asia/Jakarta');
/**
 * api/index.php — Front Controller Router untuk Vercel Serverless
 * SPK Pemilihan Raket Bulu Tangkis Metode SAW — Toko Muara Jaya Sport
 */

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath    = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$uriPath    = '/' . ltrim($uriPath, '/');

// Petakan root dan admin shortcut
if ($uriPath === '/' || $uriPath === '') {
    $uriPath = '/index.php';
} elseif ($uriPath === '/admin' || $uriPath === '/admin/') {
    $uriPath = '/admin/dashboard.php';
}

// Cegah path traversal
if (strpos($uriPath, '..') !== false || strpos($uriPath, '\\') !== false) {
    http_response_code(400);
    echo "400 Bad Request";
    exit;
}

// Blokir akses langsung ke folder atau file sensitif
$blockedPrefixes = ['/api/', '/config/', '/includes/', '/.git', '/.env'];
foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($uriPath, $prefix)) {
        http_response_code(403);
        echo "403 Forbidden: Akses ke direktori internal dibatasi.";
        exit;
    }
}

// Cegah akses langsung ke file .sql, .env, .git, atau file tersembunyi
if (preg_match('/\.(sql|env|git|log)$/i', $uriPath) || str_starts_with(basename($uriPath), '.')) {
    http_response_code(403);
    echo "403 Forbidden";
    exit;
}

$rootDir     = dirname(__DIR__);
$realRootDir = realpath($rootDir) ?: $rootDir;
$candidate   = $rootDir . $uriPath;

// Dukungan ekstensi .php opsional (misal: /metode -> /metode.php)
if (!file_exists($candidate) && file_exists($candidate . '.php')) {
    $candidate .= '.php';
    $uriPath   .= '.php';
}

$targetFile = realpath($candidate);

// Validasi keberadaan file dan pastikan berada dalam root directory
if ($targetFile === false || !is_file($targetFile) || !str_starts_with($targetFile, $realRootDir)) {
    http_response_code(404);
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>404 Halaman Tidak Ditemukan — Muara Jaya Sport</title><style>body{font-family:system-ui,-apple-system,sans-serif;background:#0b0f17;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1.5rem}.box{background:#131c2e;border:1px solid rgba(239,68,68,0.4);border-left:5px solid #ef4444;padding:2rem;border-radius:12px;max-width:520px;text-align:center}h1{color:#ef4444;font-size:1.75rem;margin:0 0 1rem}p{color:#94a3b8;margin-bottom:1.5rem}a{display:inline-block;padding:.65rem 1.5rem;background:#0d9488;color:#fff;text-decoration:none;border-radius:8px;font-weight:600}</style></head><body><div class='box'><h1>404 — Tidak Ditemukan</h1><p>Halaman atau file yang Anda tuju tidak tersedia pada server.</p><a href='/'>Kembali ke Beranda</a></div></body></html>";
    exit;
}

// Sajikan file statis jika bukan skrip PHP
if (!str_ends_with($targetFile, '.php')) {
    $mime = function_exists('mime_content_type') ? mime_content_type($targetFile) : 'application/octet-stream';
    header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($targetFile));
    readfile($targetFile);
    exit;
}

// Perbarui variabel server lingkungan agar script target mendeteksi lokasinya dengan presisi
$_SERVER['SCRIPT_FILENAME'] = $targetFile;
$_SERVER['SCRIPT_NAME']     = $uriPath;
$_SERVER['PHP_SELF']        = $uriPath;

// Ubah working directory ke direktori file target agar seluruh require_once relatif berjalan mulus
chdir(dirname($targetFile));

// Pastikan browser merender halaman sebagai HTML, bukan mendownloadnya
header('Content-Type: text/html; charset=UTF-8');

// Eksekusi skrip target
require $targetFile;
exit;
