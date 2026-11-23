<?php
if (ob_get_level() === 0) {
    ob_start();
}
/**
 * admin/raket_form.php — Redirect ke Unified CRUD raket.php
 */
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../functions.php';

$id = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    safeRedirect("raket.php?action=edit&id={$id}");
} else {
    safeRedirect("raket.php?action=add");
}
