# \# Okuly — Okul Öğrenci Yönetim Sistemi

# 

# PHP tabanlı, veritabanı kurulumu dahil tam bir okul yönetim paneli. Composer veya SSH gerekmez; tüm kurulum tarayıcı üzerinden yapılır.

# 

# \---

# 

# \## Gereksinimler

# 

# | Gereksinim | Minimum |

# |---|---|

# | PHP | 8.1 veya 8.2 |

# | PHP Uzantıları | `pdo\_mysql`, `curl` |

# | MySQL | 5.7+ veya MariaDB 10.3+ |

# | Web Sunucu | Apache (cPanel) veya Nginx |

# 

# > \*\*cPanel kullanıyorsan:\*\* cPanel → \*\*Select PHP Version\*\* → 8.1 veya 8.2 seç → `pdo\_mysql` ve `curl` uzantılarını aktif et → \*\*Save\*\* yap.

# 

# \---

# 

# \## Dosya Yapısı

# 

# ```

# /

# ├── api.php              ← REST API (tüm backend mantığı)

# ├── config.php           ← Veritabanı bağlantı ayarları (setup tarafından oluşturulur)

# ├── setup.php            ← Kurulum sihirbazı (kurulum sonrası silinecek)

# ├── panel/

# │   └── index.html       ← Yönetim paneli arayüzü

# └── uploads/

# &#x20;   └── ogrenci/         ← Öğrenci fotoğrafları (yazma izni gerekir)

# ```

# 

# \---

# 

# \## Kurulum Adımları

# 

# \### 1. Dosyaları Sunucuya Yükle

# 

# Tüm dosyaları hosting'inin `public\_html` (veya ilgili domain) klasörüne yükle.

# 

# `uploads/ogrenci/` klasörünün \*\*yazma izninin (755 veya 777)\*\* olduğundan emin ol.

# 

# \---

# 

# \### 2. MySQL Veritabanı ve Kullanıcısı Oluştur

# 

# cPanel → \*\*MySQL Databases\*\* bölümüne git:

# 

# 1\. Yeni bir veritabanı oluştur (örn: `sitead\_okul`)

# 2\. Yeni bir kullanıcı oluştur ve güçlü bir şifre belirle (örn: `sitead\_okuluser`)

# 3\. Kullanıcıyı veritabanına ekle ve \*\*Tüm Ayrıcalıklar\*\* ver

# 

# > ⚠️ cPanel, veritabanı ve kullanıcı adlarının başına otomatik prefix ekler (`sitead\_`). MySQL Databases sayfasında tam adları kopyala.

# 

# \---

# 

# \### 3. Kurulum Sihirbazını Çalıştır

# 

# Tarayıcıda `https://siteadin.com/setup.php` adresini aç. Sihirbaz 5 adımdan oluşur:

# 

# \*\*Adım 1 — Hoşgeldiniz\*\*

# Genel bilgilendirme ekranı. \*\*Başla\*\* butonuna bas.

# 

# \*\*Adım 2 — Sistem Gereksinimleri\*\*

# PHP sürümü ve uzantılar otomatik kontrol edilir. Tüm gereksinimler ✓ yeşil olmalı. Kırmızı bir gereksinim varsa, cPanel'den PHP ayarlarını düzelttikten sonra sayfayı yenile.

# 

# \*\*Adım 3 — Veritabanı Bağlantısı\*\*

# Aşağıdaki bilgileri gir:

# 

# | Alan | Açıklama |

# |---|---|

# | Host | `localhost` (değiştirme) |

# | Port | `3306` (değiştirme) |

# | Veritabanı Adı | 2. adımda oluşturduğun tam ad |

# | DB Kullanıcısı | 2. adımda oluşturduğun tam kullanıcı adı |

# | DB Şifresi | Kullanıcı şifresi |

# 

# \*\*Bağlan ve Kur\*\* butonuna bas. Başarılı olursa tüm tablolar otomatik oluşturulur ve varsayılan mesaj şablonları eklenir.

# 

# \*\*Adım 4 — Son Ayarlar\*\*

# Kurumunuzun temel bilgilerini ve admin hesabını oluştur:

# 

# | Alan | Açıklama |

# |---|---|

# | Kurum Adı | Panelde görünecek kurum adı |

# | Telefon | Velilere gidecek bildirimlerde kullanılır |

# | WhatsApp | WhatsApp bildirim numarası |

# | Admin E-posta | Panele giriş yapacağın e-posta |

# | Admin Şifresi | Minimum 8 karakter, güçlü bir şifre |

# 

# \*\*Kurulumu Tamamla\*\* butonuna bas.

# 

# \*\*Adım 5 — Kurulum Tamamlandı 🎉\*\*

# Kurulum başarıyla tamamlandı. Bu ekranda şunları kontrol et:

# 

# \- `siteniz.com/api.php` → `{"status":"ok"}` döndürmeli

# \- `siteniz.com/panel` → Giriş ekranı açılmalı

# 

# \---

# 

# \### 4. Güvenlik İçin Zorunlu Adımlar

# 

# Kurulum tamamlandıktan sonra aşağıdaki dosyaları \*\*mutlaka sil:\*\*

# 

# ```

# setup.php

# test.php

# api-debug.php

# ```

# 

# > ⚠️ Bu dosyalar sunucuda kalırsa ciddi güvenlik açığı oluşturur. cPanel → \*\*File Manager\*\* üzerinden veya FTP ile silebilirsin.

# 

# \---

# 

# \### 5. Panele Giriş

# 

# `https://siteadin.com/panel` adresine git ve kurulumda belirlediğin e-posta ile şifreyi kullan.

# 

# İlk girişte paneli tanımak için şu bölümlere bak:

# 

# \- \*\*Ayarlar\*\* → Kurum bilgilerini ve devamsızlık limitlerini güncelle

# \- \*\*Sınıflar\*\* → Sınıf ekle (Ayarlar sayfasının alt kısmında)

# \- \*\*Öğrenciler\*\* → Öğrenci kaydı oluştur

# \- \*\*Devamsızlık\*\* → Günlük devamsızlık takibini başlat

# 

# \---

# 

# \## Sık Karşılaşılan Sorunlar

# 

# \*\*"Access denied for user..." hatası\*\*

# Veritabanı kullanıcı adı veya şifresi hatalı. cPanel'den kullanıcıya veritabanı yetkisi verildiğini kontrol et.

# 

# \*\*"Column not found" hatası\*\*

# Veritabanı tabloları eksik veya eski bir sürümden kalma. `setup.php`'yi tekrar çalıştır (önce mevcut tabloları phpMyAdmin'den sil).

# 

# \*\*Kurulum tamamlandı ama setup.php açılıyor\*\*

# `.installed` dosyası oluşturulamamış. `public\_html` klasörünün yazma iznini kontrol et.

# 

# \*\*Panel açılıyor ama içerik gelmiyor\*\*

# `api.php`'nin çalıştığını `siteniz.com/api.php` adresini açarak kontrol et. `{"status":"ok"}` yerine hata dönüyorsa PHP uzantılarını kontrol et.

# 

# \---

# 

# \## Teknik Notlar

# 

# \- Tüm API istekleri `api.php` üzerinden JWT token ile kimlik doğrulaması yapır

# \- Şifreler `bcrypt` ile hashlenir

# \- Brute-force koruması için başarısız giriş denemeleri `login\_attempts` tablosunda tutulur

# \- Hata detayları kullanıcıya gösterilmez; sunucu `error\_log`'una yazılır (`\[okuly]` prefix'i ile aranabilir)

