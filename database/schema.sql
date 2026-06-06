-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 06 Haz 2026, 19:22:22
-- Sunucu sürümü: 10.4.34-MariaDB
-- PHP Sürümü: 8.2.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `galipberk_kod`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `tarih` date NOT NULL,
  `durum` enum('geldi','gelmedi','mazeretli') NOT NULL DEFAULT 'geldi',
  `not_` text DEFAULT NULL,
  `giris_yapan` int(10) UNSIGNED DEFAULT NULL,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp(),
  `guncelleme` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `institution_settings`
--

CREATE TABLE `institution_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `institution_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `institution_settings`
--

INSERT INTO `institution_settings` (`id`, `institution_id`, `setting_key`, `setting_value`) VALUES
(1, 1, 'okul_adi', 'Eğitim Kurumunuz23'),
(2, 1, 'telefon', '04622234466'),
(3, 1, 'whatsapp', '04622234466'),
(4, 1, 'email', ''),
(5, 1, 'adres', ''),
(6, 1, 'devamsizlik_limit_normal', '41'),
(7, 1, 'devamsizlik_limit_mazeret', '1'),
(8, 1, 'devamsizlik_uyari_gun', '31');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(45) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tarih` datetime NOT NULL,
  `basarili` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip`, `email`, `tarih`, `basarili`) VALUES
