<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>API Tanılama</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:monospace;background:#0f172a;color:#f1f5f9;padding:20px;font-size:13px;line-height:1.6}
h2{color:#3b82f6;margin:16px 0 8px;font-size:15px}
.ok{color:#4ade80} .err{color:#f87171} .warn{color:#fbbf24} .info{color:#60a5fa}
.box{background:#1e293b;border-radius:6px;padding:12px 16px;margin:8px 0;border-left:3px solid #334155}
.box.ok{border-color:#16a34a} .box.err{border-color:#dc2626} .box.warn{border-color:#d97706}
pre{white-space:pre-wrap;word-break:break-all;margin-top:6px;color:#94a3b8;font-size:12px}
a{color:#3b82f6;text-decoration:none} a:hover{text-decoration:underline}
.btn{display:inline-block;padding:8px 16px;background:#3b82f6;color:#fff;border-radius:5px;margin:4px 4px 4px 0;font-size:12px;font-weight:600}
.section{margin-top:20px;padding-top:16px;border-top:1px solid #334155}
</style>
</head>
<body>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2 style='color:#3b82f6;font-size:18px;margin-bottom:4px'>🔧 API Tanılama — kodlayap.tr</h2>";
echo "<div style='color:#64748b;margin-bottom:16px'>".date('d.m.Y H:i:s')."</div>";

$host = $_SERVER['HTTP_HOST'];
$base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://$host";

// ─── 1. Dosya Yapısı ───────────────────────────────────────
echo "<h2>1. Dosya Yapısı</h2>";
$files = [
    'public/api.php'         => __DIR__ . '/api.php',
    'public/.htaccess'       => __DIR__ . '/.htaccess',
    'public/setup.php'       => __DIR__ . '/setup.php',
    'public/panel/index.html'=> __DIR__ . '/panel/index.html',
    'config.php'             => __DIR__ . '/config.php',
    'database/schema.sql'    => __DIR__ . '/database/schema.sql',
    '.installed'             => __DIR__ . '/.installed',
];
foreach ($files as $label => $path) {
    $exists = file_exists($path);
    $size   = $exists ? ' ('.round(filesize($path)/1024,1).' KB)' : '';
    $cls    = $exists ? 'ok' : 'err';
    $icon   = $exists ? '✓' : '✗';
    echo "<div class='box $cls'><span class='$cls'>$icon $label</span>$size</div>";
}

// ─── 2. Config Okuma ───────────────────────────────────────
echo "<h2>2. config.php İçeriği</h2>";
$cfg_path = __DIR__ . '/config.php';
if (file_exists($cfg_path)) {
    $CFG = require $cfg_path;
    $db  = $CFG['db'] ?? [];
    echo "<div class='box ok'>✓ config.php okundu</div>";
    echo "<div class='box'>";
    echo "<span class='info'>DB Host:</span>    " . htmlspecialchars($db['host'] ?? '?') . "\n";
    echo "<span class='info'>DB Port:</span>    " . htmlspecialchars($db['port'] ?? '?') . "\n";
    echo "<span class='info'>DB Name:</span>    " . htmlspecialchars($db['name'] ?? '?') . "\n";
    echo "<span class='info'>DB User:</span>    " . htmlspecialchars($db['user'] ?? '?') . "\n";
    echo "<span class='info'>DB Pass:</span>    " . (empty($db['pass']) ? '<span class="err">BOŞ!</span>' : str_repeat('*', strlen($db['pass']))) . "\n";
    echo "<span class='info'>App URL:</span>    " . htmlspecialchars($CFG['app']['url'] ?? '?') . "\n";
    echo "<span class='info'>Secret:</span>     " . (strlen($CFG['app']['secret'] ?? '') > 10 ? '✓ Ayarlı' : '<span class="err">AYARLANMADI!</span>') . "\n";
    echo "</div>";
} else {
    echo "<div class='box err'>✗ config.php bulunamadı — kurulum tamamlanmadı</div>";
    $CFG = []; $db = [];
}

// ─── 3. Veritabanı Bağlantısı ──────────────────────────────
echo "<h2>3. Veritabanı Bağlantısı</h2>";
if (!empty($db['name']) && !empty($db['user'])) {
    try {
        $pdo = new PDO(
            "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
            $db['user'], $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
        );
        echo "<div class='box ok'>✓ Veritabanına bağlandı: {$db['name']}</div>";

        // Tabloları kontrol et
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $needed = ['users','students','siniflar','lessons','attendance','teacher_notes','notification_logs'];
        $missing = array_diff($needed, $tables);

        if (empty($missing)) {
            echo "<div class='box ok'>✓ ".count($tables)." tablo mevcut — tüm zorunlu tablolar var</div>";
        } else {
            echo "<div class='box err'>✗ Eksik tablolar: " . implode(', ', $missing) . "<pre>setup.php'yi çalıştırın veya database/schema.sql'i içe aktarın</pre></div>";
        }

        // Admin kullanıcı
        $admin = $pdo->query("SELECT id,email,rol FROM users WHERE rol='admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            echo "<div class='box ok'>✓ Admin kullanıcı: " . htmlspecialchars($admin['email']) . "</div>";
        } else {
            echo "<div class='box err'>✗ Admin kullanıcı bulunamadı — schema.sql'i içe aktarın</div>";
        }

    } catch (PDOException $e) {
        echo "<div class='box err'>✗ Veritabanı hatası: " . htmlspecialchars($e->getMessage()) . "
<pre>Kontrol: cPanel → MySQL Databases → kullanıcı adı ve şifre doğru mu?
cPanel prefix'e dikkat: örn. 'kodlayap_okul' şeklinde olmalı</pre></div>";
    }
} else {
    echo "<div class='box err'>✗ DB bilgileri config.php'de tanımlı değil — setup.php çalıştırın</div>";
}

// ─── 4. .htaccess Test ─────────────────────────────────────
echo "<h2>4. .htaccess & Yönlendirme</h2>";
$htaccess = file_exists(__DIR__ . '/.htaccess') ? file_get_contents(__DIR__ . '/.htaccess') : '';
if (str_contains($htaccess, 'RewriteRule')) {
    echo "<div class='box ok'>✓ .htaccess mevcut ve RewriteRule içeriyor</div>";
} else {
    echo "<div class='box err'>✗ .htaccess yok veya RewriteRule eksik</div>";
}

// mod_rewrite aktif mi?
if (function_exists('apache_get_modules')) {
    $mods = apache_get_modules();
    if (in_array('mod_rewrite', $mods)) {
        echo "<div class='box ok'>✓ mod_rewrite aktif</div>";
    } else {
        echo "<div class='box err'>✗ mod_rewrite aktif değil — cPanel → Apache Handlers'dan aktif edin</div>";
    }
} else {
    echo "<div class='box warn'>⚠ mod_rewrite durumu doğrulanamadı (PHP-FPM modunda normal)</div>";
}

// ─── 5. API Erişim Testi ───────────────────────────────────
echo "<h2>5. API Erişim Testi</h2>";

// Direkt dosya testi
if (file_exists(__DIR__ . '/api.php')) {
    echo "<div class='box ok'>✓ api.php dosyası mevcut (" . round(filesize(__DIR__.'/api.php')/1024,1) . " KB)</div>";
}

// HTTP üzerinden test
$ping_url = $base . '/api/ping';
$ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true, 'method' => 'GET', 'header' => "Accept: application/json\r\n"]]);
$resp = @file_get_contents($ping_url, false, $ctx);
$http_code = 0;
if (isset($http_response_header)) {
    preg_match('/HTTP\/\d\.\d (\d+)/', $http_response_header[0] ?? '', $m);
    $http_code = (int)($m[1] ?? 0);
}

if ($resp && $http_code === 200) {
    $data = json_decode($resp, true);
    if (($data['status'] ?? '') === 'ok') {
        echo "<div class='box ok'>✓ GET $ping_url → HTTP $http_code<pre>" . htmlspecialchars($resp) . "</pre></div>";
    } else {
        echo "<div class='box warn'>⚠ GET $ping_url → HTTP $http_code (beklenmedik yanıt)<pre>" . htmlspecialchars(substr($resp,0,300)) . "</pre></div>";
    }
} else {
    echo "<div class='box err'>✗ GET $ping_url → HTTP $http_code (erişilemiyor)
<pre>" . htmlspecialchars(substr($resp ?? 'Yanıt yok',0,500)) . "

ÇÖZÜM:
1. Document Root = " . $base . "/public olmalı
   cPanel → Domains → kodlayap.tr → Document Root değiştir
   
2. Ya da root .htaccess'in public_html kökünde olduğunu kontrol edin

3. .htaccess içindeki RewriteRule: ^api/(.*)$ api.php [QSA,L]</pre></div>";
}

// ─── 6. Login Testi ────────────────────────────────────────
echo "<h2>6. Login API Testi</h2>";
$login_url = $base . '/api/auth/giris';
$post_data = json_encode(['email' => 'admin@kodlayap.tr', 'sifre' => 'admin123', 'platform' => 'web']);
$ctx2 = stream_context_create(['http' => [
    'method'  => 'POST',
    'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
    'content' => $post_data,
    'timeout' => 5,
    'ignore_errors' => true,
]]);
$login_resp = @file_get_contents($login_url, false, $ctx2);
preg_match('/HTTP\/\d\.\d (\d+)/', $http_response_header[0] ?? '', $lm);
$login_code = (int)($lm[1] ?? 0);

if ($login_resp) {
    $login_data = json_decode($login_resp, true);
    $cls = $login_code === 401 ? 'warn' : ($login_code === 200 ? 'ok' : 'err');
    $msg = $login_code === 401
        ? "✓ API çalışıyor — şifre hatalı (beklenen)"
        : ($login_code === 200 ? "✓ Giriş başarılı!" : "⚠ HTTP $login_code");
    echo "<div class='box $cls'>$msg<pre>" . htmlspecialchars(json_encode($login_data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) . "</pre></div>";
} else {
    echo "<div class='box err'>✗ POST $login_url → Yanıt yok (HTTP $login_code)
<pre>API erişilemiyor. Yukarıdaki 5. maddeyi kontrol edin.</pre></div>";
}

// ─── 7. Özet & Çözüm ──────────────────────────────────────
echo "<div class='section'>";
echo "<h2 style='color:#f1f5f9'>7. Hızlı Linkler & Düzeltme</h2>";
echo "<a href='/api/ping' class='btn' target='_blank'>API Ping</a>";
echo "<a href='/setup.php' class='btn' style='background:#16a34a'>Setup.php</a>";
echo "<a href='/panel' class='btn' style='background:#7c3aed'>Panel</a>";
echo "<a href='?refresh=1' class='btn' style='background:#475569'>Yenile</a>";
echo "</div>";

echo "<div class='section' style='color:#475569;font-size:11px;margin-top:16px'>";
echo "⚠️ Bu tanılama dosyasını sorun çözüldükten sonra silin: <span style='color:#4ade80'>public/api-debug.php</span>";
echo "</div>";
?>
</body>
</html>
