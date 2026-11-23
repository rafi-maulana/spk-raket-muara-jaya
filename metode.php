<?php
if (ob_get_level() === 0) {
    ob_start();
}
/**
 * ============================================================
 *  metode.php — Penjelasan Metode SAW Secara Bersih & Bernarasi
 *  Sistem Pendukung Keputusan Pemilihan Raket Bulu Tangkis
 *  Muara Jaya Sport | Dual-Theme Tailwind CSS
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/functions.php';

$storeSettings = getStoreSettings($pdo);
$criteriaList  = getCriteria($pdo);
$isAdmin       = isset($_SESSION['admin']) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Penjelasan sederhana dan bernarasi tentang metode Simple Additive Weighting (SAW) di <?= h($storeSettings['store_name']) ?>.">
    <title>Metode & Kriteria Penilaian — <?= h($storeSettings['store_name']) ?></title>

    <!-- Google Fonts -->
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
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                        serif: ['Playfair Display', 'Georgia', 'serif'],
                    },
                    colors: {
                        warm: {
                            50: '#FBF9F5',
                            100: '#F5EFE6',
                            card: '#FFFFFF',
                            border: '#E8E1D5',
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

    <style>
        :root {
            --bg-canvas: #FBF9F5;
            --text-heading: #0F172A;
            --text-body: #334155;
            --card-border: #E8E1D5;
        }
        html.dark {
            --bg-canvas: #0B0F17;
            --text-heading: #F8FAFC;
            --text-body: #94A3B8;
            --card-border: rgba(56, 189, 248, 0.16);
        }
        html, body {
            overflow-x: clip;
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
        .luxury-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border: 1px solid var(--card-border);
        }
        html.dark .luxury-card {
            background: rgba(19, 28, 46, 0.85);
            border: 1px solid rgba(56, 189, 248, 0.16);
        }
    </style>

    <!-- Custom Corporate Layout Fixes -->
    <link href="assets/css/custom-fix.css" rel="stylesheet">
</head>

<body class="min-h-screen antialiased flex flex-col selection:bg-teal-500 selection:text-white pt-16">

    <!-- Navbar (Fixed Glassmorphism Dual-Theme) -->
    <header class="fixed top-0 left-0 right-0 w-full z-50 bg-white/95 dark:bg-[#0B0F17]/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 transition-colors shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 min-w-0">

                <!-- Brand Monogram & Name (Tampil Utuh Tanpa Terpotong) -->
                <a href="index.php" class="flex items-center gap-2.5 sm:gap-3 group min-w-0 shrink">
                    <?php if (!empty($storeSettings['logo'])): ?>
                        <img src="<?= h($storeSettings['logo']) ?>" alt="<?= h($storeSettings['store_name']) ?>" class="w-8 h-8 sm:w-9 sm:h-9 object-contain rounded-xl group-hover:scale-105 transition-transform duration-200 shrink-0">
                    <?php else: ?>
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-br from-teal-700 to-cyan-500 flex items-center justify-center text-white font-serif-heading font-black text-xs sm:text-sm shadow-sm shrink-0">
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
                        <p class="hidden sm:block text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-medium tracking-wide truncate">Metode & Kriteria SAW <?= count($criteriaList) ?> Kriteria</p>
                    </div>
                </a>

                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Theme Toggle -->
                    <button id="themeToggleBtn" type="button" aria-label="Ganti Tema"
                            class="p-2 sm:p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-teal-500 dark:hover:border-cyan-400 transition">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>

                    <!-- Desktop Actions -->
                    <div class="hidden md:flex items-center gap-2">
                        <a href="index.php" class="px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-teal-600 dark:hover:text-cyan-400 text-xs font-bold transition">
                            ← Beranda
                        </a>
                        <a href="index.php#konsultasi" class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 text-white font-bold text-xs shadow transition">
                            Mulai Simulasi
                        </a>
                    </div>

                    <!-- Mobile Hamburger Menu Button (< 768px) -->
                    <button type="button" id="mobileMenuBtnMetode" class="md:hidden p-2 rounded-xl text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition" aria-label="Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>

            </div>

            <!-- Mobile Drawer (< 768px) -->
            <div id="mobileMenuDrawerMetode" class="hidden md:hidden py-3 border-t border-slate-200 dark:border-slate-800 space-y-1">
                <a href="index.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">← Beranda Utama</a>
                <a href="index.php#konsultasi" class="block px-3 py-2 rounded-lg text-sm font-semibold text-teal-700 dark:text-cyan-400 font-bold bg-teal-500/10">Mulai Simulasi SPK</a>
                <a href="index.php#katalog" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Koleksi Raket</a>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 flex-1 space-y-10 min-w-0 max-w-full">

        <!-- Header Bernarasi (Ukuran Responsif Anti-Potong di HP) -->
        <div class="text-center max-w-3xl mx-auto space-y-3 min-w-0">
            <span class="inline-block px-3 py-1 rounded-full text-xs font-extrabold uppercase tracking-wider bg-teal-500/10 text-teal-700 dark:text-cyan-400 border border-teal-500/20">
                Transparansi & Logika Keputusan
            </span>
            <h1 class="font-serif-heading text-2xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white leading-tight break-words">
                Bagaimana Sistem Memilihkan Raket Terbaik untuk Anda?
            </h1>
            <p class="text-xs sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
                Kami tidak mengandalkan tebak-tebakan atau promosi sepihak. Sistem ini menggunakan metode <strong>Simple Additive Weighting (SAW)</strong> untuk mencocokkan gaya ayunan, kekuatan tangan, dan batas anggaran Anda secara adil dan objektif.
            </p>
        </div>

        <!-- 4 Pilar Konsep SAW yang Ramah Pengguna Awam (Tanpa Rumus Pusing) -->
        <div class="grid md:grid-cols-2 gap-6 min-w-0 max-w-full">

            <!-- Konsep 1: Penjumlahan Terbobot -->
            <div class="luxury-card rounded-3xl p-6 sm:p-8 space-y-3 border min-w-0">
                <div class="icon-box-md rounded-2xl bg-teal-500/15 text-teal-600 dark:text-cyan-400 border border-teal-500/25">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z" />
                    </svg>
                </div>
                <h3 class="font-serif-heading text-xl font-bold text-slate-900 dark:text-white">
                    1. Prinsip Keseimbangan Semua Aspek
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Setiap raket dievaluasi secara objektif dan komprehensif berdasarkan <strong><?= count($criteriaList) ?> kriteria penting</strong>. Tidak ada raket yang dinilai hanya dari nama merek semata.
                </p>
            </div>

            <!-- Konsep 2: Cost vs Benefit -->
            <div class="luxury-card rounded-3xl p-6 sm:p-8 space-y-3 border min-w-0">
                <div class="icon-box-md rounded-2xl bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/25">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <h3 class="font-serif-heading text-xl font-bold text-slate-900 dark:text-white">
                    2. Kriteria Keuntungan (Benefit) vs Biaya (Cost)
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Kriteria <strong>Biaya (Harga & Berat)</strong> dinilai semakin baik jika semakin terjangkau dan semakin ringan di tangan. Sebaliknya, kriteria <strong>Keuntungan (Flex, Balance, Tension, Durabilitas)</strong> dinilai semakin unggul saat memiliki daya tahan dan kemampuan ayunan yang tinggi.
                </p>
            </div>

            <!-- Konsep 3: Personalisasi Gaya Main -->
            <div class="luxury-card rounded-3xl p-6 sm:p-8 space-y-3 border min-w-0">
                <div class="icon-box-md rounded-2xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                    </svg>
                </div>
                <h3 class="font-serif-heading text-xl font-bold text-slate-900 dark:text-white">
                    3. Fleksibilitas Menyesuaikan Gaya Bermain Anda
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Jika Anda memilih tipe <strong>Power & Smash</strong>, sistem otomatis memprioritaskan raket <em>Head Heavy</em> dan batang kaku (*stiff*). Namun jika Anda memilih <strong>Speed & Drive</strong>, sistem akan mengutamakan raket ringan (6U/5U/4U) dengan manuver lincah.
                </p>
            </div>

            <!-- Konsep 4: Podium Top 3 Transparan -->
            <div class="luxury-card rounded-3xl p-6 sm:p-8 space-y-3 border min-w-0">
                <div class="icon-box-md rounded-2xl bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border border-cyan-500/25">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.004 0V9A4.5 4.5 0 009.875 4.5H9a4.5 4.5 0 00-4.5 4.5v1.5a4.5 4.5 0 004.5 4.5h.375m4.5 0V9a4.5 4.5 0 014.5-4.5h.875A4.5 4.5 0 0124 9v1.5a4.5 4.5 0 01-4.5 4.5h-.375" />
                    </svg>
                </div>
                <h3 class="font-serif-heading text-xl font-bold text-slate-900 dark:text-white">
                    4. Hasil Top 3 Disertai Alasan Masuk Akal
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Sistem tidak hanya memberikan nomor 1, melainkan menghadirkan <strong>Podium Top 3 Rekomendasi</strong>. Di bawah setiap raket, disajikan ringkasan alasan (Rationale) mengapa raket tersebut cocok dengan karakteristik fisik dan sisa anggaran Anda.
                </p>
            </div>

        </div>

        <!-- Tabel 8 Kriteria Dinamis (Fokus Penjelasan Manfaat & Bobot Real-Time) -->
        <div class="bg-white dark:bg-[#131C2E] rounded-3xl p-6 sm:p-8 border border-slate-200 dark:border-slate-800 shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="font-serif-heading text-2xl font-bold text-slate-900 dark:text-white">
                        Daftar <?= count($criteriaList) ?> Kriteria Penilaian Objektif
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                        Seluruh alternatif raket diuji secara setara menggunakan <?= count($criteriaList) ?> parameter matematis berikut:
                    </p>
                </div>
                <span class="text-[11px] text-slate-400 dark:text-slate-500 md:hidden font-medium">← Geser tabel ke samping →</span>
            </div>

            <div class="w-full overflow-x-auto pb-2 text-left rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
                <table class="w-full min-w-[650px] text-left text-sm whitespace-nowrap">
                    <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 font-bold uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="py-3.5 px-4 font-bold text-slate-700 dark:text-slate-300">Kode & Kriteria</th>
                            <th class="py-3.5 px-4 whitespace-nowrap">Sifat / Tipe</th>
                            <th class="py-3.5 px-4 text-center whitespace-nowrap">Bobot Relatif (Wj)</th>
                            <th class="py-3.5 px-4">Karakteristik & Manfaat Bagi Pemain</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <?php foreach ($criteriaList as $c): 
                            $isCost = strtolower($c['type']) === 'cost';
                            $badge = $isCost ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
                        ?>
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors text-sm">
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                <span class="text-teal-700 dark:text-cyan-400"><?= h($c['code']) ?>:</span> <?= h($c['name']) ?>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap <?= $badge ?>">
                                    <?= strtoupper(h($c['type'])) ?> (<?= $isCost ? 'Hemat/Ringan' : 'Keuntungan' ?>)
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap font-mono font-bold text-slate-900 dark:text-slate-100 text-sm">
                                <?= number_format($c['weight'], 4) ?> <span class="text-xs text-slate-400 font-sans font-normal">(<?= number_format($c['weight'] * 100, 1) ?>%)</span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300 text-sm">
                                <?= h($c['description'] ?: 'Parameter evaluasi objektif sistem pendukung keputusan.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Banner Call-to-Action -->
        <div class="luxury-card rounded-3xl p-8 sm:p-10 border text-center space-y-4">
            <h3 class="font-serif-heading text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                Siap Menemukan Raket Terbaik Anda?
            </h3>
            <p class="text-sm text-slate-600 dark:text-slate-400 max-w-xl mx-auto">
                Coba langsung kalkulator interaktif kami sekarang. Atur anggaran Anda dan dapatkan 3 raket terbaik dalam hitungan detik.
            </p>
            <div class="pt-2">
                <a href="index.php#konsultasi" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 text-white font-extrabold text-sm uppercase tracking-wider shadow-lg transition">
                    <span>Mulai Hitung Rekomendasi</span>
                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        </div>

    </main>

    <!-- Footer Dinamis Toko -->
    <footer class="border-t border-slate-200 dark:border-slate-800 py-10 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <span class="font-serif-heading font-bold text-slate-900 dark:text-white block"><?= h($storeSettings['store_name']) ?></span>
                    <span class="text-xs text-slate-500 dark:text-slate-400"><?= h($storeSettings['address']) ?></span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 text-center md:text-right">
                    Hubungi: <?= h($storeSettings['whatsapp'] ?: $storeSettings['phone']) ?> • Email: <?= h($storeSettings['email']) ?>
                    <div class="mt-1">&copy; <?= date('Y') ?> <?= h($storeSettings['store_name']) ?>. All rights reserved.</div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('mjs_theme', isDark ? 'dark' : 'light');
            });
        }

        const mobileMenuBtnMetode = document.getElementById('mobileMenuBtnMetode');
        const mobileMenuDrawerMetode = document.getElementById('mobileMenuDrawerMetode');
        if (mobileMenuBtnMetode && mobileMenuDrawerMetode) {
            mobileMenuBtnMetode.addEventListener('click', () => {
                mobileMenuDrawerMetode.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
