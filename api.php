<?php
// ============================================================
//  api.php v3 — Okul Yönetim Sistemi REST API
// ============================================================
date_default_timezone_set('Europe/Istanbul');
error_reporting(E_ALL);
ini_set('display_errors', 0); // ekrana basma, yakala
set_error_handler(function($no,$str,$file,$line){
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok'=>false,'message'=>"PHP Hata: $str (satir $line)"]);
    exit;
});
set_exception_handler(function($e){
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok'=>false,'message'=>$e->getMessage(),'trace'=>substr($e->getTraceAsString(),0,500)]);
    exit;
});
$CFG = require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Yardımcılar ────────────────────────────────────────────
function ok($d=null,string $m='OK',int $c=200):never{http_response_code($c);echo json_encode(['success'=>true,'message'=>$m,'data'=>$d],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function err(string $m,int $c=400,$e=null):never{http_response_code($c);echo json_encode(['success'=>false,'message'=>$m,'errors'=>$e],JSON_UNESCAPED_UNICODE);exit;}
function body():array{$r=file_get_contents('php://input');return json_decode($r,true)??$_POST;}
function now_str():string{return date('Y-m-d H:i:s');}
function today_str():string{return date('Y-m-d');}
function get(string $k,$d=null){return $_GET[$k]??$d;}

// ── DB ─────────────────────────────────────────────────────
function db():PDO{
    static $p=null; if($p)return $p;
    global $CFG; $d=$CFG['db'];
    if(empty($d['name'])||empty($d['user'])) err('DB yapilandirilmamis.',503);
    try{$p=new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['name']};charset=utf8mb4",$d['user'],$d['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
    catch(\PDOException $e){err('DB hatasi: '.$e->getMessage(),500);}
    return $p;
}
function q(string $sql,array $p=[]):array{$s=db()->prepare($sql);$s->execute($p);return $s->fetchAll();}
function qOne(string $sql,array $p=[]):?array{$r=q($sql,$p);return $r[0]??null;}
function qRun(string $sql,array $p=[]):int{$s=db()->prepare($sql);$s->execute($p);return(int)db()->lastInsertId()?:$s->rowCount();}

// ── Token ──────────────────────────────────────────────────
function b64u(string $s):string{return rtrim(strtr(base64_encode($s),'+/','-_'),'=');}
function b64d(string $s):string{return base64_decode(strtr($s,'-_','+/'));}
function tokenCreate(array $payload):string{
    global $CFG;$h=b64u(json_encode(['alg'=>'HS256']));$p=b64u(json_encode($payload));
    $s=b64u(hash_hmac('sha256',"$h.$p",$CFG['app']['secret'],true));return "$h.$p.$s";
}
function tokenVerify(string $t):?array{
    global $CFG;if(!$t)return null;$pts=explode('.',$t);if(count($pts)!==3)return null;
    [$h,$p,$s]=$pts;$exp=b64u(hash_hmac('sha256',"$h.$p",$CFG['app']['secret'],true));
    if(!hash_equals($exp,$s))return null;
    $pl=json_decode(b64d($p),true);return($pl&&($pl['exp']??0)>time())?$pl:null;
}
function getToken():string{
    foreach(['HTTP_AUTHORIZATION','REDIRECT_HTTP_AUTHORIZATION'] as $k) if(!empty($_SERVER[$k])) return trim(str_ireplace('Bearer','',$_SERVER[$k]));
    if(function_exists('apache_request_headers')){foreach(apache_request_headers() as $k=>$v) if(strtolower($k)==='authorization') return trim(str_ireplace('Bearer','',$v));}
    return $_COOKIE['okul_token']??$_POST['_token']??$_GET['_token']??'';
}
function auth(array $roles=[]):array{
    $t=getToken();if(!$t)err('Oturum gerekli',401);
    $p=tokenVerify($t);if(!$p)err('Oturum suresi doldu',401);
    if($roles&&!in_array($p['rol'],$roles))err('Yetki yok ('.$p['rol'].')',403);
    return $p;
}

// ── Kurum Ayarları ─────────────────────────────────────────
function getSetting(int $inst,string $key,string $default=''):string{
    $r=qOne('SELECT setting_value FROM institution_settings WHERE institution_id=? AND setting_key=?',[$inst,$key]);
    return $r['setting_value']??$default;
}
function setSetting(int $inst,string $key,string $val):void{
    qRun('INSERT INTO institution_settings (institution_id,setting_key,setting_value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$inst,$key,$val]);
}
function getOkulInfo(int $inst):array{
    return ['adi'=>getSetting($inst,'okul_adi','Egitim Kurumunuz'),'telefon'=>getSetting($inst,'telefon','04622234466'),'whatsapp'=>getSetting($inst,'whatsapp','04622234466'),'email'=>getSetting($inst,'email',''),'adres'=>getSetting($inst,'adres',''),'devamsizlik_limit_normal'=>(int)getSetting($inst,'devamsizlik_limit_normal','4'),'devamsizlik_limit_mazeret'=>(int)getSetting($inst,'devamsizlik_limit_mazeret','4'),'devamsizlik_uyari_gun'=>(int)getSetting($inst,'devamsizlik_uyari_gun','3')];
}

// ── Şablon doldur ──────────────────────────────────────────
function fillTemplate(string $icerik,array $vars):string{
    foreach($vars as $k=>$v) $icerik=str_replace("{{{$k}}}",$v,$icerik);
    return $icerik;
}

// ── Router ─────────────────────────────────────────────────
$method=$_SERVER['REQUEST_METHOD'];
$uri=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(!empty($_SERVER['PATH_INFO'])) $uri=$_SERVER['PATH_INFO'];
else{ $uri=preg_replace('#^/?(?:api\.php|index\.php)/?#','',$uri);$uri=preg_replace('#^/?api/?#','',$uri);}
if(empty(trim($uri,'/'))&&!empty($_GET['_r'])) $uri=$_GET['_r'];
$parts=array_values(array_filter(explode('/',trim($uri,'/'))));
$route=$parts[0]??'';
$id=isset($parts[1])&&is_numeric($parts[1])?(int)$parts[1]:null;
// $sub: parts[1]'in non-numeric olması durumunda, yoksa parts[2]
$sub=!is_numeric($parts[1]??null)?($parts[1]??''):($parts[2]??'');
$sub2=$parts[2]??($parts[3]??'');

match($route){
    'ping'            =>ok(['status'=>'ok','v'=>'3.0','t'=>now_str()]),
    'auth'            =>rAuth($method,$sub),
    'dashboard'       =>rDashboard($method,$sub),
    'ayarlar'         =>rAyarlar($method,$sub),
    'siniflar'        =>rSiniflar($method,$id),
    'dersler'         =>rDersler($method,$id),
    'ogrenciler'      =>rOgrenciler($method,$id,$sub),
    'devamsizlik'     =>rDevamsizlik($method,$id,$sub),
    'notlar'          =>rNotlar($method,$id),
    'ogretmen-notlari'=>rOgretmenNot($method,$id,$sub),
    'bildirim'        =>rBildirim($method,$sub,$id),
    'sablonlar'       =>rSablonlar($method,$id),
    'raporlar'        =>rRaporlar($method,$sub,$id),
    'kullanicilar'    =>rKullanicilar($method,$id,$sub),
    default           =>err("Endpoint yok: /$route",404),
};

// ============================================================
//  AUTH
// ============================================================
// ── Brute-force koruma (login_attempts tablosu yoksa oluştur) ──────────
function ensureLoginAttemptsTable():void{
    try{db()->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        email VARCHAR(255) NOT NULL,
        tarih DATETIME NOT NULL,
        basarili TINYINT(1) DEFAULT 0,
        INDEX idx_ip_tarih (ip, tarih),
        INDEX idx_email_tarih (email, tarih)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}
    catch(\Throwable $e){}
}
function getClientIp():string{
    foreach(['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k){
        if(!empty($_SERVER[$k])){$ip=trim(explode(',',$_SERVER[$k])[0]);if(filter_var($ip,FILTER_VALIDATE_IP))return $ip;}
    }
    return $_SERVER['REMOTE_ADDR']??'0.0.0.0';
}
function checkBruteForce(string $ip, string $email):void{
    ensureLoginAttemptsTable();
    $window=date('Y-m-d H:i:s',time()-900); // son 15 dakika
    // IP bazlı limit: 10 başarısız deneme
    $ipCount=(int)(qOne('SELECT COUNT(*) c FROM login_attempts WHERE ip=? AND tarih>? AND basarili=0',[$ip,$window])['c']??0);
    if($ipCount>=10){
        $wait=ceil((strtotime(qOne('SELECT MAX(tarih) t FROM login_attempts WHERE ip=? AND basarili=0',[$ip])['t']??'now')+900-time())/60);
        err("Çok fazla başarısız giriş denemesi. Lütfen {$wait} dakika bekleyiniz.",429);
    }
    // Email bazlı limit: 5 başarısız deneme
    $emailCount=(int)(qOne('SELECT COUNT(*) c FROM login_attempts WHERE email=? AND tarih>? AND basarili=0',[$email,$window])['c']??0);
    if($emailCount>=5){
        err('Bu hesap için çok fazla başarısız giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.',429);
    }
}
function logLoginAttempt(string $ip,string $email,bool $basarili):void{
    try{qRun('INSERT INTO login_attempts (ip,email,tarih,basarili) VALUES (?,?,?,?)',[$ip,$email,now_str(),$basarili?1:0]);}
    catch(\Throwable $e){}
    // 24 saatten eski kayıtları temizle (rastgele %5 ihtimalle)
    if(rand(1,20)===1){try{qRun('DELETE FROM login_attempts WHERE tarih<?',[date('Y-m-d H:i:s',time()-86400)]);}catch(\Throwable $e){}}
}

function rAuth(string $m,string $s):void{
    global $CFG;
    if($m==='POST'&&$s==='giris'){
        $b=body();$e=trim($b['email']??'');$p=$b['sifre']??'';
        if(!$e||!$p)err('E-posta ve sifre gerekli');
        $ip=getClientIp();
        checkBruteForce($ip,$e);
        $u=qOne('SELECT * FROM users WHERE email=? AND aktif=1',[$e]);
        if(!$u||!password_verify($p,$u['sifre'])){
            logLoginAttempt($ip,$e,false);
            err('Hatalı e-posta veya şifre',401);
        }
        logLoginAttempt($ip,$e,true);
        // Oturum süresi: admin için 60 dakika, diğerleri için config'deki süre
        $is_admin=($u['rol']==='admin');
        $exp=time()+($is_admin?3600:($CFG['token']['web']??86400));
        $tok=tokenCreate(['sub'=>$u['id'],'email'=>$u['email'],'rol'=>$u['rol'],'inst'=>$u['institution_id']??1,'exp'=>$exp]);
        qRun('UPDATE users SET son_giris=? WHERE id=?',[now_str(),$u['id']]);
        setcookie('okul_token',$tok,['expires'=>$exp,'path'=>'/','samesite'=>'Lax']);
        ok(['token'=>$tok,'expires'=>$exp,'user'=>['id'=>$u['id'],'ad'=>$u['ad'],'soyad'=>$u['soyad'],'email'=>$u['email'],'rol'=>$u['rol']]],'Giris basarili');
    }
    if(in_array($m,['POST','GET'])&&$s==='cikis'){setcookie('okul_token','',time()-3600,'/');ok(null,'Çikis yapildi');}
    if($m==='GET'&&$s==='profil'){$p=auth();ok(qOne('SELECT id,ad,soyad,email,telefon,rol FROM users WHERE id=?',[$p['sub']]));}
    if($m==='PUT'&&$s==='sifre-degistir'){
        $p=auth();$b=body();
        if(strlen($b['yeni']??'')<8)err('Şifre en az 8 karakter');
        $u=qOne('SELECT sifre FROM users WHERE id=?',[$p['sub']]);
        if(!password_verify($b['mevcut']??'',$u['sifre']))err('Mevcut sifre hatali',401);
        qRun('UPDATE users SET sifre=? WHERE id=?',[password_hash($b['yeni'],PASSWORD_BCRYPT,['cost'=>10]),$p['sub']]);
        ok(null,'Şifre guncellendi');
    }
    err("Auth endpoint yok: $s",404);
}

// ============================================================
//  DASHBOARD
// ============================================================
function rDashboard(string $m,string $s):void{
    $p=auth();$inst=$p['inst']??1;
    $okul=getOkulInfo($inst);

    if($m==='GET'&&!$s){
        // Ögrenci istatistikleri — 3 kart
        $egitim=qOne('SELECT COUNT(*) c FROM students WHERE institution_id=? AND ogrenci_turu="egitim" AND aktif=1',[$inst]);
        $proje=qOne('SELECT COUNT(*) c FROM students WHERE institution_id=? AND ogrenci_turu="proje" AND aktif=1',[$inst]);
        $toplam=qOne('SELECT COUNT(*) c FROM students WHERE institution_id=? AND aktif=1',[$inst]);
        // Mezun sayisi
        try{$mezun=qOne('SELECT COUNT(*) c FROM students WHERE institution_id=? AND aktif=0 AND mezun=1',[$inst]);}catch(\PDOException $e){$mezun=['c'=>0];}

        // Bugun devamsiz
        $bugun_dev=qOne('SELECT COUNT(DISTINCT student_id) c FROM attendance a JOIN students s ON s.id=a.student_id WHERE s.institution_id=? AND a.tarih=? AND a.durum="gelmedi"',[$inst,today_str()]);

        // Bildirim istatistikleri
        $bil_ay=qOne("SELECT COUNT(*) c FROM notification_logs WHERE institution_id=? AND (durum='sent' OR durum='manuel_gonderildi') AND MONTH(olusturma)=MONTH(NOW())",[$inst]);
        $bil_bas=qOne("SELECT COUNT(*) c FROM notification_logs WHERE institution_id=? AND durum='failed'",[$inst]);

        // Kritik devamsizlik (>=3 gelmedi VEYA >=3 mazeretli)
        $limit_u=(int)$okul['devamsizlik_uyari_gun'];
        $kritik=q('SELECT s.id,s.ad,s.soyad,s.ogrenci_no,
                   SUM(a.durum="gelmedi") AS gelmedi,
                   SUM(a.durum="mazeretli") AS mazeretli
                   FROM attendance a
                   JOIN students s ON s.id=a.student_id
                   WHERE s.institution_id=? AND s.aktif=1
                   GROUP BY a.student_id
                   HAVING gelmedi>=? OR mazeretli>=?
                   ORDER BY gelmedi DESC, mazeretli DESC',
            [$inst,$limit_u,$limit_u]);

        // Okunmamis ogretmen notlari
        $okunmamis=q('SELECT n.*,s.ad ogrenci_adi,s.soyad ogrenci_soyad,u.ad ogretmen_adi,u.soyad ogretmen_soyad
                      FROM teacher_notes n
                      JOIN students s ON s.id=n.student_id
                      JOIN users u ON u.id=n.ogretmen_id
                      WHERE s.institution_id=? AND n.is_read=0
                      ORDER BY n.olusturma DESC LIMIT 10',[$inst]);

        // Risk listesi (genel oran)
        $risk=q('SELECT s.id,s.ad,s.soyad,COUNT(*) toplam,SUM(a.durum="gelmedi") devamsiz,
                 ROUND(SUM(a.durum="gelmedi")/NULLIF(COUNT(*),0)*100,1) oran
                 FROM attendance a JOIN students s ON s.id=a.student_id
                 WHERE s.institution_id=? GROUP BY a.student_id HAVING oran>=30 ORDER BY oran DESC LIMIT 5',[$inst]);

        ok([
            'istatistik'=>['egitim_ogrenci'=>(int)$egitim['c'],'proje_ogrenci'=>(int)$proje['c'],'toplam_ogrenci'=>(int)$toplam['c'],'mezun_ogrenci'=>(int)($mezun['c']??0),'devamsiz_bugun'=>(int)$bugun_dev['c'],'bildirim_bu_ay'=>(int)$bil_ay['c'],'basarisiz_bildirim'=>(int)$bil_bas['c']],
            'kritik_devamsizlik'=>$kritik,
            'okunmamis_notlar'=>$okunmamis,
            'risk_listesi'=>$risk,
            'okul'=>$okul,
        ]);
    }

    // Takvim verisi — belirli ay için devamsız sayıları
    if($m==='GET'&&$s==='takvim'){
        $y=(int)get('yil',date('Y'));
        $mo=(int)get('ay',date('n'));
        $bas=sprintf('%04d-%02d-01',$y,$mo);
        $bit=sprintf('%04d-%02d-%02d',$y,$mo,cal_days_in_month(CAL_GREGORIAN,$mo,$y));
        $rows=q('SELECT a.tarih,s.ad,s.soyad FROM attendance a JOIN students s ON s.id=a.student_id WHERE s.institution_id=? AND a.tarih BETWEEN ? AND ? AND a.durum IN ("gelmedi","mazeretli") ORDER BY a.tarih,s.soyad',[$inst,$bas,$bit]);
        $cal=[];
        foreach($rows as $r){
            $d=$r['tarih'];
            if(!isset($cal[$d])) $cal[$d]=['sayi'=>0,'ogrenciler'=>[]];
            $cal[$d]['sayi']++;
            $cal[$d]['ogrenciler'][]=$r['ad'].' '.$r['soyad'];
        }
        ok($cal);
    }

    err('Endpoint yok',404);
}

// ============================================================
//  AYARLAR (Kurum Bilgileri + Sinif CRUD)
// ============================================================
function rAyarlar(string $m,string $s):void{
    $p=auth(['admin']);$inst=$p['inst']??1;

    // GET /ayarlar → kurum bilgileri
    if($m==='GET'&&!$s){
        ok(getOkulInfo($inst));
    }

    // PUT /ayarlar → kurum bilgilerini güncelle
    if($m==='PUT'&&!$s){
        $b=body();
        $keys=['okul_adi','telefon','whatsapp','email','adres','devamsizlik_limit_normal','devamsizlik_limit_mazeret','devamsizlik_uyari_gun'];
        foreach($keys as $k) if(isset($b[$k])) setSetting($inst,$k,(string)$b[$k]);
        // config.php'deki okul bilgilerini de guncelle
        $cfg_file=__DIR__.'/config.php';
        if(file_exists($cfg_file)&&isset($b['okul_adi'])){
            $cfg=file_get_contents($cfg_file);
            if(isset($b['telefon'])) $cfg=preg_replace("/'telefon'\s*=>\s*'[^']*'/"  ,"'telefon' => '".addslashes($b['telefon'])."'",$cfg);
            if(isset($b['whatsapp'])) $cfg=preg_replace("/'whatsapp'\s*=>\s*'[^']*'/","'whatsapp' => '".addslashes($b['whatsapp'])."'",$cfg);
            file_put_contents($cfg_file,$cfg);
        }
        ok(null,'Ayarlar kaydedildi');
    }

    err('Endpoint yok',404);
}

// ============================================================
//  SINIFlar (tam CRUD)
// ============================================================
function rSiniflar(string $m,?int $id):void{
    $p=auth();$inst=$p['inst']??1;
    if($m==='GET'&&!$id) ok(q('SELECT s.*,COUNT(o.id) ogrenci_sayisi FROM siniflar s LEFT JOIN students o ON o.sinif_id=s.id AND o.aktif=1 WHERE s.institution_id=? AND s.aktif=1 GROUP BY s.id ORDER BY s.ad',[$inst]));
    if($m==='GET'&&$id){ok(qOne('SELECT * FROM siniflar WHERE id=? AND institution_id=?',[$id,$inst]));}
    if($m==='POST'){
        auth(['admin']);$b=body();
        if(!$b['ad'])err('Sinif adi gerekli');
        ok(['id'=>qRun('INSERT INTO siniflar (institution_id,ad,donem,kapasite) VALUES (?,?,?,?)',[$inst,strtoupper(trim($b['ad'])),$b['donem']??date('Y').'-'.(date('Y')+1),$b['kapasite']??40])],'Sinif eklendi',201);
    }
    if($m==='PUT'&&$id){
        auth(['admin']);$b=body();
        qRun('UPDATE siniflar SET ad=?,donem=?,kapasite=? WHERE id=? AND institution_id=?',[strtoupper(trim($b['ad']??'')),$b['donem']??date('Y').'-'.(date('Y')+1),$b['kapasite']??40,$id,$inst]);
        ok(null,'Guncellendi');
    }
    if($m==='DELETE'&&$id){
        auth(['admin']);
        $check=qOne('SELECT COUNT(*) c FROM students WHERE sinif_id=? AND aktif=1',[$id]);
        if(($check['c']??0)>0)err('Bu sinifta aktif ogrenci var, silinemez',409);
        qRun('UPDATE siniflar SET aktif=0 WHERE id=? AND institution_id=?',[$id,$inst]);
        ok(null,'Sinif silindi');
    }
    err('Endpoint yok',404);
}

// ============================================================
//  DERSLER
// ============================================================
function rDersler(string $m,?int $id):void{
    $p=auth();$inst=$p['inst']??1;
    if($m==='GET'){
        $sql='SELECT d.*,s.ad sinif_adi,u.ad ogretmen_adi,u.soyad ogretmen_soyad FROM lessons d JOIN siniflar s ON s.id=d.sinif_id JOIN users u ON u.id=d.ogretmen_id WHERE d.institution_id=? AND d.aktif=1';
        $params=[$inst];
        if($p['rol']==='ogretmen'){$sql.=' AND d.ogretmen_id=?';$params[]=$p['sub'];}
        ok(q($sql,$params));
    }
    if($m==='POST'){
        auth(['admin']);$b=body();
        ok(['id'=>qRun('INSERT INTO lessons (institution_id,sinif_id,ogretmen_id,ad,kod,haftalik_saat,renk) VALUES (?,?,?,?,?,?,?)',[$inst,$b['sinif_id'],$b['ogretmen_id'],$b['ad'],$b['kod']??null,$b['haftalik_saat']??4,$b['renk']??'#4f46e5'])],'Ders eklendi',201);
    }
    err('Endpoint yok',404);
}

// ── TC Kimlik No Doğrulama ─────────────────────────────────────────────
function validateTC(string $tc):bool{
    if(!preg_match('/^[1-9][0-9]{10}$/',$tc))return false;
    $d=array_map('intval',str_split($tc));
    $t1=($d[0]+$d[2]+$d[4]+$d[6]+$d[8])*7-($d[1]+$d[3]+$d[5]+$d[7]);
    if(($t1%10)!==$d[9])return false;
    $t2=array_sum(array_slice($d,0,10))%10;
    return $t2===$d[10];
}
// ── Telefon Format Kontrolü (05xx ile başlamalı) ───────────────────────
function validatePhone(string $tel):bool{
    $t=preg_replace('/[\s\-\(\)]/','',trim($tel));
    return (bool)preg_match('/^05[0-9]{9}$/',$t);
}

// ============================================================
//  ÖĞRENCİLER
// ============================================================
function rOgrenciler(string $m,?int $id,string $sub):void{
    $p=auth();$inst=$p['inst']??1;

    if($m==='GET'&&!$id){
        $grupFilter=get('grup','');
        $params=[$inst];
        if($grupFilter==='mezun'){
            // mezun sutunu yoksa bos liste don — hata verme
            try{
                $sql='SELECT s.*,si.ad sinif_adi,
                      0 devamsizlik_orani, 0 gelmedi_sayi, 0 mazeret_sayi,
                      u2.ad danisman_adi, u2.soyad danisman_soyad
                      FROM students s
                      LEFT JOIN siniflar si ON si.id=s.sinif_id
                      LEFT JOIN users u2 ON u2.id=s.danisman_id
                      WHERE s.institution_id=? AND s.aktif=0 AND s.mezun=1
                      ORDER BY s.mezuniyet_yili DESC, s.soyad, s.ad';
                ok(q($sql,$params));
            }catch(\PDOException $e){ok([]);}
        }
        else if($grupFilter==='pasif'){
            // Pasif (ayrılan) öğrenciler
            try{
                $sql='SELECT s.*,si.ad sinif_adi,
                      0 devamsizlik_orani, 0 gelmedi_sayi, 0 mazeret_sayi,
                      u2.ad danisman_adi, u2.soyad danisman_soyad
                      FROM students s
                      LEFT JOIN siniflar si ON si.id=s.sinif_id
                      LEFT JOIN users u2 ON u2.id=s.danisman_id
                      WHERE s.institution_id=? AND s.aktif=0 AND (s.mezun=0 OR s.mezun IS NULL)
                      ORDER BY s.ayrilma_tarihi DESC, s.soyad, s.ad';
                ok(q($sql,$params));
            }catch(\PDOException $e){ok([]);}
        }
        $sql='SELECT s.*,si.ad sinif_adi,
              COALESCE(ROUND(SUM(CASE WHEN a.durum="gelmedi" THEN 1 ELSE 0 END)/NULLIF(COUNT(a.id),0)*100,1),0) devamsizlik_orani,
              COALESCE(SUM(CASE WHEN a.durum="gelmedi" THEN 1 ELSE 0 END),0) gelmedi_sayi,
              COALESCE(SUM(CASE WHEN a.durum="mazeretli" THEN 1 ELSE 0 END),0) mazeret_sayi,
              u2.ad danisman_adi, u2.soyad danisman_soyad
              FROM students s
              LEFT JOIN siniflar si ON si.id=s.sinif_id
              LEFT JOIN attendance a ON a.student_id=s.id
              LEFT JOIN users u2 ON u2.id=s.danisman_id
              WHERE s.institution_id=? AND s.aktif=1';
        if(!empty($_GET['tur'])){$sql.=' AND s.ogrenci_turu=?';$params[]=$_GET['tur'];}
        if(!empty($_GET['sinif_id'])){$sql.=' AND s.sinif_id=?';$params[]=(int)$_GET['sinif_id'];}
        if(!empty($_GET['q'])){$sql.=' AND (s.ad LIKE ? OR s.soyad LIKE ? OR s.tc_no LIKE ? OR s.ogrenci_no LIKE ?)';$qq='%'.$_GET['q'].'%';array_push($params,$qq,$qq,$qq,$qq);}
        $sql.=' GROUP BY s.id';
        $allowed_sorts=['soyad','okul_adi','odeme_durumu','devamsizlik_orani','gelmedi_sayi'];
        $sort=in_array($_GET['sort']??'',$allowed_sorts)?$_GET['sort']:'soyad';
        $dir=strtoupper($_GET['dir']??'ASC')==='DESC'?'DESC':'ASC';
        $sql.=" ORDER BY $sort $dir, s.ad";
        ok(q($sql,$params));
    }

    if($m==='GET'&&$id){
        $s=qOne('SELECT s.*,si.ad sinif_adi FROM students s LEFT JOIN siniflar si ON si.id=s.sinif_id WHERE s.id=? AND s.institution_id=?',[$id,$inst]);
        if(!$s)err('Ögrenci bulunamadi',404);
        ok($s);
    }

    // Öğrenci fotoğraf yükleme
    if($m==='POST'&&$sub==='fotograf'){
        auth(['admin']);
        $sid=$id;  // ✅ $id zaten function parameter'ı olarak geçiliyor
        if(!$sid)err('Öğrenci ID gerekli');
        if(empty($_FILES['fotograf']))err('Dosya yüklenmedi');
        $f=$_FILES['fotograf'];
        $allowed=['image/jpeg','image/png','image/webp','image/gif'];
        if(!in_array($f['type']??'',$allowed))err('Sadece JPEG, PNG, WebP formatları kabul edilir');
        if(($f['size']??0)>3*1024*1024)err('Dosya boyutu 3MB\'yi aşamaz');
        $dir=__DIR__.'/uploads/ogrenci/';
        if(!is_dir($dir))mkdir($dir,0755,true);
        $ext=pathinfo($f['name'],PATHINFO_EXTENSION);
        $fname='ogrenci_'.(int)$sid.'_'.time().'.'.$ext;
        if(!move_uploaded_file($f['tmp_name'],$dir.$fname))err('Dosya yüklenemedi',500);
        // Eski fotoğrafı sil
        $old=qOne('SELECT fotograf FROM students WHERE id=?',[(int)$sid]);
        if(!empty($old['fotograf'])){$oldPath=__DIR__.'/uploads/ogrenci/'.basename($old['fotograf']);if(file_exists($oldPath))@unlink($oldPath);}
        qRun('UPDATE students SET fotograf=? WHERE id=?',['uploads/ogrenci/'.$fname,(int)$sid]);
        ok(['url'=>'uploads/ogrenci/'.$fname],'Fotoğraf yüklendi');
    }

    // Öğrenci pasif yap (okulu bırak)
    if($m==='PUT'&&$sub==='pasif'&&$id){
        auth(['admin']);$b=body();
        if(!$b['ayrilma_nedeni'])err('Ayrılma nedeni gerekli');
        qRun('UPDATE students SET aktif=0,ayrilma_nedeni=?,ayrilma_tarihi=? WHERE id=? AND institution_id=?',
            [$b['ayrilma_nedeni'],now_str(),$id,$inst]);
        ok(null,'Öğrenci pasif hale getirildi');
    }
    
    // Öğrenci pasif'ten aktif yap (geri al)
    if($m==='PUT'&&$sub==='aktifle'&&$id){
        auth(['admin']);
        qRun('UPDATE students SET aktif=1,ayrilma_nedeni=NULL,ayrilma_tarihi=NULL WHERE id=? AND institution_id=?',
            [$id,$inst]);
        ok(null,'Öğrenci aktif hale getirildi');
    }

    if($m==='POST'){
        auth(['admin']);$b=body();
        if(!$b['ad']||!$b['soyad'])err('Ad ve soyad gerekli');
        // TC doğrulama
        if(!empty($b['tc_no'])){
            if(!preg_match('/^[0-9]{11}$/',$b['tc_no']))err('TC Kimlik No 11 rakamdan oluşmalıdır');
            if(!validateTC($b['tc_no']))err('Geçersiz TC Kimlik Numarası');
        }
        // Telefon doğrulama
        if(!empty($b['baba_tel'])&&!validatePhone($b['baba_tel']))err('Baba telefonu 05 ile başlayan 11 haneli formatta olmalıdır (05xx xxx xx xx)');
        if(!empty($b['anne_tel'])&&!validatePhone($b['anne_tel']))err('Anne telefonu 05 ile başlayan 11 haneli formatta olmalıdır (05xx xxx xx xx)');
        if(!empty($b['acil_tel'])&&!validatePhone($b['acil_tel']))err('Acil telefonu 05 ile başlayan 11 haneli formatta olmalıdır');
        $no=$b['kurum_no']??('OGR-'.date('Y').'-'.str_pad((qOne('SELECT IFNULL(MAX(id),0)+1 n FROM students')['n']??1),4,'0',STR_PAD_LEFT));
        try{
            $nid=qRun('INSERT INTO students (institution_id,sinif_id,ogrenci_turu,danisman_id,ogrenci_no,tc_no,kurum_no,ad,soyad,dogum_tarihi,cinsiyet,okul_adi,anne_adi,anne_tel,baba_adi,baba_tel,bildirim_tercih,acil_tel,saglik_notu,adres,yarisma_turu,yarisma_alani,odeme_durumu,kayit_tutari,odeme_notu) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$inst,$b['sinif_id']??null,$b['ogrenci_turu']??'egitim',$b['danisman_id']??null,$no,$b['tc_no']??null,$no,$b['ad'],$b['soyad'],$b['dogum_tarihi']??null,$b['cinsiyet']??null,$b['okul_adi']??null,$b['anne_adi']??null,$b['anne_tel']??null,$b['baba_adi']??null,$b['baba_tel']??null,$b['bildirim_tercih']??'baba',$b['acil_tel']??null,$b['saglik_notu']??null,$b['adres']??null,$b['yarisma_turu']??null,$b['yarisma_alani']??null,$b['odeme_durumu']??'bekliyor',$b['kayit_tutari']??0,$b['odeme_notu']??null]);
            ok(['id'=>$nid,'ogrenci_no'=>$no],'Ögrenci eklendi',201);
        }catch(\PDOException $e){
            if(str_contains($e->getMessage(),'Unknown column')){
                $nid=qRun('INSERT INTO students (institution_id,sinif_id,ogrenci_no,ad,soyad,dogum_tarihi,cinsiyet,acil_tel,saglik_notu) VALUES (?,?,?,?,?,?,?,?,?)',[$inst,$b['sinif_id']??null,$no,$b['ad'],$b['soyad'],$b['dogum_tarihi']??null,$b['cinsiyet']??null,$b['acil_tel']??null,$b['saglik_notu']??null]);
                ok(['id'=>$nid,'ogrenci_no'=>$no,'uyari'=>'schema_v3.sql calistirilmamis — bazı alanlar kaydedilmedi'],'Öğrenci eklendi (kısmi)',201);
            }
            err('DB hatası: '.$e->getMessage(),500);
        }
    }

    if($m==='PUT'&&$id){
        auth(['admin']);$b=body();
        // TC doğrulama
        if(isset($b['tc_no'])&&$b['tc_no']!==''&&$b['tc_no']!==null){
            if(!preg_match('/^[0-9]{11}$/',$b['tc_no']))err('TC Kimlik No 11 rakamdan oluşmalıdır');
            if(!validateTC($b['tc_no']))err('Geçersiz TC Kimlik Numarası');
        }
        // Telefon doğrulama
        if(!empty($b['baba_tel'])&&!validatePhone($b['baba_tel']))err('Baba telefonu 05 ile başlayan 11 haneli formatta olmalıdır');
        if(!empty($b['anne_tel'])&&!validatePhone($b['anne_tel']))err('Anne telefonu 05 ile başlayan 11 haneli formatta olmalıdır');
        if(!empty($b['acil_tel'])&&!validatePhone($b['acil_tel']))err('Acil telefonu 05 ile başlayan 11 haneli formatta olmalıdır');
        $FIELDS=['sinif_id','ogrenci_turu','danisman_id','tc_no','kurum_no','ad','soyad','dogum_tarihi','cinsiyet','okul_adi','anne_adi','anne_tel','baba_adi','baba_tel','bildirim_tercih','acil_tel','saglik_notu','adres','yarisma_turu','yarisma_alani','odeme_durumu','kayit_tutari','odeme_notu'];
        $set=[];$vals=[];foreach($FIELDS as $f) if(array_key_exists($f,$b)){$set[]="$f=?";$vals[]=$b[$f];}
        if($set){try{$vals[]=$id;qRun('UPDATE students SET '.implode(',',$set).' WHERE id=?',$vals);}catch(\PDOException $e){$temel=['sinif_id','ad','soyad','dogum_tarihi','cinsiyet','acil_tel','saglik_notu'];$s2=[];$v2=[];foreach($temel as $f) if(array_key_exists($f,$b)){$s2[]="$f=?";$v2[]=$b[$f];}if($s2){$v2[]=$id;qRun('UPDATE students SET '.implode(',',$s2).' WHERE id=?',$v2);}}}
        ok(null,'Guncellendi');
    }

    if($m==='DELETE'&&$id){auth(['admin']);qRun('UPDATE students SET aktif=0 WHERE id=?',[$id]);ok(null,'Silindi');}

    // Mezun yap (toplu)
    if($m==='PUT'&&$sub==='mezun-yap'){
        auth(['admin']);$b=body();
        $ids=$b['ids']??[];$yil=$b['mezuniyet_yili']??date('Y');
        if(empty($ids))err('Ogrenci secilmedi');
        $ph=implode(',',array_fill(0,count($ids),'?'));
        try{
            qRun("UPDATE students SET aktif=0,mezun=1,mezuniyet_yili=? WHERE id IN ($ph) AND institution_id=?"
                ,array_merge([$yil],$ids,[$inst]));
        }catch(\PDOException $e){
            if(str_contains($e->getMessage(),'Unknown column')){
                try{
                    qRun('ALTER TABLE students ADD COLUMN mezun TINYINT(1) DEFAULT 0');
                    qRun('ALTER TABLE students ADD COLUMN mezuniyet_yili YEAR DEFAULT NULL');
                    qRun("UPDATE students SET aktif=0,mezun=1,mezuniyet_yili=? WHERE id IN ($ph) AND institution_id=?"
                        ,array_merge([$yil],$ids,[$inst]));
                }catch(\PDOException $e2){err('DB hatasi: '.$e2->getMessage(),500);}
            } else err('DB hatasi: '.$e->getMessage(),500);
        }
        ok(null,count($ids).' ogrenci mezun yapildi');
    }

    // Mezundan aktif listeye geri al
    if($m==='PUT'&&$sub==='mezunu-geri-al'){
        auth(['admin']);$b=body();
        $ids=$b['ids']??[];
        if(empty($ids))err('Ogrenci secilmedi');
        $ph=implode(',',array_fill(0,count($ids),'?'));
        $pars=array_merge($ids,[$inst]);
        qRun("UPDATE students SET aktif=1,mezun=0,mezuniyet_yili=NULL WHERE id IN ($ph) AND institution_id=?",$pars);
        ok(null,'Geri alindi');
    }

    // Ogrencinin devamsizlik limiti durumu
    if($m==='GET'&&$id&&$sub==='limit'){
        $okul=getOkulInfo($inst);
        $r=qOne('SELECT SUM(durum="gelmedi") gelmedi,SUM(durum="mazeretli") mazeretli FROM attendance WHERE student_id=?',[$id]);
        ok(['gelmedi'=>(int)($r['gelmedi']??0),'mazeretli'=>(int)($r['mazeretli']??0),'limit_normal'=>$okul['devamsizlik_limit_normal'],'limit_mazeret'=>$okul['devamsizlik_limit_mazeret']]);
    }

    err('Endpoint yok',404);
}

// ============================================================
//  DEVAMSIZLIK (Geldi / Gelmedi / Mazeretli)
// ============================================================
function rDevamsizlik(string $m,?int $id,string $sub):void{
    $p=auth();$inst=$p['inst']??1;
    $okul=getOkulInfo($inst);

    if($m==='GET'&&$sub!=='ozet'){
        $sql='SELECT a.*,s.ad ogrenci_adi,s.soyad ogrenci_soyad,s.ogrenci_no,s.ogrenci_turu FROM attendance a JOIN students s ON s.id=a.student_id WHERE s.institution_id=?';
        $params=[$inst];
        if($p['rol']==='veli'){$sql.=' AND 1=0';} // veli_id kolonu yok, veli erişimi desteklenmiyor
        $bas=get('baslangic',date('Y-m-01'));$bit=get('bitis',today_str());
        $sql.=' AND a.tarih BETWEEN ? AND ?';$params[]=$bas;$params[]=$bit;
        if(!empty($_GET['student_id'])){$sql.=' AND a.student_id=?';$params[]=(int)$_GET['student_id'];}
        if(!empty($_GET['tarih'])){$sql.=' AND a.tarih=?';$params[]=$_GET['tarih'];}
        $sql.=' ORDER BY a.tarih DESC,s.soyad LIMIT 300';
        ok(['data'=>q($sql,$params)]);
    }

    // Toplu giris
    if($m==='POST'&&$sub==='toplu'){
        $b=body();
        if(!$b['tarih']||!$b['kayitlar'])err('tarih ve kayitlar gerekli');
        $eklenen=0;$bildirimler=[];
        $stmt=db()->prepare('INSERT INTO attendance (student_id,tarih,durum,not_,giris_yapan) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE durum=VALUES(durum),not_=VALUES(not_)');
        foreach($b['kayitlar'] as $k){
            $durum=$k['durum']??'geldi';
            if(!in_array($durum,['geldi','gelmedi','mazeretli']))$durum='geldi';
            $stmt->execute([(int)$k['student_id'],$b['tarih'],$durum,$k['not_']??null,$p['sub']]);
            $eklenen++;
            if(in_array($durum,['gelmedi','mazeretli'])) $bildirimler[]=['id'=>(int)$k['student_id'],'durum'=>$durum];
        }
        foreach($bildirimler as $bl) gonderDevamsizlikBildirim($bl['id'],$b['tarih'],$bl['durum'],$p['sub'],$inst,$okul);
        ok(['eklenen'=>$eklenen,'bildirim_sayisi'=>count($bildirimler)],'Devamsizlik kaydedildi');
    }

    if($m==='POST'){
        $b=body();if(!$b['student_id']||!$b['tarih']||!$b['durum'])err('Zorunlu alanlar eksik');
        $durum=$b['durum'];if(!in_array($durum,['geldi','gelmedi','mazeretli']))$durum='geldi';
        qRun('INSERT INTO attendance (student_id,tarih,durum,not_,giris_yapan) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE durum=VALUES(durum),not_=VALUES(not_)',[(int)$b['student_id'],$b['tarih'],$durum,$b['not_']??null,$p['sub']]);
        if(in_array($durum,['gelmedi','mazeretli'])) gonderDevamsizlikBildirim((int)$b['student_id'],$b['tarih'],$durum,$p['sub'],$inst,$okul);
        ok(null,'Kaydedildi',201);
    }

    if($m==='PUT'&&$id){$b=body();qRun('UPDATE attendance SET durum=?,not_=? WHERE id=?',[$b['durum']??'geldi',$b['not_']??null,$id]);ok(null,'Guncellendi');}
    if($m==='DELETE'&&$id){qRun('DELETE FROM attendance WHERE id=?',[$id]);ok(null,'Silindi');}

    if($m==='GET'&&$sub==='ozet'){
        ok(q('SELECT s.id,s.ad,s.soyad,s.ogrenci_no,s.ogrenci_turu,COUNT(*) toplam,SUM(a.durum="gelmedi") gelmedi,SUM(a.durum="mazeretli") mazeretli,ROUND(SUM(a.durum="gelmedi")/NULLIF(COUNT(*),0)*100,1) oran FROM attendance a JOIN students s ON s.id=a.student_id WHERE s.institution_id=? AND s.aktif=1 GROUP BY a.student_id ORDER BY oran DESC',[$inst]));
    }

    err('Endpoint yok',404);
}

function gonderDevamsizlikBildirim(int $sid,string $tarih,string $durum,int $giren,int $inst,array $okul):void{
    $s=qOne('SELECT s.* FROM students s WHERE s.id=?',[$sid]);
    if(!$s)return;
    // Bildirim telefonu — bildirim_tercih'e göre
    $tercih=$s['bildirim_tercih']??'baba';
    $tel=$tercih==='anne'?($s['anne_tel']??null):($s['baba_tel']??null);
    if(!$tel)$tel=$s['anne_tel']??$s['baba_tel']??$s['acil_tel']??null;
    if(!$tel)return;

    $sablon_kod=$durum==='mazeretli'?'mazeretli':'devamsizlik';
    $sablon=qOne('SELECT icerik FROM message_templates WHERE institution_id=? AND kod=?',[$inst,$sablon_kod]);
    $icerik=$sablon?$sablon['icerik']:"Sayin Velimiz,\n{$s['ad']} {$s['soyad']} ogrenciniz $tarih tarihinde derse $durum sayilmistir.\n{$okul['adi']}\n📞 {$okul['telefon']}";
    $veli_adi=$tercih==='anne'?$s['anne_adi']:$s['baba_adi'];
    $mesaj=fillTemplate($icerik,['ad_soyad'=>$s['ad'].' '.$s['soyad'],'okul'=>$okul['adi'],'telefon'=>$okul['telefon'],'veli_adi'=>$veli_adi??'Velimiz']);
    gonderBildirim($sid,$tel,$mesaj,$sablon_kod,'whatsapp',$giren,$inst);

    // Kritik uyari — limit kontrol
    $stats=qOne('SELECT SUM(durum="gelmedi") g,SUM(durum="mazeretli") m FROM attendance WHERE student_id=?',[$sid]);
    $limit_u=(int)($okul['devamsizlik_uyari_gun']??3);
    if(($stats['g']??0)>=$limit_u||($stats['m']??0)>=$limit_u){
        $krit_sab=qOne('SELECT icerik FROM message_templates WHERE institution_id=? AND kod="kritik"',[$inst]);
        $km=$krit_sab?fillTemplate($krit_sab['icerik'],['ad_soyad'=>$s['ad'].' '.$s['soyad'],'okul'=>$okul['adi'],'telefon'=>$okul['telefon']]):"⚠️ {$s['ad']} {$s['soyad']} ogrencinizin devamsizlik sayisi sinira yaklasti.";
        gonderBildirim($sid,$tel,$km,'kritik','whatsapp',$giren,$inst);
    }
}

// ============================================================
//  SINAV NOTLARI
// ============================================================
function rNotlar(string $m,?int $id):void{
    $p=auth();$inst=$p['inst']??1;
    if($m==='GET'){
        $sql='SELECT n.*,s.ad ogrenci_adi,s.soyad ogrenci_soyad,d.ad ders_adi FROM grades n JOIN students s ON s.id=n.student_id JOIN lessons d ON d.id=n.lesson_id WHERE s.institution_id=?';
        $params=[$inst];
        if(!empty($_GET['student_id'])){$sql.=' AND n.student_id=?';$params[]=(int)$_GET['student_id'];}
        ok(['data'=>q($sql.' ORDER BY n.tarih DESC LIMIT 100',$params)]);
    }
    if($m==='POST'){
        $b=body();
        if(!$b['student_id']||!$b['lesson_id']||!isset($b['puan'])||!$b['tarih'])err('Zorunlu alanlar eksik');
        ok(['id'=>qRun('INSERT INTO grades (student_id,lesson_id,sinav_turu,puan,max_puan,tarih,aciklama,giris_yapan) VALUES (?,?,?,?,?,?,?,?)',[(int)$b['student_id'],(int)$b['lesson_id'],$b['sinav_turu']??'yazili1',$b['puan'],$b['max_puan']??100,$b['tarih'],$b['aciklama']??null,$p['sub']])],'Not eklendi',201);
    }
    if($m==='DELETE'&&$id){qRun('DELETE FROM grades WHERE id=?',[$id]);ok(null,'Silindi');}
    err('Endpoint yok',404);
}

// ============================================================
//  ÖĞRETMEN NOTLARI
// ============================================================
function rOgretmenNot(string $m,?int $id,string $sub):void{
    $p=auth();$inst=$p['inst']??1;

    if($m==='GET'){
        $sql='SELECT n.*,s.ad ogrenci_adi,s.soyad ogrenci_soyad,u.ad ogretmen_adi,u.soyad ogretmen_soyad FROM teacher_notes n JOIN students s ON s.id=n.student_id JOIN users u ON u.id=n.ogretmen_id WHERE s.institution_id=?';
        $params=[$inst];
        if($p['rol']==='ogretmen'){$sql.=' AND n.ogretmen_id=?';$params[]=$p['sub'];}
        if($p['rol']==='veli'){$sql.=' AND n.veliye_gorunsun=1 AND 1=0';} // veli_id kolonu yok
        if(!empty($_GET['student_id'])){$sql.=' AND n.student_id=?';$params[]=(int)$_GET['student_id'];}
        if(isset($_GET['is_read'])){$sql.=' AND n.is_read=?';$params[]=(int)$_GET['is_read'];}
        $per=(int)($_GET['per_page']??500);if($per<1||$per>5000)$per=500;
        ok(['data'=>q($sql.' ORDER BY n.olusturma DESC LIMIT '.$per,$params)]);
    }

    if($m==='POST'){
        $b=body();if(!$b['student_id']||!$b['icerik'])err('Ögrenci ve icerik gerekli');
        $nid=qRun('INSERT INTO teacher_notes (student_id,ogretmen_id,baslik,icerik,kategori,onem,veliye_gorunsun,is_read,olusturma) VALUES (?,?,?,?,?,?,?,0,?)',[(int)$b['student_id'],$p['sub'],$b['baslik']??null,$b['icerik'],$b['kategori']??'genel',$b['onem']??'normal',isset($b['veliye_gorunsun'])?(int)$b['veliye_gorunsun']:1,now_str()]);
        ok(['id'=>$nid],'Not eklendi',201);
    }

    // Okundu isaretle
    if($m==='PUT'&&$id&&$sub==='okundu'){
        qRun('UPDATE teacher_notes SET is_read=1,read_at=?,read_by=? WHERE id=?',[now_str(),$p['sub'],$id]);
        ok(null,'Okundu olarak isaretlendi');
    }

    if($m==='PUT'&&$id){
        $b=body();$set=[];$vals=[];
        foreach(['baslik','icerik','kategori','onem','veliye_gorunsun','is_read'] as $f) if(array_key_exists($f,$b)){$set[]="$f=?";$vals[]=$b[$f];}
        if($set){$vals[]=$id;qRun('UPDATE teacher_notes SET '.implode(',',$set).' WHERE id=?',$vals);}
        ok(null,'Guncellendi');
    }

    if($m==='DELETE'&&$id){
        $where=$p['rol']==='admin'?'WHERE id=?':'WHERE id=? AND ogretmen_id=?';
        $pms=$p['rol']==='admin'?[$id]:[$id,$p['sub']];
        qRun("DELETE FROM teacher_notes $where",$pms);ok(null,'Silindi');
    }
    err('Endpoint yok',404);
}

// ============================================================
//  BİLDİRİMLER
// ============================================================
function rBildirim(string $m,string $sub,?int $id):void{
    $p=auth();$inst=$p['inst']??1;
    $okul=getOkulInfo($inst);

    if($m==='POST'&&$sub==='gonder'){
        $b=body();if(!$b['student_id']||!$b['mesaj']||!$b['kanal'])err('Zorunlu alanlar eksik');
        $s=qOne('SELECT s.* FROM students s WHERE s.id=?',[(int)$b['student_id']]);
        if(!$s)err('Ögrenci bulunamadi',404);
        $tercih=$s['bildirim_tercih']??'baba';
        $tel=$tercih==='anne'?($s['anne_tel']??null):($s['baba_tel']??null);
        if(!$tel)$tel=$s['anne_tel']??$s['baba_tel']??null;
        if(!$tel)err('Veli telefonu tanimli degil',422);
        $mesaj=fillTemplate($b['mesaj'],['ad_soyad'=>$s['ad'].' '.$s['soyad'],'okul'=>$okul['adi'],'telefon'=>$okul['telefon'],'mesaj'=>$b['mesaj']]);
        ok(gonderBildirim((int)$b['student_id'],$tel,$mesaj,$b['sablon_tipi']??'genel',$b['kanal'],$p['sub'],$inst));
    }

    if($m==='GET'&&$sub==='log'){
        $sql='SELECT n.*,s.ad student_ad,s.soyad student_soyad FROM notification_logs n JOIN students s ON s.id=n.student_id WHERE n.institution_id=?';
        $params=[$inst];
        if(!empty($_GET['durum'])){$sql.=' AND n.durum=?';$params[]=$_GET['durum'];}
        $per=(int)($_GET['per_page']??200);if($per<1||$per>1000)$per=200;
        $sql.=' ORDER BY n.olusturma DESC LIMIT '.$per;
        $rows=q($sql,$params);
        $rows=array_map(fn($r)=>array_merge($r,['student'=>['id'=>$r['student_id'],'ad'=>$r['student_ad'],'soyad'=>$r['student_soyad']]]),$rows);
        ok(['data'=>$rows]);
    }

    // Manuel gonderildi olarak isaretle (pending ve failed durumlar için)
    if($m==='PUT'&&$sub==='manuel'&&$id){
        $log=qOne('SELECT id,durum FROM notification_logs WHERE id=? AND institution_id=?',[$id,$inst]);
        if(!$log)err('Bildirim bulunamadı',404);
        qRun("UPDATE notification_logs SET durum='manuel_gonderildi',gonderim_tarihi=? WHERE id=?",[ now_str(),$id]);
        ok(null,'Manuel gonderildi olarak isaretlendi');
    }

    if($m==='POST'&&$sub==='tekrar-gonder'){
        $b=body();if(!$b['log_id'])err('log_id gerekli');
        $log=qOne('SELECT * FROM notification_logs WHERE id=?',[(int)$b['log_id']]);
        if(!$log)err('Log bulunamadi',404);
        ok(gonderBildirim((int)$log['student_id'],$log['veli_telefon'],$log['mesaj'],$log['sablon_tipi'],$log['kanal'],$p['sub'],$inst));
    }
    err('Endpoint yok',404);
}

function gonderBildirim(int $sid,string $tel,string $mesaj,string $sablon,string $kanal,?int $gonderen,int $inst):array{
    global $CFG;
    $lid=qRun('INSERT INTO notification_logs (institution_id,student_id,veli_telefon,kanal,sablon_tipi,mesaj,durum,gonderen_id,olusturma) VALUES (?,?,?,?,?,?,?,?,?)',[$inst,$sid,$tel,$kanal,$sablon,$mesaj,'pending',$gonderen,now_str()]);
    $durum='pending';$api='';
    try{
        $digits=preg_replace('/[^0-9]/','',$tel);
        if(strlen($digits)===11&&$digits[0]==='0')$digits='90'.substr($digits,1);
        elseif(strlen($digits)===10&&$digits[0]==='5')$digits='90'.$digits;
        if($kanal==='whatsapp'&&!empty($CFG['ultramsg']['instance'])&&!empty($CFG['ultramsg']['token'])){
            $ch=curl_init("https://api.ultramsg.com/{$CFG['ultramsg']['instance']}/messages/chat");
            curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>10,CURLOPT_POSTFIELDS=>http_build_query(['token'=>$CFG['ultramsg']['token'],'to'=>$digits,'body'=>$mesaj])]);
            $r=curl_exec($ch);curl_close($ch);$api=$r??'';
            $d=json_decode($r,true);$durum=($d['sent']??'')==='true'?'sent':'failed';
        }elseif($kanal==='sms'&&!empty($CFG['netgsm']['user'])){
            $nd=strlen($digits)===12?substr($digits,2):$digits;
            $r=@file_get_contents('https://api.netgsm.com.tr/sms/send/get?'.http_build_query(['usercode'=>$CFG['netgsm']['user'],'password'=>$CFG['netgsm']['pass'],'gsmno'=>$nd,'message'=>$mesaj,'msgheader'=>$CFG['netgsm']['header'],'dil'=>'TR']));
            $api=$r??'';$durum=str_starts_with(trim($r??''),'00')?'sent':'failed';
        }else{$durum='pending';$api='API ayarlanmamis';}
    }catch(\Throwable $e){$durum='failed';$api=$e->getMessage();}
    qRun('UPDATE notification_logs SET durum=?,api_response=?,gonderim_tarihi=? WHERE id=?',[$durum,substr($api,0,500),now_str(),$lid]);
    return['durum'=>$durum,'log_id'=>$lid];
}

// ============================================================
//  ŞABLON YÖNETİMİ
// ============================================================
function rSablonlar(string $m,?int $id):void{
    $p=auth();$inst=$p['inst']??1;
    if($m==='GET') ok(q('SELECT * FROM message_templates WHERE institution_id=? AND aktif=1 ORDER BY kod',[$inst]));
    if($m==='POST'){
        auth(['admin']);$b=body();if(!$b['kod']||!$b['baslik']||!$b['icerik'])err('kod, baslik, icerik gerekli');
        qRun('INSERT INTO message_templates (institution_id,kod,baslik,icerik) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE baslik=VALUES(baslik),icerik=VALUES(icerik)',[$inst,$b['kod'],$b['baslik'],$b['icerik']]);
        ok(null,'Şablon kaydedildi');
    }
    if($m==='PUT'&&$id){
        auth(['admin']);$b=body();
        qRun('UPDATE message_templates SET baslik=?,icerik=? WHERE id=? AND institution_id=?',[$b['baslik']??'',$b['icerik']??'',$id,$inst]);
        ok(null,'Guncellendi');
    }
    if($m==='DELETE'&&$id){auth(['admin']);qRun('DELETE FROM message_templates WHERE id=? AND institution_id=?',[$id,$inst]);ok(null,'Silindi');}
    err('Endpoint yok',404);
}

// ============================================================
//  RAPORLAR
// ============================================================
function rRaporlar(string $m,string $sub,?int $id):void{
    $p=auth();$inst=$p['inst']??1;
    if($sub==='devamsizlik-ozet'){
        ok(q('SELECT s.id,s.ad,s.soyad,s.ogrenci_no,s.ogrenci_turu,COUNT(*) toplam,SUM(a.durum="gelmedi") gelmedi,SUM(a.durum="mazeretli") mazeretli,ROUND(SUM(a.durum="gelmedi")/NULLIF(COUNT(*),0)*100,1) oran FROM attendance a JOIN students s ON s.id=a.student_id WHERE s.institution_id=? AND s.aktif=1 GROUP BY a.student_id ORDER BY oran DESC',[$inst]));
    }
    if($sub==='risk-listesi'){
        $okul=getOkulInfo($inst);$lu=(int)$okul['devamsizlik_uyari_gun'];
        ok(q('SELECT s.id,s.ad,s.soyad,s.ogrenci_no,CONCAT(IFNULL(s.baba_adi,"")," / ",IFNULL(s.anne_adi,"")) veli_adi,CASE s.bildirim_tercih WHEN "anne" THEN s.anne_tel ELSE s.baba_tel END veli_tel,COUNT(*) toplam,SUM(a.durum="gelmedi") gelmedi,SUM(a.durum="mazeretli") mazeretli,ROUND(SUM(a.durum="gelmedi")/NULLIF(COUNT(*),0)*100,1) oran FROM attendance a JOIN students s ON s.id=a.student_id WHERE s.institution_id=? AND s.aktif=1 GROUP BY a.student_id HAVING gelmedi>=? OR mazeretli>=? ORDER BY oran DESC',[$inst,$lu,$lu]));
    }
    err('Endpoint yok',404);
}

// ============================================================
//  KULLANICI YÖNETİMİ
// ============================================================
function rKullanicilar(string $m,?int $id,string $sub):void{
    $p=auth();$inst=$p['inst']??1;
    if($m==='GET'&&!$id){
        $sql='SELECT id,ad,soyad,email,telefon,rol,aktif,son_giris FROM users WHERE institution_id=?';$params=[$inst];
        if(!empty($_GET['rol'])){$sql.=' AND rol=?';$params[]=$_GET['rol'];}
        ok(q($sql.' ORDER BY FIELD(rol,"admin","ogretmen","veli","muhasebe"),soyad',$params));
    }
    if($m==='GET'&&$id) ok(qOne('SELECT id,ad,soyad,email,telefon,rol,aktif FROM users WHERE id=? AND institution_id=?',[$id,$inst]));

    if($m==='POST'){
        auth(['admin']);$b=body();
        foreach(['ad','soyad','email','sifre','rol'] as $f) if(empty($b[$f]))err("$f gerekli");
        if(!in_array($b['rol'],['admin','ogretmen','veli','muhasebe']))err('Gecersiz rol');
        if(qOne('SELECT id FROM users WHERE email=?',[$b['email']]))err('E-posta zaten kayitli',409);
        $nid=qRun('INSERT INTO users (institution_id,ad,soyad,email,telefon,sifre,rol,aktif) VALUES (?,?,?,?,?,?,?,1)',[$inst,$b['ad'],$b['soyad'],$b['email'],$b['telefon']??null,password_hash($b['sifre'],PASSWORD_BCRYPT,['cost'=>10]),$b['rol']]);
        ok(['id'=>$nid],'Kullanici eklendi',201);
    }

    if($m==='PUT'&&$id){
        if($p['rol']!=='admin'&&$p['sub']!==$id)err('Yetki yok',403);
        $b=body();$allowed=$p['rol']==='admin'?['ad','soyad','email','telefon','rol','aktif']:['ad','soyad','telefon'];
        $set=[];$vals=[];foreach($allowed as $f) if(array_key_exists($f,$b)){$set[]="$f=?";$vals[]=$b[$f];}
        if(!empty($b['sifre'])&&strlen($b['sifre'])>=8){$set[]='sifre=?';$vals[]=password_hash($b['sifre'],PASSWORD_BCRYPT,['cost'=>10]);}
        if($set){$vals[]=$id;$vals[]=$inst;qRun('UPDATE users SET '.implode(',',$set).' WHERE id=? AND institution_id=?',$vals);}
        ok(null,'Guncellendi');
    }

    // Aktif Et / Deaktif Et toggle
    if($m==='PUT'&&$id&&$sub==='toggle-aktif'){
        auth(['admin']);
        $u=qOne('SELECT aktif FROM users WHERE id=?',[$id]);
        if(!$u)err('Kullanici bulunamadi',404);
        $yeni=($u['aktif']?0:1);
        qRun('UPDATE users SET aktif=? WHERE id=? AND institution_id=?',[$yeni,$id,$inst]);
        ok(['aktif'=>$yeni],$yeni?'Kullanici aktif edildi':'Kullanici devre disi birakildi');
    }

    // Kalici sil
    if($m==='DELETE'&&$id){
        auth(['admin']);
        if($p['sub']===$id)err('Kendinizi silemezsiniz',422);
        qRun('DELETE FROM users WHERE id=? AND institution_id=?',[$id,$inst]);
        ok(null,'Kullanici silindi');
    }
    err('Endpoint yok',404);
}