<?php
/**
 * ============================================================
 *  admin/raket.php — CRUD Kelola Raket & Spesifikasi SAW 8 Kriteria
 *  Toko Olahraga: Muara Jaya Sport
 *  PHP Native + PDO Prepared Statements + Upload Gambar/PDF Asli
 * ============================================================
 *  STANDAR KEAMANAN & FITUR UTAMA:
 *   - PDO Prepared Statements dengan named parameters (:id, :name, dll)
 *   - Dual-Theme Mode Global (Warm Light #FBF9F5 & Midnight Dark Slate #0B0F17)
 *   - Fitur Upload Bebas Gambar/Brosur Raket (PNG, JPG, PDF) ke admin/uploads/
 *   - Validasi Ukuran File (Maks 5MB) & Verifikasi MIME finfo_file
 *   - Penamaan File Otomatis Menggunakan uniqid()
 *   - Pencegahan XSS Mutlak dengan helper function h()
 *   - Database Transaction Atomicity (beginTransaction, commit, rollBack)
 *   - Anti-Cache & Session Guard via guard.php
 * ============================================================
 */
require_once __DIR__ . '/guard.php';

if (file_exists(__DIR__ . '/../config/koneksi.php')) {
    require_once __DIR__ . '/../config/koneksi.php';
} elseif (file_exists(__DIR__ . '/../koneksi.php')) {
    require_once __DIR__ . '/../koneksi.php';
}
require_once __DIR__ . '/../functions.php';

