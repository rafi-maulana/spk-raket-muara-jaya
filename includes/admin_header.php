<?php
/**
 * ============================================================
 *  includes/admin_header.php — Admin Portal Header & Sticky Glass Navbar
 *  SPK Pemilihan Raket Bulu Tangkis — Muara Jaya Sport
 * ============================================================
 *  STANDAR:
 *   - Sticky Glassmorphism Header (bg-white/95 backdrop-blur-md)
 *   - Indikator Active State Halaman Admin
 *   - Hamburger Menu Responsif Mobile (< 768px)
 *   - Dual-Theme Synchronized (Warm Light & Midnight Dark)
 * ============================================================
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentScript = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$storeName     = $storeSettings['store_name'] ?? 'Muara Jaya Sport';
$storeLogo     = $storeSettings['logo'] ?? '';
$adminUsername = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — ' : '' ?>Admin Portal | <?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?></title>

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
                    },
                    colors: {
                        midnight: {
                            base: '#0B0F17',
                            surface: '#131C2E',
                            border: 'rgba(56, 189, 248, 0.18)',
                            cyan: '#00F0FF',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Theme Initialization Script -->
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
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/custom-fix.css" rel="stylesheet">
</head>
<body class="min-h-screen antialiased bg-[#FBF9F5] dark:bg-[#0B0F17] text-[#334155] dark:text-[#94A3B8] transition-colors flex flex-col pt-16">

<!-- Top Admin Fixed Glassmorphism Navbar (Permanent Fixed Top) -->
<nav class="fixed top-0 left-0 right-0 w-full z-50 bg-white/95 dark:bg-[#131C2E]/95 backdrop-blur-md border-b border-slate-200 dark:border-cyan-500/20 transition-colors shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 min-w-0">
            <!-- Brand & Portal Badge (Tampil Utuh Tanpa Terpotong) -->
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 shrink">
                <?php if (!empty($storeLogo)): ?>
                    <img src="../<?= htmlspecialchars($storeLogo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo" class="w-8 h-8 object-contain rounded-lg shrink-0">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-lg bg-slate-900 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-teal-600 dark:text-cyan-400 font-serif font-black text-xs shrink-0">
                        MJ
                    </div>
                <?php endif; ?>
                <a href="dashboard.php" class="font-serif font-bold text-slate-900 dark:text-white text-sm sm:text-base lg:text-lg hover:text-teal-600 dark:hover:text-cyan-400 transition flex items-center gap-2 whitespace-nowrap min-w-0">
                    <span><?= htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="hidden sm:inline-flex text-[10px] font-sans font-bold bg-teal-500/10 text-teal-700 dark:bg-cyan-500/20 dark:text-cyan-300 border border-teal-500/20 dark:border-cyan-500/40 px-2 py-0.5 rounded uppercase shrink-0">
                        Admin Portal
                    </span>
                </a>
            </div>

            <!-- Desktop Nav Links with Active State -->
            <div class="hidden md:flex items-center gap-2 text-xs font-semibold">
                <!-- Dual-Theme Switcher Button -->
                <button id="themeToggleBtnAdmin" type="button" aria-label="Ganti Tema"
                        class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-teal-500 dark:hover:border-cyan-400 transition">
                    <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <svg class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>

                <a href="dashboard.php" class="px-3 py-1.5 rounded-lg transition <?= ($currentScript === 'dashboard.php') ? 'bg-teal-600 text-white font-bold shadow-sm' : 'border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300' ?>">Dashboard</a>
                <a href="raket.php" class="px-3 py-1.5 rounded-lg transition <?= ($currentScript === 'raket.php' || $currentScript === 'raket_form.php') ? 'bg-teal-600 text-white font-bold shadow-sm' : 'border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300' ?>">Raket</a>
                <a href="kriteria.php" class="px-3 py-1.5 rounded-lg transition <?= ($currentScript === 'kriteria.php') ? 'bg-teal-600 text-white font-bold shadow-sm' : 'border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300' ?>">Kriteria</a>
                <a href="riwayat.php" class="px-3 py-1.5 rounded-lg transition <?= ($currentScript === 'riwayat.php') ? 'bg-teal-600 text-white font-bold shadow-sm' : 'border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300' ?>">Riwayat</a>
                <a href="../index.php" target="_blank" class="px-3 py-1.5 rounded-lg bg-teal-500/10 text-teal-700 dark:bg-teal-500/15 dark:text-teal-300 border border-teal-500/20 dark:border-teal-500/30 hover:bg-teal-500/20 transition">Lihat Toko ↗</a>
                <a href="../logout.php" class="px-3 py-1.5 rounded-lg bg-rose-500/10 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400 border border-rose-500/20 dark:border-rose-500/30 hover:bg-rose-500/20 transition">Keluar</a>
            </div>

            <!-- Mobile Controls (< 768px): Theme Switcher + Hamburger -->
            <div class="flex items-center gap-2 md:hidden">
                <button id="themeToggleBtnAdminMobile" type="button" aria-label="Ganti Tema"
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
            <a href="dashboard.php" class="block px-3 py-2 rounded-lg text-sm font-semibold transition <?= ($currentScript === 'dashboard.php') ? 'bg-teal-600 text-white font-bold' : 'text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">Dashboard</a>
            <a href="raket.php" class="block px-3 py-2 rounded-lg text-sm font-semibold transition <?= ($currentScript === 'raket.php' || $currentScript === 'raket_form.php') ? 'bg-teal-600 text-white font-bold' : 'text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">Data Raket</a>
            <a href="kriteria.php" class="block px-3 py-2 rounded-lg text-sm font-semibold transition <?= ($currentScript === 'kriteria.php') ? 'bg-teal-600 text-white font-bold' : 'text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">Kriteria & Bobot</a>
            <a href="riwayat.php" class="block px-3 py-2 rounded-lg text-sm font-semibold transition <?= ($currentScript === 'riwayat.php') ? 'bg-teal-600 text-white font-bold' : 'text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">Riwayat Konsultasi</a>
            <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2 px-1">
                <a href="../index.php" target="_blank" class="flex-1 text-center py-2 rounded-lg bg-teal-500/10 text-teal-700 dark:text-teal-300 font-bold text-xs">Lihat Toko ↗</a>
                <a href="../logout.php" class="flex-1 text-center py-2 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 font-bold text-xs">Keluar</a>
            </div>
        </div>
    </div>
</nav>

<?php
// Centralized Floating Toast Notification (One-Time Display per PRG / Cookie Backup for Serverless)
$toastFlash = null;
if (!empty($_SESSION['flash'])) {
    $toastFlash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    unset($_SESSION['flash_message']);
} elseif (!empty($_SESSION['flash_message'])) {
    $toastFlash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash']);
} elseif (!empty($_COOKIE['mjs_flash_toast'])) {
    $decoded = json_decode($_COOKIE['mjs_flash_toast'], true);
    if (is_array($decoded)) {
        $toastFlash = $decoded;
    }
}
if (isset($_COOKIE['mjs_flash_toast'])) {
    setcookie('mjs_flash_toast', '', time() - 3600, '/');
    unset($_COOKIE['mjs_flash_toast']);
}

if ($toastFlash && !empty($toastFlash['message'])) {
    $tType      = $toastFlash['type'] ?? 'info';
    $tTitle     = htmlspecialchars($toastFlash['title'] ?? ($tType === 'success' ? 'Aksi Berhasil' : 'Pemberitahuan Error'), ENT_QUOTES, 'UTF-8');
    $tMsg       = htmlspecialchars($toastFlash['message'] ?? '', ENT_QUOTES, 'UTF-8');
    $isSuccess  = ($tType === 'success');
    $borderCol  = $isSuccess ? 'border-emerald-600' : 'border-red-600';
    $titleCol   = $isSuccess ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400';
    $iconCol    = $isSuccess ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
?>
<!-- Centralized Floating Toast -->
<div id="adminToastFloating" class="fixed top-20 right-4 z-50 max-w-sm w-full transition-all duration-300 ease-out transform translate-y-0 opacity-100" role="alert">
    <div class="bg-white dark:bg-slate-800 border-l-4 <?= $borderCol ?> shadow-lg rounded-xl p-4 flex items-start gap-3 border border-slate-200/60 dark:border-slate-700">
        <div class="shrink-0 mt-0.5 <?= $iconCol ?>">
            <?php if ($isSuccess): ?>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?php else: ?>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?php endif; ?>
        </div>
        <div class="flex-1 min-w-0">
            <h4 class="text-xs font-bold uppercase tracking-wider <?= $titleCol ?>"><?= $tTitle ?></h4>
            <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed"><?= $tMsg ?></p>
        </div>
        <button type="button" onclick="dismissAdminToastHeader()" class="shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition p-1 rounded-lg" aria-label="Tutup">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>
</div>
<script>
    function dismissAdminToastHeader() {
        const toast = document.getElementById('adminToastFloating');
        if (toast) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            setTimeout(() => toast.remove(), 300);
        }
    }
    setTimeout(dismissAdminToastHeader, 4000);
</script>
<?php } ?>

<script>
    // Hamburger Drawer Admin
    const btnAdminDrawer = document.getElementById('adminMobileMenuBtn');
    const drawerAdminEl  = document.getElementById('adminMobileDrawer');
    if (btnAdminDrawer && drawerAdminEl) {
        btnAdminDrawer.addEventListener('click', () => {
            drawerAdminEl.classList.toggle('hidden');
        });
    }

    // Theme Switcher Admin
    function toggleAdminTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('mjs_theme', isDark ? 'dark' : 'light');
    }
    const btnThemeAdmin = document.getElementById('themeToggleBtnAdmin');
    const btnThemeAdminMob = document.getElementById('themeToggleBtnAdminMobile');
    if (btnThemeAdmin) btnThemeAdmin.addEventListener('click', toggleAdminTheme);
    if (btnThemeAdminMob) btnThemeAdminMob.addEventListener('click', toggleAdminTheme);
</script>