(1, '92.44.26.122', 'admin@kodlayap.tr', '2026-06-06 19:16:59', 1),
(2, '92.44.26.122', 'aa', '2026-06-06 19:19:50', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `message_templates`
--

CREATE TABLE `message_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `institution_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `kod` varchar(50) NOT NULL,
  `baslik` varchar(200) NOT NULL,
  `icerik` text NOT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp(),
  `guncelleme` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `message_templates`
--

INSERT INTO `message_templates` (`id`, `institution_id`, `kod`, `baslik`, `icerik`, `aktif`, `olusturma`, `guncelleme`) VALUES
(1, 1, 'devamsizlik', 'Devamsızlık Bildirimi', 'Sayın {{veli_adi}} Velimiz,\n{{ad_soyad}} adlı öğrenciniz bugün derse GELMEMİŞTİR.\n\n{{okul}}\n📞 {{telefon}}', 1, '2026-06-06 16:16:32', '2026-06-06 16:16:32'),
(2, 1, 'mazeretli', 'Mazeretli Devamsızlık', 'Sayın {{veli_adi}} Velimiz,\n{{ad_soyad}} adlı öğrenciniz bugün MAZERETLİ devamsız sayılmıştır.\n\n{{okul}}\n📞 {{telefon}}', 1, '2026-06-06 16:16:32', '2026-06-06 16:16:32'),
(3, 1, 'kritik', 'Kritik Devamsızlık Uyarısı', '⚠️ Sayın {{veli_adi}} Velimiz,\n{{ad_soyad}} öğrencinizin devamsızlık hakkı dolmak üzere. Lütfen okulumuzu arayın.\n\n{{okul}}\n📞 {{telefon}}', 1, '2026-06-06 16:16:32', '2026-06-06 16:16:32'),
(4, 1, 'odeme', 'Ödeme Hatırlatması', '💰 Sayın {{veli_adi}} Velimiz,\n{{ad_soyad}} öğrenciniz için ödeme tarihiniz yaklaşıyor.\n\n{{okul}}\n📞 {{telefon}}', 1, '2026-06-06 16:16:32', '2026-06-06 16:16:32'),
(5, 1, 'genel', 'Genel Duyuru', 'Sayın {{veli_adi}} Velimiz,\n{{mesaj}}\n\n{{okul}}\n📞 {{telefon}}', 1, '2026-06-06 16:16:32', '2026-06-06 16:16:32');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `institution_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `student_id` int(10) UNSIGNED NOT NULL,
  `veli_telefon` varchar(20) NOT NULL,
  `kanal` enum('whatsapp','sms','email') NOT NULL DEFAULT 'whatsapp',
  `sablon_tipi` varchar(50) DEFAULT 'genel',
  `mesaj` text NOT NULL,
  `durum` enum('pending','sent','delivered','failed','manuel_gonderildi') DEFAULT 'pending',
  `api_response` text DEFAULT NULL,
  `gonderen_id` int(10) UNSIGNED DEFAULT NULL,
  `gonderim_tarihi` timestamp NULL DEFAULT NULL,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `institution_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `student_id` int(10) UNSIGNED NOT NULL,
  `aciklama` varchar(200) NOT NULL,
  `tutar` decimal(10,2) NOT NULL,
  `durum` enum('bekliyor','odendi','gecikti','iptal') DEFAULT 'bekliyor',
  `son_odeme_tarihi` date DEFAULT NULL,
  `odeme_tarihi` date DEFAULT NULL,
  `odeme_yontemi` enum('nakit','kart','havale','eft') DEFAULT NULL,
  `not_` text DEFAULT NULL,
  `giris_yapan` int(10) UNSIGNED DEFAULT NULL,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `siniflar`
--

CREATE TABLE `siniflar` (
  `id` int(10) UNSIGNED NOT NULL,
  `institution_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `ad` varchar(80) NOT NULL,
  `donem` varchar(20) NOT NULL DEFAULT '2024-2025',
  `kapasite` tinyint(4) DEFAULT 40,
  `renk` varchar(7) DEFAULT '#4f46e5',
  `aktif` tinyint(1) DEFAULT 1,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `siniflar`
--

INSERT INTO `siniflar` (`id`, `institution_id`, `ad`, `donem`, `kapasite`, `renk`, `aktif`, `olusturma`) VALUES
(1, 1, '2024-2025 A Şubesi', '2024-2025', 40, '#4f46e5', 1, '2026-06-06 16:16:32');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `institution_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `sinif_id` int(10) UNSIGNED DEFAULT NULL,
  `ogrenci_turu` enum('egitim','proje') NOT NULL DEFAULT 'egitim',
  `danisman_id` int(10) UNSIGNED DEFAULT NULL,
  `ogrenci_no` varchar(20) DEFAULT NULL,
  `kurum_no` varchar(30) DEFAULT NULL,
  `tc_no` varchar(11) DEFAULT NULL,
  `ad` varchar(80) NOT NULL,
  `soyad` varchar(80) NOT NULL,
  `dogum_tarihi` date DEFAULT NULL,
  `cinsiyet` enum('E','K') DEFAULT NULL,
  `okul_adi` varchar(200) DEFAULT NULL,
  `baba_adi` varchar(80) DEFAULT NULL,
  `baba_tel` varchar(20) DEFAULT NULL,
  `anne_adi` varchar(80) DEFAULT NULL,
  `anne_tel` varchar(20) DEFAULT NULL,
  `bildirim_tercih` enum('baba','anne') NOT NULL DEFAULT 'baba',
  `acil_tel` varchar(20) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `saglik_notu` text DEFAULT NULL,
  `fotograf` varchar(255) DEFAULT NULL,
  `yarisma_turu` varchar(150) DEFAULT NULL,
  `yarisma_alani` varchar(150) DEFAULT NULL,
  `odeme_durumu` enum('odendi','bekliyor','taksit','muaf') NOT NULL DEFAULT 'bekliyor',
  `kayit_tutari` decimal(10,2) DEFAULT 0.00,
  `odeme_notu` text DEFAULT NULL,
  `kayit_tarihi` date DEFAULT curdate(),
  `aktif` tinyint(1) DEFAULT 1,
  `ayrilma_nedeni` varchar(255) DEFAULT NULL,
  `ayrilma_tarihi` datetime DEFAULT NULL,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp(),
  `guncelleme` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `mezun` tinyint(1) DEFAULT 0,
  `mezuniyet_yili` year(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `teacher_notes`
--

CREATE TABLE `teacher_notes` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `ogretmen_id` int(10) UNSIGNED NOT NULL,
  `baslik` varchar(200) DEFAULT NULL,
  `icerik` text NOT NULL,
  `kategori` enum('genel','akademik','davranis','saglik','aile','diger') DEFAULT 'genel',
  `onem` enum('normal','onemli','acil') DEFAULT 'normal',
  `veliye_gorunsun` tinyint(1) DEFAULT 1,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `read_by` int(10) UNSIGNED DEFAULT NULL,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `institution_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `ad` varchar(80) NOT NULL,
  `soyad` varchar(80) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `sifre` varchar(255) NOT NULL,
  `rol` enum('admin','ogretmen','veli','muhasebe') NOT NULL DEFAULT 'ogretmen',
  `aktif` tinyint(1) DEFAULT 1,
  `son_giris` timestamp NULL DEFAULT NULL,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp(),
  `guncelleme` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `institution_id`, `ad`, `soyad`, `email`, `telefon`, `sifre`, `rol`, `aktif`, `son_giris`, `olusturma`, `guncelleme`) VALUES
(1, 1, 'Sistem', 'Yöneticisi', 'admin@kodlayap.tr', NULL, '$2y$10$C9/VC83S5SZJ86VUcKTsROdYrUVcxXqWdydTxGbl851kjkPAXRrb2', 'admin', 1, '2026-06-06 16:16:59', '2026-06-06 16:16:32', '2026-06-06 16:16:59');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_gun` (`student_id`,`tarih`);

--
-- Tablo için indeksler `institution_settings`
--
ALTER TABLE `institution_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_setting` (`institution_id`,`setting_key`);

--
-- Tablo için indeksler `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_tarih` (`ip`,`tarih`),
  ADD KEY `idx_email_tarih` (`email`,`tarih`);

--
-- Tablo için indeksler `message_templates`
--
ALTER TABLE `message_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sablon` (`institution_id`,`kod`);

--
-- Tablo için indeksler `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_institution` (`institution_id`),
  ADD KEY `idx_durum` (`durum`),
  ADD KEY `idx_student` (`student_id`);

--
-- Tablo için indeksler `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `siniflar`
--
ALTER TABLE `siniflar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_institution` (`institution_id`);

--
-- Tablo için indeksler `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_institution` (`institution_id`),
  ADD KEY `idx_sinif` (`sinif_id`),
  ADD KEY `idx_tur` (`ogrenci_turu`),
  ADD KEY `idx_aktif` (`aktif`);

--
-- Tablo için indeksler `teacher_notes`
--
ALTER TABLE `teacher_notes`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_institution` (`institution_id`),
  ADD KEY `idx_rol` (`rol`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `institution_settings`
--
ALTER TABLE `institution_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Tablo için AUTO_INCREMENT değeri `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `message_templates`
--
ALTER TABLE `message_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `siniflar`
--
ALTER TABLE `siniflar`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `teacher_notes`
--
ALTER TABLE `teacher_notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
