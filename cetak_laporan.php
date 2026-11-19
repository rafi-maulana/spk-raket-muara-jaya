<?php
/**
 * ============================================================
 *  cetak_laporan.php — Lembar Cetak Rekomendasi Resmi SPK
 *  Toko Olahraga: Muara Jaya Sport (Pengaturan Dinamis)
 *  Desain: Clean, Formal, Elegan & Print-Friendly (A4)
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/functions.php';

$storeSettings = getStoreSettings($pdo);

// Ambil data hasil sesi terakhir
$lastResult   = $_SESSION['last_result'] ?? null;
$saw          = $lastResult['saw'] ?? null;
$visitorName  = $lastResult['visitor_name'] ?? 'Pengunjung Tamu';
$maxBudget    = $lastResult['max_budget'] ?? null;
$stylePref    = $lastResult['playing_style'] ?? 'All-Around';
$flexPref     = $lastResult['flex_pref'] ?? 'Semua Tingkat';
$consultTime  = !empty($lastResult['time']) && function_exists('formatTanggalIndonesia') 
    ? formatTanggalIndonesia($lastResult['time'], true) 
    : (function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia(null, true) : date('d F Y, H:i'));

// Jika data sesi tidak ditemukan, lakukan kalkulasi default
if (!$saw || empty($saw['ranking'])) {
    $saw = runSAW($pdo, null);
}

// Ambil Top 3 Pemenang
$top3 = [];
$counter = 0;
if ($saw['success'] && !empty($saw['ranking'])) {
    foreach ($saw['ranking'] as $rid => $rk) {
        if ($counter >= 3) break;
        $top3[] = [
            'rank'   => $rk['rank'],
            'score'  => (float)$rk['score'],
            'racket' => $saw['rackets'][$rid]['racket'],
        ];
        $counter++;
    }
}

$docNumber = 'MJS-REC/' . date('Ym') . '/' . str_pad((string)rand(1, 999), 3, '0', STR_PAD_LEFT);

// Penataan Kota & Tanggal Cetak Dinamis (Bogor & Format Bahasa Indonesia Baku)
$printCity         = 'Bogor';
$printDate         = function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia() : date('d F Y');
$printLocationDate = "{$printCity}, {$printDate}";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekomendasi Raket — <?= h($storeSettings['store_name']) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: #E2E8F0;
            color: #0F172A;
            padding: 24px 16px;
            font-size: 12px;
            line-height: 1.5;
        }

        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 16px auto;
            background: #0F172A;
            color: #FFFFFF;
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
        }

        .btn-print {
            background: #0D9488;
            color: #FFFFFF;
        }
        .btn-print:hover {
            background: #0F766E;
        }

        .btn-back {
            background: transparent;
            color: #94A3B8;
            border: 1px solid #334155;
        }
        .btn-back:hover {
            color: #FFFFFF;
            border-color: #94A3B8;
        }

        /* Lembar Kertas Laporan */
        .sheet {
            max-width: 800px;
            margin: 0 auto;
            background: #FFFFFF;
            padding: 40px 48px;
            border-radius: 8px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        /* Kop Surat Toko */
        .kop {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 18px;
            border-bottom: 2.5px solid #0F172A;
            margin-bottom: 20px;
        }

        .kop-brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .kop-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 8px;
        }

        .kop-badge {
            width: 54px;
            height: 54px;
            background: #0F172A;
            color: #00F0FF;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .kop-info h1 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.15;
        }

        .kop-info .tagline {
            font-size: 11px;
            font-weight: 600;
            color: #0D9488;
            margin-top: 2px;
        }

        .kop-contact {
            text-align: right;
            font-size: 10px;
            color: #64748B;
            line-height: 1.4;
        }

        /* Header Dokumen */
        .doc-header {
            text-align: center;
            margin-bottom: 22px;
        }

        .doc-header h2 {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0F172A;
        }

        .doc-header .doc-id {
            font-size: 10px;
            color: #64748B;
            margin-top: 2px;
            font-family: monospace;
        }

        /* Grid Data Konsultasi */
        .meta-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 12px 16px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .meta-item .lbl {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748B;
        }

        .meta-item .val {
            font-size: 11px;
            font-weight: 700;
            color: #0F172A;
            margin-top: 2px;
        }

        /* Section Title */
        .section-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0F172A;
            border-left: 3px solid #0D9488;
            padding-left: 8px;
            margin-bottom: 12px;
        }

        /* Top 3 Podium Cards */
        .podium-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .podium-card {
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            padding: 12px;
            background: #FFFFFF;
            position: relative;
        }

        .podium-card.rank-1 {
            border: 1.5px solid #D97706;
            background: #FFFDF7;
        }

        .podium-rank-badge {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 6px;
        }

        .rank-1 .podium-rank-badge {
            background: #FEF3C7;
            color: #92400E;
        }

        .rank-2 .podium-rank-badge {
            background: #F1F5F9;
            color: #475569;
        }

        .rank-3 .podium-rank-badge {
            background: #FFEDD5;
            color: #9A3412;
        }

        .racket-title {
            font-size: 13px;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.2;
        }

        .racket-brand {
            font-size: 10px;
            font-weight: 700;
            color: #0D9488;
            text-transform: uppercase;
        }

        .racket-price {
            font-size: 12px;
            font-weight: 800;
            color: #0F172A;
            margin: 4px 0 8px 0;
        }

        .specs-mini {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            font-size: 9px;
            background: #F8FAFC;
            padding: 6px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        .rationale-text {
            font-size: 9.5px;
            color: #334155;
            line-height: 1.45;
            border-top: 1px dashed #E2E8F0;
            padding-top: 6px;
        }

        /* Clean Ranking Table */
        .clean-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 24px;
        }

        .clean-table th {
            background: #0F172A;
            color: #FFFFFF;
            padding: 6px 10px;
            text-align: left;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
        }

        .clean-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #E2E8F0;
        }

        .clean-table tr:nth-child(even) td {
            background: #F8FAFC;
        }

        /* Signatures Area */
        .sig-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px solid #E2E8F0;
        }

        .sig-left {
            font-size: 10px;
            color: #64748B;
            max-width: 320px;
            line-height: 1.4;
        }

        .sig-right {
            text-align: center;
            font-size: 10px;
        }

        .sig-space {
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #CBD5E1;
            font-size: 9px;
            font-style: italic;
        }

        .sig-name {
            font-weight: 800;
            color: #0F172A;
            text-decoration: underline;
        }

        /* Print Media Style */
        @media print {
            body {
                background: #FFFFFF !important;
                padding: 0 !important;
                font-size: 10.5pt;
            }
            .no-print-bar {
                display: none !important;
            }
            .sheet {
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            .podium-card {
                break-inside: avoid;
            }
            @page {
                size: A4 portrait;
                margin: 15mm 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- Control Bar (Tidak ikut tercetak) -->
    <div class="no-print-bar">
        <div>
            <strong>Pratinjau Lembar Cetak Rekomendasi</strong>
            <div style="font-size: 10px; opacity: 0.8;">Klik "Cetak Laporan" untuk mencetak ke printer atau simpan sebagai PDF.</div>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="hasil.php" class="btn-action btn-back">← Kembali</a>
            <button onclick="window.print()" class="btn-action btn-print">
                <svg style="width:14px;height:14px;display:inline-block;vertical-align:middle;margin-right:4px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Dokumen (PDF)
            </button>
        </div>
    </div>

    <!-- Lembar Cetak Resmi -->
    <div class="sheet">

        <!-- KOP SURAT IDENTITAS TOKO -->
        <div class="kop">
            <div class="kop-brand">
                <?php if (!empty($storeSettings['logo'])): ?>
                    <img src="<?= h($storeSettings['logo']) ?>" alt="Logo" class="kop-logo">
                <?php else: ?>
                    <div class="kop-badge" style="font-weight:900;letter-spacing:1px;font-size:14px;">MJ</div>
                <?php endif; ?>
                <div class="kop-info">
                    <h1><?= h($storeSettings['store_name']) ?></h1>
                    <div class="tagline"><?= h($storeSettings['store_tagline']) ?></div>
                </div>
            </div>
            <div class="kop-contact">
                <div><strong>Alamat:</strong> <?= h($storeSettings['address']) ?></div>
                <div><strong>Kontak:</strong> <?= h($storeSettings['whatsapp'] ?: $storeSettings['phone']) ?></div>
                <div><strong>Email:</strong> <?= h($storeSettings['email']) ?></div>
            </div>
        </div>

        <!-- JUDUL DOKUMEN -->
        <div class="doc-header">
            <h2>Lembar Hasil Rekomendasi Raket Bulu Tangkis</h2>
            <div class="doc-id">No. Dokumen: <?= h($docNumber) ?> • Tanggal: <?= h($printDate) ?></div>
        </div>

        <!-- PARAMETER SIMULASI PENGUNJUNG -->
        <div class="meta-box">
            <div class="meta-item">
                <div class="lbl">Nama Pengunjung</div>
                <div class="val"><?= h($visitorName) ?></div>
            </div>
            <div class="meta-item">
                <div class="lbl">Batas Anggaran</div>
                <div class="val"><?= $maxBudget ? formatRupiah($maxBudget) : 'Tanpa Batasan' ?></div>
            </div>
            <div class="meta-item">
                <div class="lbl">Karakter Bermain</div>
                <div class="val"><?= h($stylePref ?: 'All-Around') ?></div>
            </div>
            <div class="meta-item">
                <div class="lbl">Preferensi Shaft</div>
                <div class="val"><?= h($flexPref ?: 'Semua Tingkat') ?></div>
            </div>
        </div>

        <!-- REKOMENDASI PODIUM TOP 3 -->
        <div class="section-title">
            Podium Rekomendasi 3 Raket Terbaik
        </div>

        <div class="podium-grid">
            <?php foreach ($top3 as $item):
                $rkt = $item['racket'];
                $rk  = $item['rank'];
                $sc  = $item['score'];
            ?>
            <div class="podium-card <?= $rk === 1 ? 'rank-1' : ($rk === 2 ? 'rank-2' : 'rank-3') ?>">
                <span class="podium-rank-badge">
                    <?= $rk === 1 ? 'Juara Rekomendasi (#1)' : ($rk === 2 ? 'Pilihan Unggul (#2)' : 'Alternatif Terbaik (#3)') ?>
                </span>
                <div class="racket-brand"><?= h($rkt['brand']) ?></div>
                <div class="racket-title"><?= h($rkt['name']) ?></div>
                <div class="racket-price"><?= formatRupiah($rkt['price']) ?></div>

                <div class="specs-mini">
                    <div><strong>Berat:</strong> <?= h($rkt['weight_spec'] ?? '-') ?></div>
                    <div><strong>Flex:</strong> <?= h($rkt['flex_spec'] ?? '-') ?></div>
                    <div><strong>Tension:</strong> <?= h($rkt['tension_spec'] ?? '-') ?></div>
                    <div><strong>Balance:</strong> <?= h($rkt['balance_spec'] ?? '-') ?></div>
                </div>

                <div class="rationale-text">
                    <?= generateRationale($rkt, $stylePref, $flexPref, $maxBudget, $rk) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- TABEL RINGKASAN URUTAN SKOR -->
        <div class="section-title">
            Daftar Peringkat Alternatif Lainnya
        </div>

        <table class="clean-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">Rank</th>
                    <th>Nama Raket</th>
                    <th>Merek</th>
                    <th>Harga</th>
                    <th style="text-align: center;">Skor Preferensi (Vi)</th>
                    <th style="text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($saw['success'] && !empty($saw['ranking'])):
                    $limit = 0;
                    foreach ($saw['ranking'] as $rid => $rkInfo):
                        if ($limit >= 10) break; // Tampilkan 10 teratas di lembar cetak agar tetap 1 halaman
                        $alt = $saw['rackets'][$rid]['racket'];
                ?>
                <tr>
                    <td style="text-align: center; font-weight: 700;"><?= $rkInfo['rank'] ?></td>
                    <td style="font-weight: 700;"><?= h($alt['name']) ?></td>
                    <td><?= h($alt['brand']) ?></td>
                    <td><?= formatRupiah($alt['price']) ?></td>
                    <td style="text-align: center; font-family: monospace; font-weight: 700;">
                        <?= number_format($rkInfo['score'], 4) ?> (<?= number_format($rkInfo['score'] * 100, 1) ?>%)
                    </td>
                    <td style="text-align: center;">
                        <?= $rkInfo['rank'] === 1 ? 'Sangat Direkomendasikan' : ($rkInfo['rank'] <= 3 ? 'Prioritas Unggul' : 'Alternatif') ?>
                    </td>
                </tr>
                <?php
                    $limit++;
                    endforeach;
                endif; ?>
            </tbody>
        </table>

        <!-- PENGESAHAN DOKUMEN RESMI -->
        <div class="sig-container">
            <div class="sig-left">
                <strong>Catatan Resmi:</strong><br>
                Rekomendasi ini dihitung secara matematis menggunakan metode Simple Additive Weighting (SAW) berdasarkan preferensi pengguna. Spesifikasi raket diambil dari katalog resmi turnamen <?= h($storeSettings['store_name']) ?>.
            </div>
            <div class="sig-right">
                <div style="font-weight: 700; color: #0F172A;"><?= h($printLocationDate) ?></div>
                <div style="font-weight: 600; color: #64748B; margin-top: 2px;">Penanggung Jawab Sistem / Toko</div>
                <div class="sig-space">[ Stempel Toko & Tanda Tangan ]</div>
                <div class="sig-name"><?= h($storeSettings['store_name']) ?> Management</div>
            </div>
        </div>

    </div>

</body>
</html>
