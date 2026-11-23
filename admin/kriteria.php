<?php
/**
 * ============================================================
 *  admin/kriteria.php — Manajemen Bobot Kriteria SAW
 *  Toko Olahraga: Muara Jaya Sport
 *  Design System: Studio-Grade Tailwind CSS Dual-Theme
 * ============================================================
 */
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../functions.php';

$storeSettings = getStoreSettings($pdo);
$adminUsername = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin');
$success = $error = '';

// ── SERVER-SIDE: UPDATE BOBOT SEMUA KRITERIA ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_weights'])) {
    $ids     = $_POST['criteria_id']   ?? [];
    $names   = $_POST['criteria_name'] ?? [];
    $weights = $_POST['weight']        ?? [];
    $types   = $_POST['type']          ?? [];

    if (empty($ids)) {
        $error = 'Tidak ada data kriteria yang dikirim.';
    } else {
        $total         = 0.0;
        $weightsParsed = [];
        $namesCleaned  = [];
        $typesCleaned  = [];

        foreach ($ids as $i => $id) {
            $w = (float) str_replace([',', ' '], ['.', ''], $weights[$i] ?? '0');
            $n = trim(strip_tags($names[$i] ?? ''));
            $t = in_array($types[$i] ?? '', ['benefit', 'cost']) ? $types[$i] : 'benefit';

            if ($w < 0 || $w > 1) {
                $error = "Bobot harus bernilai antara 0 dan 1. Periksa kriteria baris ke-" . ($i + 1) . ".";
                break;
            }
            if (empty($n)) {
                $error = "Nama kriteria tidak boleh kosong (baris ke-" . ($i + 1) . ").";
                break;
            }

            $total              += $w;
            $weightsParsed[$i]   = round($w, 4);
            $namesCleaned[$i]    = $n;
            $typesCleaned[$i]    = $t;
        }

        if (empty($error)) {
            $total = round($total, 4);
            $selisih = abs($total - 1.0);

            // Validasi Dual-Layer: Tolak jika total bobot != 1.0000
            if ($selisih > 0.001) {
                $pct      = number_format($total * 100, 2);
                $selisihF = number_format($selisih, 4);
                $diff     = $total > 1.0 ? "+{$selisihF}" : "-{$selisihF}";
                $error    = "Penyimpanan Ditolak — Total bobot = {$total} ({$pct}%), selisih {$diff} dari 1.0000. Sesuaikan bobot hingga totalnya tepat 1.00 (100%) sebelum menyimpan.";
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("UPDATE criteria SET name = :name, weight = :weight, type = :type WHERE id = :id");
                    foreach ($ids as $i => $id) {
                        $stmt->execute([
                            ':id'     => (int)$id,
                            ':name'   => $namesCleaned[$i],
                            ':weight' => $weightsParsed[$i],
                            ':type'   => $typesCleaned[$i],
                        ]);
                    }
                    $pdo->commit();
                    setFlash('success', "Bobot kriteria berhasil disimpan! Total bobot = " . number_format($total, 4) . " (Tepat 100%).", 'Bobot Disimpan');
                    header('Location: kriteria.php');
                    exit;
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    setFlash('error', 'Database error: ' . $e->getMessage(), 'Gagal Menyimpan Bobot');
                    header('Location: kriteria.php');
                    exit;
                }
            }
        }
    }
    if (!empty($error)) {
        setFlash('error', $error, 'Validasi Bobot Ditolak');
        header('Location: kriteria.php');
        exit;
    }
}

