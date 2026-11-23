<?php
if (ob_get_level() === 0) {
    ob_start();
}
/**
 * ============================================================
 *  admin/riwayat.php — Riwayat Log Konsultasi Pengunjung
 *  Sistem Pendukung Keputusan Pemilihan Raket Bulu Tangkis
 *  Muara Jaya Sport | Metode Simple Additive Weighting (SAW)
 * ============================================================
 *  FITUR:
 *   - Proteksi Sesi & Header Anti-Cache via guard.php
 *   - Dual-Theme Mode Global (Warm Light & Midnight Dark)
 *   - Filter Log Berdasarkan Rentang Tanggal & Kata Kunci
 *   - Format Skor SAW Presisi & Terproteksi Anti-Undefined Key
 *   - Format Tanggal Konsultasi Standar Baku Bahasa Indonesia
 *   - Hapus Log Satuan & Kosongkan Seluruh Riwayat dengan Konfirmasi
 *   - Banner Notifikasi Flash Messages Elegan (Auto-Dismiss)
 * ============================================================
 */

require_once __DIR__ . '/guard.php';

if (file_exists(__DIR__ . '/../config/koneksi.php')) {
    require_once __DIR__ . '/../config/koneksi.php';
} elseif (file_exists(__DIR__ . '/../koneksi.php')) {
    require_once __DIR__ . '/../koneksi.php';
}

require_once __DIR__ . '/../functions.php';

// Helper sanitasi output anti-XSS
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$storeSettings = getStoreSettings($pdo);
$adminUsername = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
$success       = '';

// ── PROSES HAPUS SINGLE LOG ────────────────────────────────────
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM consultation_log WHERE id = :id");
        $stmt->execute([':id' => $delId]);
        setFlash('success', 'Data riwayat konsultasi berhasil dihapus.', 'Riwayat Dihapus');
    } catch (PDOException $e) {
        error_log('[DELETE LOG ERROR] ' . $e->getMessage());
        setFlash('error', 'Gagal menghapus log riwayat konsultasi: ' . $e->getMessage(), 'Gagal Menghapus');
    }
    safeRedirect('riwayat.php');
}

// ── PROSES HAPUS SELURUH LOG ───────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['delete_all'])) {
    try {
        $pdo->exec("TRUNCATE TABLE consultation_log");
        setFlash('success', 'Seluruh riwayat konsultasi pengunjung berhasil dikosongkan.', 'Riwayat Dikosongkan');
    } catch (PDOException $e) {
        error_log('[TRUNCATE LOG ERROR] ' . $e->getMessage());
        setFlash('error', 'Gagal mengosongkan riwayat: ' . $e->getMessage(), 'Gagal Mengosongkan');
    }
    safeRedirect('riwayat.php');
}

// ── FILTER TANGGAL & PENCARIAN ─────────────────────────────────
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
$search   = trim($_GET['search']    ?? '');

$where  = 'WHERE 1=1';
$params = [];

if (!empty($dateFrom)) {
    $where .= ' AND DATE(created_at) >= :df';
    $params[':df'] = $dateFrom;
}
if (!empty($dateTo)) {
    $where .= ' AND DATE(created_at) <= :dt';
    $params[':dt'] = $dateTo;
}
if (!empty($search)) {
    $where .= ' AND (visitor_name LIKE :s OR result_top_racket LIKE :s2)';
    $params[':s']  = "%{$search}%";
    $params[':s2'] = "%{$search}%";
}

