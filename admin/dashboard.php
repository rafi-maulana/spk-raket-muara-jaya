<?php
/**
 * ============================================================
 *  admin/dashboard.php — Dashboard Administrator & Pengaturan Toko
 *  Sistem Pendukung Keputusan Pemilihan Raket Bulu Tangkis
 *  Muara Jaya Sport | Metode Simple Additive Weighting (SAW)
 * ============================================================
 *  FITUR UTAMA:
 *   - Proteksi Sesi & Header Anti-Cache via guard.php
 *   - Dual-Theme Mode Global (Warm Light #FBF9F5 & Dark Slate #0B0F17)
 *   - Pengaturan Identitas Toko Dinamis (Nama, Alamat, Kontak, Logo)
 *   - Upload Logo Toko Bebas (PNG, JPG, WEBP) Aman
 *   - Ringkasan Statistik Raket, Kriteria, & Log Konsultasi
 *   - Validasi Bobot SAW Otomatis (validateWeights()) Bebas Error
 *   - Sanitasi Output Anti-XSS via h()
 * ============================================================
 */

require_once __DIR__ . '/guard.php';

if (file_exists(__DIR__ . '/../config/koneksi.php')) {
    require_once __DIR__ . '/../config/koneksi.php';
} elseif (file_exists(__DIR__ . '/../koneksi.php')) {
    require_once __DIR__ . '/../koneksi.php';
}

require_once __DIR__ . '/../functions.php';

// Helper sanitasi
if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Pastikan fungsi validateWeights tersedia
if (!function_exists('validateWeights')) {
    function validateWeights($input = null): array {
        global $pdo;
        $total = 0.0;
        $db = ($input instanceof PDO) ? $input : (isset($pdo) && $pdo instanceof PDO ? $pdo : (function_exists('getDB') ? getDB() : null));
        if ($db instanceof PDO) {
            $stmt = $db->query("SELECT CAST(weight AS DECIMAL(6,4)) AS w FROM criteria");
            $weights = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $total = (float) array_sum($weights);
        } elseif (is_array($input)) {
            $total = (float) array_sum($input);
        }
        $total = round($total, 6);
        $diff  = abs($total - 1.0);
        return [
            'total' => $total,
            'valid' => ($diff <= 0.001),
            'diff'  => $diff,
        ];
    }
}

// Ambil data identitas toko saat ini
$storeSettings   = getStoreSettings($pdo);
$settingsSuccess = '';
$settingsError   = '';

// ── PROSES UPDATE PENGATURAN IDENTITAS TOKO ─────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_store_settings'])) {
    $sName    = trim(strip_tags($_POST['store_name']    ?? ''));
    $sTagline = trim(strip_tags($_POST['store_tagline'] ?? ''));
    $sAddress = trim(strip_tags($_POST['address']       ?? ''));
    $sPhone   = trim(strip_tags($_POST['phone']         ?? ''));
    $sWA      = trim(strip_tags($_POST['whatsapp']      ?? ''));
    $sEmail   = trim(strip_tags($_POST['email']         ?? ''));
    $logoPath = $storeSettings['logo'] ?? '';

    // Hapus logo jika diminta
    if (isset($_POST['delete_logo']) && !empty($logoPath)) {
        if (str_starts_with($logoPath, 'assets/uploads/logos/')) {
            $f = __DIR__ . '/../' . $logoPath;
            if (file_exists($f) && is_file($f)) {
                @unlink($f);
            }
        }
        $logoPath = '';
    }

    // Proses upload file logo baru
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['logo_file']['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/png', 'image/jpeg', 'image/pjpeg', 'image/webp'];
            $ext          = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));

            if (!in_array($mime, $allowedMimes, true) || !in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                $settingsError = 'Format file logo harus berupa PNG, JPG, JPEG, atau WEBP.';
            } elseif ($_FILES['logo_file']['size'] > 3 * 1024 * 1024) {
                $settingsError = 'Ukuran file logo maksimal 3 MB.';
            } else {
                $uploadDir = __DIR__ . '/../assets/uploads/logos/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }

                if (!is_writable($uploadDir)) {
                    // Proteksi lingkungan serverless read-only filesystem (misal: Vercel)
                    $settingsError = 'Direktori penyimpanan logo bersifat read-only pada environment serverless ini. Teks pengaturan toko tetap dapat disimpan tanpa memperbarui file logo.';
                } else {
                    $newLogoName = 'logo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetFile  = $uploadDir . $newLogoName;

                    if (@move_uploaded_file($_FILES['logo_file']['tmp_name'], $targetFile)) {
                        // Hapus logo lama jika ada
                        if (!empty($logoPath) && str_starts_with($logoPath, 'assets/uploads/logos/')) {
                            $oldF = __DIR__ . '/../' . $logoPath;
                            if (file_exists($oldF) && is_file($oldF)) {
                                @unlink($oldF);
                            }
                        }
                        $logoPath = 'assets/uploads/logos/' . $newLogoName;
                    } else {
                        $settingsError = 'Gagal menyimpan file logo ke folder server.';
                    }
                }
            }
        } else {
            $settingsError = 'Terjadi kesalahan saat mengunggah file logo (Kode: ' . $_FILES['logo_file']['error'] . ').';
        }
    }

    if (empty($settingsError)) {
        if (empty($sName)) {
            $settingsError = 'Nama toko wajib diisi.';
        } else {
            $successUpdate = updateStoreSettings($pdo, [
                'store_name'    => $sName,
                'store_tagline' => $sTagline,
                'address'       => $sAddress,
                'phone'         => $sPhone,
                'whatsapp'      => $sWA,
                'email'         => $sEmail,
                'logo'          => $logoPath,
            ]);

            if ($successUpdate) {
                setFlash('success', 'Identitas toko berhasil disimpan dan langsung diterapkan ke seluruh sistem!', 'Identitas Toko Disimpan');
                header('Location: dashboard.php#pengaturan-toko');
                exit;
            } else {
                setFlash('error', 'Gagal memperbarui data identitas toko di database.', 'Gagal Memperbarui');
                header('Location: dashboard.php#pengaturan-toko');
                exit;
            }
        }
    }

    if (!empty($settingsError)) {
        setFlash('error', $settingsError, 'Validasi Gagal');
        header('Location: dashboard.php#pengaturan-toko');
        exit;
    }
}