// ── SERVER-SIDE: TAMBAH KRITERIA BARU ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_criteria'])) {
    $newCode   = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $_POST['new_code'] ?? ''));
    $newName   = trim(strip_tags($_POST['new_name'] ?? ''));
    $newWeight = (float) str_replace([',', ' '], ['.', ''], $_POST['new_weight'] ?? '0');
    $newType   = in_array($_POST['new_type'] ?? '', ['benefit', 'cost']) ? $_POST['new_type'] : 'benefit';

    if (!$newCode || !$newName) {
        $error = 'Kode dan nama kriteria wajib diisi.';
    } elseif ($newWeight <= 0 || $newWeight > 1) {
        $error = 'Bobot kriteria baru harus bernilai antara 0.0001 dan 1.0000.';
    } else {
        try {
            $dup = $pdo->prepare("SELECT id FROM criteria WHERE code = :c LIMIT 1");
            $dup->execute([':c' => $newCode]);
            if ($dup->fetch()) {
                $error = "Kode kriteria \"{$newCode}\" sudah terdaftar. Gunakan kode lain.";
            } else {
                $pdo->prepare("INSERT INTO criteria (code, name, weight, type) VALUES (:c, :n, :w, :t)")
                    ->execute([':c' => $newCode, ':n' => $newName, ':w' => round($newWeight, 4), ':t' => $newType]);
                setFlash('success', "Kriteria {$newCode} berhasil ditambahkan! Silakan sesuaikan bobot kriteria lainnya agar total bernilai 1.00 (100%).", 'Kriteria Ditambahkan');
                header('Location: kriteria.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Gagal menambah kriteria: ' . $e->getMessage();
        }
    }
    if (!empty($error)) {
        setFlash('error', $error, 'Gagal Menambah Kriteria');
        header('Location: kriteria.php');
        exit;
    }
}

// ── SERVER-SIDE: HAPUS KRITERIA ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete']) && ctype_digit((string)$_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    try {
        $delName = $pdo->prepare("SELECT code FROM criteria WHERE id = :id");
        $delName->execute([':id' => $delId]);
        $delCode = $delName->fetchColumn() ?: "#{$delId}";
        $pdo->prepare("DELETE FROM criteria WHERE id = :id")->execute([':id' => $delId]);
        setFlash('success', "Kriteria {$delCode} berhasil dihapus.", 'Kriteria Dihapus');
        header('Location: kriteria.php');
        exit;
    } catch (PDOException $e) {
        setFlash('error', 'Gagal menghapus kriteria: ' . $e->getMessage(), 'Gagal Menghapus Kriteria');
        header('Location: kriteria.php');
        exit;
    }
}

