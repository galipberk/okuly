<?php
// ============================================================
//  config.php — Tüm ayarlar
//  DİKKAT: Bu dosyaya dışarıdan erişim .htaccess ile engellendi
//  Düzenlemek için: cPanel → File Manager → config.php → Düzenle
// ============================================================
return [

    // ── VERİTABANI ────────────────────────────────────────
    // cPanel → MySQL Databases'den aldığınız bilgileri girin
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'galipberk_kod',          // ← örn: kodlayap_okul
        'user' => 'galipberk_kod',          // ← örn: kodlayap_admin
        'pass' => 'kodlayap46',          // ← DB şifresi
        'charset' => 'utf8mb4',
    ],

    // ── UYGULAMA ──────────────────────────────────────────
    'app' => [
        'url'    => 'https://kodlayap.tr',
        'name' => 'galipberk_kod',
        'debug'  => false,
        'secret' => '695364a0af9daeacb980625bae9689ee6656002c04d3c27f',
    ],

    // ── OKUL BİLGİLERİ ────────────────────────────────────
    'okul' => [
        'adi'      => 'Eğitim Kurumunuz',
        'telefon'  => '04622234466',
        'whatsapp' => '04622234466',
        'email'    => 'info@kodlayap.tr',
    ],

    // ── DEVAMSIZLIK EŞİKLERİ ──────────────────────────────
    'esik' => [
        'uyari'  => 10,    // % — bu oranı geçince uyarı
        'kritik' => 20,    // % — bu oranı geçince kritik
    ],

    // ── WHATSAPP (UltraMsg) ───────────────────────────────
    // https://ultramsg.com → Instance oluştur → QR tara → ID ve Token al
    'ultramsg' => [
        'instance' => '',   // ← ultramsg.com'dan
        'token'    => '',   // ← ultramsg.com'dan
    ],

    // ── SMS (Netgsm) ──────────────────────────────────────
    // https://www.netgsm.com.tr
    'netgsm' => [
        'user' => 'galipberk_kod',
        'pass' => 'kodlayap46',
        'header' => '',
    ],

    // ── TOKEN SÜRELERİ ────────────────────────────────────
    'token' => [
        'web'    => 86400,     // 24 saat
        'mobile' => 2592000,   // 30 gün
    ],
];
