<?php
/**
 * admin/raket_form.php — Redirect ke Unified CRUD raket.php
 */
require_once __DIR__ . '/guard.php';

$id = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    header("Location: raket.php?action=edit&id={$id}");
} else {
    header("Location: raket.php?action=add");
}
exit();
