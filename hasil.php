<?php
/**
 * ============================================================
 *  hasil.php — Hasil Rekomendasi SAW 8 Kriteria
 *  Toko Olahraga: Muara Jaya Sport
 *  Design System: Studio-Grade Tailwind CSS Dual-Theme
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/functions.php';

$storeSettings = getStoreSettings($pdo);

// Validasi request method POST (atau ambil dari session jika direct print/back)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && empty($_SESSION['last_result'])) {
    header('Location: index.php#konsultasi');
    exit;
}

// Tangkap input dari form index.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitorName = trim(strip_tags($_POST['visitor_name'] ?? '')) ?: 'Pengunjung';
    $rawBudget   = $_POST['max_budget'] ?? '';
    $stylePref   = trim(strip_tags($_POST['playing_style'] ?? ''));
    $flexPref    = trim(strip_tags($_POST['flexibility_pref'] ?? ''));

    $maxBudget = (is_numeric($rawBudget) && (float)$rawBudget > 0) ? (float)$rawBudget : null;

    // Eksekusi SAW engine dengan preferensi dinamis
    $saw = runSAW($pdo, $maxBudget, null, $stylePref, $flexPref);

    // Ambil raket pemenang
    $winnerRacket = null;
    $winnerScore  = 0.0;
    if ($saw['success'] && !empty($saw['ranking'])) {
        reset($saw['ranking']);
        $topId = key($saw['ranking']);
        $winnerRacket = $saw['rackets'][$topId]['racket'];
        $winnerScore  = (float)$saw['ranking'][$topId]['score'];

        // Catat ke log konsultasi
        logConsultation($pdo, [
            'visitor_name'      => $visitorName,
            'max_budget'        => $maxBudget,
            'playing_style'     => $stylePref ?: null,
            'flexibility_pref'  => $flexPref ?: null,
            'result_top_racket' => $winnerRacket['name'],
            'result_top_score'  => $winnerScore,
        ]);

        // Simpan ke session untuk modul cetak laporan
        $_SESSION['last_result'] = [
            'visitor_name' => $visitorName,
            'max_budget'   => $maxBudget,
            'playing_style'=> $stylePref,
            'flex_pref'    => $flexPref,
            'time'         => date('d F Y, H:i'),
            'saw'          => $saw,
            'winner'       => $winnerRacket,
            'winner_score' => $winnerScore,
        ];
    }
} else {
    // Muat dari session
    $saved = $_SESSION['last_result'];
    $visitorName  = $saved['visitor_name'];
    $maxBudget    = $saved['max_budget'];
    $stylePref    = $saved['playing_style'];
    $flexPref     = $saved['flex_pref'];
    $saw          = $saved['saw'];
    $winnerRacket = $saved['winner'];
    $winnerScore  = $saved['winner_score'];
}

$isAdmin = isset($_SESSION['admin']) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);
$totalKriteria = !empty($saw['criteria']) ? count($saw['criteria']) : (int)$pdo->query("SELECT COUNT(*) FROM criteria")->fetchColumn();

// Format nomor WhatsApp toko standar internasional (62)
$rawWa   = $storeSettings['whatsapp'] ?? '087706335584';
$cleanWa = preg_replace('/[^0-9]/', '', $rawWa);
if (str_starts_with($cleanWa, '0')) {
    $cleanWa = '62' . substr($cleanWa, 1);
} elseif (!str_starts_with($cleanWa, '62')) {
    $cleanWa = '62' . $cleanWa;
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hasil Rekomendasi Raket SAW <?= $totalKriteria ?> Kriteria — <?= h($storeSettings['store_name']) ?>">
    <title>Hasil Rekomendasi Raket — <?= h($storeSettings['store_name']) ?></title>

    <!-- Google Fonts: Playfair Display & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600;1,700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

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
                        warm: { base: '#FBF9F5', card: '#FFFFFF', border: '#EAE5D9' },
                        dark: { base: '#0B0F17', surface: '#131C2E', card: '#162238' },
                    }
                }
            }
        }
    </script>

    <!-- Theme Persist Script -->
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
            --card-border: rgba(15, 23, 42, 0.08);
        }
        html.dark {
            --bg-canvas: #0B0F17;
            --text-heading: #F8FAFC;
            --text-body: #94A3B8;
            --card-border: rgba(56, 189, 248, 0.16);
        }
        html, body {
            overflow-x: hidden;
            max-width: 100%;
        }
        body {
            background-color: var(--bg-canvas);
            color: var(--text-body);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .font-serif-title {
            font-family: 'Playfair Display', Georgia, serif;
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
        }
        html.dark .glass-panel {
            background: rgba(19, 28, 46, 0.8);
            border: 1px solid rgba(56, 189, 248, 0.18);
        }
        .gold-winner-border {
            border: 2px solid #D97706;
            box-shadow: 0 0 35px rgba(217, 119, 6, 0.25);
        }
        html.dark .gold-winner-border {
            border: 2px solid #00F0FF;
            box-shadow: 0 0 40px rgba(0, 240, 255, 0.3);
        }
        @media print {
            header, footer, #themeToggleBtn, a[href*="wa.me"], .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #0f172a !important;
            }
            .glass-panel {
                background: #ffffff !important;
                border: 1px solid #cbd5e1 !important;
                box-shadow: none !important;
            }
        }
    </style>

    <!-- Custom Corporate Layout Fixes -->
    <link rel="stylesheet" href="assets/css/custom-fix.css">
</head>

<body class="min-h-screen antialiased pt-16">

    <!-- ══════════════════════════════════════════════════════════════
         NAVBAR FIXED GLASSMORPHISM DUAL-THEME (PERMANENT FIXED TOP)
         ══════════════════════════════════════════════════════════════ -->
    <header class="fixed top-0 left-0 right-0 w-full z-50 bg-white/95 dark:bg-[#0B0F17]/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 transition-colors duration-300 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 min-w-0">
                <a href="index.php" class="flex items-center gap-2.5 sm:gap-3 group min-w-0 shrink">
                    <?php if (!empty($storeSettings['logo'])): ?>
                        <img src="<?= h($storeSettings['logo']) ?>" alt="Logo" class="w-8 h-8 sm:w-10 sm:h-10 object-contain rounded-xl group-hover:scale-105 transition-transform duration-200 shrink-0">
                    <?php else: ?>
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-tr from-teal-600 to-cyan-500 flex items-center justify-center text-white text-xs sm:text-sm font-bold shadow-sm shrink-0">
                            MJ
                        </div>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <span class="font-serif-title font-bold text-sm sm:text-base lg:text-lg text-slate-900 dark:text-white block whitespace-nowrap overflow-hidden text-ellipsis"><?= h($storeSettings['store_name']) ?></span>
                        <span class="hidden sm:block text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 truncate"><?= h($storeSettings['store_tagline'] ?: 'Hasil Rekomendasi SAW ' . $totalKriteria . ' Kriteria') ?></span>
                    </div>
                </a>

                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Theme Toggle -->
                    <button id="themeToggleBtn" type="button" aria-label="Ganti Tema" class="p-2 sm:p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-teal-500 dark:hover:border-cyan-400 transition">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>

                    <div class="hidden md:flex items-center gap-2">
                        <a href="metode.php" class="px-3 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-teal-600 dark:hover:text-cyan-400 transition">
                            Metode SAW
                        </a>
                        <a href="index.php#konsultasi" class="px-3 sm:px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-teal-600 dark:hover:text-cyan-400 font-bold text-xs transition">
                            ← Hitung Ulang
                        </a>
                        <a href="cetak_laporan.php" target="_blank" class="px-3 sm:px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow transition flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            <span>Cetak Laporan</span>
                        </a>
                    </div>

                    <!-- Mobile Hamburger Toggle -->
                    <button id="mobileMenuBtnHasil" type="button" aria-label="Buka Menu" class="md:hidden p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-teal-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Drawer Dropdown -->
        <div id="mobileMenuDrawerHasil" class="hidden md:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0B0F17] px-4 py-3 space-y-2">
            <a href="metode.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                Metode SAW
            </a>
            <a href="index.php#konsultasi" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">
                ← Hitung Ulang
            </a>
            <a href="cetak_laporan.php" target="_blank" class="block px-3 py-2 rounded-lg text-sm font-semibold text-teal-600 dark:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                Cetak Laporan
            </a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

        <!-- Header Hasil -->
        <div class="text-center max-w-2xl mx-auto">
            <span class="inline-block px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-teal-500/10 text-teal-700 dark:text-cyan-400 border border-teal-500/20 mb-3">
                Laporan Keputusan Otomatis
            </span>
            <h1 class="font-serif-title text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white">
                Hasil Analisis SPK Rekomendasi Raket
            </h1>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-2">
                Analisis preferensi untuk <strong><?= h($visitorName) ?></strong>
                <?= $maxBudget ? ' | Anggaran: <strong>' . formatRupiah($maxBudget) . '</strong>' : ' | Anggaran: <strong>Semua Rentang</strong>' ?>
            </p>
        </div>

        <!-- ══════════════════════════════════════════════════════════════
             PARAMETER PREFERENSI AKTIF & MODEL BOBOT DINAMIS
             ══════════════════════════════════════════════════════════════ -->
        <div class="glass-panel rounded-2xl p-4 sm:p-5 border flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Parameter Terpilih:</span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-teal-500/15 text-teal-700 dark:text-cyan-300 border border-teal-500/30">
                    Gaya: <?= h($stylePref ?: 'All-Around (Umum)') ?>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-teal-500/15 text-teal-700 dark:text-cyan-300 border border-teal-500/30">
                    Flex: <?= h($flexPref ?: 'Semua Tingkat') ?>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold bg-teal-500/15 text-teal-700 dark:text-cyan-300 border border-teal-500/30">
                    Anggaran: <?= $maxBudget ? formatRupiah($maxBudget) : 'Tanpa Batasan' ?>
                </span>
            </div>
            <div class="text-xs font-bold text-slate-600 dark:text-slate-300">
                Model Pembobotan: <span class="text-teal-700 dark:text-cyan-400 font-extrabold"><?= h($saw['weightModelDesc'] ?? 'Standar Objektif') ?></span>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════
             FALLBACK / EMPTY NOTIFICATION BOX
             ══════════════════════════════════════════════════════════════ -->
        <?php if (!$saw['success'] || empty($saw['ranking'])): ?>
        <div class="glass-panel rounded-3xl p-8 sm:p-12 text-center border border-amber-500/30 space-y-4 max-w-2xl mx-auto shadow-xl">
            <div class="icon-box-md w-14 h-14 mx-auto rounded-2xl bg-amber-500/10 text-amber-600 dark:text-cyan-400 border border-amber-500/20">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <h3 class="font-serif-title text-2xl font-bold text-slate-900 dark:text-white">
                <?= h($saw['message'] ?? 'Tidak ada data raket yang tersedia untuk dievaluasi.') ?>
            </h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                Silakan naikkan batas anggaran Anda atau sesuaikan preferensi bermain pada formulir kalkulator rekomendasi.
            </p>
            <div class="pt-4">
                <a href="index.php#konsultasi" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 dark:bg-cyan-500 dark:hover:bg-cyan-400 text-white dark:text-slate-950 font-bold text-xs shadow-lg transition">
                    <span>← Kembali ke Formulir Kalkulator</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($saw['isFallback']) && $saw['isFallback'] === true): ?>
        <div class="p-5 rounded-2xl bg-amber-500/10 border-2 border-amber-500/40 text-amber-800 dark:text-amber-300 flex items-start gap-3.5 shadow-lg">
            <div class="icon-box-md bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h4 class="font-bold text-sm uppercase tracking-wide">Pemberitahuan Penyesuaian Anggaran (Fallback Logic)</h4>
                <p class="text-xs sm:text-sm mt-1 leading-relaxed">
                    <?= h($saw['fallbackMsg']) ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════════════
             PODIUM TOP 3 REKOMENDASI RAKET TERBAIK & RATIONALE
             ══════════════════════════════════════════════════════════════ -->
        <?php
        $top3Rackets = [];
        $topCounter = 0;
        if ($saw['success'] && !empty($saw['ranking'])) {
            foreach ($saw['ranking'] as $rid => $rk) {
                if ($topCounter >= 3) break;
                $top3Rackets[] = [
                    'rank'   => $rk['rank'],
                    'score'  => (float)$rk['score'],
                    'racket' => $saw['rackets'][$rid]['racket'],
                ];
                $topCounter++;
            }
        }
        ?>

        <?php if (!empty($top3Rackets)): ?>
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-amber-500/15 text-amber-700 dark:text-cyan-400 border border-amber-500/30">
                        <svg class="w-3.5 h-3.5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        Podium Rekomendasi Teratas
                    </span>
                    <h2 class="font-serif-title text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-1">
                        Top 3 Raket Terbaik Sesuai Kebutuhan Anda
                    </h2>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Kalkulasi SAW Dinamis Berdasarkan Gaya, Kelenturan & Anggaran
                </p>
            </div>

            <!-- Grid 3 Kolom Podium -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
                <?php foreach ($top3Rackets as $item):
                    $rkt      = $item['racket'];
                    $rkRank   = $item['rank'];
                    $rkScore  = $item['score'];
                    $rRawGbr  = !empty($rkt['gambar']) ? $rkt['gambar'] : ($rkt['image'] ?? '');
                    $rIsPdf   = !empty($rRawGbr) && (str_ends_with(strtolower($rRawGbr), '.pdf') || str_starts_with($rRawGbr, 'data:application/pdf'));
                    $rImgSrc  = '';
                    if (!empty($rRawGbr) && !$rIsPdf) {
                        if (str_starts_with($rRawGbr, 'data:image/') || str_starts_with($rRawGbr, 'http://') || str_starts_with($rRawGbr, 'https://') || str_starts_with($rRawGbr, 'admin/uploads/') || str_starts_with($rRawGbr, 'assets/')) {
                            $rImgSrc = $rRawGbr;
                        } else {
                            $rImgSrc = 'admin/uploads/' . ltrim($rRawGbr, '/');
                        }
                    }

                    // Desain visual per podium rank
                    $cardBorder = match($rkRank) {
                        1 => 'border-2 border-amber-500/80 dark:border-cyan-400 shadow-xl',
                        2 => 'border border-slate-300 dark:border-slate-600 shadow-md',
                        3 => 'border border-amber-700/50 dark:border-amber-600/50 shadow-md',
                        default => 'border border-slate-200 dark:border-slate-800',
                    };
                    $badgeStyle = match($rkRank) {
                        1 => 'bg-amber-500 text-slate-950 font-black',
                        2 => 'bg-slate-300 dark:bg-slate-600 text-slate-900 dark:text-white font-extrabold',
                        3 => 'bg-amber-700 dark:bg-amber-800 text-white font-extrabold',
                        default => 'bg-slate-200 text-slate-700',
                    };
                    $rankTitle = match($rkRank) {
                        1 => 'Peringkat #1 Rekomendasi',
                        2 => 'Peringkat #2 Rekomendasi',
                        3 => 'Peringkat #3 Rekomendasi',
                        default => '#' . $rkRank,
                    };
                ?>
                <div class="glass-panel rounded-3xl overflow-hidden <?= $cardBorder ?> flex flex-col justify-between transition-transform duration-300 hover:-translate-y-1 group">
                    <div>
                        <!-- Header Kartu: Peringkat & Skor Vi -->
                        <div class="p-4 sm:p-5 border-b border-slate-200/80 dark:border-slate-800/80 flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs <?= $badgeStyle ?>">
                                <?= $rankTitle ?>
                            </span>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase tracking-wider">Skor SAW</span>
                                <span class="font-serif-title font-black text-lg <?= $rkRank === 1 ? 'text-amber-600 dark:text-cyan-400' : 'text-slate-800 dark:text-slate-200' ?>">
                                    <?= number_format($rkScore * 100, 2) ?>%
                                </span>
                            </div>
                        </div>

                        <!-- Wadah Gambar Terbingkai Full Landscape (Anti-Ruang Kosong Aneh) -->
                        <div class="w-full h-44 overflow-hidden bg-slate-900 border-b border-slate-200 dark:border-slate-800 relative">
                            <?php if (!empty($rRawGbr) && $rIsPdf): ?>
                                <div class="p-3 text-center flex flex-col items-center justify-center h-full">
                                    <div class="icon-box-md mb-1.5 text-slate-300">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold text-cyan-300">Brosur Turnamen PDF</span>
                                    <a href="<?= h($rRawGbr) ?>" target="_blank" class="mt-1.5 inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-teal-600 dark:bg-cyan-600 text-white font-bold text-[11px] shadow hover:scale-105 transition">
                                        Buka Dokumen PDF ↗
                                    </a>
                                </div>
                            <?php else: ?>
                                <img src="<?= !empty($rImgSrc) ? htmlspecialchars($rImgSrc) : 'assets/images/fallback-racket.svg' ?>" 
                                     alt="<?= h($rkt['name']) ?>" 
                                     class="w-full h-full object-cover object-[center_22%] transition-transform duration-300 group-hover:scale-105"
                                     <?= ($rkRank === 1) ? 'fetchpriority="high" decoding="async"' : 'loading="lazy" decoding="async"' ?>
                                     onerror="this.onerror=null; this.src='assets/images/fallback-racket.svg';">
                            <?php endif; ?>
                        </div>

                        <!-- Detail Identitas Raket -->
                        <div class="p-5 space-y-4">
                            <div>
                                <span class="text-[11px] font-bold uppercase tracking-widest text-teal-700 dark:text-cyan-400">
                                    <?= h($rkt['brand']) ?>
                                </span>
                                <h3 class="font-serif-title text-xl font-extrabold text-slate-900 dark:text-white mt-0.5 leading-snug">
                                    <?= h($rkt['name']) ?>
                                </h3>
                                <p class="font-sans text-xl font-black text-teal-700 dark:text-cyan-300 mt-1">
                                    <?= formatRupiah($rkt['price']) ?>
                                </p>
                            </div>

                            <!-- 4 Spesifikasi Teknis Pilar -->
                            <div class="grid grid-cols-2 gap-2 text-center text-xs">
                                <div class="p-2 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Berat</span>
                                    <span class="font-extrabold text-slate-900 dark:text-white"><?= h($rkt['weight_spec'] ?? '-') ?></span>
                                </div>
                                <div class="p-2 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Flex Shaft</span>
                                    <span class="font-extrabold text-slate-900 dark:text-white"><?= h($rkt['flex_spec'] ?? '-') ?></span>
                                </div>
                                <div class="p-2 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Tension</span>
                                    <span class="font-extrabold text-slate-900 dark:text-white"><?= h($rkt['tension_spec'] ?? '-') ?></span>
                                </div>
                                <div class="p-2 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
                                    <span class="text-[10px] text-slate-400 block font-bold uppercase">Balance</span>
                                    <span class="font-extrabold text-slate-900 dark:text-white"><?= h($rkt['balance_spec'] ?? '-') ?></span>
                                </div>
                            </div>

                            <!-- Rationale Explanation (Penjelasan Alasan Pemilihan) -->
                            <div class="p-4 rounded-2xl bg-teal-500/5 dark:bg-slate-900/80 border border-teal-500/20 dark:border-slate-800 text-xs text-slate-700 dark:text-slate-300 leading-relaxed space-y-1">
                                <div class="flex items-center gap-1.5 font-bold text-teal-700 dark:text-cyan-400 uppercase tracking-wider text-[10px]">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                    <span>Alasan Rekomendasi (Rationale):</span>
                                </div>
                                <div class="text-[11px] text-slate-600 dark:text-slate-300 leading-relaxed">
                                    <?= generateRationale($rkt, $stylePref, $flexPref, $maxBudget, $rkRank) ?>
                                </div>
                            </div>

                            <!-- WhatsApp CTA Button Interaktif -->
                            <?php
                            $waScorePct  = number_format($rkScore * 100, 2) . '%';
                            $waBudgetFmt = $maxBudget ? formatRupiah($maxBudget) : 'fleksibel';
                            $waStoreName = $storeSettings['store_name'] ?? 'Muara Jaya Sport';
                            $waMsgText   = "Halo {$waStoreName}, berdasarkan hasil SPK SAW saya direkomendasikan raket {$rkt['name']} (Skor {$waScorePct}) dengan budget {$waBudgetFmt}. Apakah stoknya tersedia?";
                            $waUrl       = "https://wa.me/{$cleanWa}?text=" . rawurlencode($waMsgText);
                            ?>
                            <div class="pt-1">
                                <a href="<?= $waUrl ?>" target="_blank" rel="noopener noreferrer"
                                   class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-sm hover:shadow-md transition">
                                    <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24">
                                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.062-2.149-.538-1.748-.72-2.887-2.496-2.974-2.611-.088-.116-.708-.941-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.288.043.088.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86.174.086.275.073.376-.044.101-.116.433-.506.549-.68.116-.173.231-.144.39-.086s1.011.477 1.184.564.289.13.332.203c.043.072.043.419-.101.824z"/>
                                    </svg>
                                    <span>Tanya Stok via WhatsApp</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($saw['success'] && !empty($saw['ranking'])): ?>
        <!-- ══════════════════════════════════════════════════════════════
             TABEL PERINGKAT LENGKAP & RESPONSIF
             ══════════════════════════════════════════════════════════════ -->
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h3 class="font-serif-title text-2xl font-bold text-slate-900 dark:text-white">
                    Tabel Peringkat Rekomendasi Lengkap
                </h3>
                <div class="flex items-center gap-3">
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 md:hidden font-medium">← Geser tabel ke samping →</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 hidden sm:inline">Diurutkan Berdasarkan Nilai Preferensi (Vi) Tertinggi</span>
                </div>
            </div>

            <div class="glass-panel rounded-3xl overflow-hidden shadow-lg border">
                <div class="w-full overflow-x-auto text-left">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-100/90 dark:bg-slate-800/90 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="py-4 px-4 text-center">Rank</th>
                                <th class="py-4 px-4 text-center">Visual</th>
                                <th class="py-4 px-5 sticky left-0 bg-slate-100 dark:bg-slate-800 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[150px]">Nama Raket</th>
                                <th class="py-4 px-5">Brand</th>
                                <th class="py-4 px-5">Harga (C1)</th>
                                <th class="py-4 px-5">Berat (C2)</th>
                                <th class="py-4 px-5">Flex (C3)</th>
                                <th class="py-4 px-5">Balance (C4)</th>
                                <th class="py-4 px-5 text-center">Skor Akhir (Vi)</th>
                                <th class="py-4 px-5 text-center">Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60 dark:divide-slate-800/60">
                            <?php if ($saw['success'] && !empty($saw['ranking'])): ?>
                                <?php foreach ($saw['ranking'] as $rid => $rankData):
                                    $rkt = $saw['rackets'][$rid]['racket'];
                                    $isTop = $rankData['rank'] === 1;
                                    $tRawGbr = !empty($rkt['gambar']) ? $rkt['gambar'] : ($rkt['image'] ?? '');
                                    $tIsPdf  = !empty($tRawGbr) && (str_ends_with(strtolower($tRawGbr), '.pdf') || str_starts_with($tRawGbr, 'data:application/pdf'));
                                    $tImgSrc = '';
                                    if (!empty($tRawGbr) && !$tIsPdf) {
                                        if (str_starts_with($tRawGbr, 'data:image/') || str_starts_with($tRawGbr, 'http://') || str_starts_with($tRawGbr, 'https://') || str_starts_with($tRawGbr, 'admin/uploads/') || str_starts_with($tRawGbr, 'assets/')) {
                                            $tImgSrc = $tRawGbr;
                                        } else {
                                            $tImgSrc = 'admin/uploads/' . ltrim($tRawGbr, '/');
                                        }
                                    }
                                ?>
                                <tr class="hover:bg-teal-50/40 dark:hover:bg-slate-800/40 transition <?= $isTop ? 'bg-amber-500/5 dark:bg-cyan-500/5 font-semibold' : '' ?>">
                                    <!-- Rank Badge -->
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-black <?= $rankData['rank'] === 1 ? 'bg-amber-500 text-white' : ($rankData['rank'] === 2 ? 'bg-slate-300 dark:bg-slate-700 text-slate-900 dark:text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300') ?>">
                                            <?= $rankData['rank'] ?>
                                        </span>
                                    </td>
                                    <!-- Visual -->
                                    <td class="py-4 px-4 text-center">
                                        <?php if (!empty($tRawGbr) && $tIsPdf): ?>
                                            <a href="<?= h($tRawGbr) ?>" target="_blank" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-rose-500/20 text-rose-300 text-[10px] font-black border border-rose-500/30">
                                                PDF
                                            </a>
                                        <?php else: ?>
                                            <img src="<?= !empty($tImgSrc) ? htmlspecialchars($tImgSrc) : 'assets/images/fallback-racket.svg' ?>" 
                                                 alt="<?= h($rkt['name']) ?>" 
                                                 class="w-9 h-9 rounded-lg object-cover mx-auto border border-slate-200 dark:border-slate-700"
                                                 onerror="this.onerror=null; this.src='assets/images/fallback-racket.svg';">
                                        <?php endif; ?>
                                    </td>
                                    <!-- Nama (Sticky Column) -->
                                    <td class="py-4 px-5 font-bold text-slate-900 dark:text-white sticky left-0 bg-white dark:bg-[#131C2E] z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[150px]">
                                        <?= h($rkt['name']) ?>
                                    </td>
                                    <!-- Brand -->
                                    <td class="py-4 px-5 text-xs text-slate-600 dark:text-slate-400 uppercase font-semibold">
                                        <?= h($rkt['brand']) ?>
                                    </td>
                                    <!-- Harga -->
                                    <td class="py-4 px-5 font-bold text-teal-700 dark:text-cyan-400">
                                        <?= formatRupiah($rkt['price']) ?>
                                    </td>
                                    <!-- Berat -->
                                    <td class="py-4 px-5 text-xs text-slate-600 dark:text-slate-300">
                                        <?= h($rkt['weight_spec'] ?? '-') ?>
                                    </td>
                                    <!-- Flex -->
                                    <td class="py-4 px-5 text-xs text-slate-600 dark:text-slate-300">
                                        <?= h($rkt['flex_spec'] ?? '-') ?>
                                    </td>
                                    <!-- Balance -->
                                    <td class="py-4 px-5 text-xs text-slate-600 dark:text-slate-300">
                                        <?= h($rkt['balance_spec'] ?? '-') ?>
                                    </td>
                                    <!-- Skor Vi -->
                                    <td class="py-4 px-5 text-center font-serif-title font-black text-base text-slate-900 dark:text-white">
                                        <?= number_format($rankData['score'], 6) ?>
                                        <span class="block text-[10px] font-sans text-slate-400 font-medium">
                                            (<?= number_format($rankData['score'] * 100, 2) ?>%)
                                        </span>
                                    </td>
                                    <!-- Status Badge -->
                                    <td class="py-4 px-5 text-center">
                                        <?php if ($rankData['rank'] === 1): ?>
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-extrabold bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/40">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                Sangat Direkomendasikan
                                            </span>
                                        <?php elseif ($rankData['rank'] <= 3): ?>
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-teal-500/10 text-teal-700 dark:text-cyan-400 border border-teal-500/20">
                                                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-cyan-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Pilihan Unggul
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                                Alternatif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════
             TRANSPARANSI DETAIL PERHITUNGAN MATEMATIS (ACCORDION)
             ══════════════════════════════════════════════════════════════ -->
        <div class="glass-panel rounded-3xl p-6 sm:p-8 border space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div>
                    <h3 class="font-serif-title text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-teal-600 dark:text-cyan-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                        Transparansi Detail Matriks & Normalisasi SAW
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Rincian nilai asli, proses normalisasi matriks (R), hingga perkalian bobot (W) per kriteria.</p>
                </div>
                <button type="button" id="toggleMathBtn" class="px-5 py-2.5 rounded-xl bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold text-xs transition">
                    Buka / Tutup Detail Matriks (Audit)
                </button>
            </div>

            <div id="mathContainer" class="space-y-8 hidden">

                <!-- 1. Matriks Keputusan Asli (X) -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-cyan-400">
                            1. Matriks Keputusan Asli (X)
                        </h4>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500 md:hidden font-medium">← Geser tabel ke samping →</span>
                    </div>
                    <div class="w-full overflow-x-auto text-left rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
                        <table class="w-full text-xs text-left whitespace-nowrap">
                            <thead class="bg-slate-100 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300">
                                <tr>
                                    <th class="p-3 sticky left-0 bg-slate-100 dark:bg-slate-800 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[150px]">Alternatif Raket</th>
                                    <?php foreach ($saw['criteria'] as $c): ?>
                                    <th class="p-3 text-center">
                                        <?= h($c['code']) ?> (<?= strtoupper($c['type']) ?>)
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <?php foreach ($saw['matrix'] as $rid => $scores): ?>
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="p-3 font-semibold text-slate-900 dark:text-slate-100 sticky left-0 bg-white dark:bg-[#111827] z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[150px]">
                                        <?= h($saw['rackets'][$rid]['racket']['name']) ?>
                                    </td>
                                    <?php foreach ($saw['criteria'] as $c):
                                        $val = $scores[$c['id']] ?? 0;
                                    ?>
                                    <td class="p-3 text-center">
                                        <?= ($c['id'] == 1) ? formatRupiah($val) : $val ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Nilai Min / Max per Kriteria -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-cyan-400">
                        2. Nilai Ekstremum (Min & Max per Kriteria)
                    </h4>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-xs text-left whitespace-nowrap">
                            <thead class="bg-slate-100 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300">
                                <tr>
                                    <th class="p-3">Ekstremum</th>
                                    <?php foreach ($saw['criteria'] as $c): ?>
                                    <th class="p-3 text-center"><?= h($c['code']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <tr>
                                    <td class="p-3 font-bold text-rose-500">Nilai Minimum (Min)</td>
                                    <?php foreach ($saw['criteria'] as $c): 
                                        $valMin = $saw['minMax'][$c['id']]['min'] ?? 0;
                                    ?>
                                    <td class="p-3 text-center"><?= ($c['id'] == 1) ? formatRupiah($valMin) : $valMin ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <td class="p-3 font-bold text-emerald-500">Nilai Maksimum (Max)</td>
                                    <?php foreach ($saw['criteria'] as $c): 
                                        $valMax = $saw['minMax'][$c['id']]['max'] ?? 0;
                                    ?>
                                    <td class="p-3 text-center"><?= ($c['id'] == 1) ? formatRupiah($valMax) : $valMax ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. Matriks Normalisasi (R) -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-cyan-400">
                            3. Matriks Ternormalisasi (R) — Benefit: X/Max | Cost: Min/X
                        </h4>
                        <span class="text-[11px] text-slate-400 dark:text-slate-500 md:hidden font-medium">← Geser tabel ke samping →</span>
                    </div>
                    <div class="w-full overflow-x-auto text-left rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">
                        <table class="w-full text-xs text-left whitespace-nowrap">
                            <thead class="bg-slate-100 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300">
                                <tr>
                                    <th class="p-3 sticky left-0 bg-slate-100 dark:bg-slate-800 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[150px]">Alternatif Raket</th>
                                    <?php foreach ($saw['criteria'] as $c): ?>
                                    <th class="p-3 text-center">R_<?= h($c['code']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <?php foreach ($saw['normalized'] as $rid => $norms): ?>
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="p-3 font-semibold text-slate-900 dark:text-slate-100 sticky left-0 bg-white dark:bg-[#111827] z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[150px]">
                                        <?= h($saw['rackets'][$rid]['racket']['name']) ?>
                                    </td>
                                    <?php foreach ($saw['criteria'] as $c): ?>
                                    <td class="p-3 text-center font-mono">
                                        <?= number_format($norms[$c['id']] ?? 0, 4) ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. Perkalian Bobot (W * R) & Nilai Akhir (Vi) -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-cyan-400">
                        4. Matriks Terbobot (W * R) & Akumulasi Skor Preferensi (Vi)
                    </h4>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-xs text-left whitespace-nowrap">
                            <thead class="bg-slate-100 dark:bg-slate-800 font-bold text-slate-700 dark:text-slate-300">
                                <tr>
                                    <th class="p-3">Alternatif Raket</th>
                                    <?php foreach ($saw['criteria'] as $c): ?>
                                    <th class="p-3 text-center">W * R_<?= h($c['code']) ?></th>
                                    <?php endforeach; ?>
                                    <th class="p-3 text-center text-teal-700 dark:text-cyan-400 font-black">Total Skor (Vi)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                <?php foreach ($saw['ranking'] as $rid => $rk): ?>
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="p-3 font-semibold text-slate-900 dark:text-slate-100 sticky left-0 bg-white dark:bg-[#111827] z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[150px]">
                                        <?= h($saw['rackets'][$rid]['racket']['name']) ?>
                                    </td>
                                    <?php foreach ($saw['criteria'] as $c): ?>
                                    <td class="p-3 text-center font-mono text-slate-500 dark:text-slate-400">
                                        <?= number_format($saw['weighted'][$rid][$c['id']] ?? 0, 4) ?>
                                    </td>
                                    <?php endforeach; ?>
                                    <td class="p-3 text-center font-mono font-bold text-teal-700 dark:text-cyan-300">
                                        <?= number_format($rk['score'], 6) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
        <?php endif; ?>

        <!-- Tombol Aksi Akhir -->
        <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
            <a href="index.php" class="px-6 py-3.5 rounded-2xl border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                ← Kembali ke Halaman Utama
            </a>
            <a href="cetak_laporan.php" target="_blank" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 text-white font-bold text-xs shadow-lg transition">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Dokumen Rekomendasi Resmi</span>
            </a>
        </div>
    </main>

    <!-- Footer Dinamis Toko -->
    <footer class="border-t border-slate-200 dark:border-slate-800 py-10 transition-colors mt-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <?php if (!empty($storeSettings['logo'])): ?>
                        <img src="<?= h($storeSettings['logo']) ?>" alt="Logo" class="w-10 h-10 object-contain rounded-xl">
                    <?php else: ?>
                        <div class="w-9 h-9 rounded-xl bg-slate-900 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-serif-title font-black text-sm text-teal-600 dark:text-cyan-400">
                            MJ
                        </div>
                    <?php endif; ?>
                    <div>
                        <span class="font-serif-title font-bold text-slate-900 dark:text-white block"><?= h($storeSettings['store_name']) ?></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400"><?= h($storeSettings['address']) ?></span>
                    </div>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 text-center md:text-right">
                    <div>Kontak: <strong><?= h($storeSettings['whatsapp'] ?: $storeSettings['phone']) ?></strong> • Email: <strong><?= h($storeSettings['email']) ?></strong></div>
                    <div class="mt-1">&copy; <?= date('Y') ?> <?= h($storeSettings['store_name']) ?>. Metode SAW <?= $totalKriteria ?> Kriteria.</div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Dual theme toggle
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        themeToggleBtn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('mjs_theme', isDark ? 'dark' : 'light');
        });

        // Mobile drawer toggle
        const mobileMenuBtnHasil = document.getElementById('mobileMenuBtnHasil');
        const mobileMenuDrawerHasil = document.getElementById('mobileMenuDrawerHasil');
        if (mobileMenuBtnHasil && mobileMenuDrawerHasil) {
            mobileMenuBtnHasil.addEventListener('click', () => {
                mobileMenuDrawerHasil.classList.toggle('hidden');
            });
        }

        // Toggle math container accordion
        const toggleMathBtn = document.getElementById('toggleMathBtn');
        const mathContainer = document.getElementById('mathContainer');
        toggleMathBtn.addEventListener('click', () => {
            mathContainer.classList.toggle('hidden');
        });
    </script>
</body>
</html>
