<?php
/**
 * ============================================================
 *  functions.php — Backend SAW Engine & Core Business Logic
 *  SPK Rekomendasi Raket Bulu Tangkis | Muara Jaya Sport
 * ============================================================
 *
 *  Implementasi Metode Simple Additive Weighting (SAW) 8 Kriteria:
 *    C1: Harga (Cost)
 *    C2: Berat Raket (Cost)
 *    C3: Flexibilitas Shaft (Benefit)
 *    C4: Balance Point / Titik Keseimbangan (Benefit)
 *    C5: Tarikan Senar / Tension (Benefit)
 *    C6: Ukuran Grip (Benefit)
 *    C7: Daya Tahan / Durabilitas (Benefit)
 *    C8: Kontrol & Manufaktur (Benefit)
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Inisialisasi Koneksi Database ─────────────────────────────
if (file_exists(__DIR__ . '/config/koneksi.php')) {
    require_once __DIR__ . '/config/koneksi.php';
} elseif (file_exists(__DIR__ . '/koneksi.php')) {
    require_once __DIR__ . '/koneksi.php';
} elseif (file_exists(__DIR__ . '/config/database.php')) {
    require_once __DIR__ . '/config/database.php';
}

// ── Global Sanitasi XSS ───────────────────────────────────────
if (!function_exists('h')) {
    function h(?string $data): string {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// ── Koneksi Helper Fallback ───────────────────────────────────
if (!function_exists('getDB')) {
    function getDB(): PDO {
        global $pdo;
        if (isset($pdo) && $pdo instanceof PDO) {
            return $pdo;
        }
        throw new RuntimeException('Koneksi PDO tidak tersedia.');
    }
}

// ── Pengaturan Identitas Toko Dinamis ──────────────────────────
if (!function_exists('getStoreSettings')) {
    /**
     * Mengambil data identitas toko terkini dari database
     *
     * @param  PDO   $pdo
     * @return array
     */
    function getStoreSettings(PDO $pdo): array {
        try {
            $stmt = $pdo->query("SELECT * FROM store_settings WHERE id = 1 LIMIT 1");
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return $data;
            }
        } catch (PDOException $e) {
            error_log("Error getStoreSettings: " . $e->getMessage());
        }

        return [
            'id'            => 1,
            'store_name'    => 'Muara Jaya Sport',
            'store_tagline' => 'Toko Perlengkapan Bulu Tangkis Profesional & Terlengkap',
            'address'       => 'Kp. Muara jaya, RT.01/RW.05, Ciaruteun Ilir, Kec. Cibungbulang, Kabupaten Bogor, Jawa Barat 16630',
            'phone'         => '+62 877-0633-5584',
            'whatsapp'      => '087706335584',
            'email'         => 'muarajayasport@gmail.com',
            'logo'          => '',
        ];
    }
}

