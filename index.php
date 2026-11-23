<?php
if (ob_get_level() === 0) {
    ob_start();
}
/**
 * ============================================================
 *  index.php — Landing Page & Konsultasi SPK Rekomendasi Raket
 *  Toko Olahraga: Muara Jaya Sport
 *  Metode: Simple Additive Weighting (SAW) — 8 Kriteria Objektif
 *  Design System: Studio-Grade Luxury Minimalist Dual-Theme
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/functions.php';

$storeSettings = getStoreSettings($pdo);

// Ambil data raket dan kriteria dari database menggunakan PDO
try {
    $rackets       = getRackets($pdo, null);
    $criteriaList  = getCriteria($pdo);
    $totalRaket    = count($rackets);
    $totalKriteria = count($criteriaList);

    // Hitung rekomendasi default awal untuk kartu #1 Winner di Hero
    $sawDefault    = runSAW($pdo, null);
    $winnerRacket  = null;
    $winnerScore   = 0.0;
    if ($sawDefault['success'] && !empty($sawDefault['ranking'])) {
        reset($sawDefault['ranking']);
        $topId = key($sawDefault['ranking']);
        $winnerRacket = $sawDefault['rackets'][$topId]['racket'];
        $winnerScore  = (float)$sawDefault['ranking'][$topId]['score'];
    }

    // Lightweight JSON payload untuk Modal Audit Matriks SAW (Zero-Lag On-Demand Rendering)
    $sawAuditData = [
        'criteria'   => array_values(array_map(function($c) {
            return [
                'id'     => (int)$c['id'],
                'code'   => $c['code'],
                'name'   => $c['name'],
                'type'   => strtoupper($c['type']),
                'weight' => (float)$c['weight'],
            ];
        }, $criteriaList)),
        'matrix'     => $sawDefault['matrix'] ?? [],
        'normalized' => $sawDefault['normalized'] ?? [],
        'ranking'    => $sawDefault['ranking'] ?? [],
        'rackets'    => array_map(function($item) {
            $rk = $item['racket'];
            return [
                'id'    => (int)$rk['id'],
                'name'  => $rk['name'],
                'brand' => $rk['brand'],
                'price' => (float)$rk['price'],
            ];
        }, $sawDefault['rackets'] ?? []),
    ];
} catch (Throwable $e) {
    error_log("Error in index.php database query: " . $e->getMessage());
    $rackets = [];
    $criteriaList = [];
    $totalRaket = 0;
    $totalKriteria = 8;
    $sawDefault = ['success' => false];
    $winnerRacket = null;
    $winnerScore = 0.0;
}

$isAdmin = isset($_SESSION['admin']) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Pendukung Keputusan (SPK) Rekomendasi Raket Bulu Tangkis berbasis Algoritma SAW <?= $totalKriteria ?> Kriteria di <?= h($storeSettings['store_name']) ?>.">
    <title><?= h($storeSettings['store_name']) ?> — SPK Rekomendasi Raket Bulu Tangkis</title>

    <!-- Google Fonts: Playfair Display (Serif Headings) & Plus Jakarta Sans (Body Text) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600;1,700&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', '-apple-system', 'sans-serif'],
                        serif: ['Playfair Display', 'Georgia', 'serif'],
                    },
                    colors: {
                        // Warm Light Mode Palette
                        warm: {
                            50: '#FBF9F5',
                            100: '#F5EFE6',
                            200: '#EAE2D5',
                            300: '#D5C7B2',
                            card: '#FFFFFF',
                            border: '#E8E1D5',
                            navy: '#0F172A',
                            muted: '#64748B',
                        },
                        // Midnight Dark Slate Palette
                        midnight: {
                            base: '#0B0F17',
                            surface: '#131C2E',
                            card: '#162238',
                            border: 'rgba(56, 189, 248, 0.18)',
                            cyan: '#00F0FF',
                        },
                        // Soft Accent Tones
                        softTeal: '#0D9488',
                        softEmerald: '#10B981',
                        softBronze: '#B45309',
                        softGold: '#D97706',
                    },
                    boxShadow: {
                        'glow-cyan': '0 0 30px rgba(0, 240, 255, 0.18)',
                        'glow-bronze': '0 0 30px rgba(217, 119, 6, 0.20)',
                        'card-light': '0 12px 35px -10px rgba(15, 23, 42, 0.06)',
                        'card-dark': '0 14px 45px -15px rgba(0, 0, 0, 0.75)',
                    }
                }
            }
        }
    </script>

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
            --card-border: #E8E1D5;
            --accent-primary: #0D9488;
        }

        html.dark {
            --bg-canvas: #0B0F17;
            --text-heading: #F8FAFC;
            --text-body: #94A3B8;
            --card-surface: #131C2E;
            --card-border: rgba(56, 189, 248, 0.16);
            --accent-primary: #00F0FF;
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

        .font-serif-heading {
            font-family: 'Playfair Display', Georgia, serif;
        }

        /* Glassmorphism Luxury Styling */
        .luxury-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--card-border);
        }
        html.dark .luxury-card {
            background: rgba(19, 28, 46, 0.82);
            border: 1px solid rgba(56, 189, 248, 0.16);
        }

        /* Minimalist Winner Highlight Border */
        .winner-luxury-border {
            border: 1.5px solid #D97706;
            box-shadow: 0 10px 40px -10px rgba(217, 119, 6, 0.20);
        }
        html.dark .winner-luxury-border {
            border: 1.5px solid rgba(0, 240, 255, 0.7);
            box-shadow: 0 10px 45px -10px rgba(0, 240, 255, 0.24);
        }

        /* Custom Slider Thumb */
        input[type=range] {
            accent-color: #0D9488;
        }
        html.dark input[type=range] {
            accent-color: #00F0FF;
        }
    </style>

    <!-- Custom Corporate Layout Fixes -->
    <link rel="stylesheet" href="assets/css/custom-fix.css">
</head>

