<?php
/**
 * ============================================================
 *  includes/header.php — Global Public Header & Sticky Glass Navbar
 *  SPK Pemilihan Raket Bulu Tangkis — Muara Jaya Sport
 * ============================================================
 *  STANDAR:
 *   - Sticky Glassmorphism Navbar (bg-white/95 backdrop-blur-md)
 *   - Indikator Active State Halaman Dinamis
 *   - Hamburger Menu Responsif Mobile (< 768px)
 *   - Dual-Theme Synchronized (Warm Light & Midnight Dark)
 * ============================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentScript   = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$isAdminLoggedIn = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) || !empty($_SESSION['admin']);
$storeName       = $storeSettings['store_name'] ?? 'Muara Jaya Sport';
$storeTagline    = $storeSettings['store_tagline'] ?? 'SPK Rekomendasi Raket SAW';
$storeLogo       = $storeSettings['logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'SPK Pemilihan Raket Bulu Tangkis menggunakan metode SAW - ' . $storeName, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — ' : '' ?><?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Google Fonts -->
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
                    }
                }
            }
        }
    </script>

    <!-- Theme Initialization Script (Instant Prevention of Flicker) -->
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

    <!-- Custom CSS -->
    <link href="<?= $basePath ?? '' ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= $basePath ?? '' ?>assets/css/custom-fix.css" rel="stylesheet">
</head>
<body class="min-h-screen antialiased bg-[#FBF9F5] dark:bg-[#0B0F17] text-[#334155] dark:text-[#94A3B8] transition-colors pt-16">

<!-- Fixed Top Glassmorphism Header & Navbar (Permanent Fixed Top) -->
<header class="fixed top-0 left-0 right-0 w-full z-50 bg-white/95 dark:bg-[#0B0F17]/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 min-w-0">
            <!-- Brand Logo & Name (Tampil Utuh Tanpa Terpotong) -->
            <a href="<?= $basePath ?? '' ?>index.php" class="flex items-center gap-2.5 sm:gap-3 group min-w-0 shrink">
                <?php if (!empty($storeLogo)): ?>
                    <img src="<?= $basePath ?? '' ?><?= htmlspecialchars($storeLogo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?>" class="w-8 h-8 sm:w-9 sm:h-9 object-contain rounded-xl shadow-sm group-hover:scale-105 transition-transform duration-200 shrink-0">
                <?php else: ?>
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-br from-teal-700 to-cyan-500 flex items-center justify-center text-white font-serif font-black text-xs sm:text-sm shadow-sm group-hover:scale-105 transition-transform duration-200 shrink-0">
                        MJ
                    </div>
                <?php endif; ?>
                <div class="min-w-0">
                    <div class="font-serif font-bold text-sm sm:text-base lg:text-lg text-slate-900 dark:text-white tracking-tight flex items-center gap-1.5 whitespace-nowrap">
                        <span><?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="hidden sm:inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-sans font-extrabold uppercase bg-teal-500/10 text-teal-700 dark:text-cyan-300 border border-teal-500/20">
                            SPK SAW
                        </span>
                    </div>
                    <p class="hidden sm:block text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-medium tracking-wide truncate"><?= htmlspecialchars($storeTagline, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </a>

            <!-- Desktop Nav Links (Active State Highlighted) -->
            <nav class="hidden md:flex items-center gap-1.5 text-xs font-bold tracking-wide uppercase">
                <a href="<?= $basePath ?? '' ?>index.php#hero"
                   class="px-3.5 py-2 rounded-xl transition-colors <?= ($currentScript === 'index.php') ? 'bg-teal-600 text-white font-extrabold shadow-sm' : 'text-slate-700 dark:text-slate-200 hover:text-teal-700 dark:hover:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' ?>">
                    Beranda
                </a>
                <a href="<?= $basePath ?? '' ?>index.php#konsultasi"
                   class="px-3.5 py-2 rounded-xl transition-colors text-slate-700 dark:text-slate-200 hover:text-teal-700 dark:hover:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-800/60">
                    Kalkulator SPK
                </a>
                <a href="<?= $basePath ?? '' ?>index.php#katalog"
                   class="px-3.5 py-2 rounded-xl transition-colors text-slate-700 dark:text-slate-200 hover:text-teal-700 dark:hover:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-800/60">
                    Koleksi Raket
                </a>
                <a href="<?= $basePath ?? '' ?>metode.php"
                   class="px-3.5 py-2 rounded-xl transition-colors <?= ($currentScript === 'metode.php') ? 'bg-teal-600 text-white font-extrabold shadow-sm' : 'text-slate-700 dark:text-slate-200 hover:text-teal-700 dark:hover:text-cyan-400 hover:bg-slate-100 dark:hover:bg-slate-800/60' ?>">
                    Metode SAW
                </a>
            </nav>

            <!-- Actions: Theme Toggle & Admin Button & Hamburger -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Theme Toggle Button -->
                <button id="themeToggleBtnInc" type="button" aria-label="Ganti Tema"
                        class="p-2 sm:p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-teal-500 dark:hover:border-cyan-400 transition-colors">
                    <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>

                <!-- Admin Action Button -->
                <?php if ($isAdminLoggedIn): ?>
                    <a href="<?= $basePath ?? '' ?>admin/dashboard.php" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-cyan-500/15 dark:hover:bg-cyan-500/25 text-white dark:text-cyan-300 dark:border dark:border-cyan-500/40 text-xs font-bold transition">
                        <span>Panel Admin</span>
                    </a>
                    <a href="<?= $basePath ?? '' ?>logout.php" class="hidden sm:inline-flex items-center px-3 py-2 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 text-xs font-bold transition">
                        Keluar
                    </a>
                <?php else: ?>
                    <a href="<?= $basePath ?? '' ?>login.php" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:border-teal-600 dark:hover:border-cyan-400 text-xs font-bold transition">
                        Masuk Admin
                    </a>
                <?php endif; ?>

                <!-- Mobile Hamburger Button -->
                <button type="button" id="mobileMenuBtnInc" class="md:hidden p-2 rounded-xl text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition" aria-label="Menu Navigasi">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer (< 768px) -->
        <div id="mobileMenuDrawerInc" class="hidden md:hidden py-3 border-t border-slate-200 dark:border-slate-800 space-y-1">
            <a href="<?= $basePath ?? '' ?>index.php#hero" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Beranda</a>
            <a href="<?= $basePath ?? '' ?>index.php#konsultasi" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Kalkulator SPK</a>
            <a href="<?= $basePath ?? '' ?>index.php#katalog" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Koleksi Raket</a>
            <a href="<?= $basePath ?? '' ?>metode.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Metode SAW</a>
            <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                <?php if ($isAdminLoggedIn): ?>
                    <a href="<?= $basePath ?? '' ?>admin/dashboard.php" class="block text-center w-full py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs">Dashboard Admin</a>
                    <a href="<?= $basePath ?? '' ?>logout.php" class="block text-center w-full py-2 mt-1 rounded-xl text-rose-500 font-bold text-xs">Keluar</a>
                <?php else: ?>
                    <a href="<?= $basePath ?? '' ?>login.php" class="block text-center w-full py-2.5 rounded-xl border border-teal-600 text-teal-600 dark:text-cyan-300 font-bold text-xs">Login Admin</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
    // Inisialisasi Hamburger Drawer
    const btnDrawer = document.getElementById('mobileMenuBtnInc');
    const drawerEl  = document.getElementById('mobileMenuDrawerInc');
    if (btnDrawer && drawerEl) {
        btnDrawer.addEventListener('click', () => {
            drawerEl.classList.toggle('hidden');
        });
    }

    // Inisialisasi Theme Switcher
    const btnThemeInc = document.getElementById('themeToggleBtnInc');
    if (btnThemeInc) {
        btnThemeInc.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('mjs_theme', isDark ? 'dark' : 'light');
        });
    }
</script>
