<?php
/**
 * ============================================================
 *  config/koneksi.php
 *  Skrip Koneksi Database PDO & MySQLi — PHP Native
 *  SPK Pemilihan Raket Bulu Tangkis | Muara Jaya Sport
 *  Ready for Localhost (XAMPP/Laragon), Hostinger & TiDB Cloud Serverless
 * ============================================================
 */

// ── Konfigurasi Database (Mendukung Environment Variables TiDB / Vercel / Local) ──
$readEnv = function(string $key, ?string $default = null): ?string {
    if (isset($_ENV[$key]) && (string)$_ENV[$key] !== '') {
        return (string)$_ENV[$key];
    }
    if (isset($_SERVER[$key]) && (string)$_SERVER[$key] !== '') {
        return (string)$_SERVER[$key];
    }
    $val = getenv($key);
    if ($val !== false && (string)$val !== '') {
        return (string)$val;
    }
    return $default;
};

$readDbPass = function() use ($readEnv): string {
    if (array_key_exists('DB_PASS', $_ENV)) {
        return (string)$_ENV['DB_PASS'];
    }
    if (array_key_exists('DB_PASS', $_SERVER)) {
        return (string)$_SERVER['DB_PASS'];
    }
    $val = getenv('DB_PASS');
    if ($val !== false) {
        return (string)$val;
    }
    return defined('DB_PASS') ? (string)DB_PASS : '';
};