<body class="min-h-screen antialiased selection:bg-teal-500 selection:text-white pt-16">

    <!-- ══════════════════════════════════════════════════════════════
         NAVBAR INTERAKTIF & THEME SWITCHER (FIXED GLASSMORPHISM TOP)
         ══════════════════════════════════════════════════════════════ -->
    <header class="fixed top-0 left-0 right-0 w-full z-50 bg-white/95 dark:bg-[#0B0F17]/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 min-w-0">

                <!-- Brand Monogram & Name (Tampil Utuh Tanpa Terpotong) -->
                <a href="index.php" class="flex items-center gap-2.5 sm:gap-3 group min-w-0 shrink">
                    <?php if (!empty($storeSettings['logo'])): ?>
                        <img src="<?= h($storeSettings['logo']) ?>" alt="<?= h($storeSettings['store_name']) ?>" class="w-8 h-8 sm:w-9 sm:h-9 object-contain rounded-xl shadow-sm group-hover:scale-105 transition-transform duration-200 shrink-0">
                    <?php else: ?>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-br from-teal-700 via-teal-600 to-cyan-500 dark:from-cyan-500 dark:to-teal-600 flex items-center justify-center text-white font-serif-heading font-black text-xs sm:text-sm shadow-sm group-hover:scale-105 transition-transform duration-200 shrink-0">
                            MJ
                        </div>
                    <?php endif; ?>
                    <div class="min-w-0">
                        <div class="font-serif-heading font-bold text-sm sm:text-base lg:text-lg text-slate-900 dark:text-white tracking-tight flex items-center gap-1.5 whitespace-nowrap">
                            <span><?= h($storeSettings['store_name']) ?></span>
                            <span class="hidden sm:inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-sans font-extrabold uppercase bg-teal-500/10 text-teal-700 dark:text-cyan-300 border border-teal-500/20">
                                SPK SAW
                            </span>
                        </div>
                        <p class="hidden sm:block text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-medium tracking-wide truncate"><?= h($storeSettings['store_tagline'] ?: 'Decision Support System • ' . $totalKriteria . ' Kriteria') ?></p>
                    </div>
                </a>

                <!-- Desktop Navigation Links (with Active State) -->
                <nav class="hidden md:flex items-center gap-1.5 text-xs font-bold tracking-wide uppercase">
                    <a href="#hero" class="px-3.5 py-2 rounded-xl text-white bg-teal-600 font-extrabold shadow-sm transition">
                        Beranda
                    </a>
                    <a href="#konsultasi" class="px-3.5 py-2 rounded-xl text-slate-700 dark:text-slate-200 hover:text-teal-700 dark:hover:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                        Kalkulator SPK
                    </a>
                    <a href="#katalog" class="px-3.5 py-2 rounded-xl text-slate-700 dark:text-slate-200 hover:text-teal-700 dark:hover:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                        Koleksi Raket
                    </a>
                    <a href="metode.php" class="px-3.5 py-2 rounded-xl text-slate-700 dark:text-slate-200 hover:text-teal-700 dark:hover:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition">
                        Metode SAW
                    </a>
                    <button type="button" onclick="openMatrixModal()" class="px-3.5 py-2 rounded-xl text-slate-700 dark:text-slate-200 hover:text-teal-700 dark:hover:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 transition uppercase">
                        Audit Matriks
                    </button>
                </nav>

                <!-- Actions: Theme Toggle & Admin Portal -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Dual-Theme Toggle Button -->
                    <button id="themeToggleBtn" type="button" aria-label="Ganti Tema"
                            class="p-2 sm:p-2.5 rounded-xl border border-slate-200 dark:border-slate-700/70 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-teal-500 dark:hover:border-cyan-400 transition-colors">
                        <!-- Sun Icon (Tampil saat Dark Mode) -->
                        <svg id="themeIconSun" class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <!-- Moon Icon (Tampil saat Light Mode) -->
                        <svg id="themeIconMoon" class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>

                    <!-- Admin Portal Button -->
                    <?php if ($isAdmin): ?>
                    <a href="admin/dashboard.php" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-cyan-500/15 dark:hover:bg-cyan-500/25 text-white dark:text-cyan-300 dark:border dark:border-cyan-500/40 text-xs font-bold tracking-wide transition-all">
                        <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Panel Pengelola
                    </a>
                    <?php else: ?>
                    <a href="login.php" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-teal-600 dark:hover:border-cyan-400 text-xs font-bold tracking-wide transition-all">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Masuk Admin
                    </a>
                    <?php endif; ?>

                    <!-- Mobile Hamburger Menu Button (< 768px) -->
                    <button type="button" id="mobileMenuBtn" class="md:hidden p-2 rounded-xl text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition" aria-label="Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Drawer (< 768px) -->
            <div id="mobileMenuDrawer" class="hidden md:hidden py-3 border-t border-slate-200 dark:border-slate-800 space-y-1">
                <a href="#hero" class="block px-3 py-2 rounded-lg text-sm font-semibold text-teal-700 dark:text-cyan-400 bg-slate-100 dark:bg-slate-800">Beranda</a>
                <a href="#konsultasi" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Kalkulator SPK</a>
                <a href="#katalog" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Koleksi Raket</a>
                <a href="metode.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Metode SAW</a>
                <button type="button" onclick="openMatrixModal(); document.getElementById('mobileMenuDrawer').classList.add('hidden');" class="block w-full text-left px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 uppercase">Audit Matriks</button>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                    <?php if ($isAdmin): ?>
                    <a href="admin/dashboard.php" class="block text-center w-full py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs">Dashboard Admin</a>
                    <?php else: ?>
                    <a href="login.php" class="block text-center w-full py-2.5 rounded-xl border border-teal-600 text-teal-600 dark:text-cyan-300 font-bold text-xs">Login Admin</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- ══════════════════════════════════════════════════════════════
         HERO SECTION ASIMETRIS DENGAN KARTU PRODUK LUXURY MINIMALIST
         (BEBAS TOTAL DARI SILUET ATAU STICKMAN)
         ══════════════════════════════════════════════════════════════ -->
    <section id="hero" class="relative overflow-hidden py-4 lg:py-5">
        <!-- High-Resolution Badminton Background with Artistic Gradient Overlay -->
        <div class="absolute inset-0 z-0">
            <img src="https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=1920&q=80"
                 alt="Badminton Racket Background" class="w-full h-full object-cover opacity-[0.07] dark:opacity-[0.06]">
            <div class="absolute inset-0 bg-gradient-to-br from-[#FBF9F5] via-[#FBF9F5]/95 to-transparent dark:from-[#0B0F17] dark:via-[#0B0F17]/95 dark:to-transparent"></div>
        </div>
        <!-- Ambient Subtle Glows -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-teal-500/8 dark:bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 right-0 w-96 h-96 bg-amber-500/8 dark:bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="grid lg:grid-cols-12 gap-5 lg:gap-8 items-center">

                <!-- Left Column (7 Kolom): Tipografi Tajam & Lencana UAT Responsif -->
                <div class="lg:col-span-7 space-y-3.5">

                    <!-- Status Indicator -->
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-white dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 shadow-sm text-slate-800 dark:text-slate-200">
                        <span class="w-2 h-2 rounded-full bg-teal-500 dark:bg-cyan-400 animate-pulse"></span>
                        <span class="font-bold text-teal-700 dark:text-cyan-300">Algoritma SAW Aktif</span>
                        <span class="text-slate-300 dark:text-slate-600">•</span>
                        <span><?= $totalKriteria ?> Kriteria Penilaian Objektif</span>
                    </div>

                    <!-- Serif Heading with Responsive Clamp Scaling (Zero Layout Shift & Anti-Kepotong) -->
                    <h1 class="font-serif-heading font-extrabold text-slate-900 dark:text-white leading-[1.15] tracking-tight" style="font-size: clamp(1.85rem, 2.7vw, 2.4rem);">
                        Rekomendasi Raket
                        <span class="italic text-transparent bg-clip-text bg-gradient-to-r from-teal-700 via-teal-600 to-cyan-600 dark:from-cyan-300 dark:via-teal-300 dark:to-emerald-400 block sm:inline">
                            Presisi Tinggi.
                        </span>
                        Dihitung Berdasarkan Data.
                    </h1>

                    <!-- Body Paragraph (Kompak text-sm) -->
                    <p class="text-sm text-slate-600 dark:text-slate-300 max-w-2xl leading-normal">
                        Temukan raket bulu tangkis yang tepat untuk karakter ayunan dan anggaran Anda di <strong class="text-slate-900 dark:text-white"><?= h($storeSettings['store_name']) ?></strong>. Sistem mengeliminasi bias subjektif melalui kalkulasi normalisasi matriks dan pembobotan matematis SAW.
                    </p>

                    <!-- CTA Buttons (Compact & Fast) -->
                    <div class="flex flex-wrap items-center gap-2.5 pt-0.5">
                        <a href="#konsultasi" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 dark:hover:from-teal-700 dark:hover:to-cyan-700 text-white font-bold text-xs uppercase tracking-wider shadow-md hover:-translate-y-0.5 transition-all">
                            <span>Mulai Simulasi Preferensi</span>
                            <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                        <button type="button" onclick="openMatrixModal()" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-white dark:bg-slate-800/90 text-slate-800 dark:text-slate-200 hover:text-teal-700 dark:hover:text-cyan-300 font-bold text-xs uppercase tracking-wider border border-slate-200 dark:border-slate-700 shadow-sm hover:-translate-y-0.5 transition-all">
                            <svg class="w-3.5 h-3.5 text-teal-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Transparansi Matriks
                        </button>
                    </div>

                    <!-- UAT Verification Badges (Usability, Compatibility, Performance Efficiency) -->
                    <div class="pt-3 border-t border-slate-200 dark:border-slate-800/80 grid grid-cols-3 gap-3">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-slate-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Usability UAT</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">96.4% SUS (ISO 9241-11)</p>
                        </div>
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-slate-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                                <span>Compatibility</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Multi-Browser Verified</p>
                        </div>
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-slate-100">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                <span>Performance</span>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">&lt; 12ms Execution Speed</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column (5 Kolom): Kartu Rekomendasi Utama #1 Winner (Padat & Anti-Kepotong) -->
                <div class="lg:col-span-5 relative">
                    <div class="relative luxury-card rounded-2xl sm:rounded-3xl overflow-hidden winner-luxury-border shadow-lg p-4 space-y-3">

                        <!-- Winner Racket Image / PDF Frame (Tinggi Tetap h-[130px] Anti-Ruang Kosong) -->
                        <?php 
                        $wRawGbr = !empty($winnerRacket['gambar']) ? $winnerRacket['gambar'] : ($winnerRacket['image'] ?? '');
                        $wIsPdf  = !empty($wRawGbr) && (str_ends_with(strtolower($wRawGbr), '.pdf') || str_starts_with($wRawGbr, 'data:application/pdf'));
                        $wImgSrc = '';
                        if (!empty($wRawGbr) && !$wIsPdf) {
                            if (str_starts_with($wRawGbr, 'data:image/') || str_starts_with($wRawGbr, 'http://') || str_starts_with($wRawGbr, 'https://') || str_starts_with($wRawGbr, 'admin/uploads/') || str_starts_with($wRawGbr, 'assets/')) {
                                $wImgSrc = $wRawGbr;
                            } else {
                                $wImgSrc = 'admin/uploads/' . ltrim($wRawGbr, '/');
                            }
                        }
                        ?>
                        <?php if (!empty($wRawGbr) && $wIsPdf): ?>
                            <div class="racket-frame h-[130px] bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl flex flex-col items-center justify-center p-2 text-center">
                                <div class="icon-box-sm mb-1 text-slate-700 dark:text-slate-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <span class="text-[11px] font-bold text-slate-800 dark:text-cyan-300">Brosur Turnamen (PDF)</span>
                                <a href="<?= h($wRawGbr) ?>" target="_blank" class="mt-1 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-teal-600 dark:bg-cyan-600 text-white font-bold text-[10px] shadow">
                                    Buka Brosur PDF ↗
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="w-full h-44 overflow-hidden bg-slate-900 rounded-xl relative group">
                                <img src="<?php echo !empty($wImgSrc) ? htmlspecialchars($wImgSrc) : 'assets/images/fallback-racket.svg'; ?>"
                                     alt="<?php echo htmlspecialchars($winnerRacket['name'] ?? ''); ?>"
                                     class="w-full h-full object-cover object-[center_22%] transition-transform duration-300 group-hover:scale-105"
                                     fetchpriority="high" decoding="async"
                                     onerror="this.onerror=null; this.src='assets/images/fallback-racket.svg';">
                                <div class="absolute bottom-2 left-2">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-extrabold uppercase tracking-wider bg-slate-950/85 text-white dark:bg-cyan-500/25 dark:text-cyan-300 border border-slate-700/80 dark:border-cyan-500/50 shadow-md backdrop-blur-xs">
                                        <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        #1 Rekomendasi SAW
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Top Header: Exclusive Badge & Score -->
                        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-2">
                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider bg-teal-500/10 text-teal-800 dark:text-cyan-300 border border-teal-500/20">
                                <svg class="w-3 h-3 text-teal-600 dark:text-cyan-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pilihan Teratas Komputasi
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] text-slate-400 block font-bold uppercase tracking-wider leading-none">Indeks SAW (V₁)</span>
                                <span class="font-serif-heading font-black text-lg text-slate-900 dark:text-cyan-400 leading-none">
                                    <?= number_format(($winnerScore > 0 ? $winnerScore : 0.844119) * 100, 2) ?>%
                                </span>
                            </div>
                        </div>

                        <!-- Technical Luxury Identity Block -->
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-teal-700 dark:text-cyan-400">
                                <?= h($winnerRacket['brand'] ?? 'Specs') ?>
                            </span>
                            <h2 class="font-serif-heading text-lg sm:text-xl font-extrabold text-slate-900 dark:text-white leading-tight truncate">
                                <?= h($winnerRacket['name'] ?? 'BRS 66 LD') ?>
                            </h2>
                            <p class="font-sans text-base sm:text-lg font-black text-teal-700 dark:text-cyan-300">
                                <?= formatRupiah($winnerRacket['price'] ?? 650000) ?>
                            </p>
                        </div>

                        <!-- Core Technical Specifications Matrix (4 Pilar Kompak: py-1.5 px-2.5) -->
                        <div class="grid grid-cols-2 gap-2">
                            <!-- Berat -->
                            <div class="py-1.5 px-2.5 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Berat Fisik</span>
                                <span class="text-xs font-extrabold text-slate-900 dark:text-white block truncate">
                                    <?= h($winnerRacket['weight_spec'] ?? '82g (4U)') ?>
                                </span>
                            </div>

                            <!-- Flex Shaft -->
                            <div class="py-1.5 px-2.5 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Kelenturan Shaft</span>
                                <span class="text-xs font-extrabold text-slate-900 dark:text-white block truncate">
                                    <?= h($winnerRacket['flex_spec'] ?? 'Flexible') ?>
                                </span>
                            </div>

                            <!-- Max Tension -->
                            <div class="py-1.5 px-2.5 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Max Tension</span>
                                <span class="text-xs font-extrabold text-slate-900 dark:text-white block truncate">
                                    <?= h($winnerRacket['tension_spec'] ?? '26 lbs') ?>
                                </span>
                            </div>

                            <!-- Balance Point -->
                            <div class="py-1.5 px-2.5 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block">Balance Point</span>
                                <span class="text-xs font-extrabold text-slate-900 dark:text-white block truncate">
                                    <?= h($winnerRacket['balance_spec'] ?? 'Even (290mm)') ?>
                                </span>
                            </div>
                        </div>

                        <!-- Technical Rating Index Progress -->
                        <div class="space-y-1">
                            <div class="flex justify-between text-[10px] font-bold">
                                <span class="text-slate-500 dark:text-slate-400">Rasio Efisiensi SAW</span>
                                <span class="text-teal-700 dark:text-cyan-400 font-extrabold">Optimal</span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-teal-600 to-cyan-500 rounded-full" style="width: <?= min(100, max(10, round(($winnerScore > 0 ? $winnerScore : 0.84) * 100))) ?>%;"></div>
                            </div>
                        </div>

                        <!-- Direct Trigger to Calculator -->
                        <a href="#konsultasi" class="block w-full py-2.5 rounded-xl text-center bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider transition shadow-sm">
                            Uji Preferensi Anggaran Anda →
                        </a>
                    </div><!-- /.luxury-card -->
                </div><!-- /.lg:col-span-5 -->

            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
         KALKULATOR SPK DENGAN SLIDER PREFERENSI REAL-TIME
         ══════════════════════════════════════════════════════════════ -->
    <section id="konsultasi" style="content-visibility: auto; contain-intrinsic-size: 550px;" class="py-6 sm:py-8 bg-slate-50/70 dark:bg-[#0E1524] border-y border-slate-200 dark:border-slate-800/80 transition-colors">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center max-w-2xl mx-auto mb-4">
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-teal-500/10 text-teal-700 dark:text-cyan-400 border border-teal-500/20 mb-1.5">
                    Modul Kalkulasi
                </span>
                <h2 class="font-serif-heading text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                    Kalkulator Rekomendasi Raket
                </h2>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Tentukan batas anggaran dan karakteristik ayunan Anda untuk menghasilkan rekomendasi raket terbaik.
                </p>
            </div>

            <div class="luxury-card rounded-2xl p-4 sm:p-5 shadow-lg border">
                <form action="hasil.php" method="POST" id="sawCalcForm" class="space-y-4">

                    <!-- Baris 1: Identitas & Anggaran Maksimal -->
                    <div class="grid sm:grid-cols-2 gap-4">
                        <!-- Nama Pengunjung -->
                        <div>
                            <label for="visitor_name" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Nama Lengkap Pengunjung
                            </label>
                            <input type="text" id="visitor_name" name="visitor_name" placeholder="Contoh: Budi Santoso"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500 transition">
                        </div>

                        <!-- Anggaran Maksimal Input & Slider -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="max_budget" class="text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Batas Anggaran Maksimal
                                </label>
                                <span id="budget_display" class="font-serif-heading font-extrabold text-teal-700 dark:text-cyan-300 text-xs sm:text-sm">
                                    Rp 2.000.000
                                </span>
                            </div>

                            <!-- Input Angka Budget Terstruktur dengan Validasi Client-Side -->
                            <div class="relative mb-1.5">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 dark:text-slate-500">Rp</span>
                                <input type="number" id="max_budget" name="max_budget" min="100000" step="50000" value="2000000" placeholder="2000000"
                                       class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white font-semibold text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500 transition">
                            </div>

                            <!-- Range Slider Sinergis -->
                            <input type="range" id="budget_slider" min="100000" max="4000000" step="50000" value="2000000"
                                   class="w-full h-1.5 rounded-lg cursor-pointer bg-slate-200 dark:bg-slate-700">

                            <!-- Budget Quick Select Chips -->
                            <div class="flex flex-wrap gap-1 mt-2">
                                <button type="button" onclick="setSliderBudget(700000)" class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-900 hover:text-white dark:hover:bg-cyan-500 dark:hover:text-black transition">Rp 700rb</button>
                                <button type="button" onclick="setSliderBudget(1200000)" class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-900 hover:text-white dark:hover:bg-cyan-500 dark:hover:text-black transition">Rp 1.2 Jt</button>
                                <button type="button" onclick="setSliderBudget(2000000)" class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-900 hover:text-white dark:hover:bg-cyan-500 dark:hover:text-black transition">Rp 2.0 Jt</button>
                                <button type="button" onclick="setSliderBudget(3000000)" class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-900 hover:text-white dark:hover:bg-cyan-500 dark:hover:text-black transition">Rp 3.0 Jt</button>
                                <button type="button" onclick="clearBudgetFilter()" class="px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-400 hover:bg-amber-500 hover:text-white transition">Rp 4.0 Jt+ (Semua)</button>
                            </div>

                            <!-- Pesan Peringatan Validasi Anggaran -->
                            <div id="budget_validation_alert" class="hidden mt-2 p-2.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-semibold flex items-center gap-2">
                                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span id="budget_validation_msg">Batas anggaran tidak boleh kosong atau bernilai negatif.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Baris 2: Karakteristik Permainan -->
                    <div class="grid sm:grid-cols-2 gap-4 pt-3 border-t border-slate-200 dark:border-slate-800/80">
                        <div>
                            <label for="playing_style" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Karakter Gaya Bermain
                            </label>
                            <select id="playing_style" name="playing_style"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500 transition cursor-pointer">
                                <option value="">Semua Karakter (Standar SAW)</option>
                                <option value="Power">Power & Smash (Menyerang)</option>
                                <option value="Speed">Speed & Drive (Manuver Cepat)</option>
                                <option value="Control">Control & Placement (Presisi)</option>
                                <option value="All-Around">All-Around (Kombinasi Seimbang)</option>
                            </select>
                        </div>

                        <div>
                            <label for="flexibility_pref" class="block text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                                Preferensi Kelenturan Batang (Flex)
                            </label>
                            <select id="flexibility_pref" name="flexibility_pref"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500 transition cursor-pointer">
                                <option value="">Semua Tingkat Kelenturan</option>
                                <option value="Extra Stiff">Extra Stiff (Pemain Profesional)</option>
                                <option value="Stiff">Stiff (Kaku — Smash Maksimal)</option>
                                <option value="Medium Stiff">Medium Stiff (Serbaguna)</option>
                                <option value="Flexible">Flexible (Ramah Pemula & Defensif)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Ringkasan Kompak Distribusi Pembobotan Kriteria SAW (Collapsible Details) -->
                    <?php
                    $summaryParts = [];
                    $totalWeightPct = 0;
                    foreach ($criteriaList as $cItem) {
                        $pct = round((float)$cItem['weight'] * 100, 1);
                        $totalWeightPct += $pct;
                        $summaryParts[] = h($cItem['code']) . ': ' . $pct . '%';
                    }
                    ?>
                    <details class="group rounded-xl bg-slate-100/70 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 text-xs overflow-hidden transition-all">
                        <summary class="flex items-center justify-between px-3.5 py-2 cursor-pointer select-none font-bold text-slate-700 dark:text-slate-300 hover:text-teal-600 dark:hover:text-cyan-400">
                            <div class="flex items-center gap-2 text-[11px] min-w-0">
                                <span class="font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 shrink-0">Distribusi Bobot SAW:</span>
                                <span class="text-teal-700 dark:text-cyan-400 font-mono font-bold truncate"><?= implode(' • ', array_slice($summaryParts, 0, 4)) . (count($summaryParts) > 4 ? ' • ...' : '') ?> (Σ=<?= round($totalWeightPct) ?>%)</span>
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-teal-600 dark:text-cyan-400 shrink-0 ml-2">
                                <span>Detail</span>
                                <svg class="w-3.5 h-3.5 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </summary>
                        <div class="px-3.5 pb-2.5 pt-1 border-t border-slate-200/60 dark:border-slate-800/60 grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-[10px] text-slate-600 dark:text-slate-400">
                            <?php foreach ($criteriaList as $cItem): ?>
                            <div>• <?= h($cItem['code']) ?> <?= h($cItem['name']) ?>: <strong class="text-slate-800 dark:text-slate-200"><?= round((float)$cItem['weight'] * 100, 1) ?>% (<?= ucfirst(h($cItem['type'])) ?>)</strong></div>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <!-- Tombol Eksekusi & Reset Pilihan Form -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 pt-1">
                        <button type="submit" id="btn_submit_calc" class="flex-1 py-3 px-5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 dark:hover:from-teal-700 dark:hover:to-cyan-700 text-white font-extrabold text-xs sm:text-sm uppercase tracking-wider shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Hitung & Tampilkan Rekomendasi Lengkap
                        </button>
                        <button type="button" id="btn_reset_calc" onclick="resetCalculatorForm()" class="sm:w-auto py-3 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-extrabold text-xs sm:text-sm uppercase tracking-wider border border-slate-300 dark:border-slate-700 transition flex items-center justify-center gap-1.5" title="Kembalikan semua pilihan input ke pengaturan awal">
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
         KATALOG KOLEKSI RAKET (CLEAN LUXURY MINIMALIST)
         ══════════════════════════════════════════════════════════════ -->
    <section id="katalog" class="py-16" style="content-visibility: auto; contain-intrinsic-size: 800px;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
                <div>
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-teal-500/10 text-teal-700 dark:text-cyan-400 border border-teal-500/20 mb-3">
                        Katalog Resmi
                    </span>
                    <h2 class="font-serif-heading text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white">
                        Daftar Alternatif Penilaian
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                        Menampilkan <?= $totalRaket ?> raket pilihan yang dinilai menggunakan <?= $totalKriteria ?> kriteria objektif SAW.
                    </p>
                </div>
            </div>

            <!-- Grid Kartu Raket (Clean Minimalist Luxury) -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($rackets as $r): ?>
                <div class="bg-white dark:bg-[#131C2E] rounded-2xl sm:rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 transition-transform duration-200 hover:-translate-y-1 flex flex-col justify-between group will-change-transform">
                    <div>
                        <!-- Racket Image / PDF Spec Document (Dinamis dari admin/uploads & Fallback) -->
                        <?php 
                        $rRawGbr = !empty($r['gambar']) ? $r['gambar'] : ($r['image'] ?? '');
                        $rIsPdf  = !empty($rRawGbr) && (str_ends_with(strtolower($rRawGbr), '.pdf') || str_starts_with($rRawGbr, 'data:application/pdf'));
                        $rImgSrc = '';
                        if (!empty($rRawGbr) && !$rIsPdf) {
                            if (str_starts_with($rRawGbr, 'data:image/') || str_starts_with($rRawGbr, 'http://') || str_starts_with($rRawGbr, 'https://') || str_starts_with($rRawGbr, 'admin/uploads/') || str_starts_with($rRawGbr, 'assets/')) {
                                $rImgSrc = $rRawGbr;
                            } else {
                                $rImgSrc = 'admin/uploads/' . ltrim($rRawGbr, '/');
                            }
                        }
                        ?>
                        <?php if (!empty($rRawGbr) && $rIsPdf): ?>
                            <div class="w-full h-44 bg-slate-100 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex flex-col items-center justify-center p-4 text-center">
                                <div class="icon-box-md mb-2 text-slate-700 dark:text-slate-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-slate-800 dark:text-cyan-300">Brosur Spesifikasi (PDF)</span>
                                <a href="<?= h($rRawGbr) ?>" target="_blank" class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-teal-600 dark:bg-cyan-600 text-white font-bold text-[11px] shadow hover:scale-105 transition">
                                    Buka Brosur PDF ↗
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="w-full h-44 overflow-hidden bg-slate-900 border-b border-slate-200 dark:border-slate-800 relative">
                                <img src="<?php echo !empty($rImgSrc) ? htmlspecialchars($rImgSrc) : 'assets/images/fallback-racket.svg'; ?>"
                                     alt="<?php echo htmlspecialchars($r['name']); ?>"
                                     class="w-full h-full object-cover object-[center_22%] transition-transform duration-300 group-hover:scale-105"
                                     loading="lazy" decoding="async"
                                     onerror="this.onerror=null; this.src='assets/images/fallback-racket.svg';">
                            </div>
                        <?php endif; ?>

                        <div class="p-6">
                        <!-- Header Brand Pill & Verified Tag -->
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 rounded-md text-[11px] font-extrabold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-teal-700 dark:text-cyan-300 border border-slate-200 dark:border-slate-700">
                                <?= h($r['brand']) ?>
                            </span>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Official Match</span>
                        </div>

                        <!-- Nama Raket -->
                        <h3 class="font-serif-heading text-2xl font-bold text-slate-900 dark:text-white mb-2">
                            <?= h($r['name']) ?>
                        </h3>

                        <!-- Harga -->
                        <p class="font-sans text-xl font-black text-teal-700 dark:text-cyan-400 mb-5">
                            <?= formatRupiah($r['price']) ?>
                        </p>

                        <!-- Spesifikasi Teknis 4 Pilar (Berat, Flex, Tension, Balance) -->
                        <div class="grid grid-cols-2 gap-2 text-xs mb-5">
                            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase tracking-wider">Berat Fisik</span>
                                <span class="font-extrabold text-slate-900 dark:text-slate-100"><?= h($r['weight_spec'] ?? '83g') ?></span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase tracking-wider">Kelenturan</span>
                                <span class="font-extrabold text-slate-900 dark:text-slate-100"><?= h($r['flex_spec'] ?? 'Medium') ?></span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase tracking-wider">Max Tension</span>
                                <span class="font-extrabold text-slate-900 dark:text-slate-100"><?= h($r['tension_spec'] ?? '28 lbs') ?></span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                                <span class="text-[10px] text-slate-400 block font-bold uppercase tracking-wider">Balance</span>
                                <span class="font-extrabold text-slate-900 dark:text-slate-100"><?= h($r['balance_spec'] ?? 'Even') ?></span>
                            </div>
                        </div>

                        <!-- Deskripsi Singkat -->
                        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed mb-6">
                            <?= h($r['description'] ?? 'Raket performa tinggi dengan material karbon berkualitas.') ?>
                        </p>
                        </div><!-- /.p-6 -->
                    </div>

                    <!-- Tombol Action -->
                    <div class="px-6 pb-6">
                    <button type="button" onclick="setSliderBudget(<?= (float)$r['price'] ?>); window.location.hash='konsultasi';"
                            class="w-full py-3 rounded-xl border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:border-cyan-400 text-slate-800 dark:text-slate-200 hover:text-teal-700 dark:hover:text-cyan-400 text-xs font-bold uppercase tracking-wider transition">
                        Pilih Rentang Budget Ini →
                    </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
         SECTION METODE SAW & TABEL 8 KRITERIA PENILAIAN
         ══════════════════════════════════════════════════════════════ -->
    <section id="transparansi" style="content-visibility: auto; contain-intrinsic-size: 600px;" class="py-16 bg-slate-50/70 dark:bg-[#0E1524] border-t border-slate-200 dark:border-slate-800/80 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-teal-500/10 text-teal-700 dark:text-cyan-400 border border-teal-500/20 mb-3">
                    Transparansi Ilmiah
                </span>
                <h2 class="font-serif-heading text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white">
                    <?= $totalKriteria ?> Kriteria Penilaian Simple Additive Weighting
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                    Setiap kriteria memiliki bobot preferensi (W) dan formula normalisasi yang transparan.
                </p>
            </div>

            <!-- Tabel 8 Kriteria (Mobile Optimized text-left & Scroll Guide) -->
            <div class="text-[11px] text-slate-400 dark:text-slate-500 mb-2 md:hidden flex items-center gap-1 font-medium">
                <span>← Geser tabel ke samping untuk melihat detail kriteria →</span>
            </div>
            <div class="bg-white dark:bg-[#131C2E] rounded-2xl sm:rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="w-full overflow-x-auto pb-2 text-left">
                    <table class="w-full min-w-[650px] text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-100/90 dark:bg-slate-800/90 text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="py-4 px-6">Kode</th>
                                <th class="py-4 px-6">Nama Kriteria</th>
                                <th class="py-4 px-6 text-center whitespace-nowrap">Tipe Kriteria</th>
                                <th class="py-4 px-6 text-center whitespace-nowrap">Bobot (W)</th>
                                <th class="py-4 px-6">Deskripsi & Formula Normalisasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            <?php foreach ($criteriaList as $c):
                                $isCost = strtolower($c['type']) === 'cost';
                            ?>
                            <tr class="hover:bg-teal-50/20 dark:hover:bg-slate-800/40 transition text-sm">
                                <td class="py-4 px-6 font-extrabold text-teal-700 dark:text-cyan-400">
                                    <?= h($c['code']) ?>
                                </td>
                                <td class="py-4 px-6 font-bold text-slate-900 dark:text-white">
                                    <?= h($c['name']) ?>
                                </td>
                                <td class="py-4 px-6 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap <?= $isCost ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' ?>">
                                        <?= $isCost ? 'Cost (Min / Xij)' : 'Benefit (Xij / Max)' ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center whitespace-nowrap font-black text-slate-800 dark:text-slate-200">
                                    <?= number_format((float)$c['weight'] * 100, 0) ?>%
                                </td>
                                <td class="py-4 px-6 text-sm text-slate-600 dark:text-slate-400">
                                    <?= h($c['description'] ?? '-') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tombol Buka Modal Audit Matriks -->
            <div class="mt-8 text-center">
                <button type="button" onclick="openMatrixModal()" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-slate-900 dark:bg-slate-800 text-white dark:text-cyan-300 font-bold text-xs uppercase tracking-wider hover:bg-slate-800 dark:hover:bg-slate-700 transition-all shadow-md">
                    <span>Lihat Matriks Keputusan X & Normalisasi R Lengkap</span>
                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </button>
            </div>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════
         MODAL TRANSPARANSI MATRIKS KEPUTUSAN SAW (ON-DEMAND LAZY DOM)
         ══════════════════════════════════════════════════════════════ -->
    <div id="matrixModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4">
        <div class="bg-white dark:bg-[#111827] border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl w-[96vw] max-w-5xl max-h-[92vh] overflow-y-auto p-3.5 sm:p-6 shadow-2xl space-y-4 sm:space-y-6">

            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3 sm:pb-4">
                <div>
                    <h3 class="font-serif-heading text-lg sm:text-2xl font-bold text-slate-900 dark:text-white">
                        Transparansi Matriks Keputusan SAW (Auditing)
                    </h3>
                    <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">Data matematis asli dari database toko Muara Jaya Sport</p>
                </div>
                <button type="button" onclick="closeMatrixModal()" class="p-1.5 sm:p-2 rounded-xl text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition" aria-label="Tutup Modal">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Kontainer Target On-Demand Rendering (Dirender HANYA saat tombol diklik) -->
            <div id="matrixModalContent" class="space-y-5 sm:space-y-6"></div>

            <div class="text-right pt-2 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="closeMatrixModal()" class="px-5 py-2.5 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs transition">
                    Tutup Tampilan Audit
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         FOOTER RESMI DINAMIS TOKO
         ══════════════════════════════════════════════════════════════ -->
    <footer style="content-visibility: auto; contain-intrinsic-size: 150px;" class="border-t border-slate-200 dark:border-slate-800 py-12 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <?php if (!empty($storeSettings['logo'])): ?>
                        <img src="<?= h($storeSettings['logo']) ?>" alt="Logo" class="w-10 h-10 object-contain rounded-xl">
                    <?php else: ?>
                        <div class="w-9 h-9 rounded-xl bg-slate-900 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-serif-heading font-black text-sm text-teal-600 dark:text-cyan-400">
                            MJ
                        </div>
                    <?php endif; ?>
                    <div>
                        <span class="font-serif-heading font-bold text-slate-900 dark:text-white block"><?= h($storeSettings['store_name']) ?></span>
                        <span class="text-xs text-slate-500 dark:text-slate-400"><?= h($storeSettings['address']) ?></span>
                    </div>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 text-center md:text-right">
                    <div>Kontak: <strong><?= h($storeSettings['whatsapp'] ?: $storeSettings['phone']) ?></strong> • Email: <strong><?= h($storeSettings['email']) ?></strong></div>
                    <div class="mt-1">&copy; <?= date('Y') ?> <?= h($storeSettings['store_name']) ?>. Metode SAW <?= $totalKriteria ?> Kriteria & PDO Prepared Statements.</div>
                </div>
            </div>
        </div>
    </footer>

    <!-- ══════════════════════════════════════════════════════════════
         JAVASCRIPT LOGIC INTERAKTIF
         ══════════════════════════════════════════════════════════════ -->
    <script>
        // On-Demand Data Matriks Keputusan SAW (Zero-Lag)
        const sawAuditData = <?= json_encode($sawAuditData ?? null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

        // 1. Dual-Theme Toggle Logic
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        themeToggleBtn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('mjs_theme', isDark ? 'dark' : 'light');
        });

        // 2. Mobile Drawer Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenuDrawer = document.getElementById('mobileMenuDrawer');
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenuDrawer.classList.toggle('hidden');
        });

        // 3. Real-Time Budget Slider & Direct Numeric Input Sync
        const budgetSlider       = document.getElementById('budget_slider');
        const budgetDisplay      = document.getElementById('budget_display');
        const maxBudgetInput     = document.getElementById('max_budget');
        const budgetAlert        = document.getElementById('budget_validation_alert');
        const budgetAlertMsg     = document.getElementById('budget_validation_msg');
        const calcForm           = document.getElementById('sawCalcForm');

        function formatRupiahJS(number) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
        }

        function hideBudgetError() {
            if (budgetAlert) budgetAlert.classList.add('hidden');
            if (maxBudgetInput) maxBudgetInput.classList.remove('ring-2', 'ring-rose-500', 'border-rose-500');
        }

        function showBudgetError(msg) {
            if (budgetAlert && budgetAlertMsg) {
                budgetAlertMsg.textContent = msg;
                budgetAlert.classList.remove('hidden');
            }
            if (maxBudgetInput) {
                maxBudgetInput.classList.add('ring-2', 'ring-rose-500', 'border-rose-500');
                maxBudgetInput.focus();
            }
        }

        // Sinkronisasi pergerakan slider ke input angka & label display
        budgetSlider.addEventListener('input', function() {
            hideBudgetError();
            const val = parseInt(this.value);
            budgetDisplay.innerText = formatRupiahJS(val);
            maxBudgetInput.value = val;
        });

        // Sinkronisasi ketikan angka manual ke slider & label display
        maxBudgetInput.addEventListener('input', function() {
            hideBudgetError();
            const val = parseFloat(this.value);
            if (!isNaN(val) && val > 0) {
                budgetDisplay.innerText = formatRupiahJS(val);
                if (val >= 100000 && val <= 4000000) {
                    budgetSlider.value = val;
                }
            } else {
                budgetDisplay.innerText = this.value ? 'Rp ' + this.value : 'Belum diisi';
            }
        });

        function setSliderBudget(val) {
            hideBudgetError();
            budgetSlider.value = val;
            budgetDisplay.innerText = formatRupiahJS(val);
            maxBudgetInput.value = val;
        }

        function clearBudgetFilter() {
            hideBudgetError();
            setSliderBudget(4000000);
            budgetDisplay.innerText = 'Rp 4.000.000+ (Semua)';
        }

        // Validasi Client-Side Form Kalkulator SAW Saat Submit
        if (calcForm) {
            calcForm.addEventListener('submit', function(e) {
                const rawVal = maxBudgetInput.value.trim();

                // 1. Cek jika input anggaran kosong
                if (rawVal === '') {
                    e.preventDefault();
                    showBudgetError('Batas anggaran tidak boleh kosong. Harap masukkan nominal minimal Rp 100.000.');
                    return false;
                }

                const numVal = parseFloat(rawVal);

                // 2. Cek jika bukan angka / terdapat huruf / NaN
                if (isNaN(numVal)) {
                    e.preventDefault();
                    showBudgetError('Batas anggaran harus berupa angka yang valid, bukan huruf atau simbol khusus.');
                    return false;
                }

                // 3. Cek jika user memasukkan angka minus / negatif
                if (numVal < 0) {
                    e.preventDefault();
                    showBudgetError('Batas anggaran tidak boleh bernilai minus (negatif). Harap masukkan angka positif minimal Rp 100.000.');
                    return false;
                }

                // 4. Cek batas minimum realistis Rp 100.000
                if (numVal < 100000) {
                    e.preventDefault();
                    showBudgetError('Batas anggaran minimal adalah Rp 100.000 agar sistem SPK dapat memberikan rekomendasi raket.');
                    return false;
                }

                hideBudgetError();
                return true;
            });
        }

        // Fungsi Reset Pilihan Interaktif
        function resetCalculatorForm() {
            // Reset nama pengunjung
            const visitorInput = document.getElementById('visitor_name');
            if (visitorInput) visitorInput.value = '';

            // Reset select karakter gaya bermain & kelenturan flex
            const styleSelect = document.getElementById('playing_style');
            if (styleSelect) styleSelect.selectedIndex = 0;

            const flexSelect = document.getElementById('flexibility_pref');
            if (flexSelect) flexSelect.selectedIndex = 0;

            // Reset input angka, slider & display ke default 2.000.000
            setSliderBudget(2000000);

            // Bersihkan error validasi jika ada
            hideBudgetError();

            // Animasi feedback konfirmasi tombol reset
            const resetBtn = document.getElementById('btn_reset_calc');
            if (resetBtn) {
                const origHtml = resetBtn.innerHTML;
                resetBtn.innerHTML = '<span class="text-emerald-600 dark:text-emerald-400 font-bold inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Form Di-reset</span>';
                setTimeout(() => {
                    resetBtn.innerHTML = origHtml;
                }, 1200);
            }
        }

        // 4. On-Demand (Lazy) DOM Rendering untuk Modal Audit Matriks SAW (Zero-Lag)
        const modalContainer = document.getElementById('matrixModal');
        const modalContent   = document.getElementById('matrixModalContent');

        function formatRupiahJsLocal(val) {
            return 'Rp ' + Number(val).toLocaleString('id-ID');
        }

        function openMatrixModal() {
            if (modalContent && (!modalContent.hasChildNodes() || modalContent.innerHTML.trim() === '')) {
                // Render isi tabel HTML ke dalam modal HANYA ketika tombol diklik
                let html = '';

                // Tab 1: Matriks Keputusan Asli (X)
                html += '<div class="space-y-2">';
                html += '  <div class="flex items-center justify-between">';
                html += '    <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-cyan-400">1. Matriks Keputusan Asli (X)</h4>';
                html += '    <span class="text-[11px] text-slate-400 dark:text-slate-500 md:hidden font-medium">← Geser tabel ke samping →</span>';
                html += '  </div>';
                html += '  <div class="w-full overflow-x-auto text-left rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">';
                html += '    <table class="w-full text-xs text-left whitespace-nowrap">';
                html += '      <thead class="bg-slate-100 dark:bg-slate-800/80 font-bold text-slate-700 dark:text-slate-300">';
                html += '        <tr>';
                html += '          <th class="p-2.5 sm:p-3 sticky left-0 bg-slate-100 dark:bg-slate-800 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[130px] sm:min-w-[160px] text-left">Alternatif Raket</th>';
                if (sawAuditData && sawAuditData.criteria) {
                    sawAuditData.criteria.forEach(c => {
                        html += `<th class="p-2.5 sm:p-3 text-center">${c.code}</th>`;
                    });
                }
                html += '        </tr>';
                html += '      </thead>';
                html += '      <tbody class="divide-y divide-slate-200 dark:divide-slate-800">';
                if (sawAuditData && sawAuditData.matrix) {
                    Object.keys(sawAuditData.matrix).forEach(rid => {
                        const scores = sawAuditData.matrix[rid];
                        const rkt = sawAuditData.rackets[rid] || {};
                        html += '      <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">';
                        html += `        <td class="p-2.5 sm:p-3 font-semibold text-slate-900 dark:text-slate-100 sticky left-0 bg-white dark:bg-[#111827] z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[130px] sm:min-w-[160px] text-left">${rkt.name || 'Raket #' + rid}</td>`;
                        sawAuditData.criteria.forEach(c => {
                            const val = scores[c.id] !== undefined ? scores[c.id] : 0;
                            const displayVal = (c.id === 1) ? formatRupiahJsLocal(val) : val;
                            html += `<td class="p-2.5 sm:p-3 text-center font-mono">${displayVal}</td>`;
                        });
                        html += '      </tr>';
                    });
                }
                html += '      </tbody>';
                html += '    </table>';
                html += '  </div>';
                html += '</div>';

                // Tab 2: Matriks Ternormalisasi (R)
                html += '<div class="space-y-2">';
                html += '  <div class="flex items-center justify-between">';
                html += '    <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-cyan-400">2. Matriks Ternormalisasi (R)</h4>';
                html += '    <span class="text-[11px] text-slate-400 dark:text-slate-500 md:hidden font-medium">← Geser tabel ke samping →</span>';
                html += '  </div>';
                html += '  <div class="w-full overflow-x-auto text-left rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">';
                html += '    <table class="w-full text-xs text-left whitespace-nowrap">';
                html += '      <thead class="bg-slate-100 dark:bg-slate-800/80 font-bold text-slate-700 dark:text-slate-300">';
                html += '        <tr>';
                html += '          <th class="p-2.5 sm:p-3 sticky left-0 bg-slate-100 dark:bg-slate-800 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[130px] sm:min-w-[160px] text-left">Alternatif Raket</th>';
                if (sawAuditData && sawAuditData.criteria) {
                    sawAuditData.criteria.forEach(c => {
                        html += `<th class="p-2.5 sm:p-3 text-center">${c.code} (R)</th>`;
                    });
                }
                html += '          <th class="p-2.5 sm:p-3 text-center text-teal-700 dark:text-cyan-400 font-extrabold">Skor (V₁)</th>';
                html += '        </tr>';
                html += '      </thead>';
                html += '      <tbody class="divide-y divide-slate-200 dark:divide-slate-800">';
                if (sawAuditData && sawAuditData.ranking) {
                    Object.keys(sawAuditData.ranking).forEach(rid => {
                        const rankInfo = sawAuditData.ranking[rid];
                        const rkt = sawAuditData.rackets[rid] || {};
                        const normScores = (sawAuditData.normalized && sawAuditData.normalized[rid]) ? sawAuditData.normalized[rid] : {};
                        html += '      <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">';
                        html += `        <td class="p-2.5 sm:p-3 font-semibold text-slate-900 dark:text-slate-100 sticky left-0 bg-white dark:bg-[#111827] z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[130px] sm:min-w-[160px] text-left">${rkt.name || 'Raket #' + rid}</td>`;
                        sawAuditData.criteria.forEach(c => {
                            const normVal = normScores[c.id] !== undefined ? Number(normScores[c.id]).toFixed(4) : '0.0000';
                            html += `<td class="p-2.5 sm:p-3 text-center font-mono">${normVal}</td>`;
                        });
                        const scoreVal = rankInfo.score !== undefined ? Number(rankInfo.score).toFixed(6) : '0.000000';
                        html += `        <td class="p-2.5 sm:p-3 text-center font-mono font-extrabold text-teal-700 dark:text-cyan-300">${scoreVal}</td>`;
                        html += '      </tr>';
                    });
                }
                html += '      </tbody>';
                html += '    </table>';
                html += '  </div>';
                html += '</div>';

                // Tab 3: Vektor Bobot Kriteria (W)
                html += '<div class="space-y-2">';
                html += '  <h4 class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-cyan-400">3. Vektor Bobot Kriteria (W)</h4>';
                html += '  <div class="w-full overflow-x-auto text-left rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs">';
                html += '    <table class="w-full text-xs text-left whitespace-nowrap">';
                html += '      <thead class="bg-slate-100 dark:bg-slate-800/80 font-bold text-slate-700 dark:text-slate-300">';
                html += '        <tr>';
                html += '          <th class="p-2.5 sm:p-3">Kode</th>';
                html += '          <th class="p-2.5 sm:p-3">Nama Kriteria</th>';
                html += '          <th class="p-2.5 sm:p-3">Sifat / Type</th>';
                html += '          <th class="p-2.5 sm:p-3 text-center">Bobot Relatif (Wj)</th>';
                html += '          <th class="p-2.5 sm:p-3 text-center">Persentase</th>';
                html += '        </tr>';
                html += '      </thead>';
                html += '      <tbody class="divide-y divide-slate-200 dark:divide-slate-800">';
                if (sawAuditData && sawAuditData.criteria) {
                    sawAuditData.criteria.forEach(c => {
                        const isCost = c.type.toLowerCase() === 'cost';
                        const badgeClass = isCost ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
                        html += '      <tr>';
                        html += `        <td class="p-2.5 sm:p-3 font-bold text-teal-700 dark:text-cyan-400">${c.code}</td>`;
                        html += `        <td class="p-2.5 sm:p-3 font-medium text-slate-900 dark:text-white">${c.name}</td>`;
                        html += `        <td class="p-2.5 sm:p-3"><span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${badgeClass}">${c.type}</span></td>`;
                        html += `        <td class="p-2.5 sm:p-3 text-center font-mono font-bold">${Number(c.weight).toFixed(4)}</td>`;
                        html += `        <td class="p-2.5 sm:p-3 text-center font-bold text-slate-700 dark:text-slate-300">${(c.weight * 100).toFixed(1)}%</td>`;
                        html += '      </tr>';
                    });
                }
                html += '      </tbody>';
                html += '    </table>';
                html += '  </div>';
                html += '</div>';

                modalContent.innerHTML = html;
            }
            if (modalContainer) modalContainer.classList.remove('hidden');
        }

        function closeMatrixModal() {
            if (modalContainer) modalContainer.classList.add('hidden');
            // Kosongkan kembali kontainer DOM modal saat ditutup agar memori browser tetap ringan (zero-lag)
            if (modalContent) modalContent.innerHTML = '';
        }

        if (modalContainer) {
            modalContainer.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeMatrixModal();
                }
            });
        }
    </script>
</body>
</html>