// ── Statistik Dashboard ───────────────────────────────────────
$totalRaket      = (int) $pdo->query("SELECT COUNT(*) FROM rackets")->fetchColumn();
$totalKriteria   = (int) $pdo->query("SELECT COUNT(*) FROM criteria")->fetchColumn();
$totalKonsultasi = (int) $pdo->query("SELECT COUNT(*) FROM consultation_log")->fetchColumn();

// Validasi Bobot Kriteria
$wv         = validateWeights($pdo);
$totalBobot = $wv['total'];
$bobotValid = $wv['valid'];

// Log Konsultasi Terkini
$recentLogs = $pdo->query("
    SELECT * FROM consultation_log ORDER BY created_at DESC LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// Preview SAW
$sawPreview = ['success' => false];
if ($bobotValid && $totalRaket >= 2) {
    try {
        $sawPreview = runSAW($pdo, null);
    } catch (Throwable $e) {
        error_log("Gagal hitung preview SAW: " . $e->getMessage());
    }
}

$rankingOrdered = [];
if ($sawPreview['success'] && !empty($sawPreview['ranking'])) {
    $rankingOrdered = $sawPreview['ranking'];
    uksort($rankingOrdered, fn($a, $b) =>
        $sawPreview['ranking'][$a]['rank'] <=> $sawPreview['ranking'][$b]['rank']);
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Pengelola — <?= h($storeSettings['store_name']) ?></title>

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

    <!-- Theme Initialization (Instant Prevention of Flicker) -->
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
                <!-- Brand & Portal Badge -->
                <div class="flex items-center gap-3">
                    <?php if (!empty($storeSettings['logo'])): ?>
                        <img src="../<?= h($storeSettings['logo']) ?>" alt="Logo" class="w-8 h-8 object-contain rounded-lg">
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
                    <!-- Dual-Theme Switcher Button -->
                    <button id="themeToggleBtn" type="button" aria-label="Ganti Tema"
                            class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-teal-500 dark:hover:border-cyan-400 transition">
                        <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>

                    <a href="dashboard.php" class="px-3 py-1.5 rounded-lg bg-teal-600 text-white font-bold shadow-sm transition">Dashboard</a>
                    <a href="raket.php" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300 transition">Raket</a>
                    <a href="kriteria.php" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300 transition">Kriteria</a>
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
                <a href="dashboard.php" class="block px-3 py-2 rounded-lg text-sm font-semibold bg-teal-600 text-white font-bold">Dashboard</a>
                <a href="raket.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Data Raket</a>
                <a href="kriteria.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Kriteria & Bobot</a>
                <a href="riwayat.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Riwayat Konsultasi</a>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2 px-1">
                    <a href="../index.php" target="_blank" class="flex-1 text-center py-2 rounded-lg bg-teal-500/10 text-teal-700 dark:text-teal-300 font-bold text-xs">Lihat Toko ↗</a>
                    <a href="../logout.php" class="flex-1 text-center py-2 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 font-bold text-xs">Keluar</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full space-y-6">

        <!-- Header Ringkasan Sistem -->
        <div>
            <h1 class="font-serif text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">Ringkasan Sistem & Manajemen</h1>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">Kelola data alternatif raket, bobot kriteria SAW, dan identitas resmi toko.</p>
        </div>

        <!-- Alert Notifikasi Flash Messages -->
        <?php renderFlash(); ?>

        <!-- Compact KPI Bar (Windows 125% DPI Optimized) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Total Raket -->
            <a href="raket.php" class="bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-xl p-3 sm:p-3.5 hover:border-teal-500 dark:hover:border-cyan-400 transition-all shadow-xs flex items-center justify-between group">
                <div>
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Total Raket</span>
                    <div class="text-xl sm:text-2xl font-serif font-black text-slate-900 dark:text-white leading-tight mt-0.5"><?= $totalRaket ?></div>
                    <span class="text-[10px] text-teal-600 dark:text-teal-400 font-semibold block">20 Katalog Aktif</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-600 dark:text-cyan-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </a>

            <!-- Kriteria SAW -->
            <a href="kriteria.php" class="bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-xl p-3 sm:p-3.5 hover:border-teal-500 dark:hover:border-cyan-400 transition-all shadow-xs flex items-center justify-between group">
                <div>
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Kriteria SAW</span>
                    <div class="text-xl sm:text-2xl font-serif font-black text-slate-900 dark:text-white leading-tight mt-0.5"><?= $totalKriteria ?></div>
                    <span class="text-[10px] text-teal-600 dark:text-cyan-400 font-semibold block">C1–C8 Objektif</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                </div>
            </a>

            <!-- Total Konsultasi -->
            <a href="riwayat.php" class="bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-xl p-3 sm:p-3.5 hover:border-teal-500 dark:hover:border-cyan-400 transition-all shadow-xs flex items-center justify-between group">
                <div>
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Simulasi Selesai</span>
                    <div class="text-xl sm:text-2xl font-serif font-black text-slate-900 dark:text-white leading-tight mt-0.5"><?= $totalKonsultasi ?></div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold block">Log Konsultasi</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
            </a>

            <!-- Status Bobot SAW -->
            <a href="kriteria.php" class="bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-xl p-3 sm:p-3.5 hover:border-teal-500 dark:hover:border-cyan-400 transition-all shadow-xs flex items-center justify-between group">
                <div>
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Status Bobot SAW</span>
                    <div class="text-xl sm:text-2xl font-serif font-black leading-tight mt-0.5 <?= $bobotValid ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>">
                        <?= number_format($totalBobot * 100, 1) ?>%
                    </div>
                    <span class="text-[10px] <?= $bobotValid ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?> font-semibold block">
                        <?= $bobotValid ? 'Normal (100%)' : 'Perlu Koreksi' ?>
                    </span>
                </div>
                <div class="w-10 h-10 rounded-xl <?= $bobotValid ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400' ?> flex items-center justify-center shrink-0">
                    <?php if ($bobotValid): ?>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <?php else: ?>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <?php endif; ?>
                </div>
            </a>
        </div>

        <!-- ══════════════════════════════════════════════════════════════
             FORM PENGATURAN IDENTITAS TOKO DINAMIS (ADMIN SETTINGS)
             ══════════════════════════════════════════════════════════════ -->
        <div id="pengaturan-toko" class="bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-2xl p-4 sm:p-6 shadow-sm dark:shadow-xl space-y-4 transition-colors">
            <div class="border-b border-slate-200 dark:border-slate-800 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="font-serif text-lg sm:text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-teal-500/10 text-teal-600 dark:text-cyan-400 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <span>Pengaturan Identitas Resmi Toko</span>
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Ubah data nama toko, alamat, kontak WhatsApp, email, dan logo toko. Informasi ini akan langsung tampil di Header, Footer, dan Lembar Cetak Laporan.
                    </p>
                </div>
                <span class="text-[11px] font-bold text-teal-700 dark:text-cyan-400 uppercase tracking-wider bg-teal-500/10 dark:bg-cyan-500/10 border border-teal-500/30 dark:border-cyan-500/30 px-3 py-1 rounded-full self-start sm:self-auto">
                    Sinkronisasi Dinamis
                </span>
            </div>

            <form action="dashboard.php" method="POST" enctype="multipart/form-data" class="space-y-4">

                <div class="grid sm:grid-cols-2 gap-3.5 sm:gap-4">
                    <!-- Nama Toko -->
                    <div>
                        <label for="store_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Nama Toko / Usaha *
                        </label>
                        <input type="text" id="store_name" name="store_name" required
                               value="<?= h($storeSettings['store_name']) ?>"
                               class="w-full px-3.5 py-2 sm:py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500">
                    </div>

                    <!-- Slogan / Tagline -->
                    <div>
                        <label for="store_tagline" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Slogan / Sub-Judul Toko
                        </label>
                        <input type="text" id="store_tagline" name="store_tagline"
                               value="<?= h($storeSettings['store_tagline']) ?>"
                               placeholder="Contoh: Toko Perlengkapan Bulu Tangkis Profesional"
                               class="w-full px-3.5 py-2 sm:py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500">
                    </div>
                </div>

                <!-- Alamat Lengkap Toko -->
                <div>
                    <label for="address" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Alamat Lengkap Toko (Untuk Kop Surat Laporan)
                    </label>
                    <textarea id="address" name="address" rows="2"
                              class="w-full px-3.5 py-2 sm:py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500"><?= h($storeSettings['address']) ?></textarea>
                </div>

                <div class="grid sm:grid-cols-3 gap-3.5 sm:gap-4">
                    <!-- WhatsApp -->
                    <div>
                        <label for="whatsapp" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Nomor WhatsApp Resmi
                        </label>
                        <input type="text" id="whatsapp" name="whatsapp"
                               value="<?= h($storeSettings['whatsapp']) ?>"
                               placeholder="Contoh: 6281234567890"
                               class="w-full px-3.5 py-2 sm:py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500">
                    </div>

                    <!-- Telepon -->
                    <div>
                        <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Nomor Telepon Kantor
                        </label>
                        <input type="text" id="phone" name="phone"
                               value="<?= h($storeSettings['phone']) ?>"
                               placeholder="Contoh: (021) 123-4567"
                               class="w-full px-3.5 py-2 sm:py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500">
                    </div>

                    <!-- Email Resmi -->
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Email Resmi Toko
                        </label>
                        <input type="email" id="email" name="email"
                               value="<?= h($storeSettings['email']) ?>"
                               placeholder="info@toko.id"
                               class="w-full px-3.5 py-2 sm:py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500">
                    </div>
                </div>

                <!-- Upload Logo Toko -->
                <div class="p-3.5 sm:p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                Logo Toko (Format PNG, JPG, atau WEBP — Maks. 3 MB)
                            </label>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Logo akan otomatis muncul di bagian Header situs dan Kop Surat cetak.</p>
                        </div>
                        <?php if (!empty($storeSettings['logo'])): ?>
                        <div class="flex items-center gap-2.5">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Logo Aktif:</span>
                            <img src="../<?= h($storeSettings['logo']) ?>" alt="Logo Toko" class="w-10 h-10 object-contain rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-white/5 p-1 shadow-xs">
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-3">
                        <input type="file" name="logo_file" accept=".png,.jpg,.jpeg,.webp"
                               class="w-full text-xs text-slate-600 dark:text-slate-300 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-500/10 file:text-teal-700 dark:file:bg-cyan-500/20 dark:file:text-cyan-300 hover:file:bg-teal-500/20 dark:hover:file:bg-cyan-500/30 cursor-pointer">

                        <?php if (!empty($storeSettings['logo'])): ?>
                        <label class="flex items-center gap-2 text-xs text-rose-500 cursor-pointer whitespace-nowrap">
                            <input type="checkbox" name="delete_logo" value="1" class="rounded border-slate-300 dark:border-slate-700 text-rose-500 focus:ring-0">
                            <span>Hapus Logo Ini</span>
                        </label>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-right pt-1">
                    <button type="submit" name="save_store_settings"
                            class="inline-flex items-center gap-2 px-6 py-2.5 sm:py-3 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 dark:hover:from-teal-700 dark:hover:to-cyan-700 text-white font-extrabold text-xs uppercase tracking-wider shadow-md transition">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                        <span>Simpan Perubahan Identitas Toko</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Dua Kolom: Preview Ranking & Log Terkini -->
        <div class="grid lg:grid-cols-2 gap-8">

            <!-- Kolom Kiri: Preview Ranking SAW -->
            <div class="bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-2xl p-4 sm:p-6 shadow-sm dark:shadow-xl space-y-4 transition-colors">
                <!-- Petunjuk Geser Khusus Tampilan Mobile -->
                <div class="flex items-center justify-end md:hidden">
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Geser ke samping untuk detail →</span>
                </div>

                <div class="w-full overflow-x-auto pb-1">
                    <div class="min-w-[420px] sm:min-w-0 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 gap-2">
                            <div>
                                <h2 class="font-serif text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-teal-500/10 text-teal-600 dark:text-cyan-400 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                    </div>
                                    <span>Preview Ranking SAW 8 Kriteria</span>
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Hasil peringkat descending berdasarkan nilai preferensi Vi.</p>
                            </div>
                            <a href="raket.php" class="text-xs text-teal-600 dark:text-cyan-400 hover:underline font-bold whitespace-nowrap">Kelola Raket →</a>
                        </div>

                        <?php if ($sawPreview['success'] && !empty($rankingOrdered)): ?>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            <?php 
                            $previewCount = 0;
                            foreach ($rankingOrdered as $rid => $rd):
                                if ($previewCount >= 6) break;
                                $rkt  = $sawPreview['rackets'][$rid]['racket'];
                                $rank = $rd['rank'];
                                $vi   = $rd['score'];
                                $pct  = round($vi * 100, 2);
                                $previewCount++;
                            ?>
                            <div class="py-3 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-black <?= $rank === 1 ? 'bg-amber-500 text-slate-950' : ($rank === 2 ? 'bg-slate-300 text-slate-900' : ($rank === 3 ? 'bg-amber-700 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400')) ?>">
                                        <?= $rank ?>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-slate-900 dark:text-white truncate"><?= h($rkt['name']) ?></div>
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400"><?= h($rkt['brand']) ?> • <?= formatRupiah($rkt['price']) ?></div>
                                    </div>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-serif font-black text-teal-600 dark:text-cyan-400"><?= $pct ?>%</div>
                                    <div class="text-[10px] text-slate-400">Vi: <?= number_format($vi, 4) ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="py-12 text-center text-slate-400 text-xs">
                            Perhitungan belum aktif. Pastikan total bobot = 1.00 dan minimal 2 raket tersedia.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Log Riwayat Konsultasi Terkini -->
            <div class="bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-2xl p-4 sm:p-6 shadow-sm dark:shadow-xl space-y-4 transition-colors">
                <!-- Petunjuk Geser Khusus Tampilan Mobile -->
                <div class="flex items-center justify-end md:hidden">
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Geser ke samping untuk detail →</span>
                </div>

                <div class="w-full overflow-x-auto pb-1">
                    <div class="min-w-[420px] sm:min-w-0 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 gap-2">
                            <div>
                                <h2 class="font-serif text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-teal-500/10 text-teal-600 dark:text-cyan-400 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <span>Aktivitas Konsultasi Terkini</span>
                                </h2>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Riwayat simulasi preferensi pengunjung toko.</p>
                            </div>
                            <span class="text-xs text-slate-400 whitespace-nowrap"><?= count($recentLogs) ?> Data Terbaru</span>
                        </div>

                        <?php if (!empty($recentLogs)): ?>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                            <?php foreach ($recentLogs as $log): ?>
                            <div class="py-3 flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                        <?= h($log['visitor_name'] ?: 'Pengunjung Publik') ?>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                        Rekomendasi: <span class="text-teal-600 dark:text-teal-300 font-semibold"><?= h($log['result_top_racket'] ?? '-') ?></span>
                                        <?php if (!empty($log['max_budget'])): ?>
                                        • Budget: <?= formatRupiah($log['max_budget']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-right text-[11px] text-slate-400 flex-shrink-0">
                                    <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="py-12 text-center text-slate-400 text-xs">
                            Belum ada riwayat konsultasi pengunjung yang tercatat.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Footer Admin -->
    <footer class="border-t border-slate-200 dark:border-slate-800 py-6 text-center text-xs text-slate-500 mt-auto transition-colors">
        &copy; <?= date('Y') ?> <?= h($storeSettings['store_name']) ?> — Sistem Pendukung Keputusan Pemilihan Raket Bulu Tangkis.
    </footer>

    <!-- Dual-Theme Switcher & Mobile Drawer Script -->
    <script>
        // 1. Mobile Menu Drawer
        const adminMobileMenuBtn = document.getElementById('adminMobileMenuBtn');
        const adminMobileDrawer  = document.getElementById('adminMobileDrawer');
        if (adminMobileMenuBtn && adminMobileDrawer) {
            adminMobileMenuBtn.addEventListener('click', () => {
                adminMobileDrawer.classList.toggle('hidden');
            });
        }

        // 2. Dual-Theme Toggle
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
