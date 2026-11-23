-- ============================================================
-- Database Schema & Initial Data for SPK Muara Jaya Sport
-- Metode: Simple Additive Weighting (SAW) 8 Kriteria
-- Total Raket: 20 Pilihan Profesional Turnamen
-- Generated: 2026-11-02 04:53:35
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Tabel users & admin (Autentikasi Administrator: username=admin, password=admin123)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT 'Administrator SPK',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `full_name`) VALUES
(1, 'admin', '$2y$10$4VSdmOv2fcGOY2IiuzXUb.B4IdTXemTtVnjKsoWhgcaJYu2COmMEK', 'Administrator SPK')
ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);

DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$4VSdmOv2fcGOY2IiuzXUb.B4IdTXemTtVnjKsoWhgcaJYu2COmMEK')
ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);

-- Tabel criteria (8 Kriteria SAW)
DROP TABLE IF EXISTS `criteria`;
CREATE TABLE `criteria` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL UNIQUE,
  `name` varchar(100) NOT NULL,
  `type` enum('benefit','cost') NOT NULL,
  `description` text DEFAULT NULL,
  `weight` decimal(5,4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `criteria` (`id`, `code`, `name`, `type`, `description`, `weight`) VALUES
(1, 'C1', 'Harga (Price)', 'cost', 'Biaya pengadaan raket. Kriteria Cost: harga semakin terjangkau semakin menguntungkan.', 0.20),
(2, 'C2', 'Berat Raket (Weight)', 'cost', 'Bobot fisik raket (gram). Kriteria Cost: bobot semakin ringan memberikan kelincahan manuver.', 0.15),
(3, 'C3', 'Flexibilitas Shaft (Flexibility)', 'benefit', 'Tingkat kelenturan shaft. Kriteria Benefit: fleksibilitas responsif meningkatkan transfer daya.', 0.15),
(4, 'C4', 'Balance Point / Titik Keseimbangan', 'benefit', 'Titik berat raket. Kriteria Benefit: bobot kepala optimal menghasilkan power smash bertenaga.', 0.10),
(5, 'C5', 'Tarikan Senar / Tension Maksimal', 'benefit', 'Daya tampung tarikan senar (lbs). Kriteria Benefit: tension tinggi menjamin pantulan presisi.', 0.10),
(6, 'C6', 'Ukuran Grip & Ergonomi', 'benefit', 'Kenyamanan pegangan handle raket. Kriteria Benefit: ergonomi pegangan menjamin stabilitas grip.', 0.10),
(7, 'C7', 'Daya Tahan / Durabilitas', 'benefit', 'Kekuatan High-Modulus Carbon frame & shaft. Kriteria Benefit: durabilitas tinggi saat benturan.', 0.10),
(8, 'C8', 'Kontrol & Kualitas Manufaktur', 'benefit', 'Presisi aerodinamika frame dan standar manufaktur. Kriteria Benefit: kontrol pukulan presisi.', 0.10);

-- Tabel rackets (20 Pilihan Alternatif Raket)
DROP TABLE IF EXISTS `rackets`;
CREATE TABLE `rackets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `image` mediumtext DEFAULT NULL,
  `weight_spec` varchar(50) DEFAULT NULL,
  `balance_spec` varchar(50) DEFAULT NULL,
  `flex_spec` varchar(50) DEFAULT NULL,
  `tension_spec` varchar(50) DEFAULT NULL,
  `grip_spec` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rackets` (`id`, `name`, `brand`, `price`, `image`, `weight_spec`, `balance_spec`, `flex_spec`, `tension_spec`, `grip_spec`, `description`) VALUES
(1, 'Astrox 88D Pro', 'Yonex', 2750000.00, 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=600&q=80', '83g (4U)', 'Head Heavy (305mm)', 'Stiff', '28 lbs', 'G5', 'Raket smash ganda andalan pemain elite dunia. Dioptimalkan untuk serangan tajam mematikan dari lini belakang.'),
(2, 'Astrox 77 Pro', 'Yonex', 2450000.00, 'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=600&q=80', '83g (4U)', 'Head Heavy (300mm)', 'Medium', '28 lbs', 'G5', 'Raket all-around serang terpopuler di Indonesia, dipakai Fajar Alfian dan An Se Young untuk smash kontinu.'),
(3, 'Astrox 99 Pro', 'Yonex', 2950000.00, 'https://images.unsplash.com/photo-1521537634581-0dced2fed2a8?auto=format&fit=crop&w=600&q=80', '83g (4U)', 'Head Heavy (310mm)', 'Extra Stiff', '28 lbs', 'G5', 'Raket power murni Kento Momota dengan teknologi Namd generasi terbaru untuk sudut smash menukik ekstrem.'),
(4, 'Arcsaber 11 Pro', 'Yonex', 2700000.00, 'https://images.unsplash.com/photo-1599474924187-334a4ae5bd3c?auto=format&fit=crop&w=600&q=80', '88g (3U)', 'Even Balance (295mm)', 'Stiff', '27 lbs', 'G5', 'Standar emas raket kontrol dan akurasi di Indonesia. Menawarkan shuttle-hold superior untuk placing dan netting.'),
(5, 'Nanoflare 800 Pro', 'Yonex', 2650000.00, 'https://images.unsplash.com/photo-1544919982-b61976f0ba43?auto=format&fit=crop&w=600&q=80', '83g (4U)', 'Head Light (285mm)', 'Stiff', '28 lbs', 'G5', 'Raja manuver drive cepat dan intercept netting kilat dengan profil frame tipis pemecah hambatan angin.'),
(6, 'Nanoflare 700', 'Yonex', 2300000.00, 'https://images.unsplash.com/photo-1617083934555-ac7d4fed881a?auto=format&fit=crop&w=600&q=80', '83g (4U)', 'Head Light (288mm)', 'Medium', '28 lbs', 'G5', 'Salah satu seri Yonex paling laris sepanjang masa di GOR Indonesia karena sangat bersahabat di bahu dan lincah.'),
(7, 'Thruster K Ryuga II', 'Victor', 2850000.00, 'https://images.unsplash.com/photo-1587280501635-68a0e82cd5ff?auto=format&fit=crop&w=600&q=80', '84g (4U)', 'Head Heavy (310mm)', 'Stiff', '31 lbs', 'G5', 'Raket Lee Zii Jia yang sangat viral di Indonesia. Didesain untuk smash agresif bertenaga badai dengan material Pyrofil.'),
(8, 'Thruster K 9900', 'Victor', 2250000.00, 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=600&q=80', '88g (3U)', 'Head Heavy (305mm)', 'Stiff', '32 lbs', 'G5', 'Seri Thruster smash legendaris dengan rangka kokoh multi-layer carbon untuk pemain serang bertipe power.'),
(9, 'Jetspeed S 12 II', 'Victor', 2400000.00, 'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=600&q=80', '84g (4U)', 'Even Balance (295mm)', 'Stiff', '29 lbs', 'G5', 'Legenda ganda putra kecepatan tinggi. Struktur Aero-Sword menghasilkan ayunan super mulus dan counter smash instan.'),
(10, 'Auraspeed 100X', 'Victor', 2550000.00, 'https://images.unsplash.com/photo-1521537634581-0dced2fed2a8?auto=format&fit=crop&w=600&q=80', '84g (4U)', 'Even Balance (295mm)', 'Stiff', '29 lbs', 'G5', 'Raket juara dunia Mohammad Ahsan dengan handle Free Core sintetis untuk pantulan bersih dan minim getaran.'),
(11, 'Aeronaut 9000', 'Li-Ning', 2650000.00, 'https://images.unsplash.com/photo-1599474924187-334a4ae5bd3c?auto=format&fit=crop&w=600&q=80', '87g (3U)', 'Even Balance (295mm)', 'Medium Stiff', '30 lbs', 'G6', 'Senjata ikonik Anthony Sinisuka Ginting dengan saluran udara aerodinamis untuk ayunan cepat tanpa hambatan.'),
(12, 'Windstorm 72', 'Li-Ning', 1150000.00, 'https://images.unsplash.com/photo-1544919982-b61976f0ba43?auto=format&fit=crop&w=600&q=80', '72g (6U)', 'Head Heavy (312mm)', 'Flexible', '30 lbs', 'G6', 'Raket ultra ringan 72 gram nomor 1 di Indonesia. Sangat dicari untuk pertahanan rapat, netting, dan permainan cepat.'),
(13, 'Halbertec 8000', 'Li-Ning', 2500000.00, 'https://images.unsplash.com/photo-1617083934555-ac7d4fed881a?auto=format&fit=crop&w=600&q=80', '83g (4U)', 'Even Balance (298mm)', 'Medium Stiff', '30 lbs', 'G5', 'Raket kontrol generasi baru Li-Ning dengan presisi titik jatuh bola tertinggi, favorit pemain ganda taktis.'),
(14, 'Tectonic 7', 'Li-Ning', 2100000.00, 'https://images.unsplash.com/photo-1587280501635-68a0e82cd5ff?auto=format&fit=crop&w=600&q=80', '84g (4U)', 'Head Heavy (305mm)', 'Stiff', '32 lbs', 'G6', 'Konstruksi Tectonic Carbon di posisi jam 5 dan 7 untuk pantulan energi seketika dan smash stabil.'),
(15, 'Woven TJ 1000 / 999', 'Felet', 1550000.00, 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=600&q=80', '82g (4U)', 'Head Heavy (300mm)', 'Stiff', '36 lbs', 'G5', 'Raket Goh V Shem berkonstruksi woven carbon super kokoh, sanggup ditarik hingga 36 lbs tanpa deformasi frame.'),
(16, 'Woven Tech 700', 'Felet', 950000.00, 'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?auto=format&fit=crop&w=600&q=80', '84g (4U)', 'Even Balance (295mm)', 'Medium', '35 lbs', 'G5', 'Best-seller raket mid-range di Indonesia. Menawarkan durabilitas anyaman karbon tinggi dengan harga sangat terjangkau.'),
(17, 'Z-Ziggler', 'Apacs', 580000.00, 'https://images.unsplash.com/photo-1521537634581-0dced2fed2a8?auto=format&fit=crop&w=600&q=80', '85g (4U)', 'Head Heavy (295mm)', 'Medium', '38 lbs', 'G5', 'Raket smash budget sejuta umat di Indonesia. Rangka compact kuat sanggup menahan tarikan ekstrem hingga 38 lbs.'),
(18, 'Lethal 10', 'Apacs', 650000.00, 'https://images.unsplash.com/photo-1599474924187-334a4ae5bd3c?auto=format&fit=crop&w=600&q=80', '84g (4U)', 'Head Heavy (295mm)', 'Medium Stiff', '35 lbs', 'G5', 'Seri Apacs legendaris yang terkenal sangat badak dan tahan benturan saat beradu raket di nomor ganda.'),
(19, 'Tornado 800', 'Flypower', 780000.00, 'https://images.unsplash.com/photo-1544919982-b61976f0ba43?auto=format&fit=crop&w=600&q=80', '86g (3U)', 'Head Heavy (300mm)', 'Stiff', '30 lbs', 'G5', 'Karya legenda bulu tangkis Indonesia Hariyanto Arbi. Karakter ayunan bertenaga khas Indonesia dengan suara dentuman khas.'),
(20, 'Fortius 11 Quick', 'Mizuno', 2800000.00, 'https://images.unsplash.com/photo-1617083934555-ac7d4fed881a?auto=format&fit=crop&w=600&q=80', '83g (4U)', 'Head Heavy (302mm)', 'Stiff', '27 lbs', 'G5', 'Masterpiece Hendra Setiawan buatan Jepang dengan Beyond Force System untuk akselerasi bola instan dan sentuhan net magis.');

-- Tabel racket_scores (Nilai Matriks Keputusan X)
DROP TABLE IF EXISTS `racket_scores`;
CREATE TABLE `racket_scores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `racket_id` int(10) unsigned NOT NULL,
  `criteria_id` int(10) unsigned NOT NULL,
  `score_value` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_racket_scores_racket` (`racket_id`),
  KEY `idx_racket_scores_criteria` (`criteria_id`),
  CONSTRAINT `fk_scores_racket` FOREIGN KEY (`racket_id`) REFERENCES `rackets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_scores_criteria` FOREIGN KEY (`criteria_id`) REFERENCES `criteria` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `racket_scores` (`id`, `racket_id`, `criteria_id`, `score_value`) VALUES
(321, 1, 1, 2750000.00),
(322, 1, 2, 83.00),
(323, 1, 3, 9.00),
(324, 1, 4, 10.00),
(325, 1, 5, 9.00),
(326, 1, 6, 9.00),
(327, 1, 7, 9.00),
(328, 1, 8, 10.00),
(329, 2, 1, 2450000.00),
(330, 2, 2, 83.00),
(331, 2, 3, 8.00),
(332, 2, 4, 9.00),
(333, 2, 5, 8.00),
(334, 2, 6, 9.00),
(335, 2, 7, 9.00),
(336, 2, 8, 9.00),
(337, 3, 1, 2950000.00),
(338, 3, 2, 83.00),
(339, 3, 3, 10.00),
(340, 3, 4, 10.00),
(341, 3, 5, 8.00),
(342, 3, 6, 9.00),
(343, 3, 7, 9.00),
(344, 3, 8, 10.00),
(345, 4, 1, 2700000.00),
(346, 4, 2, 88.00),
(347, 4, 3, 9.00),
(348, 4, 4, 8.00),
(349, 4, 5, 8.00),
(350, 4, 6, 9.00),
(351, 4, 7, 10.00),
(352, 4, 8, 10.00),
(353, 5, 1, 2650000.00),
(354, 5, 2, 83.00),
(355, 5, 3, 9.00),
(356, 5, 4, 7.00),
(357, 5, 5, 8.00),
(358, 5, 6, 9.00),
(359, 5, 7, 9.00),
(360, 5, 8, 9.00),
(361, 6, 1, 2300000.00),
(362, 6, 2, 83.00),
(363, 6, 3, 8.00),
(364, 6, 4, 7.00),
(365, 6, 5, 8.00),
(366, 6, 6, 9.00),
(367, 6, 7, 9.00),
(368, 6, 8, 9.00),
(369, 7, 1, 2850000.00),
(370, 7, 2, 84.00),
(371, 7, 3, 9.00),
(372, 7, 4, 10.00),
(373, 7, 5, 9.00),
(374, 7, 6, 8.00),
(375, 7, 7, 9.00),
(376, 7, 8, 9.00),
(377, 8, 1, 2250000.00),
(378, 8, 2, 88.00),
(379, 8, 3, 9.00),
(380, 8, 4, 9.00),
(381, 8, 5, 9.00),
(382, 8, 6, 8.00),
(383, 8, 7, 9.00),
(384, 8, 8, 9.00),
(385, 9, 1, 2400000.00),
(386, 9, 2, 84.00),
(387, 9, 3, 9.00),
(388, 9, 4, 8.00),
(389, 9, 5, 9.00),
(390, 9, 6, 9.00),
(391, 9, 7, 9.00),
(392, 9, 8, 9.00),
(393, 10, 1, 2550000.00),
(394, 10, 2, 84.00),
(395, 10, 3, 9.00),
(396, 10, 4, 8.00),
(397, 10, 5, 9.00),
(398, 10, 6, 10.00),
(399, 10, 7, 9.00),
(400, 10, 8, 9.00),
(401, 11, 1, 2650000.00),
(402, 11, 2, 87.00),
(403, 11, 3, 8.00),
(404, 11, 4, 8.00),
(405, 11, 5, 9.00),
(406, 11, 6, 9.00),
(407, 11, 7, 9.00),
(408, 11, 8, 9.00),
(409, 12, 1, 1150000.00),
(410, 12, 2, 72.00),
(411, 12, 3, 7.00),
(412, 12, 4, 9.00),
(413, 12, 5, 8.00),
(414, 12, 6, 8.00),
(415, 12, 7, 8.00),
(416, 12, 8, 8.00),
(417, 13, 1, 2500000.00),
(418, 13, 2, 83.00),
(419, 13, 3, 8.00),
(420, 13, 4, 8.00),
(421, 13, 5, 9.00),
(422, 13, 6, 9.00),
(423, 13, 7, 9.00),
(424, 13, 8, 9.00),
(425, 14, 1, 2100000.00),
(426, 14, 2, 84.00),
(427, 14, 3, 8.00),
(428, 14, 4, 9.00),
(429, 14, 5, 9.00),
(430, 14, 6, 8.00),
(431, 14, 7, 9.00),
(432, 14, 8, 8.00),
(433, 15, 1, 1550000.00),
(434, 15, 2, 82.00),
(435, 15, 3, 8.00),
(436, 15, 4, 9.00),
(437, 15, 5, 10.00),
(438, 15, 6, 8.00),
(439, 15, 7, 10.00),
(440, 15, 8, 8.00),
(441, 16, 1, 950000.00),
(442, 16, 2, 84.00),
(443, 16, 3, 8.00),
(444, 16, 4, 8.00),
(445, 16, 5, 10.00),
(446, 16, 6, 8.00),
(447, 16, 7, 9.00),
(448, 16, 8, 8.00),
(449, 17, 1, 580000.00),
(450, 17, 2, 85.00),
(451, 17, 3, 8.00),
(452, 17, 4, 8.00),
(453, 17, 5, 10.00),
(454, 17, 6, 8.00),
(455, 17, 7, 9.00),
(456, 17, 8, 8.00),
(457, 18, 1, 650000.00),
(458, 18, 2, 84.00),
(459, 18, 3, 8.00),
(460, 18, 4, 8.00),
(461, 18, 5, 10.00),
(462, 18, 6, 8.00),
(463, 18, 7, 10.00),
(464, 18, 8, 8.00),
(465, 19, 1, 780000.00),
(466, 19, 2, 86.00),
(467, 19, 3, 8.00),
(468, 19, 4, 9.00),
(469, 19, 5, 9.00),
(470, 19, 6, 8.00),
(471, 19, 7, 9.00),
(472, 19, 8, 8.00),
(473, 20, 1, 2800000.00),
(474, 20, 2, 83.00),
(475, 20, 3, 9.00),
(476, 20, 4, 9.00),
(477, 20, 5, 8.00),
(478, 20, 6, 9.00),
(479, 20, 7, 9.00),
(480, 20, 8, 10.00);

-- Tabel consultation_log (Log Konsultasi Pengunjung)
DROP TABLE IF EXISTS `consultation_log`;
CREATE TABLE `consultation_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `visitor_name` varchar(100) DEFAULT NULL,
  `max_budget` decimal(12,2) DEFAULT NULL,
  `playing_style` varchar(50) DEFAULT NULL,
  `flexibility_pref` varchar(50) DEFAULT NULL,
  `result_top_racket` varchar(150) DEFAULT NULL,
  `result_score` decimal(6,4) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel store_settings (Pengaturan Identitas Toko Dinamis)
DROP TABLE IF EXISTS `store_settings`;
CREATE TABLE `store_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_name` varchar(150) NOT NULL DEFAULT 'Muara Jaya Sport',
  `store_tagline` varchar(255) NOT NULL DEFAULT 'Toko Perlengkapan Bulu Tangkis Profesional & Terlengkap',
  `address` text DEFAULT 'Jl. Muara Jaya No. 45, Jakarta Barat, DKI Jakarta',
  `phone` varchar(50) DEFAULT '+62 812-3456-7890',
  `whatsapp` varchar(50) DEFAULT '6281234567890',
  `email` varchar(100) DEFAULT 'info@muarajayasport.id',
  `logo` mediumtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `store_settings` (`id`, `store_name`, `store_tagline`, `address`, `phone`, `whatsapp`, `email`, `logo`) VALUES
(1, 'Muara Jaya Sport', 'Toko Perlengkapan Bulu Tangkis Profesional & Terlengkap', 'Jl. Muara Jaya No. 45, Jakarta Barat, DKI Jakarta', '+62 812-3456-7890', '6281234567890', 'info@muarajayasport.id', '')
ON DUPLICATE KEY UPDATE `store_name`=VALUES(`store_name`);

SET FOREIGN_KEY_CHECKS = 1;