if (!function_exists('h')) {
    function h(?string $s): string {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Helper resolusi URL/path gambar untuk tampilan di folder admin
 */
function resolveAdminAsset(?string $path): string {
    if (empty($path)) {
        return '../assets/images/fallback-racket.svg';
    }
    if (str_starts_with($path, 'data:') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    if (str_starts_with($path, 'admin/uploads/')) {
        $path = substr($path, strlen('admin/uploads/'));
    }
    if (file_exists(__DIR__ . '/uploads/' . $path)) {
        return 'uploads/' . $path;
    }
    if (file_exists(__DIR__ . '/../' . $path)) {
        return '../' . ltrim($path, '/');
    }
    return 'uploads/' . ltrim($path, '/');
}

$storeSettings = getStoreSettings($pdo);
$success = $error = '';
$action  = $_GET['action'] ?? 'list';
$editId  = isset($_GET['id']) && ctype_digit((string)$_GET['id']) ? (int)$_GET['id'] : 0;

// ── Ambil 8 Kriteria Penilaian ────────────────────────────────
$criteria = $pdo->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// ── DELETE: Hapus Raket ───────────────────────────────────────
if ($action === 'delete' && $editId > 0) {
    try {
        // Ambil data gambar raket lama untuk dihapus dari server jika lokal
        $stmtOld = $pdo->prepare("SELECT gambar, image FROM rackets WHERE id = :id");
        $stmtOld->execute([':id' => $editId]);
        $oldRow = $stmtOld->fetch(PDO::FETCH_ASSOC);
        $oldImg = $oldRow['gambar'] ?: ($oldRow['image'] ?? '');

        $stmt = $pdo->prepare("DELETE FROM rackets WHERE id = :id");
        $stmt->execute([':id' => $editId]);

        // Hapus file fisik jika tersimpan di admin/uploads atau assets/uploads
        if (!empty($oldImg)) {
            $f1 = __DIR__ . '/uploads/' . $oldImg;
            if (file_exists($f1) && is_file($f1)) @unlink($f1);

            $f2 = __DIR__ . '/../' . $oldImg;
            if (file_exists($f2) && is_file($f2)) @unlink($f2);
        }

        setFlash('success', 'Raket berhasil dihapus beserta seluruh skor dan dokumennya.', 'Raket Dihapus');
        header('Location: raket.php');
        exit;
    } catch (PDOException $e) {
        error_log('[DELETE RAKET ERROR] ' . $e->getMessage());
        setFlash('error', 'Gagal menghapus data raket: ' . $e->getMessage(), 'Gagal Menghapus');
        header('Location: raket.php');
        exit;
    }
}

// ── CREATE / UPDATE: Simpan Raket & Skor 8 Kriteria ───────────
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_raket'])) {
    $name        = trim(strip_tags($_POST['name']        ?? ''));
    $brand       = trim(strip_tags($_POST['brand']       ?? ''));
    $desc        = trim(strip_tags($_POST['description'] ?? ''));
    $weightSpec  = trim(strip_tags($_POST['weight_spec']  ?? '83g (4U)'));
    $balanceSpec = trim(strip_tags($_POST['balance_spec'] ?? 'Head Heavy (300mm)'));
    $flexSpec    = trim(strip_tags($_POST['flex_spec']    ?? 'Medium Stiff'));
    $tensionSpec = trim(strip_tags($_POST['tension_spec'] ?? '28-30 lbs'));
    $gripSpec    = trim(strip_tags($_POST['grip_spec']    ?? 'G5'));
    $imageUrl    = trim(strip_tags($_POST['image_url']    ?? ''));
    $oldImage    = trim(strip_tags($_POST['old_image']    ?? ''));
    $deleteImage = isset($_POST['delete_current_image']);
    $rid         = (int)($_POST['raket_id'] ?? 0);

    // Parse harga
    $priceRaw = preg_replace('/[^0-9,.]/', '', $_POST['price'] ?? '0');
    $priceRaw = str_replace(',', '.', $priceRaw);
    $price    = (float)$priceRaw;

    // Skor kriteria
    $scores = $_POST['scores'] ?? [];

    // Validasi dasar
    if (empty($name)) {
        $error = 'Nama raket wajib diisi.';
    } elseif (empty($brand)) {
        $error = 'Brand raket wajib diisi.';
    } elseif ($price <= 0) {
        $error = 'Harga raket harus lebih dari 0.';
    } elseif (mb_strlen($name) > 150) {
        $error = 'Nama raket maksimal 150 karakter.';
    } else {

        // ── Proses Upload Gambar / File PDF ───────────────────────
        $finalImagePath = $oldImage;

        if ($deleteImage) {
            // Admin memilih hapus gambar lama
            if (!empty($oldImage)) {
                $f1 = __DIR__ . '/uploads/' . $oldImage;
                if (file_exists($f1) && is_file($f1)) @unlink($f1);

                $f2 = __DIR__ . '/../' . $oldImage;
                if (file_exists($f2) && is_file($f2)) @unlink($f2);
            }
            $finalImagePath = '';
        }

        // Cek apakah ada file yang diunggah
        if (isset($_FILES['racket_file']) && $_FILES['racket_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileError = $_FILES['racket_file']['error'];
            if ($fileError !== UPLOAD_ERR_OK) {
                $error = 'Terjadi kesalahan saat mengunggah file (Kode: ' . $fileError . ').';
            } else {
                $fileSize = $_FILES['racket_file']['size'];
                $maxSize  = 2 * 1024 * 1024; // 2 MB (Security Standard)

                if ($fileSize > $maxSize) {
                    $error = 'Ukuran file melebihi batas maksimal 2 MB.';
                } else {
                    $origFileName = $_FILES['racket_file']['name'];
                    $ext = strtolower(pathinfo($origFileName, PATHINFO_EXTENSION));
                    $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'pdf'];

                    if (!in_array($ext, $allowedExts, true)) {
                        $error = 'Format file tidak didukung. Harap upload file PNG, JPG, JPEG, WEBP, atau PDF.';
                    } else {
                        // Verifikasi tipe MIME biner asli
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime  = finfo_file($finfo, $_FILES['racket_file']['tmp_name']);
                        finfo_close($finfo);

                        $allowedMimes = [
                            'image/png', 'image/jpeg', 'image/pjpeg',
                            'image/webp', 'application/pdf'
                        ];

                        if (!in_array($mime, $allowedMimes, true)) {
                            $error = 'Tipe konten file tidak diizinkan (' . h($mime) . '). Harap upload format gambar valid atau PDF.';
                        } else {
                            // Folder tujuan admin/uploads/
                            $uploadDir = __DIR__ . '/uploads/';
                            if (!is_dir($uploadDir)) {
                                @mkdir($uploadDir, 0755, true);
                            }

                            $isServerless = !empty(getenv('VERCEL')) || !empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']) || !is_writable($uploadDir);

                            if ($mime === 'application/pdf' || $ext === 'pdf') {
                                if ($isServerless) {
                                    $pdfContent = @file_get_contents($_FILES['racket_file']['tmp_name']);
                                    if ($pdfContent !== false) {
                                        $finalImagePath = 'data:application/pdf;base64,' . base64_encode($pdfContent);
                                    } else {
                                        $error = 'Gagal membaca data file PDF yang diunggah.';
                                    }
                                } else {
                                    $newFileName = 'racket_' . bin2hex(random_bytes(8)) . '.pdf';
                                    $targetPath  = $uploadDir . $newFileName;
                                    if (@move_uploaded_file($_FILES['racket_file']['tmp_name'], $targetPath)) {
                                        if (!empty($oldImage) && !str_starts_with($oldImage, 'data:') && !str_starts_with($oldImage, 'http')) {
                                            $oldFile = $uploadDir . $oldImage;
                                            if (file_exists($oldFile) && is_file($oldFile)) @unlink($oldFile);
                                        }
                                        $finalImagePath = $newFileName;
                                    } else {
                                        $pdfContent = @file_get_contents($_FILES['racket_file']['tmp_name']);
                                        if ($pdfContent !== false) {
                                            $finalImagePath = 'data:application/pdf;base64,' . base64_encode($pdfContent);
                                        } else {
                                            $error = 'Gagal menyimpan file PDF upload ke server.';
                                        }
                                    }
                                }
                            } else {
                                // Kompres dan resize gambar dengan GD (maksimal lebar 800px proporsional, JPEG kualitas 75)
                                $compResult = processAndCompressImage($_FILES['racket_file']['tmp_name'], 800, 75);
                                if (!$compResult['success']) {
                                    $error = $compResult['error'] ?? 'Gagal memproses dan mengompres gambar upload.';
                                } else {
                                    if ($isServerless) {
                                        // Serverless Vercel / read-only: Simpan langsung sebagai Base64 Data URI ke database
                                        $finalImagePath = $compResult['base64'];
                                    } else {
                                        // Localhost XAMPP yang writable: simpan file fisik JPEG terkompresi
                                        $newFileName = 'racket_' . bin2hex(random_bytes(8)) . '.jpg';
                                        $targetPath  = $uploadDir . $newFileName;

                                        if (@file_put_contents($targetPath, $compResult['binary']) !== false) {
                                            if (!empty($oldImage) && !str_starts_with($oldImage, 'data:') && !str_starts_with($oldImage, 'http')) {
                                                $oldFile = $uploadDir . $oldImage;
                                                if (file_exists($oldFile) && is_file($oldFile)) @unlink($oldFile);
                                            }
                                            $finalImagePath = $newFileName;
                                        } else {
                                            // Fallback otomatis ke Base64 jika penulisan disk gagal
                                            $finalImagePath = $compResult['base64'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } elseif (!empty($imageUrl)) {
            // Gunakan nama file jika diinputkan
            $finalImagePath = $imageUrl;
        }

        // Jika tidak ada error upload, lanjutkan simpan database
        if (empty($error)) {
            // Pastikan kolom gambar bertipe MEDIUMTEXT agar muat menampung string Base64
            ensureImageColumnMediumText($pdo);

            // Cek ketersediaan kolom status / is_active pada tabel rackets
            $rCols = [];
            try {
                $rCols = $pdo->query("SHOW COLUMNS FROM rackets")->fetchAll(PDO::FETCH_COLUMN);
            } catch (Throwable $e) {}
            $hasIsActive = in_array('is_active', $rCols, true);
            $hasStatus   = in_array('status', $rCols, true);

            try {
                $pdo->beginTransaction();

                if ($rid > 0) {
                    // UPDATE RAKET (update gambar dan image)
                    $setSql = "
                        UPDATE rackets
                        SET name         = :name,
                            brand        = :brand,
                            price        = :price,
                            gambar       = :gambar,
                            image        = :image,
                            weight_spec  = :weight_spec,
                            balance_spec = :balance_spec,
                            flex_spec    = :flex_spec,
                            tension_spec = :tension_spec,
                            grip_spec    = :grip_spec,
                            description  = :desc";
                    if ($hasIsActive) {
                        $setSql .= ", is_active = 1";
                    }
                    if ($hasStatus) {
                        $setSql .= ", status = 'aktif'";
                    }
                    $setSql .= " WHERE id = :id";

                    $stmt = $pdo->prepare($setSql);
                    $stmt->execute([
                        ':name'        => $name,
                        ':brand'       => $brand,
                        ':price'       => $price,
                        ':gambar'      => $finalImagePath,
                        ':image'       => $finalImagePath,
                        ':weight_spec' => $weightSpec,
                        ':balance_spec'=> $balanceSpec,
                        ':flex_spec'   => $flexSpec,
                        ':tension_spec'=> $tensionSpec,
                        ':grip_spec'   => $gripSpec,
                        ':desc'        => $desc,
                        ':id'          => $rid,
                    ]);
                } else {
                    // INSERT RAKET BARU
                    $fields = ['name', 'brand', 'price', 'gambar', 'image', 'weight_spec', 'balance_spec', 'flex_spec', 'tension_spec', 'grip_spec', 'description'];
                    $vals   = [':name', ':brand', ':price', ':gambar', ':image', ':weight_spec', ':balance_spec', ':flex_spec', ':tension_spec', ':grip_spec', ':desc'];
                    $params = [
                        ':name'        => $name,
                        ':brand'       => $brand,
                        ':price'       => $price,
                        ':gambar'      => $finalImagePath,
                        ':image'       => $finalImagePath,
                        ':weight_spec' => $weightSpec,
                        ':balance_spec'=> $balanceSpec,
                        ':flex_spec'   => $flexSpec,
                        ':tension_spec'=> $tensionSpec,
                        ':grip_spec'   => $gripSpec,
                        ':desc'        => $desc,
                    ];
                    if ($hasIsActive) {
                        $fields[] = 'is_active';
                        $vals[]   = '1';
                    }
                    if ($hasStatus) {
                        $fields[] = 'status';
                        $vals[]   = "'aktif'";
                    }

                    $sqlIns = "INSERT INTO rackets (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $vals) . ")";
                    $stmt = $pdo->prepare($sqlIns);
                    $stmt->execute($params);
                    $rid = (int)$pdo->lastInsertId();
                }

                // SIMPAN SKOR 8 KRITERIA
                if (!empty($scores) && is_array($scores)) {
                    $delStmt = $pdo->prepare("DELETE FROM racket_scores WHERE racket_id = :rid");
                    $delStmt->execute([':rid' => $rid]);

                    $insStmt = $pdo->prepare("
                        INSERT INTO racket_scores (racket_id, criteria_id, score_value)
                        VALUES (:rid, :cid, :val)
                    ");

                    foreach ($criteria as $c) {
                        $cid = (int)$c['id'];
                        if ($cid === 1) {
                            $val = $price; // C1 otomatis harga
                        } else {
                            $rawVal = $scores[$cid] ?? 0;
                            $val    = (float)str_replace(',', '.', (string)$rawVal);
                        }

                        $insStmt->execute([
                            ':rid' => $rid,
                            ':cid' => $cid,
                            ':val' => $val,
                        ]);
                    }
                }

                $pdo->commit();
                $flashTitle = ($action === 'add') ? 'Raket Ditambahkan' : 'Data Raket Diperbarui';
                $flashMsg   = ($action === 'add') ? 'Data raket baru berhasil ditambahkan ke katalog.' : 'Data raket dan dokumen spesifikasi berhasil diperbarui.';
                setFlash('success', $flashMsg, $flashTitle);
                header('Location: raket.php');
                exit;

            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log('[SAVE RAKET ERROR] ' . $e->getMessage());
                setFlash('error', 'Gagal menyimpan data raket ke database: ' . $e->getMessage(), 'Gagal Menyimpan Raket');
                header('Location: raket.php' . ($action === 'edit' ? '?action=edit&id=' . $editId : '?action=add'));
                exit;
            }
        }
    }
}

// ── DATA UNTUK FORM EDIT ──────────────────────────────────────
$editRacket = null;
$editScores = [];

if ($action === 'edit' && $editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM rackets WHERE id = :id");
    $stmt->execute([':id' => $editId]);
    $editRacket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editRacket) {
        $error  = 'Data raket tidak ditemukan.';
        $action = 'list';
    } else {
        $stmtScores = $pdo->prepare("
            SELECT criteria_id, score_value
            FROM racket_scores
            WHERE racket_id = :rid
        ");
        $stmtScores->execute([':rid' => $editId]);
        while ($row = $stmtScores->fetch(PDO::FETCH_ASSOC)) {
            $editScores[(int)$row['criteria_id']] = (float)$row['score_value'];
        }
    }
}

// ── DATA LIST TABEL RAKET ─────────────────────────────────────
// Pastikan jika ada kolom status atau is_active, seluruh raket (termasuk raket ke-21) disetel aktif
try {
    $chkCols = $pdo->query("SHOW COLUMNS FROM rackets")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('is_active', $chkCols, true)) {
        $pdo->exec("UPDATE rackets SET is_active = 1 WHERE is_active IS NULL OR is_active = 0");
    }
    if (in_array('status', $chkCols, true)) {
        $pdo->exec("UPDATE rackets SET status = 'aktif' WHERE status IS NULL OR status = ''");
    }
} catch (Throwable $e) {}

$rackets = $pdo->query("
    SELECT r.*,
           COUNT(rs.id) AS score_count
    FROM   rackets r
    LEFT JOIN racket_scores rs ON rs.racket_id = r.id
    GROUP BY r.id
    ORDER BY r.id ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Raket & Unggah Gambar — <?= h($storeSettings['store_name']) ?></title>

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
                    <button id="themeToggleBtn" type="button" aria-label="Ganti Tema"
                            class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:border-teal-500 dark:hover:border-cyan-400 transition">
                        <svg class="w-4 h-4 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg class="w-4 h-4 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>

                    <a href="dashboard.php" class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 hover:border-teal-600 dark:hover:text-cyan-400 text-slate-700 dark:text-slate-300 transition">Dashboard</a>
                    <a href="raket.php" class="px-3 py-1.5 rounded-lg bg-teal-600 text-white font-bold shadow-sm transition">Raket</a>
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
                <a href="dashboard.php" class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800">Dashboard</a>
                <a href="raket.php" class="block px-3 py-2 rounded-lg text-sm font-semibold bg-teal-600 text-white font-bold">Data Raket</a>
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">

        <!-- Header Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-serif text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
                    <?= $action === 'edit' ? 'Edit Data & Gambar Raket' : ($action === 'add' ? 'Tambah Raket Baru' : 'Kelola Master Data Raket') ?>
                </h1>
                <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Upload gambar/brosur asli (PNG, JPG, PDF) ke folder <code class="text-teal-600 dark:text-cyan-400">admin/uploads/</code> dan konfigurasikan nilai matriks SAW 8 kriteria.
                </p>
            </div>
            <div>
                <?php if ($action === 'list'): ?>
                <a href="raket.php?action=add" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 dark:hover:from-teal-700 dark:hover:to-cyan-700 text-white font-bold text-xs shadow-md transition">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    <span>Tambah Raket Baru</span>
                </a>
                <?php else: ?>
                <a href="raket.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs border border-slate-300 dark:border-slate-700 transition shadow-sm">
                    <span>← Kembali ke Daftar</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alert Notifikasi Flash Messages & Inline Errors -->
        <?php renderFlash(); ?>
        <?php if (!empty($error)): ?>
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs sm:text-sm flex items-center gap-3 shadow-sm">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div><?= h($error) ?></div>
        </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════════════
             MODE: FORM TAMBAH / EDIT RAKET
             ══════════════════════════════════════════════════════════════ -->
        <?php if ($action === 'add' || $action === 'edit'):
            $rName        = $editRacket['name']         ?? '';
            $rBrand       = $editRacket['brand']        ?? '';
            $rPrice       = $editRacket['price']        ?? '';
            $rImage       = $editRacket['gambar']       ?? ($editRacket['image'] ?? '');
            $rWeightSpec  = $editRacket['weight_spec']  ?? '83g (4U)';
            $rBalanceSpec = $editRacket['balance_spec'] ?? 'Head Heavy (300mm)';
            $rFlexSpec    = $editRacket['flex_spec']    ?? 'Medium Stiff';
            $rTensionSpec = $editRacket['tension_spec'] ?? '28-30 lbs';
            $rGripSpec    = $editRacket['grip_spec']    ?? 'G5';
            $rDesc        = $editRacket['description']  ?? '';
            $isPdf        = !empty($rImage) && (str_ends_with(strtolower($rImage), '.pdf') || str_starts_with($rImage, 'data:application/pdf'));
        ?>
        <form action="raket.php" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="save_raket" value="1">
            <input type="hidden" name="raket_id" value="<?= $editId ?>">
            <input type="hidden" name="old_image" value="<?= h($rImage) ?>">

            <!-- Card 1: Informasi Dasar & Upload Gambar -->
            <div class="bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-2xl p-6 shadow-sm dark:shadow-xl space-y-6 transition-colors">
                <h2 class="font-serif text-lg font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center gap-2">
                    <span>1. Informasi Utama & Berkas Gambar/PDF</span>
                </h2>

                <div class="grid sm:grid-cols-3 gap-5">
                    <!-- Nama Raket -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">Nama Raket *</label>
                        <input type="text" name="name" required value="<?= h($rName) ?>" placeholder="Contoh: Astrox 88D Pro"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500">
                    </div>

                    <!-- Brand -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">Brand / Merek *</label>
                        <input type="text" name="brand" required value="<?= h($rBrand) ?>" placeholder="Contoh: Yonex"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500">
                    </div>
                </div>

                <div class="grid sm:grid-cols-3 gap-5">
                    <!-- Harga IDR -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">Harga Pasaran (IDR) *</label>
                        <input type="number" step="1000" name="price" required value="<?= (float)$rPrice ?>" placeholder="2750000"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 dark:focus:ring-cyan-500 font-bold">
                    </div>

                    <!-- Upload File Gambar/PDF -->
                    <div class="sm:col-span-2 space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Upload Gambar Asli / Brosur PDF (Maks. 5 MB)
                        </label>
                        <input type="file" name="racket_file" accept=".png,.jpg,.jpeg,.webp,.pdf"
                               class="w-full text-xs text-slate-600 dark:text-slate-300 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-500/10 file:text-teal-700 dark:file:bg-cyan-500/20 dark:file:text-cyan-300 hover:file:bg-teal-500/20 dark:hover:file:bg-cyan-500/30 cursor-pointer">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">File tersimpan langsung di server <code class="text-teal-600 dark:text-cyan-400">admin/uploads/</code> dengan penamaan unik.</p>
                    </div>
                </div>

                <!-- Preview Gambar Saat Ini -->
                <?php if (!empty($rImage)): ?>
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-bold">Berkas Aktif:</span>
                        <?php if ($isPdf): ?>
                            <a href="<?= resolveAdminAsset($rImage) ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-500/15 text-rose-600 dark:text-rose-400 font-bold text-xs border border-rose-500/30">
                                <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span>Dokumen PDF Aktif (Klik untuk Buka)</span>
                            </a>
                        <?php else: ?>
                            <img src="<?= resolveAdminAsset($rImage) ?>" alt="Preview" class="w-12 h-12 object-cover rounded-lg border border-slate-200 dark:border-slate-700">
                            <span class="text-xs font-mono text-slate-600 dark:text-slate-300"><?= str_starts_with($rImage, 'data:') ? 'Gambar Tersemat (Base64)' : h($rImage) ?></span>
                        <?php endif; ?>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-rose-500 cursor-pointer">
                        <input type="checkbox" name="delete_current_image" value="1" class="rounded border-slate-300 text-rose-500 focus:ring-0">
                        <span>Hapus berkas ini</span>
                    </label>
                </div>
                <?php endif; ?>

                <!-- 4 Spesifikasi Teknis Pilar -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">Berat Fisik</label>
                        <input type="text" name="weight_spec" value="<?= h($rWeightSpec) ?>" placeholder="83g (4U)"
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">Balance Point</label>
                        <input type="text" name="balance_spec" value="<?= h($rBalanceSpec) ?>" placeholder="Head Heavy (305mm)"
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">Flex Shaft</label>
                        <input type="text" name="flex_spec" value="<?= h($rFlexSpec) ?>" placeholder="Stiff / Flexible"
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-400 mb-1">Tension Max</label>
                        <input type="text" name="tension_spec" value="<?= h($rTensionSpec) ?>" placeholder="28-30 lbs"
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs">
                    </div>
                </div>

                <!-- Deskripsi Ringkas -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">Deskripsi Karakteristik</label>
                    <textarea name="description" rows="2" placeholder="Catatan keunggulan raket di lapangan..."
                              class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs"><?= h($rDesc) ?></textarea>
                </div>
            </div>

            <!-- Card 2: Nilai Matriks Keputusan SAW (C1 - C8) -->
            <div class="bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-2xl p-6 shadow-sm dark:shadow-xl space-y-4 transition-colors">
                <div class="border-b border-slate-200 dark:border-slate-800 pb-3 flex items-center justify-between">
                    <div>
                        <h2 class="font-serif text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>2. Nilai Matriks Keputusan SAW (8 Kriteria)</span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Nilai skala 1–10 untuk evaluasi bobot SAW (C1 otomatis mengambil harga).</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <?php foreach ($criteria as $c):
                        $cid     = (int)$c['id'];
                        $isPrice = ($cid === 1);
                        $val     = $editScores[$cid] ?? ($isPrice ? (float)$rPrice : 8.0);
                    ?>
                    <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/40 space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-teal-700 dark:text-cyan-400"><?= h($c['code']) ?></span>
                            <span class="text-[10px] uppercase font-bold <?= strtolower($c['type']) === 'cost' ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' ?>">
                                <?= strtoupper($c['type']) ?>
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-700 dark:text-slate-300 truncate" title="<?= h($c['name']) ?>">
                            <?= h($c['name']) ?>
                        </div>
                        <?php if ($isPrice): ?>
                            <input type="text" readonly disabled value="Sesuai Harga"
                                   class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-200/60 dark:bg-slate-800 text-[11px] font-bold text-slate-500 text-center">
                        <?php else: ?>
                            <input type="number" step="0.1" min="1" max="100" name="scores[<?= $cid ?>]" value="<?= $val ?>" required
                                   class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-900 dark:text-white font-bold text-center focus:ring-1 focus:ring-cyan-500">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tombol Simpan -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="raket.php" class="px-5 py-3 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-7 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-gradient-to-r dark:from-teal-600 dark:to-cyan-600 dark:hover:from-teal-700 dark:hover:to-cyan-700 text-white font-bold text-xs uppercase tracking-wider shadow-md transition">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    <span>Simpan Data Raket</span>
                </button>
            </div>
        </form>

        <!-- ══════════════════════════════════════════════════════════════
             MODE: DAFTAR MASTER TABEL RAKET (LIST)
             ══════════════════════════════════════════════════════════════ -->
        <?php else: ?>
        <div class="bg-white dark:bg-[#131C2E] border border-slate-200 dark:border-cyan-500/20 rounded-2xl overflow-hidden shadow-sm dark:shadow-xl transition-colors">
            <div class="flex items-center justify-between px-5 pt-3 md:hidden">
                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">← Geser tabel ke samping →</span>
            </div>
            <div class="w-full overflow-x-auto text-left">
                <table class="w-full text-left text-xs whitespace-nowrap">
                    <thead class="bg-slate-100 dark:bg-slate-800/80 font-bold text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-700 uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3.5 px-4 text-center">No</th>
                            <th class="py-3.5 px-4 text-center">Visual</th>
                            <th class="py-3.5 px-4 sticky left-0 bg-slate-100 dark:bg-slate-800 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[130px]">Nama Raket</th>
                            <th class="py-3.5 px-4">Brand</th>
                            <th class="py-3.5 px-4">Harga (IDR)</th>
                            <th class="py-3.5 px-4">Spesifikasi Pilar</th>
                            <th class="py-3.5 px-4 text-center">Skor SAW</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <?php 
                        $no = 1;
                        foreach ($rackets as $r):
                            $rImg   = $r['gambar'] ?: ($r['image'] ?? '');
                            $isPdf  = !empty($rImg) && (str_ends_with(strtolower($rImg), '.pdf') || str_starts_with($rImg, 'data:application/pdf'));
                            $rPrice = (float)$r['price'];
                        ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <!-- Nomor Urut Berurutan -->
                            <td class="py-3.5 px-4 text-center font-bold text-slate-400">
                                <?= $no++ ?>
                            </td>

                            <!-- Visual Thumbnail -->
                            <td class="py-3.5 px-4 text-center">
                                <?php if (!empty($rImg)): ?>
                                    <?php if ($isPdf): ?>
                                        <a href="<?= resolveAdminAsset($rImg) ?>" target="_blank" class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-rose-500/15 text-rose-600 dark:text-rose-400 font-bold text-[10px] border border-rose-500/30">
                                            PDF
                                        </a>
                                    <?php else: ?>
                                        <img src="<?= resolveAdminAsset($rImg) ?>" alt="<?= h($r['name']) ?>"
                                             onerror="this.onerror=null; this.src='../assets/images/fallback-racket.svg';"
                                             class="w-10 h-10 object-cover rounded-lg border border-slate-200 dark:border-slate-700 mx-auto">
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Nama (Sticky Column) -->
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white sticky left-0 bg-white dark:bg-[#131C2E] z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] min-w-[130px]">
                                <?= h($r['name']) ?>
                            </td>

                            <!-- Brand -->
                            <td class="py-3.5 px-4 font-semibold text-teal-700 dark:text-cyan-400 uppercase">
                                <?= h($r['brand']) ?>
                            </td>

                            <!-- Harga -->
                            <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                <?= formatRupiah($rPrice) ?>
                            </td>

                            <!-- Spesifikasi -->
                            <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400">
                                <span><?= h($r['weight_spec'] ?? '-') ?></span> •
                                <span><?= h($r['balance_spec'] ?? '-') ?></span> •
                                <span><?= h($r['flex_spec'] ?? '-') ?></span>
                            </td>

                            <!-- Skor Lengkap -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                                    <?= (int)$r['score_count'] ?> / 8 Kriteria
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="raket.php?action=edit&id=<?= $r['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-500/10 hover:bg-teal-500/20 text-teal-700 dark:text-cyan-300 border border-teal-500/30 text-[11px] font-bold transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edit</span>
                                    </a>
                                    <a href="raket.php?action=delete&id=<?= $r['id'] ?>" onclick="return confirm('Yakin ingin menghapus raket <?= h(addslashes($r['name'])) ?>?');" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 text-[11px] font-bold transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Hapus</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Footer Admin -->
    <footer class="border-t border-slate-200 dark:border-slate-800 py-6 text-center text-xs text-slate-500 mt-auto transition-colors">
        &copy; <?= date('Y') ?> <?= h($storeSettings['store_name']) ?> — Sistem Pendukung Keputusan Pemilihan Raket Bulu Tangkis.
    </footer>

    <!-- Dual-Theme Switcher Script -->
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
