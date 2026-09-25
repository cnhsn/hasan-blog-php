# İlerleme kaydı

## Güncel durum (2026-09-25)

Uygulama **çalışır durumda ve yerelde doğrulanmıştır**. M0–M5 aşamalarının
tamamı bitti. Üretime dağıtım yapılmadı (dış bağımlılıklar sağlanmadı).

Şartname paketi bir Next.js 16 + Payload 3 + PostgreSQL uygulamasına
dönüştürüldü: gerçek CMS, tek sahipli yetkilendirme, S3 uyumlu medya,
güvenli taslak önizleme, yayın akışı, animasyonlar, Docker paketi, testler
ve kurulum belgeleri.

## Tamamlanan aşamalar

- **M0 — Bootstrap**: sürümler resmi peer dependency verisiyle doğrulanıp
  sabitlendi, `pnpm-lock.yaml` işlendi, yerel servisler (Postgres/Garage/
  Mailpit) Compose ile ayağa kalkıyor, admin girişi ve güvenli ilk yönetici
  CLI'ı çalışıyor.
- **M1 — Gerçek CMS**: tüm koleksiyonlar/global, erişim kuralları,
  migration'lar ve üretilmiş tipler. Taslak/sürüm/autosave, medya kısıtları,
  güvenli içerik doğrulaması. Seed yalnızca site ayarlarını yazar.
- **M2 — Frontend**: ana sayfa, blog ve proje listeleri/detayları, kategori
  filtresi, sayfalama, RSS, sitemap, robots, 404, slug geçmişi yönlendirmeleri.
- **M3 — Preview ve hareket**: sahip oturumu + imzalı token isteyen preview,
  çıkış akışı, önizleme bandı; erişilebilir geçişler, reduced-motion,
  klavye ve geçmiş davranışı.
- **M4 — Üretim paketi**: çok aşamalı Dockerfile (root olmayan kullanıcı,
  healthcheck, graceful shutdown), `compose.production.yaml` (ayrı migration
  işi), yedekleme/geri dönüş betikleri ve gerçek geri dönüş tatbikatı, CI.
- **M5 — Doğrulama**: 51 birim/entegrasyon + 36 tarayıcı testi, A01–A28
  kabul kayıtları, README çalıştırma kılavuzu.

## Son çalıştırılan komutlar ve sonuçları

| Komut | Sonuç |
|---|---|
| `pnpm typecheck` | 0 hata |
| `pnpm lint` | 0 hata, 0 uyarı |
| `pnpm test` | 51/51 geçti (gerçek Postgres + gerçek S3) |
| `pnpm test:e2e` | 36/36 geçti (Chromium masaüstü + mobil) |
| `pnpm build` | başarılı, tüm veri rotaları istek anında render |
| `docker build` | ortam değişkeni ve veritabanı olmadan başarılı |
| `pnpm backup` + `pnpm restore:check` | geçti, ölçülen kurtarma süresi 3.4 s |
| Temiz kurulum (A01) | volume'ler silinip belgelenen sıra baştan çalıştırıldı, başarılı |

Ayrıntılı kanıtlar: `docs/VERIFICATION.md`.

## Geliştirme sırasında bulunup düzeltilen gerçek hatalar

Testler yalnızca mevcut davranışı onaylamadı, şunları yakaladı:

1. Payload'ın "ilk kullanıcıyı oluştur" akışı `access.create`'i **baypas
   ediyordu**; kimliksiz biri kendini yönetici yapabilirdi. `beforeOperation`
   kancasıyla kapatıldı (`/api/users/first-register` artık 403).
2. `slugify` Türkçe noktasız "ı" harfini düşürüyordu ("Başlık" → `basl-k`).
3. Kapak görseli olan her liste **boş** render ediliyordu: Payload mutlak
   medya URL'i döndürüyor, `next/image` bunu yapılandırılmamış uzak host
   sayıp hata veriyordu.
4. `/rss.xml` seed edilmemiş sitede 500 veriyordu.
5. `/robots.txt` hiç kayıtlı değildi (Next'te yalnızca kök dizinde geçerli).
6. Ana sayfa dört hedef ekranın üçünde taşıyordu (375×667'de 298 piksel).
7. React StrictMode geliştirmede efektleri iki kez çalıştırdığı için ilk
   yüklemede başlığa odak kayıyor ve istenmeyen odak halkası çiziliyordu.
8. `/` ve `/sitemap.xml` derleme anında ön-render ediliyordu; bu hem içeriği
   derlemeye gömüyor hem de derlemeyi veritabanına bağımlı kılıyordu.

## Bilinen eksikler

- **Üretim dağıtımı yapılmadı.** Alan adı, sunucu, üretim PostgreSQL'i,
  S3 sağlayıcısı ve SMTP bilgileri sağlanmadı. Dağıtım adımları yerelde
  üretim-benzeri konteynerde doğrulandı.
- Tarayıcı testleri yalnızca Chromium ile; WebKit/Firefox kurulmadı.
- Admin arayüzündeki autosave ve satır içi doğrulama mesajları tarayıcıdan
  sürülmedi (API üzerinden doğrulandı).
- Boyut/piksel üst sınırları uygulandı ama gerçek aşırı büyük dosyalarla
  tetiklenmedi.
- CSP açılmadı (şartname test edilmemiş CSP'ye karşı uyarıyor).

## Sonraki adım

Üretime almak için gereken dış bağımlılıklar kullanıcıdan gelmeli:
alan adı + DNS, Docker çalıştıran sunucu, PostgreSQL, S3 uyumlu depolama
ve SMTP bilgileri. Bunlar geldiğinde `README.md` içindeki "Üretim" bölümü
sırayla uygulanabilir; kod tarafında bekleyen iş yok.