if (!function_exists('updateStoreSettings')) {
    /**
     * Memperbarui pengaturan identitas toko
     *
     * @param  PDO   $pdo
     * @param  array $data
     * @return bool
     */
    function updateStoreSettings(PDO $pdo, array $data): bool {
        try {
            // Pastikan baris id=1 ada
            $check = (int)$pdo->query("SELECT COUNT(*) FROM store_settings WHERE id = 1")->fetchColumn();
            if ($check === 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO store_settings
                    (id, store_name, store_tagline, address, phone, whatsapp, email, logo)
                    VALUES
                    (1, :store_name, :store_tagline, :address, :phone, :whatsapp, :email, :logo)
                ");
            } else {
                $stmt = $pdo->prepare("
                    UPDATE store_settings
                    SET store_name    = :store_name,
                        store_tagline = :store_tagline,
                        address       = :address,
                        phone         = :phone,
                        whatsapp      = :whatsapp,
                        email         = :email,
                        logo          = :logo
                    WHERE id = 1
                ");
            }

            return $stmt->execute([
                ':store_name'    => $data['store_name']    ?? 'Muara Jaya Sport',
                ':store_tagline' => $data['store_tagline'] ?? '',
                ':address'       => $data['address']       ?? '',
                ':phone'         => $data['phone']         ?? '',
                ':whatsapp'      => $data['whatsapp']      ?? '',
                ':email'         => $data['email']         ?? '',
                ':logo'          => $data['logo']          ?? '',
            ]);
        } catch (PDOException $e) {
            error_log("Error updateStoreSettings: " . $e->getMessage());
            return false;
        }
    }
}

/* ============================================================
   STEP 1: AMBIL DATA KRITERIA & BOBOT DARI DATABASE
   ============================================================ */
/**
 * Mengambil 8 kriteria penilaian dari tabel criteria.
 *
 * @param  PDO   $pdo
 * @return array
 */
function getCriteria(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT id, code, name,
               CAST(weight AS DECIMAL(6,4)) AS weight,
               type, description
        FROM   criteria
        ORDER  BY id ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================================================
   STEP 2: AMBIL ALTERNATIF RAKET (+ FILTER ANGGARAN)
   ============================================================ */
/**
 * Mengambil data alternatif raket dengan filter anggaran opsional.
 *
 * @param  PDO        $pdo
 * @param  float|null $maxBudget
 * @return array
 */
function getRackets(PDO $pdo, ?float $maxBudget = null): array
{
    if ($maxBudget !== null && $maxBudget > 0) {
        $stmt = $pdo->prepare("
            SELECT id, name, brand, price,
                   COALESCE(gambar, image, '') AS gambar,
                   COALESCE(image, gambar, '') AS image,
                   weight_spec, balance_spec, flex_spec, tension_spec, grip_spec,
                   description, created_at
            FROM   rackets
            WHERE  price <= :max_budget
            ORDER  BY price ASC, name ASC
        ");
        $stmt->execute([':max_budget' => $maxBudget]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, name, brand, price,
                   COALESCE(gambar, image, '') AS gambar,
                   COALESCE(image, gambar, '') AS image,
                   weight_spec, balance_spec, flex_spec, tension_spec, grip_spec,
                   description, created_at
            FROM   rackets
            ORDER  BY brand ASC, name ASC
        ");
        $stmt->execute();
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================================================
   STEP 3: MEMBANGUN MATRIKS KEPUTUSAN (X_ij)
   ============================================================ */
/**
 * Membangun matriks keputusan X untuk raket yang dievaluasi.
 *
 * @param  PDO   $pdo
 * @param  array $rackets
 * @param  array $criteria
 * @return array [racket_id => [criteria_id => score_value]]
 */
function buildMatrix(PDO $pdo, array $rackets, array $criteria): array
{
    if (empty($rackets) || empty($criteria)) {
        return [];
    }

    $racketIds = array_column($rackets, 'id');
    $placeholders = implode(',', array_fill(0, count($racketIds), '?'));

    $stmt = $pdo->prepare("
        SELECT racket_id, criteria_id, score_value
        FROM   racket_scores
        WHERE  racket_id IN ($placeholders)
        ORDER  BY racket_id ASC, criteria_id ASC
    ");
    $stmt->execute($racketIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $matrix = [];
    foreach ($rackets as $r) {
        $rid = (int)$r['id'];
        foreach ($criteria as $c) {
            $cid = (int)$c['id'];
            // Jika kriteria C1 adalah harga dan belum tercatat di racket_scores, ambil dari kolom price
            $matrix[$rid][$cid] = ($cid === 1) ? (float)$r['price'] : 0.0;
        }
    }

    foreach ($rows as $row) {
        $rid = (int)$row['racket_id'];
        $cid = (int)$row['criteria_id'];
        $matrix[$rid][$cid] = (float)$row['score_value'];
    }

    return $matrix;
}

/* ============================================================
   STEP 4: MENCARI NILAI MINIMUM DAN MAKSIMUM (X_j)
   ============================================================ */
/**
 * Mencari nilai min dan max untuk setiap kolom kriteria j.
 *
 * @param  array $matrix
 * @param  array $criteria
 * @return array [criteria_id => ['min' => float, 'max' => float]]
 */
function findMinMax(array $matrix, array $criteria): array
{
    $minMax = [];
    foreach ($criteria as $c) {
        $cid = (int)$c['id'];
        $values = [];
        foreach ($matrix as $rid => $scores) {
            if (isset($scores[$cid]) && $scores[$cid] > 0) {
                $values[] = (float)$scores[$cid];
            }
        }

        if (empty($values)) {
            $minMax[$cid] = ['min' => 0.0, 'max' => 0.0];
        } else {
            $minMax[$cid] = [
                'min' => min($values),
                'max' => max($values),
            ];
        }
    }
    return $minMax;
}

/* ============================================================
   STEP 5: NORMALISASI MATRIKS KEPUTUSAN (R_ij)
   ============================================================
   - Benefit: R_ij = X_ij / Max(X_j)
   - Cost   : R_ij = Min(X_j) / X_ij
   ============================================================ */
/**
 * Normalisasi matriks keputusan X menjadi matriks R.
 *
 * @param  array $matrix
 * @param  array $criteria
 * @param  array $minMax
 * @return array [racket_id => [criteria_id => R_ij]]
 */
function normalizeMatrix(array $matrix, array $criteria, array $minMax): array
{
    $typeMap = [];
    foreach ($criteria as $c) {
        $typeMap[(int)$c['id']] = strtolower($c['type']);
    }

    $normalized = [];
    foreach ($matrix as $rid => $rowScores) {
        foreach ($rowScores as $cid => $xij) {
            $type = $typeMap[$cid] ?? 'benefit';
            $min  = $minMax[$cid]['min'] ?? 0.0;
            $max  = $minMax[$cid]['max'] ?? 0.0;

            if ($type === 'cost') {
                // Kriteria Cost: semakin kecil nilai X_ij semakin tinggi skor R_ij
                $rij = ($xij > 0 && $min > 0) ? ($min / $xij) : 0.0;
            } else {
                // Kriteria Benefit: semakin besar nilai X_ij semakin tinggi skor R_ij
                $rij = ($max > 0) ? ($xij / $max) : 0.0;
            }

            $normalized[$rid][$cid] = max(0.0, min(1.0, round($rij, 6)));
        }
    }

    return $normalized;
}

/* ============================================================
   STEP 6: HITUNG NILAI TERBOBOT & PREFERENSI (V_i)
   ============================================================
   Rumus SAW:
     V_i = SUM( W_j * R_ij )
   ============================================================ */
/**
 * Menghitung matriks terbobot W * R dan skor preferensi akhir Vi.
 *
 * @param  array      $normalized
 * @param  array      $criteria
 * @param  array|null $customWeights [criteria_id => custom_weight]
 * @return array ['weighted' => [...], 'preferences' => [...]]
 */
function calcWeighted(array $normalized, array $criteria, ?array $customWeights = null): array
{
    $weightMap = [];
    foreach ($criteria as $c) {
        $cid = (int)$c['id'];
        if ($customWeights !== null && isset($customWeights[$cid]) && is_numeric($customWeights[$cid])) {
            $weightMap[$cid] = (float)$customWeights[$cid];
        } else {
            $weightMap[$cid] = (float)$c['weight'];
        }
    }

    // Normalisasi bobot jika custom agar total tetap 1.00
    $totalW = array_sum($weightMap);
    if ($totalW > 0 && abs($totalW - 1.0) > 0.001) {
        foreach ($weightMap as $cid => $w) {
            $weightMap[$cid] = $w / $totalW;
        }
    }

    $weighted    = [];
    $preferences = [];

    foreach ($normalized as $rid => $rowNorm) {
        $vi = 0.0;
        foreach ($rowNorm as $cid => $rij) {
            $wj  = $weightMap[$cid] ?? 0.0;
            $wij = $wj * $rij;
            $weighted[$rid][$cid] = round($wij, 6);
            $vi += $wij;
        }
        $preferences[$rid] = round($vi, 6);
    }

    return [
        'weights'     => $weightMap,
        'weighted'    => $weighted,
        'preferences' => $preferences,
    ];
}

/* ============================================================
   STEP 7: PERANGKINGAN (Descending Berdasarkan V_i)
   ============================================================ */
/**
 * Memberikan peringkat ranking descending berdasarkan nilai preferensi Vi.
 *
 * @param  array $preferences
 * @return array [racket_id => ['rank' => int, 'score' => float]]
 */
function rankResults(array $preferences): array
{
    arsort($preferences);
    $ranking = [];
    $rank = 1;
    foreach ($preferences as $rid => $vi) {
        $ranking[$rid] = [
            'rank'  => $rank,
            'score' => round($vi, 6),
        ];
        $rank++;
    }
    return $ranking;
}

/* ============================================================
   MASTER ENGINE: runSAW() / hitungSAW() — DYNAMIC PREFERENCE ENGINE
   ============================================================ */
/**
 * Menghasilkan distribusi bobot kriteria dinamis berbasis gaya bermain dan preferensi kelenturan pengguna.
 *
 * @param  string|null $playingStyle (Power, Speed, Control, All-Around)
 * @param  array       $criteria
 * @param  string|null $flexPref     (Extra Stiff, Stiff, Medium Stiff, Medium, Flexible)
 * @return array [criteria_id => float_weight]
 */
function generateDynamicWeights(?string $playingStyle, array $criteria, ?string $flexPref = null): array
{
    $codeMap = [];
    foreach ($criteria as $c) {
        $codeMap[$c['code']] = (int)$c['id'];
    }

    $c1 = $codeMap['C1'] ?? 1; // Harga (Cost)
    $c2 = $codeMap['C2'] ?? 2; // Berat (Cost)
    $c3 = $codeMap['C3'] ?? 3; // Flexibilitas (Benefit)
    $c4 = $codeMap['C4'] ?? 4; // Balance Point (Benefit)
    $c5 = $codeMap['C5'] ?? 5; // Tarikan Senar (Benefit)
    $c6 = $codeMap['C6'] ?? 6; // Grip (Benefit)
    $c7 = $codeMap['C7'] ?? 7; // Durabilitas (Benefit)
    $c8 = $codeMap['C8'] ?? 8; // Kontrol & Manufaktur (Benefit)

    $weights = [];

    switch ($playingStyle) {
        case 'Power':
            // Prioritas Serang/Smash: Balance Head Heavy (C4), Flexibilitas Responsif (C3), Tarikan Senar (C5)
            $weights[$c1] = 0.05;
            $weights[$c2] = 0.10;
            $weights[$c3] = 0.20;
            $weights[$c4] = 0.25;
            $weights[$c5] = 0.15;
            $weights[$c6] = 0.05;
            $weights[$c7] = 0.10;
            $weights[$c8] = 0.10;
            break;

        case 'Speed':
            // Prioritas Kecepatan: Bobot Ringan (C2 Cost), Manuver (C4), Grip Ergonomi (C6), Kontrol Cepat (C8)
            $weights[$c1] = 0.05;
            $weights[$c2] = 0.25;
            $weights[$c3] = 0.15;
            $weights[$c4] = 0.15;
            $weights[$c5] = 0.10;
            $weights[$c6] = 0.10;
            $weights[$c7] = 0.05;
            $weights[$c8] = 0.15;
            break;

        case 'Control':
            // Prioritas Presisi: Kontrol & Kualitas Manufaktur (C8), Stabilitas Durabilitas (C7), Even Balance (C4)
            $weights[$c1] = 0.05;
            $weights[$c2] = 0.10;
            $weights[$c3] = 0.15;
            $weights[$c4] = 0.15;
            $weights[$c5] = 0.10;
            $weights[$c6] = 0.10;
            $weights[$c7] = 0.15;
            $weights[$c8] = 0.20;
            break;

        case 'All-Around':
            // Keseimbangan Seluruh Aspek
            $weights[$c1] = 0.12;
            $weights[$c2] = 0.13;
            $weights[$c3] = 0.15;
            $weights[$c4] = 0.15;
            $weights[$c5] = 0.10;
            $weights[$c6] = 0.10;
            $weights[$c7] = 0.12;
            $weights[$c8] = 0.13;
            break;

        default:
            // Ambil bobot bawaan dari database
            foreach ($criteria as $c) {
                $weights[(int)$c['id']] = (float)$c['weight'];
            }
            break;
    }

    // Jika pengguna secara spesifik memilih preferensi Flexibilitas Batang,
    // tingkatkan bobot C3 dan redam dominasi kriteria harga (C1) agar raket dengan flex sesuai memenangkan perangkingan
    if (!empty($flexPref)) {
        $weights[$c3] = 0.28;
        if (($weights[$c1] ?? 0.20) > 0.08) {
            $weights[$c1] = 0.08;
        }
    }

    // Normalisasi agar total akumulasi tepat 1.0000
    $total = array_sum($weights);
    if ($total > 0) {
        foreach ($weights as $cid => $w) {
            $weights[$cid] = round($w / $total, 4);
        }
    }

    return $weights;
}

/**
 * Menyesuaikan matriks keputusan Xij berdasarkan kecocokan preferensi pengguna.
 * Menjamin bahwa user yang memilih Flexible akan memenangkan raket Flexible,
 * user yang memilih Stiff akan memenangkan raket Stiff, dst.
 *
 * @param  array       $matrix
 * @param  array       $racketRows
 * @param  array       $criteria
 * @param  string|null $playingStyle
 * @param  string|null $flexPref
 * @return array
 */
function adjustMatrixForPreferences(
    array $matrix,
    array $racketRows,
    array $criteria,
    ?string $playingStyle,
    ?string $flexPref
): array {
    $codeMap = [];
    foreach ($criteria as $c) {
        $codeMap[$c['code']] = (int)$c['id'];
    }
    $c3Id = $codeMap['C3'] ?? 3; // Flexibilitas Shaft
    $c4Id = $codeMap['C4'] ?? 4; // Balance Point

    foreach ($racketRows as $r) {
        $rid = (int)$r['id'];
        $flexSpec    = strtolower($r['flex_spec'] ?? '');
        $balanceSpec = strtolower($r['balance_spec'] ?? '');
        $weightSpec  = strtolower($r['weight_spec'] ?? '');

        // 1. Kecocokan Flexibilitas Shaft (C3)
        if (!empty($flexPref)) {
            $fPref = strtolower($flexPref);
            $flexScore = 5.0; // baseline

            if ($fPref === 'flexible') {
                if (str_contains($flexSpec, 'flexible')) {
                    $flexScore = 10.0;
                } elseif (str_contains($flexSpec, 'medium') && !str_contains($flexSpec, 'stiff')) {
                    $flexScore = 5.0;
                } elseif (str_contains($flexSpec, 'medium stiff')) {
                    $flexScore = 3.5;
                } elseif (str_contains($flexSpec, 'extra stiff')) {
                    $flexScore = 1.0;
                } elseif (str_contains($flexSpec, 'stiff')) {
                    $flexScore = 2.0;
                }
            } elseif ($fPref === 'stiff') {
                if ($flexSpec === 'stiff') {
                    $flexScore = 10.0;
                } elseif (str_contains($flexSpec, 'extra stiff')) {
                    $flexScore = 8.5;
                } elseif (str_contains($flexSpec, 'medium stiff')) {
                    $flexScore = 6.5;
                } elseif (str_contains($flexSpec, 'medium')) {
                    $flexScore = 4.0;
                } elseif (str_contains($flexSpec, 'flexible')) {
                    $flexScore = 1.5;
                }
            } elseif ($fPref === 'extra stiff') {
                if (str_contains($flexSpec, 'extra stiff')) {
                    $flexScore = 10.0;
                } elseif ($flexSpec === 'stiff') {
                    $flexScore = 7.5;
                } elseif (str_contains($flexSpec, 'medium stiff')) {
                    $flexScore = 5.0;
                } elseif (str_contains($flexSpec, 'medium')) {
                    $flexScore = 3.0;
                } elseif (str_contains($flexSpec, 'flexible')) {
                    $flexScore = 1.0;
                }
            } elseif (str_contains($fPref, 'medium stiff')) {
                if (str_contains($flexSpec, 'medium stiff')) {
                    $flexScore = 10.0;
                } elseif (str_contains($flexSpec, 'medium')) {
                    $flexScore = 8.5;
                } elseif ($flexSpec === 'stiff') {
                    $flexScore = 7.0;
                } elseif (str_contains($flexSpec, 'flexible')) {
                    $flexScore = 4.5;
                } elseif (str_contains($flexSpec, 'extra stiff')) {
                    $flexScore = 3.0;
                }
            } elseif (str_contains($fPref, 'medium')) {
                if (str_contains($flexSpec, 'medium') && !str_contains($flexSpec, 'stiff')) {
                    $flexScore = 10.0;
                } elseif (str_contains($flexSpec, 'medium stiff')) {
                    $flexScore = 8.0;
                } elseif (str_contains($flexSpec, 'flexible')) {
                    $flexScore = 7.0;
                } elseif ($flexSpec === 'stiff') {
                    $flexScore = 5.5;
                } elseif (str_contains($flexSpec, 'extra stiff')) {
                    $flexScore = 3.0;
                }
            }

            $matrix[$rid][$c3Id] = $flexScore;
        }

        // 2. Kecocokan Balance Point dengan Karakter Permainan (C4)
        if (!empty($playingStyle)) {
            $balScore = 8.0;
            if ($playingStyle === 'Power') {
                if (str_contains($balanceSpec, '310') || str_contains($balanceSpec, '312') || str_contains($balanceSpec, '315')) {
                    $balScore = 10.0;
                } elseif (str_contains($balanceSpec, '305') || str_contains($balanceSpec, '300') || str_contains($balanceSpec, '302')) {
                    $balScore = 9.5;
                } elseif (str_contains($balanceSpec, 'head heavy')) {
                    $balScore = 9.0;
                } elseif (str_contains($balanceSpec, 'even')) {
                    $balScore = 6.5;
                } elseif (str_contains($balanceSpec, 'head light')) {
                    $balScore = 3.5;
                }
            } elseif ($playingStyle === 'Speed') {
                if (str_contains($balanceSpec, 'head light')) {
                    $balScore = 10.0;
                } elseif (str_contains($weightSpec, '72g') || str_contains($weightSpec, '6u')) {
                    $balScore = 10.0; // Bobot sangat ringan
                } elseif (str_contains($balanceSpec, 'even')) {
                    $balScore = 8.5;
                } elseif (str_contains($balanceSpec, '300mm')) {
                    $balScore = 7.0;
                } else {
                    $balScore = 5.0;
                }
            } elseif ($playingStyle === 'Control') {
                if (str_contains($balanceSpec, 'even')) {
                    $balScore = 10.0;
                } elseif (str_contains($balanceSpec, 'head light')) {
                    $balScore = 8.5;
                } elseif (str_contains($balanceSpec, '300mm') || str_contains($balanceSpec, '298mm')) {
                    $balScore = 8.0;
                } else {
                    $balScore = 6.0;
                }
            } elseif ($playingStyle === 'All-Around') {
                if (str_contains($balanceSpec, 'even')) {
                    $balScore = 10.0;
                } elseif (str_contains($balanceSpec, '300mm')) {
                    $balScore = 9.0;
                } else {
                    $balScore = 7.5;
                }
            }
            $matrix[$rid][$c4Id] = $balScore;
        }
    }

    return $matrix;
}

/**
 * Menjalankan seluruh pipeline perhitungan SPK SAW 8 Kriteria secara dinamis.
 *
 * @param  PDO         $pdo
 * @param  float|null  $maxBudget     Anggaran maksimal pengguna (null = tanpa batas)
 * @param  array|null  $customWeights Pembobotan preferensi kustom (opsional)
 * @param  string|null $playingStyle  Gaya bermain (Power, Speed, Control, All-Around)
 * @param  string|null $flexPref      Preferensi kelenturan batang (Extra Stiff, Stiff, Medium, Flexible)
 * @return array
 */
function runSAW(
    PDO $pdo,
    ?float $maxBudget = null,
    ?array $customWeights = null,
    ?string $playingStyle = null,
    ?string $flexPref = null
): array
{
    // 1. Ambil 8 Kriteria
    $criteria = getCriteria($pdo);
    if (empty($criteria)) {
        return [
            'success' => false,
            'message' => 'Kriteria penilaian SAW tidak ditemukan di database.',
        ];
    }

    // 2. Ambil alternatif raket sesuai budget
    $racketRows = getRackets($pdo, $maxBudget);
    $isFallback = false;
    $fallbackMsg = '';

    // Penanganan defensif jika raket kosong karena batas anggaran terlalu ketat
    if (empty($racketRows)) {
        $msg = ($maxBudget !== null && $maxBudget > 0)
            ? 'Belum ada raket di bawah Rp ' . number_format($maxBudget, 0, ',', '.') . ', silakan naikkan batas anggaran Anda.'
            : 'Tidak ada data raket yang tersedia untuk dievaluasi.';
        return [
            'success'         => false,
            'message'         => $msg,
            'isFallback'      => false,
            'fallbackMsg'     => '',
            'ranking'         => [],
            'rackets'         => [],
            'matrix'          => [],
            'normalized'      => [],
            'weighted'        => [],
            'criteria'        => $criteria,
            'minMax'          => [],
            'weights'         => [],
            'preferences'     => [],
            'weightModelDesc' => 'Standar Objektif SAW',
        ];
    }

    // Indeks raket dengan key id
    $rackets = [];
    foreach ($racketRows as $r) {
        $rackets[(int)$r['id']] = ['racket' => $r];
    }

    // 3. Matriks Keputusan Asli X
    $matrix = buildMatrix($pdo, $racketRows, $criteria);

    // 4. Penyesuaian Matriks X Berbasis Preferensi Dinamis (Kecocokan Flex & Gaya Bermain)
    if (!empty($playingStyle) || !empty($flexPref)) {
        $matrix = adjustMatrixForPreferences($matrix, $racketRows, $criteria, $playingStyle, $flexPref);
    }

    // 5. Min & Max per kriteria
    $minMax = findMinMax($matrix, $criteria);

    // 6. Normalisasi Matriks R (Benefit: X/Max | Cost: Min/X)
    $normalized = normalizeMatrix($matrix, $criteria, $minMax);

    // 7. Penentuan Bobot (Custom, Dinamis berbasis Gaya & Flex, atau Bawaan Database)
    if ($customWeights !== null && !empty($customWeights)) {
        $activeWeights = $customWeights;
    } elseif (!empty($playingStyle) || !empty($flexPref)) {
        $activeWeights = generateDynamicWeights($playingStyle, $criteria, $flexPref);
    } else {
        $activeWeights = null; // gunakan bawaan criteria
    }

    // 8. Matriks Terbobot W * R & Skor Vi
    $calc = calcWeighted($normalized, $criteria, $activeWeights);
    $weighted      = $calc['weighted'];
    $preferences   = $calc['preferences'];
    $finalWeights  = $calc['weights'];

    // 9. Perankingan Descending
    $ranking = rankResults($preferences);

    // Deskripsi Model Bobot
    $weightModelDesc = 'Standar Objektif SAW';
    if (!empty($playingStyle) && !empty($flexPref)) {
        $weightModelDesc = 'Dinamis: Gaya ' . ucfirst($playingStyle) . ' & Flex ' . ucfirst($flexPref);
    } elseif (!empty($playingStyle)) {
        $weightModelDesc = 'Dinamis: Prioritas Gaya ' . ucfirst($playingStyle);
    } elseif (!empty($flexPref)) {
        $weightModelDesc = 'Dinamis: Prioritas Flex ' . ucfirst($flexPref);
    }

    return [
        'success'         => true,
        'message'         => 'Kalkulasi SAW 8 kriteria berhasil diproses secara dinamis.',
        'isFallback'      => $isFallback,
        'fallbackMsg'     => $fallbackMsg,
        'criteria'        => $criteria,
        'activeWeights'   => $finalWeights,
        'weightModelDesc' => $weightModelDesc,
        'playingStyle'    => $playingStyle,
        'flexPref'        => $flexPref,
        'rackets'         => $rackets,
        'matrix'          => $matrix,
        'minMax'          => $minMax,
        'normalized'      => $normalized,
        'weighted'        => $weighted,
        'preferences'     => $preferences,
        'ranking'         => $ranking,
        'maxBudget'       => $maxBudget,
        'totalCount'      => count($racketRows),
        'racketCount'     => count($racketRows),
        'totalWeight'     => array_sum($finalWeights),
    ];
}

// Alias hitungSAW untuk kompatibilitas
if (!function_exists('hitungSAW')) {
    function hitungSAW(
        PDO $pdo,
        ?float $maxBudget = null,
        ?array $customWeights = null,
        ?string $playingStyle = null,
        ?string $flexPref = null
    ): array {
        return runSAW($pdo, $maxBudget, $customWeights, $playingStyle, $flexPref);
    }
}

/* ============================================================
   UTILITIES & HELPER FUNCTIONS
   ============================================================ */
/**
 * Format mata uang Rupiah
 *
 * @param  float|int $amount
 * @return string
 */
function formatRupiah($amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

/**
 * Format tanggal standar Indonesia baku (contoh: "02 November 2026")
 *
 * @param  int|string|null $time     Timestamp UNIX, date string, atau null untuk saat ini
 * @param  bool            $withTime Tampilkan jam dan menit (WIB)
 * @return string
 */
function formatTanggalIndonesia($time = null, bool $withTime = false): string
{
    if ($time === null) {
        $timestamp = time();
    } elseif (is_numeric($time)) {
        $timestamp = (int)$time;
    } else {
        $timestamp = strtotime((string)$time) ?: time();
    }

    $bulanIndo = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $hari  = date('d', $timestamp);
    $bulan = $bulanIndo[(int)date('n', $timestamp)] ?? date('F', $timestamp);
    $tahun = date('Y', $timestamp);

    $formatted = "{$hari} {$bulan} {$tahun}";
    if ($withTime) {
        $formatted .= ', ' . date('H:i', $timestamp) . ' WIB';
    }

    return $formatted;
}

/**
 * Format angka desimal SAW
 *
 * @param  float $value
 * @param  int   $decimals
 * @return string
 */
function formatSAW(float $value, int $decimals = 6): string
{
    return number_format($value, $decimals);
}

/**
 * Simpan log konsultasi pengunjung ke database
 *
 * @param  PDO   $pdo
 * @param  array $data
 * @return bool
 */
function logConsultation(PDO $pdo, array $data): bool
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO consultation_log
            (visitor_name, max_budget, playing_style, flexibility_pref, result_top_racket, result_top_score)
            VALUES
            (:visitor_name, :max_budget, :playing_style, :flexibility_pref, :result_top_racket, :result_top_score)
        ");
        return $stmt->execute([
            ':visitor_name'     => $data['visitor_name']     ?? 'Tamu',
            ':max_budget'       => $data['max_budget']       ?? null,
            ':playing_style'    => $data['playing_style']    ?? null,
            ':flexibility_pref' => $data['flexibility_pref'] ?? null,
            ':result_top_racket'=> $data['result_top_racket']?? null,
            ':result_top_score' => $data['result_top_score'] ?? null,
        ]);
    } catch (PDOException $e) {
        error_log("Gagal simpan log konsultasi: " . $e->getMessage());
        return false;
    }
}

/**
 * Memvalidasi total bobot kriteria SAW (harus = 1.0000 atau toleransi 0.001)
 * Mendukung parameter instance PDO, array bobot, atau null (auto lookup global $pdo)
 *
 * @param  PDO|array|null $input
 * @return array ['total' => float, 'valid' => bool, 'diff' => float]
 */
if (!function_exists('validateWeights')) {
    function validateWeights($input = null): array {
        global $pdo;
        $total = 0.0;

        if ($input instanceof PDO) {
            $stmt = $input->query("SELECT CAST(weight AS DECIMAL(6,4)) AS w FROM criteria");
            $weights = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $total = (float) array_sum($weights);
        } elseif (is_array($input)) {
            $total = (float) array_sum($input);
        } else {
            $db = (isset($pdo) && $pdo instanceof PDO) ? $pdo : (function_exists('getDB') ? getDB() : null);
            if ($db instanceof PDO) {
                $stmt = $db->query("SELECT CAST(weight AS DECIMAL(6,4)) AS w FROM criteria");
                $weights = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $total = (float) array_sum($weights);
            }
        }

        $total = round($total, 6);
        $diff  = abs($total - 1.0);
        $valid = ($diff <= 0.001);

        return [
            'total' => $total,
            'valid' => $valid,
            'diff'  => $diff,
        ];
    }
}

/**
 * Mendapatkan medali emoji untuk peringkat
 *
 * @param  int $rank
 * @return string
 */
function getRankEmoji(int $rank): string
{
    return match($rank) {
        1 => '#1',
        2 => '#2',
        3 => '#3',
        default => '#' . $rank,
    };
}

/**
 * Mendapatkan kelas styling badge ranking
 *
 * @param  int $rank
 * @return string
 */
function getRankBadgeClass(int $rank): string
{
    return match($rank) {
        1 => 'bg-amber-500/15 text-amber-500 border border-amber-500/30 font-bold',
        2 => 'bg-slate-400/15 text-slate-400 border border-slate-400/30 font-bold',
        3 => 'bg-orange-700/15 text-orange-400 border border-orange-700/30 font-bold',
        default => 'bg-slate-700/30 text-slate-400 border border-slate-700/50',
    };
}

/**
 * Menghasilkan narasi penjelasan alasan (Rationale Explanation) yang ramah user awam,
 * menjelaskan mengapa raket tersebut terpilih berdasarkan parameter gaya bermain,
 * preferensi flex shaft, dan anggaran yang diinputkan.
 *
 * @param  array       $racket      Data raket (nama, brand, price, specs, dll)
 * @param  string|null $playingStyle Gaya bermain (Power, Speed, Control, All-Around)
 * @param  string|null $flexPref    Preferensi shaft flex (Extra Stiff, Stiff, Medium, Flexible)
 * @param  float|null  $maxBudget   Batas anggaran user
 * @param  int         $rank        Peringkat raket (1, 2, 3, dst)
 * @return string Penjelasan rasional dalam kalimat terstruktur
 */
function generateRationale(array $racket, ?string $playingStyle, ?string $flexPref, ?float $maxBudget, int $rank): string
{
    $name    = $racket['name'] ?? 'Raket';
    $brand   = $racket['brand'] ?? '';
    $price   = (float)($racket['price'] ?? 0);
    $weight  = $racket['weight_spec'] ?? '83g (4U)';
    $flex    = $racket['flex_spec'] ?? 'Medium';
    $balance = $racket['balance_spec'] ?? 'Even';
    $tension = $racket['tension_spec'] ?? '28 lbs';

    $intro = match($rank) {
        1 => "<strong>Juara Rekomendasi (#1)</strong>: Raket ini meraih skor tertinggi secara matematis karena memiliki sinergi terbaik antara profil fisik dan parameter yang Anda tentukan.",
        2 => "<strong>Pilihan Unggul (#2)</strong>: Merupakan runner-up terkuat dengan performa teknis yang sangat kompetitif dan selisih tipis dari peringkat pertama.",
        3 => "<strong>Alternatif Terbaik (#3)</strong>: Menjadi opsi ideal di podium Top 3 dengan karakteristik seimbang dan daya tahan luar biasa.",
        default => "<strong>Alternatif Kompetitif (#{$rank})</strong>: Memenuhi standar kriteria SPK dengan performa yang layak dipertimbangkan.",
    };

    // Alasan Gaya Bermain & Balance
    $styleLower = strtolower($playingStyle ?? '');
    if ($styleLower === 'power') {
        $styleText = "Titik keseimbangan <strong>{$balance}</strong> dan tarikan hingga <strong>{$tension}</strong> memaksimalkan transfer energi smash keras dan daya penetrasi kok.";
    } elseif ($styleLower === 'speed') {
        $styleText = "Bobot <strong>{$weight}</strong> yang ringan dipadukan balance <strong>{$balance}</strong> mempermudah akselerasi ayunan cepat dan manuver defensif rapat.";
    } elseif ($styleLower === 'control') {
        $styleText = "Konfigurasi balance <strong>{$balance}</strong> memberikan kestabilan torsi tinggi untuk netting halus dan penempatan bola presisi di garis sudut.";
    } else {
        $styleText = "Karakteristik balance <strong>{$balance}</strong> dan bobot <strong>{$weight}</strong> memberikan kenyamanan serbaguna baik saat bertahan maupun menyerang.";
    }

    // Alasan Flexibilitas Shaft
    $flexLower = strtolower($flexPref ?? '');
    if ($flexLower === 'flexible') {
        $flexText = "Kelenturan shaft <strong>{$flex}</strong> menghasilkan efek cambukan (whip-action) maksimal tanpa perlu menguras tenaga lengan, sangat nyaman di tangan.";
    } elseif ($flexLower === 'stiff' || $flexLower === 'extra stiff') {
        $flexText = "Batang shaft bertipe <strong>{$flex}</strong> menghadirkan respon pantulan instan dan solid, sangat akurat bagi pemain dengan kekuatan pergelangan tangan aktif.";
    } elseif (str_contains($flexLower, 'medium')) {
        $flexText = "Fleksibilitas batang <strong>{$flex}</strong> menghadirkan kompromi ideal antara kontrol penempatan kok dan dorongan tenaga lob jauh.";
    } else {
        $flexText = "Batang <strong>{$flex}</strong> menawarkan fleksibilitas responsif yang mudah beradaptasi dengan beragam pola permainan.";
    }

    // Alasan Anggaran
    if ($maxBudget !== null && $maxBudget > 0) {
        if ($price <= $maxBudget) {
            $hemat = $maxBudget - $price;
            if ($hemat > 50000) {
                $budgetText = "Dengan banderol <strong>" . formatRupiah($price) . "</strong>, raket ini hemat <strong>" . formatRupiah($hemat) . "</strong> dari batas anggaran Anda.";
            } else {
                $budgetText = "Banderol <strong>" . formatRupiah($price) . "</strong> berada pas di dalam alokasi anggaran <strong>" . formatRupiah($maxBudget) . "</strong> Anda.";
            }
        } else {
            $budgetText = "Harga <strong>" . formatRupiah($price) . "</strong> sebanding dengan kualitas turnamen profesional kelas dunia.";
        }
    } else {
        $budgetText = "Dipasarkan seharga <strong>" . formatRupiah($price) . "</strong> dengan rasio efisiensi harga berbanding spesifikasi (value for money) yang luar biasa.";
    }

    return "{$intro} {$styleText} {$flexText} {$budgetText}";
}



/**
 * ============================================================
 *  FLASH MESSAGE SYSTEM (SESSION-BASED POP-UP ALERTS)
 * ============================================================
 *  Menyimpan dan merender banner notifikasi sementara di sesi.
 *  Otomatis dibersihkan setelah dibaca (F5 tidak menduplikasi alert).
 */

/**
 * Set pesan flash ke dalam session (Format Terpusat Toast & Banner)
 *
 * @param string $type Tipe: "success", "error", "warning", atau "info"
 * @param string $message Teks pesan yang ingin ditampilkan
 * @param string|null $title Judul notifikasi opsional
 */
function setFlash(string $type, string $message, ?string $title = null): void
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $typeClean  = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
    $titleClean = $title ?? ($typeClean === 'success' ? 'Aksi Berhasil' : ($typeClean === 'error' ? 'Pemberitahuan Error' : 'Informasi Sistem'));

    $flashData = [
        'type'    => $typeClean,
        'title'   => $titleClean,
        'message' => trim($message),
        'time'    => time(),
    ];

    $_SESSION['flash']         = $flashData;
    $_SESSION['flash_message'] = $flashData;

    // Simpan juga ke cookie sementara (auto-clear setelah dibaca) untuk kompatibilitas Vercel Serverless
    $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
           || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    setcookie('mjs_flash_toast', json_encode($flashData), [
        'expires'  => time() + 120,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => false,
        'samesite' => 'Lax'
    ]);
    $_COOKIE['mjs_flash_toast'] = json_encode($flashData);
}

/**
 * Periksa apakah ada pesan flash di session
 */
function hasFlash(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    return !empty($_SESSION['flash']['message']) || !empty($_SESSION['flash_message']['message']) || !empty($_COOKIE['mjs_flash_toast']);
}

/**
 * Ambil pesan flash dan hapus dari session (one-time read)
 *
 * @return array|null Array [type => ..., title => ..., message => ...] atau null
 */
function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $flash = null;
    if (isset($_SESSION['flash']) && is_array($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        unset($_SESSION['flash_message']);
    } elseif (isset($_SESSION['flash_message']) && is_array($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash']);
    } elseif (!empty($_COOKIE['mjs_flash_toast'])) {
        $decoded = json_decode($_COOKIE['mjs_flash_toast'], true);
        if (is_array($decoded)) {
            $flash = $decoded;
        }
    }

    // Auto-clear cookie flash setelah dibaca
    if (isset($_COOKIE['mjs_flash_toast'])) {
        setcookie('mjs_flash_toast', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => false,
            'httponly' => false,
            'samesite' => 'Lax'
        ]);
        unset($_COOKIE['mjs_flash_toast']);
    }

    return $flash;
}

/**
 * Render notifikasi Floating Toast terpusat di pojok kanan atas
 */
function renderFlash(): void
{
    $flash = getFlash();
    if (!$flash || empty($flash['message'])) {
        return;
    }

    $type      = $flash['type'] ?? 'info';
    $message   = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');
    $title     = htmlspecialchars($flash['title'] ?? ($type === 'success' ? 'Aksi Berhasil' : 'Pemberitahuan Error'), ENT_QUOTES, 'UTF-8');
    $isSuccess = ($type === 'success');
    $toastId   = 'adminToastFloating_' . bin2hex(random_bytes(3));

    $borderCol = $isSuccess ? 'border-emerald-600' : 'border-red-600';
    $titleCol  = $isSuccess ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400';
    $iconCol   = $isSuccess ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';

    echo '<div id="' . $toastId . '" class="fixed top-20 right-4 z-50 max-w-sm w-full transition-all duration-300 ease-out transform translate-y-0 opacity-100" role="alert">';
    echo '  <div class="bg-white dark:bg-slate-800 border-l-4 ' . $borderCol . ' shadow-lg rounded-xl p-4 flex items-start gap-3 border border-slate-200/60 dark:border-slate-700">';
    echo '    <div class="shrink-0 mt-0.5 ' . $iconCol . '">';
    if ($isSuccess) {
        echo '      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
    } else {
        echo '      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
    }
    echo '    </div>';
    echo '    <div class="flex-1 min-w-0">';
    echo '      <h4 class="text-xs font-bold uppercase tracking-wider ' . $titleCol . '">' . $title . '</h4>';
    echo '      <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed">' . $message . '</p>';
    echo '    </div>';
    echo '    <button type="button" onclick="dismissToastAlert(\'' . $toastId . '\')" class="shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition p-1 rounded-lg" aria-label="Tutup">';
    echo '      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>';
    echo '    </button>';
    echo '  </div>';
    echo '</div>';
    echo '<script>';
    echo 'function dismissToastAlert(id) {';
    echo '  var el = document.getElementById(id);';
    echo '  if (el) {';
    echo '    el.style.opacity = "0";';
    echo '    el.style.transform = "translateY(-8px)";';
    echo '    setTimeout(function() { if (el) el.remove(); }, 300);';
    echo '  }';
    echo '}';
    echo 'setTimeout(function() { dismissToastAlert("' . $toastId . '"); }, 4000);';
    echo '</script>';
}