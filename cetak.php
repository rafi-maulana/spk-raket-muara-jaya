<?php
if (ob_get_level() === 0) {
    ob_start();
}
/**
 * cetak.php — Forwarder ke Laporan Cetak Resmi A4
 * SPK Raket Bulu Tangkis — Muara Jaya Sport
 */
if (!headers_sent()) {
    header('Location: cetak_laporan.php');
    exit;
}
echo '<script>window.location.href="cetak_laporan.php";</script>';
exit;
