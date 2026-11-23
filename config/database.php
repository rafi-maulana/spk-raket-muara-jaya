<?php
date_default_timezone_set('Asia/Jakarta');
/**
 * config/database.php — Compatibility Wrapper
 * Muara Jaya Sport - SPK SAW
 */
require_once __DIR__ . '/koneksi.php';

// Pastikan zona waktu sesi TiDB Cloud / MySQL terpasang ke +07:00 (WIB)
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $pdo->exec("SET time_zone = '+07:00';");
    } catch (Throwable $e) {
        // Fallback aman jika permission server database terbatas
    }
}
