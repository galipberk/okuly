-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 04 Haz 2026, 21:16:34
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
  `lesson_id` int(10) UNSIGNED DEFAULT NULL,
  `tarih` date NOT NULL,
  `durum` enum('geldi','gelmedi','mazeretli') NOT NULL DEFAULT 'geldi',
  `not_` text DEFAULT NULL,
  `giris_yapan` int(10) UNSIGNED DEFAULT NULL,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp(),
  `guncelleme` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `institution_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `event` varchar(50) NOT NULL,
  `subject_type` varchar(80) NOT NULL,
  `subject_id` int(10) UNSIGNED DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `ip_adresi` varchar(45) DEFAULT NULL,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `grades`
--

CREATE TABLE `grades` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `lesson_id` int(10) UNSIGNED NOT NULL,
  `sinav_turu` enum('yazili1','yazili2','sozlu','proje','performans','yilsonu') NOT NULL DEFAULT 'yazili1',
  `puan` decimal(5,2) NOT NULL DEFAULT 0.00,
  `max_puan` decimal(5,2) NOT NULL DEFAULT 100.00,
  `tarih` date NOT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `giris_yapan` int(10) UNSIGNED DEFAULT NULL,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `institutions`
--

CREATE TABLE `institutions` (
  `id` int(10) UNSIGNED NOT NULL,
  `ad` varchar(150) NOT NULL DEFAULT 'Eğitim Kurumunuz',
  `slug` varchar(80) NOT NULL DEFAULT 'kurum',
  `telefon` varchar(20) DEFAULT '04622234466',
  `whatsapp` varchar(20) DEFAULT '04622234466',
  `email` varchar(150) DEFAULT '',
  `adres` text DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `olusturma` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `institutions`
--

INSERT INTO `institutions` (`id`, `ad`, `slug`, `telefon`, `whatsapp`, `email`, `adres`, `aktif`, `olusturma`) VALUES
(1, 'Deneme Eğitim Kurumunuz', 'kurum', '04622234455', '04622234455', 'info@deneme.tr', NULL, 1, '2026-05-31 09:29:54');

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
(1, 1, 'okul_adi', 'Eğitim Kurumunuz'),
(2, 1, 'telefon', '04622234466'),
(3, 1, 'whatsapp', '04622234466'),
(4, 1, 'email', 'info@kodlayap.tr'),
(5, 1, 'adres', ''),
(6, 1, 'devamsizlik_limit_normal', '4'),
(7, 1, 'devamsizlik_limit_mazeret', '4'),
(8, 1, 'devamsizlik_uyari_gun', '3');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `lessons`
--

CREATE TABLE `lessons` (
  `id` int(10) UNSIGNED NOT NULL,
  `institution_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `sinif_id` int(10) UNSIGNED NOT NULL,
  `ogretmen_id` int(10) UNSIGNED NOT NULL,
  `ad` varchar(100) NOT NULL,
  `kod` varchar(20) DEFAULT NULL,
  `haftalik_saat` tinyint(4) DEFAULT 4,
  `renk` varchar(7) DEFAULT '#4f46e5',
  `aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

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
(1, '92.44.26.122', 'galipberk@gmail.com', '2026-06-02 23:29:17', 0),
(2, '92.44.26.122', 'galipberk@gmail.com', '2026-06-02 23:29:22', 1),
(3, '92.44.26.122', 'galipberk@gmail.com', '2026-06-04 00:15:40', 1),
(4, '92.44.26.122', 'galipberk@gmail.com', '2026-06-04 01:32:48', 1),
(5, '176.54.189.203', 'galipberk@gmail.com', '2026-06-04 12:05:47', 1),
(6, '92.44.26.122', 'galipberk@gmail.com', '2026-06-04 20:57:02', 1);

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
(1, 1, 'devamsizlik', 'Devamsızlık Bildirimi', 'Sayın {{veli_adi}} Velimiz,\n{{ad_soyad}} adlı öğrenciniz bugün derse GELMEMİŞTİR.\n\n{{okul}}\n📞 {{telefon}}', 1, '2026-05-31 09:29:54', '2026-05-31 09:29:54'),
(2, 1, 'mazeretli', 'Mazeretli Devamsızlık', 'Sayın {{veli_adi}} Velimiz,\n{{ad_soyad}} adlı öğrenciniz bugün MAZERETLİ devamsız sayılmıştır.\n\n{{okul}}\n📞 {{telefon}}', 1, '2026-05-31 09:29:54', '2026-05-31 09:29:54'),
(3, 1, 'kritik', 'Kritik Devamsızlık Uyarısı', '⚠️ Sayın {{veli_adi}} Velimiz,\n{{ad_soyad}} öğrencinizin devamsızlık hakkı dolmak üzere. Lütfen okulumuzu arayın.\n\n{{okul}}\n📞 {{telefon}}', 1, '2026-05-31 09:29:54', '2026-05-31 09:29:54'),
(4, 1, 'odeme', 'Ödeme Hatırlatması', '💰 Sayın {{veli_adi}} Velimiz,\n{{ad_soyad}} öğrenciniz için ödeme tarihiniz yaklaşıyor.\n\n{{okul}}\n📞 {{telefon}}', 1, '2026-05-31 09:29:54', '2026-05-31 09:29:54'),
(5, 1, 'genel', 'Genel Duyuru', 'Sayın {{veli_adi}} Velimiz,\n{{mesaj}}\n\n{{okul}}\n📞 {{telefon}}', 1, '2026-05-31 09:29:54', '2026-05-31 09:29:54');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 1, 'GRAFİK', '2024-2025', 40, '#7c3aed', 1, '2026-05-31 09:29:54'),
(2, 1, 'ROBOTİK', '2024-2025', 40, '#0369a1', 1, '2026-05-31 09:29:54'),
(3, 1, 'ELEKTRONİK', '2024-2025', 40, '#b45309', 1, '2026-05-31 09:29:54'),
(4, 1, '2024-2025 A Şubesi', '2024-2025', 40, '#4f46e5', 0, '2026-05-31 09:37:59'),
(5, 1, '2024-2025 A Şubesi', '2024-2025', 40, '#4f46e5', 0, '2026-05-31 10:26:30');

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

--
-- Tablo döküm verisi `students`
--

INSERT INTO `students` (`id`, `institution_id`, `sinif_id`, `ogrenci_turu`, `danisman_id`, `ogrenci_no`, `kurum_no`, `tc_no`, `ad`, `soyad`, `dogum_tarihi`, `cinsiyet`, `okul_adi`, `baba_adi`, `baba_tel`, `anne_adi`, `anne_tel`, `bildirim_tercih`, `acil_tel`, `adres`, `saglik_notu`, `fotograf`, `yarisma_turu`, `yarisma_alani`, `odeme_durumu`, `kayit_tutari`, `odeme_notu`, `kayit_tarihi`, `aktif`, `ayrilma_nedeni`, `ayrilma_tarihi`, `olusturma`, `guncelleme`, `mezun`, `mezuniyet_yili`) VALUES
(3, 1, 0, 'egitim', NULL, '2026', NULL, NULL, 'Galip', 'BERK', '1111-01-01', 'E', NULL, NULL, NULL, NULL, NULL, 'baba', '54654654654', NULL, 'sdas', NULL, NULL, NULL, 'bekliyor', 0.00, NULL, '2026-05-31', 0, NULL, NULL, '2026-05-31 19:25:35', '2026-06-01 03:54:42', 1, '2024'),
(4, 1, 3, 'egitim', 0, '2026', '2026', '17816786622', 'Eğitim', 'b', '0000-00-00', 'K', '223', '21321', '05777777777', '32132', '05999999999', 'baba', '05333333333', '', 'saglık not', 'uploads/ogrenci/ogrenci_4_1780433251.jpg', '', '', 'odendi', 7000.00, 'ödeme not', '2026-06-01', 0, 'devamsizlik', '2026-06-04 00:32:46', '2026-06-01 03:52:16', '2026-06-03 21:32:46', 0, NULL),
(5, 1, 3, 'proje', 7, 's', 's', '17816786622', 'proje1', 'as', '2222-02-22', 'K', 'okul', 'sad', '05555555555', 'poı', '05444444444', 'baba', '05888888888', '', 'ds', NULL, 'tekn', 'robot', 'muaf', 4.00, 'ödemen', '2026-06-01', 1, NULL, NULL, '2026-06-01 03:53:56', '2026-06-02 20:31:09', 0, NULL),
(6, 1, 3, 'egitim', 0, '20264', '20264', '17816786622', 'Galip2', 'BERK', '1111-01-01', 'K', 'sdd', 'baba', '05555555555', 'anne', '05444444444', 'anne', '', '', 'alerhi', 'uploads/ogrenci/ogrenci_6_1780526047.jpg', '', '', 'taksit', 0.00, 'çdeme', '2026-06-04', 1, NULL, NULL, '2026-06-03 22:34:06', '2026-06-03 22:34:07', 0, NULL);

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
(1, 1, 'Sistem', 'Yöneticisi', 'admin@kodlayap.tr', '04622234466', '$2y$10$MzdAHUawQSn5T4RncKDV5ekZ3nCpsXNgYzsKLuwtEfaWnmzYVFTD6', 'admin', 1, '2026-06-01 08:06:41', '2026-05-31 09:29:54', '2026-06-01 08:06:41'),
(2, 1, 'galip', 'galip', 'galipberk@gmail.com', '05301110001', '$2y$10$9ixV7TndNRlT8HGr8v5DB.9LxlunYIYXFVsuzY6/C1LVKV1zlOOWi', 'admin', 1, '2026-06-04 17:57:02', '2026-05-31 09:29:54', '2026-06-04 17:57:02'),
(7, 1, 'Galip', 'BERK', 'g@g.net', '', '$2y$10$Oug7eBvn3MtF5a.fBZvcjet2bc0PVaGMbwQ7QITX93OT/qTLAhoxW', 'ogretmen', 1, '2026-06-01 17:36:09', '2026-05-31 11:19:21', '2026-06-01 17:36:09');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_gun` (`student_id`,`tarih`),
  ADD KEY `idx_tarih` (`tarih`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_durum` (`durum`);

--
-- Tablo için indeksler `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Tablo için indeksler `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`,`lesson_id`);

--
-- Tablo için indeksler `institutions`
--
ALTER TABLE `institutions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Tablo için indeksler `institution_settings`
--
ALTER TABLE `institution_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_setting` (`institution_id`,`setting_key`);

--
-- Tablo için indeksler `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ogretmen` (`ogretmen_id`),
  ADD KEY `idx_sinif` (`sinif_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_okunmamis` (`is_read`),
  ADD KEY `idx_onem` (`onem`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Tablo için AUTO_INCREMENT değeri `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `institutions`
--
ALTER TABLE `institutions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `institution_settings`
--
ALTER TABLE `institution_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `message_templates`
--
ALTER TABLE `message_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Tablo için AUTO_INCREMENT değeri `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `siniflar`
--
ALTER TABLE `siniflar`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `teacher_notes`
--
ALTER TABLE `teacher_notes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