// ── AMBIL DATA KRITERIA TERKINI ────────────────────────────────
$criteria = $pdo->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$currentTotal = 0.0;
foreach ($criteria as $c) {
    $currentTotal += (float)$c['weight'];
}
$currentTotal = round($currentTotal, 4);
$isValid      = abs($currentTotal - 1.0) <= 0.001;
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Bobot Kriteria SAW — <?= h($storeSettings['store_name']) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
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
                    <a href="kriteria.php" class="px-3 py-1.5 rounded-lg bg-teal-600 text-white font-bold shadow-sm transition">Kriteria</a>
                    <a href="riwayat.php" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300 transition">Riwayat</a>
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
                <a href="kriteria.php" class="block px-3 py-2 rounded-lg text-sm font-semibold bg-teal-600 text-white font-bold">Kriteria & Bobot</a>
                <a href="riwayat.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Riwayat Konsultasi</a>
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
                    Manajemen Bobot Kriteria SAW
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Atur nama, tipe benefit/cost, dan bobot preferensi kriteria. Total bobot seluruh kriteria wajib tepat bernilai <strong>1.0000 (100%)</strong>.
                </p>
            </div>
            <div>
                <button type="button" onclick="document.getElementById('addModal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 dark:hover:from-teal-700 dark:hover:to-cyan-700 text-white font-bold text-xs shadow-md transition">
                    <span>+ Tambah Kriteria</span>
                </button>
            </div>
        </div>

        <!-- Alert Notification & Flash Messages -->
        <?php renderFlash(); ?>
        <?php if (!empty($success)): ?>
            <div class="p-4 rounded-2xl bg-teal-500/10 border border-teal-500/30 text-teal-800 dark:text-teal-300 text-xs sm:text-sm flex items-center gap-3">
                <svg class="w-4 h-4 text-teal-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span><?= h($success) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs sm:text-sm flex items-center gap-3">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span><?= h($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Progress Bar & Status Akumulasi Bobot Real-Time -->
        <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Akumulasi Bobot:</span>
                    <span id="liveTotalDisplay" class="font-serif font-black text-xl <?= $isValid ? 'text-teal-600 dark:text-cyan-400' : 'text-amber-600 dark:text-amber-400' ?>">
                        <?= number_format($currentTotal, 4) ?> (<?= number_format($currentTotal * 100, 1) ?>%)
                    </span>
                </div>
                <div id="liveBadge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider <?= $isValid ? 'bg-teal-500/10 text-teal-700 dark:bg-cyan-500/15 dark:text-cyan-300 border border-teal-500/20' : 'bg-amber-500/10 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 border border-amber-500/30' ?>">
                    <?= $isValid ? 'Akumulasi Valid (Tepat 100%)' : 'Belum Seimbang (Harus 100%)' ?>
                </div>
            </div>

            <!-- Visual Bar -->
            <div class="w-full h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden relative border border-slate-200 dark:border-slate-700">
                <div id="liveProgressBar" class="h-full rounded-full transition-all duration-300 <?= $isValid ? 'bg-teal-500 dark:bg-cyan-400' : 'bg-amber-500' ?>"
                     style="width: <?= min(100, max(0, $currentTotal * 100)) ?>%;"></div>
            </div>
            <p id="liveHelperText" class="text-xs text-slate-500 dark:text-slate-400">
                <?= $isValid ? 'Distribusi pembobotan kriteria telah memenuhi kaidah matematika SAW (Σ W = 1.00).' : 'Ubah nilai bobot pada tabel di bawah agar akumulasinya tepat bernilai 1.0000 sebelum menyimpan.' ?>
            </p>
        </div>

        <!-- Form Edit Bobot Kriteria -->
        <form method="POST" action="kriteria.php" id="weightsForm" class="space-y-6">
            <input type="hidden" name="update_weights" value="1">

            <div class="rounded-3xl bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 pt-3 md:hidden">
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">← Geser tabel ke samping →</span>
                </div>
                <div class="w-full overflow-x-auto text-left">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="py-4 px-4 text-center">Kode</th>
                                <th class="py-4 px-5 sticky left-0 bg-slate-50 dark:bg-slate-800 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[140px]">Nama Kriteria</th>
                                <th class="py-4 px-5">Tipe Kriteria</th>
                                <th class="py-4 px-5 text-center w-40">Nilai Bobot (W)</th>
                                <th class="py-4 px-4 text-center">Persentase</th>
                                <th class="py-4 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <?php foreach ($criteria as $i => $c): ?>
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                <td class="py-4 px-4 text-center font-serif font-black text-slate-900 dark:text-white">
                                    <input type="hidden" name="criteria_id[]" value="<?= (int)$c['id'] ?>">
                                    <span class="inline-block px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-teal-700 dark:text-cyan-400 font-mono text-xs font-bold border border-slate-200 dark:border-slate-700">
                                        <?= h($c['code']) ?>
                                    </span>
                                </td>
                                <td class="py-4 px-5">
                                    <input type="text" name="criteria_name[]" value="<?= h($c['name']) ?>" required
                                           class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </td>
                                <td class="py-4 px-5">
                                    <select name="type[]"
                                            class="px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500 cursor-pointer">
                                        <option value="benefit" <?= $c['type'] === 'benefit' ? 'selected' : '' ?>>Benefit (Makin Besar Makin Baik)</option>
                                        <option value="cost" <?= $c['type'] === 'cost' ? 'selected' : '' ?>>Cost (Makin Kecil Makin Baik)</option>
                                    </select>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <input type="number" step="0.0001" min="0" max="1" name="weight[]" value="<?= number_format((float)$c['weight'], 4, '.', '') ?>" required
                                           class="weight-input w-28 text-center px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-bold font-mono focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="pct-display font-mono text-xs font-bold text-slate-500 dark:text-slate-400">
                                        <?= number_format((float)$c['weight'] * 100, 1) ?>%
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <a href="kriteria.php?delete=<?= (int)$c['id'] ?>" onclick="return confirm('Hapus kriteria <?= h($c['code']) ?>? Data skor raket terkait juga akan dihapus.');"
                                       class="p-2 rounded-lg text-rose-500 hover:bg-rose-500/10 transition inline-block" title="Hapus Kriteria">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Simpan & Auto-Normalisasi -->
                <div class="p-4 sm:p-5 bg-slate-50 dark:bg-slate-900/60 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Tombol simpan otomatis aktif jika akumulasi bobot tepat bernilai 1.0000.
                    </span>
                    <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                        <button type="button" id="autoNormalizeBtn"
                                class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl border border-teal-600 dark:border-cyan-500 text-teal-700 dark:text-cyan-300 hover:bg-teal-50 dark:hover:bg-cyan-500/10 font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                            <svg class="w-4 h-4 text-teal-600 dark:text-cyan-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Auto-Normalisasi Bobot</span>
                        </button>
                        <button type="submit" id="saveWeightsBtn" <?= $isValid ? '' : 'disabled' ?>
                                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 dark:hover:from-teal-700 dark:hover:to-cyan-700 text-white font-bold text-xs shadow-md transition disabled:opacity-40 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            <span>Simpan Perubahan Bobot Kriteria</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </div>

    <!-- Modal Tambah Kriteria Baru -->
    <div id="addModal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-[#162238] border border-slate-200 dark:border-cyan-500/30 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <h3 class="font-serif font-bold text-lg text-slate-900 dark:text-white">Tambah Kriteria Baru</h3>
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-200 text-xl font-bold">&times;</button>
            </div>

            <form method="POST" action="kriteria.php" class="space-y-4">
                <input type="hidden" name="add_criteria" value="1">

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Kode Kriteria</label>
                    <input type="text" name="new_code" required placeholder="Misal: C9"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Nama Kriteria</label>
                    <input type="text" name="new_name" required placeholder="Misal: Desain Aerodinamis"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Tipe</label>
                        <select name="new_type"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="benefit">Benefit</option>
                            <option value="cost">Cost</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">Bobot Awal</label>
                        <input type="number" step="0.0001" min="0.0001" max="1" name="new_weight" required placeholder="0.1000"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-teal-500">
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                            class="px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 text-white text-xs font-bold shadow transition">
                        Simpan Kriteria
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Kalkulasi Real-Time & Theme Toggle -->
    <script>
        // 1. Dual-Theme Toggle Synchronizer
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

        // 2. Real-Time Weight Recalculation
        const weightInputs     = document.querySelectorAll('.weight-input');
        const liveTotalDisplay = document.getElementById('liveTotalDisplay');
        const liveBadge        = document.getElementById('liveBadge');
        const liveProgressBar  = document.getElementById('liveProgressBar');
        const liveHelperText   = document.getElementById('liveHelperText');
        const saveWeightsBtn   = document.getElementById('saveWeightsBtn');

        function recalculateWeights() {
            let total = 0;
            weightInputs.forEach((inp) => {
                const val = parseFloat(String(inp.value || '').replace(',', '.').trim()) || 0;
                total += val;

                // Update percentage display next to it
                const row = inp.closest('tr');
                if (row) {
                    const pctSpan = row.querySelector('.pct-display');
                    if (pctSpan) {
                        pctSpan.textContent = (val * 100).toFixed(1) + '%';
                    }
                }
            });

            total = Math.round(total * 10000) / 10000;
            const diff = Math.abs(total - 1.0);
            const isValid = diff <= 0.001;

            if (liveTotalDisplay) {
                liveTotalDisplay.textContent = total.toFixed(4) + ' (' + (total * 100).toFixed(1) + '%)';
            }
            if (liveProgressBar) {
                liveProgressBar.style.width = Math.min(100, Math.max(0, total * 100)) + '%';
            }

            if (isValid) {
                if (liveTotalDisplay) liveTotalDisplay.className = 'font-serif font-black text-xl text-teal-600 dark:text-cyan-400';
                if (liveBadge) {
                    liveBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-teal-500/10 text-teal-700 dark:bg-cyan-500/15 dark:text-cyan-300 border border-teal-500/20';
                    liveBadge.textContent = 'Akumulasi Valid (Tepat 100%)';
                }
                if (liveProgressBar) liveProgressBar.className = 'h-full rounded-full transition-all duration-300 bg-teal-500 dark:bg-cyan-400';
                if (liveHelperText) liveHelperText.textContent = 'Distribusi pembobotan kriteria telah memenuhi kaidah matematika SAW (Σ W = 1.00).';
                if (saveWeightsBtn) saveWeightsBtn.disabled = false;
            } else {
                if (liveTotalDisplay) liveTotalDisplay.className = 'font-serif font-black text-xl text-amber-600 dark:text-amber-400';
                if (liveBadge) {
                    liveBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-amber-500/10 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 border border-amber-500/30';
                    const delta = (total > 1.0 ? '+' : '-') + diff.toFixed(4);
                    liveBadge.textContent = 'Belum Seimbang (' + delta + ')';
                }
                if (liveProgressBar) liveProgressBar.className = 'h-full rounded-full transition-all duration-300 bg-amber-500';
                if (liveHelperText) liveHelperText.textContent = 'Ubah nilai bobot pada tabel agar akumulasinya tepat bernilai 1.0000 sebelum menyimpan.';
                if (saveWeightsBtn) saveWeightsBtn.disabled = true;
            }
        }

        weightInputs.forEach((inp) => {
            inp.addEventListener('input', recalculateWeights);
        });

        // 3. Auto-Normalisasi Bobot Otomatis (Σ W = 1.0000)
        const autoNormalizeBtn = document.getElementById('autoNormalizeBtn');
        if (autoNormalizeBtn) {
            autoNormalizeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const form = document.getElementById('weightsForm');
                const inputs = form ? Array.from(form.querySelectorAll('.weight-input')) : Array.from(document.querySelectorAll('.weight-input'));
                if (inputs.length === 0) return;

                // 1. Ambil nilai numerik dari setiap input (konversi koma ke titik jika ada)
                const values = inputs.map((inp) => {
                    const cleanStr = String(inp.value || '').replace(',', '.').trim();
                    const num = parseFloat(cleanStr);
                    return isNaN(num) || num < 0 ? 0 : num;
                });

                // 2. Hitung total seluruh nilai input saat ini
                const totalCurrent = values.reduce((acc, v) => acc + v, 0);
                if (totalCurrent <= 0) {
                    alert('Nilai bobot masih kosong atau bernilai nol. Masukkan angka bobot awal terlebih dahulu sebelum dinormalisasi.');
                    return;
                }

                // 3. Bagi setiap nilai dengan total sementara, bulatkan 4 angka desimal (toFixed(4)),
                //    dan koreksi selisih pembulatan pada input terakhir agar jumlah tepat 1.0000
                let runningSum = 0;
                inputs.forEach((inp, idx) => {
                    if (idx === inputs.length - 1) {
                        const lastVal = Math.max(0, 1.0 - runningSum);
                        inp.value = lastVal.toFixed(4);
                    } else {
                        const normalized = parseFloat((values[idx] / totalCurrent).toFixed(4));
                        inp.value = normalized.toFixed(4);
                        runningSum += normalized;
                        runningSum = Math.round(runningSum * 10000) / 10000;
                    }

                    // 4. Picu input event agar indikator live recalculate otomatis terpanggil
                    inp.dispatchEvent(new Event('input', { bubbles: true }));
                });
            });
        }
    </script>
</body>
</html>
