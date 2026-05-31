<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kurulum — Okul Sistemi</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
.wrap{background:#1e293b;border-radius:12px;width:100%;max-width:560px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.4)}
.hdr{background:linear-gradient(135deg,#1e40af,#3b82f6);padding:22px 28px}
.hdr h1{color:#fff;font-size:19px;font-weight:800}.hdr p{color:rgba(255,255,255,.5);font-size:12px;margin-top:3px}
.steps{display:flex;border-bottom:1px solid #334155}
.step{flex:1;padding:10px 4px;text-align:center;font-size:11px;font-weight:600;color:#475569;border-bottom:2px solid transparent}
.step.act{color:#3b82f6;border-bottom-color:#3b82f6}.step.done{color:#16a34a;border-bottom-color:#16a34a}
.prog{height:3px;background:#334155}.prog-b{height:100%;background:#3b82f6;transition:width .4s}
.body{padding:22px 28px}
h3{color:#f1f5f9;font-size:14px;font-weight:700;margin-bottom:12px}
p{color:#94a3b8;font-size:13px;line-height:1.6;margin-bottom:10px}
.fg{margin-bottom:11px}
label{display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px}
input{width:100%;padding:8px 11px;background:#0f172a;border:1px solid #334155;border-radius:6px;color:#f1f5f9;font-size:13px;outline:none;transition:border-color .15s}
input:focus{border-color:#3b82f6}
.r2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.btn{padding:9px 18px;border-radius:6px;font-size:13px;font-weight:600;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:5px;text-decoration:none}
.btn-p{background:#3b82f6;color:#fff}.btn-p:hover{background:#2563eb}
.btn-s{background:#334155;color:#94a3b8}.btn-g{background:#16a34a;color:#fff}
.msg{padding:9px 12px;border-radius:5px;margin-bottom:6px;font-size:13px;display:flex;align-items:flex-start;gap:8px;line-height:1.5}
.ok{background:#052e16;border:1px solid #166534;color:#86efac}
.er{background:#450a0a;border:1px solid #991b1b;color:#fca5a5}
.wa{background:#422006;border:1px solid #92400e;color:#fde68a}
.log{background:#0f172a;border-radius:5px;padding:10px;font-family:monospace;font-size:12px;max-height:200px;overflow-y:auto;margin:10px 0}
.l-ok{color:#4ade80}.l-er{color:#f87171}.l-wa{color:#fbbf24}
.foot{padding:14px 28px;border-top:1px solid #334155;display:flex;justify-content:space-between;align-items:center}
.foot small{color:#475569;font-size:11px}
.warn{background:#422006;border:1px solid #92400e;border-radius:5px;padding:10px 12px;color:#fde68a;font-size:12px;margin-top:10px;line-height:1.5}
code{background:#0f172a;border-radius:3px;padding:2px 6px;font-family:monospace;color:#4ade80;font-size:11px}
a{color:#3b82f6}
</style>
</head>
<body>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (file_exists(__DIR__.'/.installed')) {
    echo '<div style="padding:40px;text-align:center;color:#86efac;background:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center"><div><h2>✅ Kurulum Tamamlandı</h2><br><a href="/panel" style="color:#3b82f6">Panele Git →</a></div></div>';
    exit;
}

$step    = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$msgs    = [];
$err_msg = '';
$cfg_path = __DIR__.'/config.php';

// ── TABLOLARI OLUŞTUR (SQL dosyasına bağımlı değil) ──────────────────────
function createTables(PDO $pdo, array &$msgs): bool {
    $tables = [

    'institutions' => "CREATE TABLE IF NOT EXISTS institutions (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      ad VARCHAR(150) NOT NULL,
      slug VARCHAR(80) UNIQUE NOT NULL,
      telefon VARCHAR(20),
      whatsapp VARCHAR(20) DEFAULT '04622234466',
      email VARCHAR(150),
      adres TEXT,
      aktif TINYINT(1) DEFAULT 1,
      olusturma TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'users' => "CREATE TABLE IF NOT EXISTS users (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      institution_id INT UNSIGNED NOT NULL DEFAULT 1,
      ad VARCHAR(80) NOT NULL,
      soyad VARCHAR(80) NOT NULL,
      email VARCHAR(150) UNIQUE NOT NULL,
      telefon VARCHAR(20),
      sifre VARCHAR(255) NOT NULL,
      rol ENUM('admin','ogretmen','veli','muhasebe') NOT NULL DEFAULT 'ogretmen',
      avatar VARCHAR(255),
      aktif TINYINT(1) DEFAULT 1,
      son_giris TIMESTAMP NULL,
      olusturma TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'siniflar' => "CREATE TABLE IF NOT EXISTS siniflar (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      institution_id INT UNSIGNED NOT NULL DEFAULT 1,
      ad VARCHAR(80) NOT NULL,
      donem VARCHAR(20) NOT NULL,
      kapasite TINYINT DEFAULT 40,
      aktif TINYINT(1) DEFAULT 1,
      olusturma TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'students' => "CREATE TABLE IF NOT EXISTS students (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      institution_id INT UNSIGNED NOT NULL DEFAULT 1,
      sinif_id INT UNSIGNED,
      veli_id INT UNSIGNED,
      ogrenci_no VARCHAR(20) UNIQUE,
      ad VARCHAR(80) NOT NULL,
      soyad VARCHAR(80) NOT NULL,
      dogum_tarihi DATE,
      cinsiyet ENUM('E','K'),
      avatar VARCHAR(255),
      adres TEXT,
      acil_tel VARCHAR(20),
      saglik_notu TEXT,
      kayit_tarihi DATE DEFAULT (CURRENT_DATE),
      aktif TINYINT(1) DEFAULT 1,
      olusturma TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'lessons' => "CREATE TABLE IF NOT EXISTS lessons (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      institution_id INT UNSIGNED NOT NULL DEFAULT 1,
      sinif_id INT UNSIGNED,
      ogretmen_id INT UNSIGNED,
      ad VARCHAR(100) NOT NULL,
      kod VARCHAR(20),
      haftalik_saat TINYINT DEFAULT 4,
      renk VARCHAR(7) DEFAULT '#4f46e5',
      aktif TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'attendance' => "CREATE TABLE IF NOT EXISTS attendance (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      student_id INT UNSIGNED NOT NULL,
      lesson_id INT UNSIGNED NOT NULL,
      tarih DATE NOT NULL,
      durum ENUM('var','yok','gec','izinli') NOT NULL DEFAULT 'var',
      gec_dakika SMALLINT DEFAULT 0,
      not_ TEXT,
      giris_yapan INT UNSIGNED NOT NULL,
      olusturma TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      guncelleme TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_devam (student_id, lesson_id, tarih)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'grades' => "CREATE TABLE IF NOT EXISTS grades (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      student_id INT UNSIGNED NOT NULL,
      lesson_id INT UNSIGNED NOT NULL,
      sinav_turu ENUM('yazili1','yazili2','sozlu','proje','performans','yilsonu') NOT NULL,
      puan DECIMAL(5,2),
      max_puan DECIMAL(5,2) DEFAULT 100,
      tarih DATE NOT NULL,
      aciklama VARCHAR(255),
      giris_yapan INT UNSIGNED NOT NULL,
      olusturma TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'teacher_notes' => "CREATE TABLE IF NOT EXISTS teacher_notes (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      student_id INT UNSIGNED NOT NULL,
      ogretmen_id INT UNSIGNED NOT NULL,
      baslik VARCHAR(200),
      icerik TEXT NOT NULL,
      kategori ENUM('genel','akademik','davranis','saglik','aile','diger') DEFAULT 'genel',
      onem ENUM('normal','onemli','acil') DEFAULT 'normal',
      veliye_gorunsun TINYINT(1) DEFAULT 1,
      olusturma TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'notification_logs' => "CREATE TABLE IF NOT EXISTS notification_logs (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      institution_id INT UNSIGNED NOT NULL DEFAULT 1,
      student_id INT UNSIGNED NOT NULL,
      veli_id INT UNSIGNED,
      veli_telefon VARCHAR(20) NOT NULL,
      kanal ENUM('whatsapp','sms','email','push') NOT NULL,
      sablon_tipi ENUM('devamsizlik','not','odeme','duyuru','genel') DEFAULT 'genel',
      mesaj TEXT NOT NULL,
      durum ENUM('pending','sent','delivered','failed') DEFAULT 'pending',
      api_response TEXT,
      gonderen_id INT UNSIGNED,
      gonderim_tarihi DATETIME,
      olusturma TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'payments' => "CREATE TABLE IF NOT EXISTS payments (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      institution_id INT UNSIGNED NOT NULL DEFAULT 1,
      student_id INT UNSIGNED NOT NULL,
      aciklama VARCHAR(200) NOT NULL,
      tutar DECIMAL(10,2) NOT NULL,
      durum ENUM('bekliyor','odendi','gecikti','iptal') DEFAULT 'bekliyor',
      son_odeme_tarihi DATE,
      odeme_tarihi DATE,
      odeme_yontemi ENUM('nakit','kart','havale','eft'),
      not_ TEXT,
      giris_yapan INT UNSIGNED,
      olusturma TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    ];

    $ok = 0; $er = 0;
    foreach ($tables as $name => $sql) {
        try {
            $pdo->exec($sql);
            $ok++;
        } catch (PDOException $e) {
            $msgs[] = ['er', "✗ $name tablosu: " . $e->getMessage()];
            $er++;
        }
    }
    $msgs[] = ['ok', "✓ $ok tablo oluşturuldu" . ($er ? ", $er hata" : '')];

    // Başlangıç verileri
    try {
        // Kurum
        $pdo->exec("INSERT IGNORE INTO institutions (id,ad,slug,telefon,whatsapp) VALUES (1,'Eğitim Kurumunuz','egitim-kurumunuz','04622234466','04622234466')");

        // Sınıf
        $pdo->exec("INSERT IGNORE INTO siniflar (institution_id,ad,donem,kapasite) VALUES (1,'2024-2025 A Şubesi','2024-2025',40)");

        // Geçici admin (setup adım 4'te güncellenir)
        $hash = password_hash('Admin123!', PASSWORD_BCRYPT, ['cost'=>10]);
        $pdo->exec("INSERT IGNORE INTO users (institution_id,ad,soyad,email,sifre,rol,aktif) VALUES (1,'Sistem','Yöneticisi','admin@kodlayap.tr','$hash','admin',1)");
        $msgs[] = ['ok', '✓ Başlangıç verileri eklendi'];

    } catch (PDOException $e) {
        $msgs[] = ['wa', '⚠ Başlangıç verileri: ' . $e->getMessage()];
    }

    return $er === 0;
}

// ── ADIM 3: VERİTABANI ──────────────────────────────────────
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $port = trim($_POST['db_port'] ?? '3306');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = $_POST['db_pass'] ?? '';

    if (!$name || !$user) {
        $err_msg = 'Veritabanı adı ve kullanıcı adı gerekli';
    } else {
        try {
            // Önce DB adı olmadan bağlan
            $pdo = new PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                $user, $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]
            );
            $msgs[] = ['ok', "✓ MySQL bağlantısı kuruldu ({$host})"];

            // Veritabanını oluştur
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$name}`");
            $msgs[] = ['ok', "✓ Veritabanı hazır: {$name}"];

            // Tabloları oluştur (SQL dosyasına bağımlı değil)
            createTables($pdo, $msgs);

            // config.php güncelle
            $secret = bin2hex(random_bytes(24));
            $cfg = file_get_contents($cfg_path);
            $cfg = preg_replace("/'host'\s*=>\s*'[^']*'/",   "'host' => '{$host}'",   $cfg);
            $cfg = preg_replace("/'port'\s*=>\s*'[^']*'/",   "'port' => '{$port}'",   $cfg);
            $cfg = preg_replace("/'name'\s*=>\s*'[^']*'/",   "'name' => '{$name}'",   $cfg);
            $cfg = preg_replace("/'user'\s*=>\s*'[^']*'/",   "'user' => '{$user}'",   $cfg);
            $cfg = preg_replace("/'pass'\s*=>\s*'[^']*'/",   "'pass' => '{$pass}'",   $cfg);
            $cfg = preg_replace("/'secret'\s*=>\s*'[^']*'/", "'secret' => '{$secret}'", $cfg);
            file_put_contents($cfg_path, $cfg);
            $msgs[] = ['ok', '✓ config.php güncellendi'];

            $step = 4;

        } catch (PDOException $e) {
            $err_msg = $e->getMessage();
        }
    }
}

// ── ADIM 4: AYARLAR & ADMİN ─────────────────────────────────
if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_url'])) {

    $okul  = trim($_POST['okul_adi'] ?? 'Eğitim Kurumunuz');
    $tel   = trim($_POST['okul_tel'] ?? '04622234466');
    $wa    = trim($_POST['okul_wa']  ?? '04622234466');
    $amail = trim($_POST['admin_email'] ?? 'admin@kodlayap.tr');
    $apass = trim($_POST['admin_pass'] ?? '');

    // config.php güncelle
    if (file_exists($cfg_path)) {
        $cfg = file_get_contents($cfg_path);
        $cfg = preg_replace("/'adi'\s*=>\s*'[^']*'/",      "'adi' => '".addslashes($okul)."'", $cfg);
        $cfg = preg_replace("/'telefon'\s*=>\s*'[^']*'/",   "'telefon' => '{$tel}'",  $cfg);
        $cfg = preg_replace("/'whatsapp'\s*=>\s*'[^']*'/",  "'whatsapp' => '{$wa}'",  $cfg);
        file_put_contents($cfg_path, $cfg);
        $msgs[] = ['ok', '✓ Okul bilgileri kaydedildi'];
    }

    // Admin hesabını güncelle
    if (strlen($apass) < 8) {
        $msgs[] = ['wa', '⚠ Şifre en az 8 karakter olmalı — varsayılan şifre korundu (Admin123!)'];
    } else {
        try {
            $CFG2 = require $cfg_path;
            $d    = $CFG2['db'];
            $dsn  = "mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset=utf8mb4";
            $pdo2 = new PDO($dsn, $d['user'], $d['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            $hash = password_hash($apass, PASSWORD_BCRYPT, ['cost' => 10]);

            // Admin var mı?
            $count = (int)$pdo2->query("SELECT COUNT(*) FROM users WHERE rol='admin'")->fetchColumn();
            if ($count > 0) {
                $pdo2->prepare("UPDATE users SET email=?, sifre=? WHERE rol='admin'")->execute([$amail, $hash]);
                $msgs[] = ['ok', "✓ Admin güncellendi: {$amail}"];
            } else {
                $pdo2->prepare("INSERT INTO users (institution_id,ad,soyad,email,sifre,rol,aktif) VALUES (1,'Sistem','Yöneticisi',?,?,'admin',1)")->execute([$amail, $hash]);
                $msgs[] = ['ok', "✓ Admin oluşturuldu: {$amail}"];
            }

            // institutions tablosunu da güncelle
            $pdo2->prepare("UPDATE institutions SET ad=?,telefon=?,whatsapp=? WHERE id=1")->execute([$okul,$tel,$wa]);

        } catch (PDOException $e) {
            $msgs[] = ['er', '✗ Admin hatası: '.$e->getMessage()];
            // Hata olsa bile devam et
        }
    }

    file_put_contents(__DIR__.'/.installed', date('Y-m-d H:i:s'));
    $step = 5;
}

// ── SİSTEM KONTROL ──────────────────────────────────────────
function sysChecks(): array {
    return [
        ['PHP ≥ 8.0',      version_compare(PHP_VERSION,'8.0','>='), PHP_VERSION],
        ['PDO MySQL',      extension_loaded('pdo_mysql'), ''],
        ['cURL',           extension_loaded('curl'), ''],
        ['api.php mevcut', file_exists(__DIR__.'/api.php'), ''],
        ['config.php',     is_writable(__DIR__.'/config.php'), is_writable(__DIR__.'/config.php')?'yazılabilir':'YAZMA İZNİ YOK'],
    ];
}
$checks = sysChecks();
$allOk  = !in_array(false, array_column($checks, 1));
$pct    = ['1'=>20,'2'=>40,'3'=>60,'4'=>80,'5'=>100][$step] ?? 20;
?>

<div class="wrap">
  <div class="hdr"><h1>📚 Okul Yönetim Sistemi</h1><p>Kurulum Sihirbazı</p></div>
  <div class="steps">
    <?php foreach(['Hoşgeldin','Sistem','Veritabanı','Ayarlar','Bitti!'] as $i=>$l): $s=$i+1; ?>
    <div class="step <?= $s==$step?'act':($s<$step?'done':'') ?>"><?= $s ?>. <?= $l ?></div>
    <?php endforeach; ?>
  </div>
  <div class="prog"><div class="prog-b" style="width:<?= $pct ?>%"></div></div>

  <?php if($step===1): ?>
  <div class="body">
    <h3>👋 Hoşgeldiniz</h3>
    <p>Bu sihirbaz <strong>Okul Yönetim Sistemini</strong> kodlayap.tr'ye kurar. Composer veya SSH gerekmez.</p>
    <p>Tablolar SQL dosyasına değil, <strong>PHP kodu ile</strong> oluşturulur — hata riski sıfır.</p>
    <div class="warn">⚠️ Kurulum bittikten sonra <code>setup.php</code> ve <code>test.php</code> dosyalarını silin!</div>
  </div>
  <div class="foot">
    <small>Bağımsız PHP · Composer yok</small>
    <form method="POST"><input type="hidden" name="step" value="2"><button class="btn btn-p">Başla →</button></form>
  </div>

  <?php elseif($step===2): ?>
  <div class="body">
    <h3>🔍 Sistem Gereksinimleri</h3>
    <?php foreach($checks as $c): ?>
    <div class="msg <?= $c[1]?'ok':'er' ?>">
      <span style="flex-shrink:0"><?= $c[1]?'✓':'✗' ?></span>
      <span><?= $c[0] ?><?= $c[2]?" ({$c[2]})":'' ?></span>
    </div>
    <?php endforeach; ?>
    <?php if(!$allOk): ?>
    <div class="warn" style="margin-top:10px">
      ⚠️ cPanel → <strong>Select PHP Version</strong> → PHP 8.1 veya 8.2 → pdo_mysql + curl aktif et → Save
    </div>
    <?php endif; ?>
  </div>
  <div class="foot">
    <a href="?step=1" class="btn btn-s">← Geri</a>
    <form method="POST"><input type="hidden" name="step" value="3">
      <button class="btn btn-p" <?= !$allOk?'disabled':'' ?>>Devam →</button>
    </form>
  </div>

  <?php elseif($step===3): ?>
  <div class="body">
    <h3>🗄️ MySQL Veritabanı</h3>
    <p>cPanel → <strong>MySQL Databases</strong> → Veritabanı + kullanıcı oluşturun → her ikisini de buraya girin.</p>
    <?php if($err_msg): ?><div class="msg er">✗ <?= htmlspecialchars($err_msg) ?></div><?php endif; ?>
    <?php if($msgs): ?><div class="log"><?php foreach($msgs as $l) echo "<div class='l-{$l[0]}'>{$l[1]}</div>"; ?></div><?php endif; ?>
    <form method="POST" style="margin-top:10px">
      <input type="hidden" name="step" value="3">
      <div class="r2">
        <div class="fg"><label>Host</label><input name="db_host" value="localhost"></div>
        <div class="fg"><label>Port</label><input name="db_port" value="3306"></div>
      </div>
      <div class="fg"><label>Veritabanı Adı *</label><input name="db_name" placeholder="ornekkullanici_okul" required></div>
      <div class="r2">
        <div class="fg"><label>DB Kullanıcısı *</label><input name="db_user" placeholder="ornekkullanici_admin" required></div>
        <div class="fg"><label>DB Şifresi</label><input type="password" name="db_pass"></div>
      </div>
      <div class="warn" style="font-size:11px">
        💡 cPanel prefix ekler! <strong>MySQL Databases</strong> sayfasında tam adı kopyalayın.<br>
        Örn: <code>u12345_okul</code> ve <code>u12345_admin</code>
      </div>
      <div style="display:flex;justify-content:space-between;margin-top:12px">
        <a href="?step=2" class="btn btn-s">← Geri</a>
        <button class="btn btn-p" type="submit">Bağlan ve Kur →</button>
      </div>
    </form>
  </div>

  <?php elseif($step===4): ?>
  <div class="body">
    <h3>⚙️ Son Ayarlar</h3>
    <?php if($msgs): ?><div class="log"><?php foreach($msgs as $l) echo "<div class='l-{$l[0]}'>{$l[1]}</div>"; ?></div><?php endif; ?>
    <form method="POST" style="margin-top:12px">
      <input type="hidden" name="step" value="4">
      <div class="fg"><label>Kurum Adı</label><input name="okul_adi" value="Eğitim Kurumunuz"></div>
      <div class="r2">
        <div class="fg"><label>Telefon</label><input name="okul_tel" value="04622234466"></div>
        <div class="fg"><label>WhatsApp</label><input name="okul_wa"  value="04622234466"></div>
      </div>
      <hr style="border:none;border-top:1px solid #334155;margin:14px 0">
      <p style="margin-bottom:10px">Admin giriş bilgilerinizi belirleyin:</p>
      <div class="fg"><label>Admin E-posta</label><input type="email" name="admin_email" value="admin@kodlayap.tr"></div>
      <div class="fg"><label>Admin Şifresi (min. 8 karakter)</label><input type="password" name="admin_pass" minlength="8" placeholder="Güçlü bir şifre girin"></div>
      <div style="display:flex;justify-content:flex-end;margin-top:14px">
        <button class="btn btn-g" type="submit">🚀 Kurulumu Tamamla</button>
      </div>
    </form>
  </div>

  <?php elseif($step===5): ?>
  <div class="body">
    <?php if($msgs): ?><div class="log"><?php foreach($msgs as $l) echo "<div class='l-{$l[0]}'>{$l[1]}</div>"; ?></div><?php endif; ?>
    <div style="text-align:center;font-size:44px;margin:8px 0">🎉</div>
    <h3 style="text-align:center;font-size:17px;margin-bottom:12px">Kurulum Tamamlandı!</h3>
    <div class="msg ok">✓ API: <a href="/api.php" target="_blank" style="color:#4ade80;margin-left:6px">kodlayap.tr/api.php</a> → {"status":"ok"} görmeli</div>
    <div class="msg ok">✓ Panel: <a href="/panel" style="color:#4ade80;margin-left:6px">kodlayap.tr/panel →</a></div>
    <div class="msg ok">✓ Test: <a href="/test.php" style="color:#4ade80;margin-left:6px">kodlayap.tr/test.php →</a></div>
    <div class="msg wa">⚠ Silin: <code>setup.php</code> · <code>test.php</code> · <code>api-debug.php</code></div>
    <div style="background:#0f172a;border-radius:6px;padding:12px;margin-top:12px;font-size:12px;color:#64748b;line-height:1.8">
      <strong style="color:#94a3b8">Varsayılan giriş bilgileri:</strong><br>
      E-posta: <code>admin@kodlayap.tr</code> (veya girdiğiniz)<br>
      Şifre: Adım 4'te belirlediğiniz şifre<br>
      Belirlemediyseniz: <code>Admin123!</code>
    </div>
  </div>
  <div class="foot">
    <small>⚠ Hassas dosyaları silin</small>
    <a href="/panel" class="btn btn-g">Panele Git →</a>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
