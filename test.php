<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sistem Testi — Okul Paneli</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#0f172a;color:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px}
.wrap{width:100%;max-width:620px}
.card{background:#1e293b;border-radius:10px;overflow:hidden;margin-bottom:14px}
.card-head{padding:12px 18px;background:#0f172a;font-size:13px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #334155}
.row{display:flex;align-items:center;padding:10px 18px;border-bottom:1px solid #1e293b;gap:10px;font-size:13px}
.row:last-child{border-bottom:none}
.dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
.ok{background:#16a34a}.err{background:#dc2626}.warn{background:#d97706}.info{background:#3b82f6}
.lbl{flex:1;color:#94a3b8}
.val{color:#f1f5f9;font-weight:500;text-align:right;font-size:12px}
.val code{background:#0f172a;padding:2px 7px;border-radius:4px;font-family:monospace;color:#4ade80;font-size:11px}
.title{text-align:center;padding:20px 0 16px;font-size:20px;font-weight:800;color:#f1f5f9}
.sub{text-align:center;color:#475569;font-size:12px;margin-bottom:20px}
.summary{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px}
.sum-card{background:#1e293b;border-radius:8px;padding:14px;text-align:center}
.sum-val{font-size:24px;font-weight:800;margin-bottom:4px}
.sum-lbl{font-size:11px;color:#64748b}
.links{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap}
a.btn{padding:9px 16px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.btn-p{background:#3b82f6;color:#fff}
.btn-s{background:#334155;color:#94a3b8}
.btn-g{background:#16a34a;color:#fff}
.warn-box{background:#422006;border:1px solid #92400e;border-radius:8px;padding:12px 16px;color:#fde68a;font-size:13px;margin-bottom:14px;line-height:1.5}
</style>
</head>
<body>
<?php
// test.php — Kurulum Doğrulama (kurulduktan sonra silin!)
error_reporting(0);

$config_file = __DIR__ . '/config.php';
$CFG = file_exists($config_file) ? require $config_file : [];
$db  = $CFG['db'] ?? [];

$results   = [];
$all_ok    = true;
$db_ok     = false;
$db_tables = 0;
$student_c = 0;
$user_c    = 0;

// ── PHP Kontrolleri ──────────────────────────
function chk(string $lbl, bool $ok, string $val = ''): array {
    return ['lbl' => $lbl, 'ok' => $ok, 'val' => $val ?: ($ok ? '✓' : '✗')];
}

$php_checks = [
    chk('PHP Sürümü', version_compare(PHP_VERSION,'8.1','>='), PHP_VERSION),
    chk('PDO MySQL',  extension_loaded('pdo_mysql')),
    chk('cURL',       extension_loaded('curl')),
    chk('JSON',       extension_loaded('json')),
    chk('mbstring',   extension_loaded('mbstring')),
    chk('OpenSSL',    extension_loaded('openssl')),
];

// ── Dosya Kontrolleri ────────────────────────
$file_checks = [
    chk('config.php',    file_exists($config_file)),
    chk('.installed',    file_exists(__DIR__.'/.installed'), file_exists(__DIR__.'/.installed') ? date('d.m.Y H:i', filemtime(__DIR__.'/.installed')) : 'YOK'),
    chk('schema.sql',    file_exists(__DIR__.'/database/schema.sql')),
    chk('api.php',       file_exists(__DIR__.'/api.php')),
    chk('.htaccess',     file_exists(__DIR__.'/.htaccess')),
    chk('Vue.js',        file_exists(__DIR__.'/assets/js/vue.min.js'), file_exists(__DIR__.'/assets/js/vue.min.js') ? round(filesize(__DIR__.'/assets/js/vue.min.js')/1024).' KB' : 'YOK'),
    chk('Axios',         file_exists(__DIR__.'/assets/js/axios.min.js')),
    chk('Tabler CSS',    file_exists(__DIR__.'/assets/css/tabler-icons.min.css')),
];

// ── Veritabanı Kontrolü ──────────────────────
if (!empty($db['name']) && !empty($db['user'])) {
    try {
        $pdo = new PDO(
            "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
            $db['user'], $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
        );
        $db_ok = true;
        $db_tables = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()")->fetchColumn();
        $student_c = (int)($pdo->query("SELECT COUNT(*) FROM students WHERE aktif=1")->fetchColumn() ?? 0);
        $user_c    = (int)($pdo->query("SELECT COUNT(*) FROM users WHERE aktif=1")->fetchColumn() ?? 0);

        $db_checks = [
            chk('Bağlantı', true, "{$db['host']}:{$db['port']}"),
            chk('Veritabanı', true, $db['name']),
            chk('Tablo Sayısı', $db_tables >= 10, "$db_tables tablo"),
            chk('users tablosu',    (bool)$pdo->query("SHOW TABLES LIKE 'users'")->fetch()),
            chk('students tablosu', (bool)$pdo->query("SHOW TABLES LIKE 'students'")->fetch()),
            chk('attendance tablosu',(bool)$pdo->query("SHOW TABLES LIKE 'attendance'")->fetch()),
        ];
    } catch (PDOException $e) {
        $db_checks = [chk('Bağlantı', false, substr($e->getMessage(), 0, 60))];
    }
} else {
    $db_checks = [chk('config.php dolduruldu', false, 'DB bilgileri eksik')];
}

// ── API Testi ────────────────────────────────
$api_ok = false;
$api_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/api/ping';
$ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
$api_resp = @file_get_contents($api_url, false, $ctx);
if ($api_resp) {
    $api_data = json_decode($api_resp, true);
    $api_ok = ($api_data['status'] ?? '') === 'ok';
}
$api_checks = [
    chk('/api/ping', $api_ok, $api_ok ? 'Çalışıyor' : 'Erişilemiyor — .htaccess kontrol edin'),
    chk('API Yanıtı', $api_ok, $api_ok ? ($api_data['version'] ?? '') : substr($api_resp ?? 'Yanıt yok',0,40)),
];

// Genel durum
$all_checks = array_merge($php_checks, $file_checks, $db_checks, $api_checks);
$all_ok = !in_array(false, array_column($all_checks, 'ok'));
$ok_count  = count(array_filter($all_checks, fn($c) => $c['ok']));
$err_count = count($all_checks) - $ok_count;
?>

<div class="wrap">
  <div class="title">🔍 Sistem Durum Raporu</div>
  <div class="sub">kodlayap.tr · <?= date('d.m.Y H:i:s') ?></div>

  <!-- Özet -->
  <div class="summary">
    <div class="sum-card">
      <div class="sum-val" style="color:<?= $all_ok?'#16a34a':'#dc2626' ?>"><?= $all_ok ? '✓' : '✗' ?></div>
      <div class="sum-lbl"><?= $all_ok ? 'Tüm Kontroller OK' : "$err_count Hata Var" ?></div>
    </div>
    <div class="sum-card">
      <div class="sum-val" style="color:#3b82f6"><?= $student_c ?></div>
      <div class="sum-lbl">Öğrenci</div>
    </div>
    <div class="sum-card">
      <div class="sum-val" style="color:#7c3aed"><?= $user_c ?></div>
      <div class="sum-lbl">Kullanıcı</div>
    </div>
  </div>

  <?php if($err_count > 0): ?>
  <div class="warn-box">
    ⚠️ <strong><?= $err_count ?> kontrol başarısız.</strong>
    <?= !$db_ok ? ' Veritabanı bağlantısı kurulamadı — config.php içindeki DB bilgilerini kontrol edin.' : '' ?>
    <?= !$api_ok ? ' API erişilemiyor — .htaccess dosyasının public/ klasöründe olduğunu ve mod_rewrite aktif olduğunu kontrol edin.' : '' ?>
  </div>
  <?php endif; ?>

  <!-- Hızlı Linkler -->
  <div class="links">
    <a href="/panel" class="btn btn-p">📊 Panele Git</a>
    <a href="/api/ping" class="btn btn-s" target="_blank">🔗 API Ping</a>
    <?php if(!file_exists(__DIR__.'/.installed')): ?>
    <a href="/setup.php" class="btn btn-g">⚙️ Kurulumu Tamamla</a>
    <?php endif; ?>
  </div>

  <!-- PHP -->
  <div class="card">
    <div class="card-head">PHP & Eklentiler</div>
    <?php foreach($php_checks as $c): ?>
    <div class="row">
      <div class="dot <?= $c['ok']?'ok':'err' ?>"></div>
      <div class="lbl"><?= $c['lbl'] ?></div>
      <div class="val"><?= htmlspecialchars($c['val']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Dosyalar -->
  <div class="card">
    <div class="card-head">Dosya Sistemi</div>
    <?php foreach($file_checks as $c): ?>
    <div class="row">
      <div class="dot <?= $c['ok']?'ok':'err' ?>"></div>
      <div class="lbl"><?= $c['lbl'] ?></div>
      <div class="val"><?= htmlspecialchars($c['val']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Veritabanı -->
  <div class="card">
    <div class="card-head">Veritabanı (<?= htmlspecialchars($db['name'] ?? '?') ?>)</div>
    <?php foreach($db_checks as $c): ?>
    <div class="row">
      <div class="dot <?= $c['ok']?'ok':'err' ?>"></div>
      <div class="lbl"><?= $c['lbl'] ?></div>
      <div class="val"><?= htmlspecialchars($c['val']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- API -->
  <div class="card">
    <div class="card-head">API Bağlantısı</div>
    <?php foreach($api_checks as $c): ?>
    <div class="row">
      <div class="dot <?= $c['ok']?'ok':'err' ?>"></div>
      <div class="lbl"><?= $c['lbl'] ?></div>
      <div class="val"><?= htmlspecialchars($c['val']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if($all_ok): ?>
  <div class="card">
    <div class="card-head">✅ Sistem Sağlıklı</div>
    <div class="row">
      <div class="dot warn"></div>
      <div class="lbl">Güvenlik: Bu dosyayı silin</div>
      <div class="val"><code>public/test.php</code></div>
    </div>
    <div class="row">
      <div class="dot warn"></div>
      <div class="lbl">setup.php de silin</div>
      <div class="val"><code>public/setup.php</code></div>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
