<?php
if (ob_get_level() === 0) {
    ob_start();
}
/**
 * ============================================================
 *  login.php — Admin Login Portal | Muara Jaya Sport SPK
 *  Security: Anti-cache headers, CSRF, Session Fixation Guard
 *  Design System: Studio-Grade Tailwind CSS Dual-Theme
 * ============================================================
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika sudah login
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if (!headers_sent()) {
        header('Location: admin/dashboard.php');
        exit;
    }
    echo '<script>window.location.href="admin/dashboard.php";</script>';
    exit;
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/functions.php';

$storeSettings = getStoreSettings($pdo);

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$error   = '';
$success = '';

// Proses Form Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf_token, $submitted_token)) {
        $error = 'Token keamanan CSRF tidak valid. Silakan muat ulang halaman.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username dan password wajib diisi.';
        } else {
            try {
                // Cek di tabel admin
                $stmt = $pdo->prepare('SELECT * FROM admin WHERE username = :username LIMIT 1');
                $stmt->execute([':username' => $username]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                // Fallback ke tabel users jika di tabel admin tidak ditemukan
                if (!$row) {
                    $stmtUsers = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
                    $stmtUsers->execute([':username' => $username]);
                    $row = $stmtUsers->fetch(PDO::FETCH_ASSOC);
                }

                if ($row && password_verify($password, $row['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['csrf_token']       = bin2hex(random_bytes(32));
                    $_SESSION['admin_login_time'] = time();
                    $_SESSION['admin_logged_in']  = true;
                    $_SESSION['admin_id']         = $row['id'];
                    $_SESSION['admin_username']   = $row['username'];

                    // Stateless HMAC Authentication Cookie (Kompatibilitas Serverless Vercel)
                    require_once __DIR__ . '/includes/auth_stateless.php';
                    createAdminAuthCookie($row['id'], $row['username']);

                    $redirectTo = 'admin/dashboard.php';
                    if (!empty($_SESSION['redirect_after_login'])) {
                        $intended = $_SESSION['redirect_after_login'];
                        if (str_starts_with($intended, 'admin/')) {
                            $redirectTo = $intended;
                        }
                        unset($_SESSION['redirect_after_login']);
                    }

                    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                    header('Location: ' . $redirectTo);
                    exit;
                } else {
                    $error = 'Username atau password yang Anda masukkan salah.';
                }
            } catch (PDOException $e) {
                error_log('Login PDOException: ' . $e->getMessage());
                $error = 'Terjadi kesalahan sistem database. Silakan coba beberapa saat lagi.';
            }
        }
    }
}

// Parameter alert dari redirect
$reason = $_GET['reason'] ?? '';
if ($reason === 'unauthorized') {
    $error = 'Sesi Anda tidak valid. Silakan login terlebih dahulu untuk mengakses panel admin.';
} elseif ($reason === 'timeout') {
    $error = 'Sesi Anda telah berakhir karena waktu tunggu. Silakan login kembali.';
} elseif ($reason === 'logout') {
    $success = 'Anda telah berhasil keluar dari sistem.';
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login Administrator — <?= h($storeSettings['store_name']) ?>">
    <title>Login Admin — <?= h($storeSettings['store_name']) ?></title>

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
    <link rel="stylesheet" href="assets/css/custom-fix.css">

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
<body class="min-h-screen antialiased flex flex-col justify-between transition-colors relative">

    <!-- Floating Theme Switcher & Back Link -->
    <div class="absolute top-6 left-6 right-6 flex items-center justify-between z-20">
        <a href="index.php" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/80 dark:bg-slate-800/80 backdrop-blur border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-teal-600 dark:hover:text-cyan-400 transition shadow-sm">
            ← Kembali ke Website
        </a>

        <button id="themeToggleBtn" type="button" aria-label="Ganti Tema"
                class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 backdrop-blur text-slate-700 dark:text-slate-200 hover:border-teal-500 dark:hover:border-cyan-400 transition shadow-sm">
            <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <svg class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        </button>
    </div>

    <!-- Main Container Card -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 z-10 my-16">
        <div class="w-full max-w-md bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">

            <!-- Brand Header -->
            <div class="text-center space-y-2">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-teal-500/10 dark:bg-cyan-500/10 border border-teal-500/30 dark:border-cyan-500/30 flex items-center justify-center text-3xl shadow-sm">
                    <?php if (!empty($storeSettings['logo'])): ?>
                        <img src="<?= h($storeSettings['logo']) ?>" alt="Logo" class="w-12 h-12 object-contain rounded-xl">
                    <?php else: ?>
                        <span class="font-serif font-black text-xl text-teal-600 dark:text-cyan-400">MJ</span>
                    <?php endif; ?>
                </div>
                <h1 class="font-serif font-extrabold text-2xl text-slate-900 dark:text-white">
                    <?= h($storeSettings['store_name']) ?>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Masuk ke Portal Administrator SPK SAW
                </p>
            </div>

            <!-- Notification Alerts -->
            <?php if (!empty($success)): ?>
                <div class="p-3.5 rounded-2xl bg-teal-500/10 border border-teal-500/30 text-teal-800 dark:text-teal-300 text-xs flex items-center gap-2">
                    <svg class="w-4 h-4 text-teal-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <span><?= h($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span><?= h($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form method="POST" action="login.php" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">

                <div>
                    <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Username Administrator
                    </label>
                    <input type="text" id="username" name="username" required autocomplete="username" autofocus
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500 transition"
                           placeholder="admin">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Kata Sandi
                        </label>
                    </div>
                    <div class="relative">
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900/80 text-slate-900 dark:text-white text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500 transition pr-10"
                               placeholder="••••••••">
                        <button type="button" onclick="togglePasswordVisibility()" aria-label="Lihat Sandi"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 dark:hover:from-teal-700 dark:hover:to-cyan-700 text-white font-bold text-xs uppercase tracking-wider shadow-lg transition">
                    Masuk ke Sistem Admin →
                </button>
            </form>

            <div class="pt-2 text-center border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400">
                Default Akun: <code class="font-mono text-teal-600 dark:text-cyan-400 font-bold">admin</code> / <code class="font-mono text-teal-600 dark:text-cyan-400 font-bold">admin123</code>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 text-center text-xs text-slate-500 dark:text-slate-400 z-10">
        &copy; <?= date('Y') ?> <?= h($storeSettings['store_name']) ?>. Sistem Pendukung Keputusan SAW.
    </footer>

    <!-- Script Password Toggle & Theme Toggle -->
    <script>
        function togglePasswordVisibility() {
            const pwd = document.getElementById('password');
            if (pwd.type === 'password') {
                pwd.type = 'text';
            } else {
                pwd.type = 'password';
            }
        }

        const themeToggleBtn = document.getElementById('themeToggleBtn');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('mjs_theme', isDark ? 'dark' : 'light');
            });
        }
    </script>
</body>
</html>