$dbHost    = $readEnv('DB_HOST', defined('DB_HOST') ? DB_HOST : 'localhost');
$dbPort    = $readEnv('DB_PORT', defined('DB_PORT') ? (string)DB_PORT : '3306');
$dbName    = $readEnv('DB_NAME', defined('DB_NAME') ? DB_NAME : 'spk_raket_db');
$dbUser    = $readEnv('DB_USER', defined('DB_USER') ? DB_USER : 'root');
$dbPass    = $readDbPass();
$dbCharset = $readEnv('DB_CHARSET', defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');

if (!defined('DB_HOST'))    define('DB_HOST',    $dbHost);
if (!defined('DB_PORT'))    define('DB_PORT',    (int)$dbPort);
if (!defined('DB_NAME'))    define('DB_NAME',    $dbName);
if (!defined('DB_USER'))    define('DB_USER',    $dbUser);
if (!defined('DB_PASS'))    define('DB_PASS',    $dbPass);
if (!defined('DB_CHARSET')) define('DB_CHARSET', $dbCharset);

if (!defined('APP_NAME'))   define('APP_NAME',   'SPK Rekomendasi Raket Bulu Tangkis');
if (!defined('STORE_NAME')) define('STORE_NAME', 'Muara Jaya Sport');
if (!defined('APP_VERSION'))define('APP_VERSION','2.5.0');

// ── Inisialisasi Koneksi PDO ──────────────────────────────────
try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ];

    // Deteksi kebutuhan SSL/TLS: TiDB Cloud (port 4000 / tidbcloud.com) atau remote host
    $isTiDB  = ((string)DB_PORT === '4000' || str_contains(strtolower(DB_HOST), 'tidbcloud.com'));
    $isLocal = in_array(strtolower(DB_HOST), ['localhost', '127.0.0.1', '::1'], true);

    if ($isTiDB || !$isLocal) {
        // Cari file CA certificate sistem Linux yang tersedia di lingkungan serverless/container
        $caCandidates = [
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/ssl/cert.pem'
        ];
        if (function_exists('openssl_get_cert_locations')) {
            $locs = openssl_get_cert_locations();
            if (!empty($locs['default_cert_file'])) {
                $caCandidates[] = $locs['default_cert_file'];
            }
        }

        $caPath = null;
        foreach ($caCandidates as $candidate) {
            if (file_exists($candidate) && is_readable($candidate)) {
                $caPath = $candidate;
                break;
            }
        }

        if ($caPath !== null && defined('PDO::MYSQL_ATTR_SSL_CA')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
        }

        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
    }

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $pe) {
        // Fallback cerdas lokal: jika database spk_raket_db belum ada namun spk_raket tersedia pada localhost
        if ($isLocal && empty($readEnv('DB_NAME')) && (int)$pe->getCode() === 1049 && DB_NAME === 'spk_raket_db') {
            $fallbackDsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, 'spk_raket', DB_CHARSET);
            $pdo = new PDO($fallbackDsn, DB_USER, DB_PASS, $options);
        } else {
            throw $pe;
        }
    }
    $conn = $pdo; // Alias kompatibilitas

} catch (PDOException $e) {
    error_log("Database connection failure: " . $e->getMessage());
    $errMsg = $e->getMessage();
    die('
    <!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
    <title>Koneksi Gagal — Muara Jaya Sport</title>
    <style>
        body{font-family:system-ui,-apple-system,sans-serif;background:#0b0f17;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1.5rem}
        .box{background:#131c2e;border:1px solid rgba(239,68,68,0.4);border-left:5px solid #ef4444;padding:2rem;border-radius:12px;max-width:540px;box-shadow:0 10px 30px rgba(0,0,0,0.5)}
        h2{color:#ef4444;margin:0 0 1rem;font-size:1.35rem;display:flex;align-items:center;gap:.5rem}
        code{background:#0b0f17;padding:3px 8px;border-radius:6px;font-size:.85rem;color:#f87171;word-break:break-all}
        p{line-height:1.6;font-size:.95rem;color:#94a3b8}ol{margin:.75rem 0 0 1.25rem;line-height:1.8;color:#cbd5e1;font-size:.9rem}
    </style></head><body>
    <div class="box">
        <h2>Koneksi Database Gagal</h2>
        <p>Gagal terhubung ke database server. Pastikan konfigurasi Environment Variables (TiDB Cloud / Vercel) atau XAMPP lokal sudah sesuai.</p>
        <p><strong>Error log:</strong> <code>' . htmlspecialchars($errMsg, ENT_QUOTES, 'UTF-8') . '</code></p>
        <ol>
            <li>Pastikan service <strong>MySQL / TiDB Cloud</strong> sedang aktif dan port (3306 / 4000) terbuka.</li>
            <li>Import file <code>schema.sql</code> ke database MySQL / phpMyAdmin.</li>
            <li>Periksa kredensial <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, dan <code>DB_NAME</code>.</li>
        </ol>
    </div></body></html>
    ');
}

// ── Inisialisasi Fungsi Helper MySQLi (Kompatibilitas Tambahan) ──
if (!function_exists('getMySQLi')) {
    function getMySQLi(): mysqli|false {
        $conn = mysqli_init();
        if (!$conn) return false;
        $isTiDB  = ((string)DB_PORT === '4000' || str_contains(strtolower(DB_HOST), 'tidbcloud.com'));
        $isLocal = in_array(strtolower(DB_HOST), ['localhost', '127.0.0.1', '::1'], true);
        if ($isTiDB || !$isLocal) {
            $caCandidates = [
                '/etc/pki/tls/certs/ca-bundle.crt',
                '/etc/ssl/certs/ca-certificates.crt',
                '/etc/ssl/cert.pem'
            ];
            $caPath = null;
            foreach ($caCandidates as $candidate) {
                if (file_exists($candidate) && is_readable($candidate)) {
                    $caPath = $candidate;
                    break;
                }
            }
            mysqli_ssl_set($conn, NULL, NULL, $caPath, NULL, NULL);
            $flags = MYSQLI_CLIENT_SSL;
        } else {
            $flags = 0;
        }
        @mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, NULL, $flags);
        return $conn;
    }
}

// ── Global Helper Sanitasi Output XSS ─────────────────────────
if (!function_exists('h')) {
    function h(?string $data): string {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// ── Helper Function getDB() ───────────────────────────────────
if (!function_exists('getDB')) {
    function getDB(): PDO {
        global $pdo;
        return $pdo;
    }
}

// ── Helper Query DB (Prepared Statements) ─────────────────────
if (!function_exists('dbSelect')) {
    function dbSelect(string $sql, array $params = []): array {
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('dbSelectOne')) {
    function dbSelectOne(string $sql, array $params = []): array|false {
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