$stmt = $pdo->prepare("SELECT * FROM consultation_log {$where} ORDER BY created_at DESC");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalLogs = (int)$pdo->query("SELECT COUNT(*) FROM consultation_log")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Konsultasi — <?= h($storeSettings['store_name']) ?></title>

    <!-- Google Fonts: Playfair Display & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                        serif: ['Playfair Display', 'Georgia', 'serif'],
                    },
                    colors: {
                        warm: {
                            base: '#FBF9F5',
                            card: '#FFFFFF',
                            border: '#E2E8F0',
                            navy: '#0F172A',
                        },
                        midnight: {
                            base: '#0B0F17',
                            surface: '#131C2E',
                            card: '#162238',
                            border: 'rgba(56, 189, 248, 0.18)',
                            cyan: '#00F0FF',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Custom Fixes & Anti-Overflow Protection -->
    <link rel="stylesheet" href="../assets/css/custom-fix.css">

    <!-- Theme Initialization (Instant Prevention of Theme Flicker) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('mjs_theme');
            if (savedTheme === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <style>
        :root {
            --bg-canvas: #FBF9F5;
            --text-heading: #0F172A;
            --text-body: #334155;
            --card-surface: #FFFFFF;
            --card-border: #E2E8F0;
        }
        html.dark {
            --bg-canvas: #0B0F17;
            --text-heading: #F8FAFC;
            --text-body: #94A3B8;
            --card-surface: #131C2E;
            --card-border: rgba(56, 189, 248, 0.18);
        }
        html, body {
            overflow-x: hidden;
            max-width: 100%;
        }
        body {
            background-color: var(--bg-canvas);
            color: var(--text-body);
            transition: background-color 0.25s ease, color 0.25s ease;
        }
    </style>
</head>
<body class="min-h-screen antialiased flex flex-col transition-colors pt-16">

    <!-- Top Admin Fixed Glassmorphism Navbar -->
    <nav class="fixed top-0 left-0 right-0 w-full z-50 bg-white/95 dark:bg-[#131C2E]/95 backdrop-blur-md border-b border-slate-200 dark:border-cyan-500/20 transition-colors shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Identitas Toko & Brand -->
                <div class="flex items-center gap-3">
                    <?php if (!empty($storeSettings['logo'])): ?>
                        <img src="<?= h(resolveStoreLogo($storeSettings['logo'], '../')) ?>" alt="Logo" class="w-8 h-8 object-contain rounded-lg">
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-lg bg-slate-900 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-serif font-black text-xs text-teal-600 dark:text-cyan-400">
                            MJ
                        </div>
                    <?php endif; ?>
                    <a href="dashboard.php" class="font-serif font-bold text-slate-900 dark:text-white text-base sm:text-lg hover:text-teal-600 dark:hover:text-cyan-400 transition flex items-center gap-2">
                        <?= h($storeSettings['store_name']) ?>
                        <span class="text-[10px] font-sans font-bold bg-teal-500/10 text-teal-700 dark:bg-cyan-500/20 dark:text-cyan-300 border border-teal-500/20 dark:border-cyan-500/40 px-2 py-0.5 rounded uppercase">
                            Admin Portal
                        </span>
                    </a>
                </div>

                <!-- Desktop Nav Links with Active State -->
                <div class="hidden md:flex items-center gap-2 text-xs font-semibold">
                    <button id="themeToggleBtn" type="button" aria-label="Ganti Tema"
                            class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-teal-500 dark:hover:border-cyan-400 transition">
                        <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>

                    <a href="dashboard.php" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300 transition">Dashboard</a>
                    <a href="raket.php" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300 transition">Raket</a>
                    <a href="kriteria.php" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300 transition">Kriteria</a>
                    <a href="riwayat.php" class="px-3 py-1.5 rounded-lg bg-teal-600 text-white font-bold shadow-sm transition">Riwayat</a>
                    <a href="../index.php" target="_blank" class="px-3 py-1.5 rounded-lg bg-teal-500/10 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300 border border-teal-500/20 dark:border-teal-500/30 hover:bg-teal-500/20 transition">Lihat Toko ↗</a>
                    <a href="../logout.php" class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400 border border-rose-500/20 dark:border-rose-500/30 hover:bg-rose-500/20 transition">Keluar</a>
                </div>

                <!-- Mobile Controls (< 768px): Theme Switcher + Hamburger -->
                <div class="flex items-center gap-2 md:hidden">
                    <button id="themeToggleBtnMobile" type="button" aria-label="Ganti Tema"
                            class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 transition">
                        <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                    <button type="button" id="adminMobileMenuBtn" class="p-2 rounded-xl text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition" aria-label="Menu Admin">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Drawer (< 768px) -->
            <div id="adminMobileDrawer" class="hidden md:hidden py-3 border-t border-slate-200 dark:border-slate-800 space-y-1">
                <a href="dashboard.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Dashboard</a>
                <a href="raket.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Data Raket</a>
                <a href="kriteria.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Kriteria & Bobot</a>
                <a href="riwayat.php" class="block px-3 py-2 rounded-lg text-sm font-semibold bg-teal-600 text-white font-bold">Riwayat Konsultasi</a>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2 px-1">
                    <a href="../index.php" target="_blank" class="flex-1 text-center py-2 rounded-lg bg-teal-500/10 text-teal-700 dark:text-teal-300 font-bold text-xs">Lihat Toko ↗</a>
                    <a href="../logout.php" class="flex-1 text-center py-2 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 font-bold text-xs">Keluar</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">

        <!-- Header Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-serif text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                    Riwayat Konsultasi Pengunjung
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Log seluruh aktivitas rekomendasi raket sistem SAW. Total tercatat: <strong><?= $totalLogs ?></strong> konsultasi.
                </p>
            </div>
            <?php if ($totalLogs > 0): ?>
            <div>
                <form method="POST" action="riwayat.php" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan seluruh riwayat konsultasi? Tindakan ini tidak dapat dibatalkan.');">
                    <button type="submit" name="delete_all" value="1"
                            class="px-4 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400 border border-rose-500/30 font-bold text-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Semua Riwayat
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Alert Notification & Flash Message -->
        <?php renderFlash(); ?>
        <?php if (!empty($success)): ?>
            <div class="p-4 rounded-2xl bg-teal-500/10 border border-teal-500/30 text-teal-800 dark:text-teal-300 text-xs sm:text-sm flex items-center gap-3 shadow-sm">
                <svg class="w-4 h-4 text-teal-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span><?= h($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- Filter & Pencarian Bar -->
        <div class="p-5 rounded-3xl bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-slate-800 shadow-sm">
            <form method="GET" action="riwayat.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Cari Pengunjung / Raket</label>
                    <input type="text" name="search" value="<?= h($search) ?>" placeholder="Nama pengunjung / raket..."
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="<?= h($dateFrom) ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="<?= h($dateTo) ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="flex-1 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 dark:hover:from-teal-700 dark:hover:to-cyan-700 text-white font-bold text-xs shadow transition">
                        Filter Data
                    </button>
                    <a href="riwayat.php"
                       class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-xs transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Tabel Riwayat Konsultasi -->
        <div class="rounded-3xl bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 pt-3 md:hidden">
                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">← Geser tabel ke samping →</span>
            </div>
            <div class="w-full overflow-x-auto text-left">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-4 px-4 text-center">#</th>
                            <th class="py-4 px-5 sticky left-0 bg-slate-50 dark:bg-slate-800 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[130px]">Nama Pengunjung</th>
                            <th class="py-4 px-5">Batas Anggaran</th>
                            <th class="py-4 px-5">Gaya Bermain</th>
                            <th class="py-4 px-5">Kelenturan (Flex)</th>
                            <th class="py-4 px-5">Pemenang Rekomendasi</th>
                            <th class="py-4 px-4 text-center">Skor Preferensi (SAW)</th>
                            <th class="py-4 px-5">Waktu Konsultasi</th>
                            <th class="py-4 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                <span class="font-bold text-slate-600 dark:text-slate-300">Belum ada riwayat konsultasi yang cocok.</span>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $i => $l):
                                // Ekstraksi nilai skor dengan null coalescing multi-key & pengaman tipe data
                                $scoreVal = $l['result_top_score'] 
                                    ?? $l['result_score'] 
                                    ?? $l['score'] 
                                    ?? $l['skor'] 
                                    ?? $l['nilai'] 
                                    ?? null;

                                if ($scoreVal !== null && is_numeric($scoreVal)) {
                                    $floatScore = (float)$scoreVal;
                                    $pctScore   = ($floatScore <= 1.0 && $floatScore > 0) ? ($floatScore * 100) : $floatScore;
                                    $decScore   = ($floatScore <= 1.0) ? $floatScore : ($floatScore / 100);
                                    $hasScore   = true;
                                } else {
                                    $pctScore   = 0.0;
                                    $decScore   = 0.0;
                                    $hasScore   = false;
                                }

                                // Format tanggal konsultasi
                                $rawTime = $l['created_at'] ?? null;
                                if ($rawTime) {
                                    $formattedTime = function_exists('formatTanggalIndonesia') 
                                        ? formatTanggalIndonesia($rawTime, true) 
                                        : date('d M Y, H:i', strtotime($rawTime));
                                } else {
                                    $formattedTime = '—';
                                }
                            ?>
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="py-4 px-4 text-center text-xs text-slate-400 font-mono"><?= $i + 1 ?></td>
                                <td class="py-4 px-5 font-bold text-slate-900 dark:text-white">
                                    <?= h($l['visitor_name'] ?? 'Pengunjung') ?>
                                </td>
                                <td class="py-4 px-5 text-teal-700 dark:text-cyan-400 font-bold font-sans">
                                    <?= (!empty($l['max_budget']) && (float)$l['max_budget'] > 0) ? formatRupiah($l['max_budget']) : '<span class="text-slate-400 font-normal">Tanpa Batasan</span>' ?>
                                </td>
                                <td class="py-4 px-5">
                                    <?php if (!empty($l['playing_style'])): ?>
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-teal-500/10 text-teal-700 dark:bg-cyan-500/15 dark:text-cyan-300 border border-teal-500/20">
                                            <?= h($l['playing_style']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-5">
                                    <?php if (!empty($l['flexibility_pref'])): ?>
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-500/10 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 border border-amber-500/20">
                                            <?= h($l['flexibility_pref']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-5 font-serif font-black text-slate-900 dark:text-white">
                                    <?= h($l['result_top_racket'] ?? '-') ?>
                                </td>
                                <td class="py-4 px-4 text-center font-mono">
                                    <?php if ($hasScore): ?>
                                        <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-teal-500/10 text-teal-700 dark:bg-cyan-500/15 dark:text-cyan-300 border border-teal-500/20 dark:border-cyan-500/30 shadow-xs">
                                            <?= number_format($pctScore, 2) ?>%
                                        </span>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 font-sans">
                                            v = <?= number_format($decScore, 4) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs font-sans">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-5 text-xs text-slate-500 dark:text-slate-400">
                                    <?= h($formattedTime) ?>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <a href="riwayat.php?delete=<?= (int)$l['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus catatan riwayat konsultasi ini?');"
                                       class="p-2 rounded-lg text-rose-500 hover:bg-rose-500/10 transition inline-block" title="Hapus Baris Log">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="p-4 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
                <span>Menampilkan <?= count($logs) ?> dari <?= $totalLogs ?> catatan riwayat</span>
                <span>Diurutkan berdasarkan waktu terbaru</span>
            </div>
        </div>

    </div>

    <!-- Script Theme Toggle Interaktif -->
    <script>
        const adminMobileMenuBtn = document.getElementById('adminMobileMenuBtn');
        const adminMobileDrawer  = document.getElementById('adminMobileDrawer');
        if (adminMobileMenuBtn && adminMobileDrawer) {
            adminMobileMenuBtn.addEventListener('click', () => {
                adminMobileDrawer.classList.toggle('hidden');
            });
        }

        function toggleAdminTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('mjs_theme', isDark ? 'dark' : 'light');
        }
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeToggleBtnMobile = document.getElementById('themeToggleBtnMobile');
        if (themeToggleBtn) themeToggleBtn.addEventListener('click', toggleAdminTheme);
        if (themeToggleBtnMobile) themeToggleBtnMobile.addEventListener('click', toggleAdminTheme);
    </script>
</body>
</html>
