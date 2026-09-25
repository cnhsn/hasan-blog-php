# Hasan Journal

Next.js + Payload CMS + PostgreSQL ile çalışan kişisel blog ve proje portföyü.
Tasarım `reference-site/` içindeki mevcut siteden port edilmiştir.

Bu dosya kurulum ve işletim kılavuzudur. Şartname `docs/` altındadır;
doğrulama kayıtları `docs/VERIFICATION.md`, kararlar `docs/DECISIONS.md`.

---

## Gereksinimler

| Araç | Sürüm | Not |
|---|---|---|
| Node.js | 24.19.0 | `.nvmrc` dosyasında sabitli |
| pnpm | 9.12.0 | `package.json` → `packageManager` |
| Docker Engine / Desktop | Çalışır durumda | Postgres, S3 (Garage) ve mail yakalama için |

Bulut hesabı veya ücretli servis **gerekmez**. Yerel geliştirme tamamen
Docker üzerindeki servislerle çalışır.

---

## Hızlı başlangıç (temiz kurulum)

Komutları bu sırayla çalıştır:

```bash
pnpm install                # bağımlılıklar (kilitli sürümler)
pnpm setup:local            # rastgele yerel secret'larla .env üretir
pnpm services:up            # Postgres + Garage (S3) + Mailpit başlatır
pnpm db:migrate             # veritabanı şemasını migration'larla kurar
pnpm admin:bootstrap        # İLK VE TEK yöneticiyi oluşturur (aşağıya bak)
pnpm seed                   # varsayılan site ayarları (yazı/proje oluşturmaz)
pnpm dev                    # http://localhost:3000
```

> **Sıralama güvenlik açısından önemlidir.** `pnpm admin:bootstrap` adımını
> uygulamayı ağa açmadan **önce** çalıştır. Detay: "İlk yönetici" bölümü.

Adresler:

| Adres | Ne |
|---|---|
| http://localhost:3000 | Genel site |
| http://localhost:3000/admin | Payload yönetim paneli |
| http://localhost:8025 | Mailpit arayüzü (yerelde giden tüm e-postalar burada kalır) |

### İlk yönetici

`pnpm admin:bootstrap` e-posta ve parolayı **terminalden gizli olarak** sorar,
kaydı doğrudan veritabanına yazar ve zaten bir yönetici varsa reddeder.
Parola en az 8 karakter olmalıdır.

Payload'ın kendi "ilk kullanıcıyı oluştur" ekranı bu projede **kapatılmıştır**:
`src/hooks/restrictUserCreation.ts`, bootstrap CLI dışındaki her kullanıcı
oluşturma denemesini reddeder. Yani uygulama yanlışlıkla yöneticisiz halde
internete açılsa bile kimse kendini yönetici yapamaz (`/api/users/first-register`
isteği 403 döner — `docs/VERIFICATION.md` A03).

Parolanı unutursan: `/admin` üzerinden "forgot password" iste, e-posta
Mailpit'te (http://localhost:8025) görünür. Üretimde gerçek SMTP kullanılır.

---

## Günlük komutlar

| Komut | Ne yapar |
|---|---|
| `pnpm dev` | Geliştirme sunucusu |
| `pnpm services:up` / `pnpm services:down` | Yerel servisleri başlatır / **veri silmeden** durdurur |
| `pnpm db:migrate` | Bekleyen migration'ları uygular |
| `pnpm db:migrate:create` | Şema değişikliğinden sonra yeni migration üretir |
| `pnpm generate:types` | `src/payload-types.ts` üretir (şema değişince çalıştır) |
| `pnpm generate:importmap` | Admin panel import map'ini üretir |
| `pnpm typecheck` / `pnpm lint` | Statik kontroller |
| `pnpm test` | Birim + entegrasyon testleri (gerçek Postgres ve gerçek S3 ile) |
| `pnpm test:e2e` | Tarayıcı testleri (Playwright) |
| `pnpm build` / `pnpm start` | Üretim derlemesi / sunucusu |
| `pnpm backup` | Veritabanı dökümü + medya kopyası + sağlama toplamları |
| `pnpm restore:check` | Yedeği **izole** hedeflere geri yükleyip doğrular |
| `pnpm seed:demo` | Yerel örnek içerik (açıkça izin gerekir, aşağıya bak) |
| `pnpm services:reset` | **YIKICI** — yerel veritabanı/medya volume'lerini siler, onay ister |

### Örnek içerik (sadece yerel)

Üretimde sahte yazı/proje oluşturulmaz. Yerelde arayüzü dolu görmek için:

```bash
ALLOW_DEMO_SEED=true pnpm seed:demo
```

11 yayınlanmış yazı, 1 taslak, 2 proje ve 1 görsel oluşturur. Tüm slug'lar
`sample-` ile başlar; komutu tekrar çalıştırmak sadece bu kayıtları değiştirir.
`NODE_ENV=production` iken veya izin verilmediğinde çalışmayı reddeder.

---

## Testler

```bash
pnpm test        # 51 test: birim + entegrasyon
pnpm test:e2e    # 36 test: masaüstü + mobil tarayıcı
```

Entegrasyon testleri **gerçek** Postgres ve **gerçek** S3 servisine bağlanır;
sahte (mock) yanıt kullanılmaz. Kendilerine ait tek kullanımlık
`hasan_journal_test` veritabanı ve `hasan-journal-test` bucket'ı ile çalışır,
geliştirme verine dokunmaz.

Tarayıcı testleri geliştirme sunucusunu kullanır ve `pnpm admin:bootstrap`
ile oluşturulmuş bir yönetici bekler. Farklı bilgiler kullandıysan:

```bash
E2E_OWNER_EMAIL=... E2E_OWNER_PASSWORD=... pnpm test:e2e
```

Ekran görüntüsü matrisi (4 ekran boyutu × 5 sayfa):

```bash
node scripts/screenshots.mjs     # artifacts/screenshots/ altına yazar
```

---

## Üretim

### Docker imajı

```bash
docker build -t hasan-journal:latest .
```

İmaj **hiçbir ortam değişkeni veya veritabanı olmadan** derlenir; gerçek
secret'lar yalnızca çalışma anında dışarıdan verilir.

### Dağıtım (tek Docker sunucusu + TLS reverse proxy)

`compose.production.yaml` uygulamayı ve PostgreSQL'i tanımlar. Postgres
dışarıya **hiç** açılmaz; uygulama yalnızca `127.0.0.1:3000` üzerinde dinler,
TLS'i sunucudaki reverse proxy (Caddy/nginx/Traefik) sonlandırır.

Operatörün sağlaması gerekenler: `PUBLIC_SITE_URL`, `PAYLOAD_SECRET`,
`POSTGRES_*`, `S3_*`, `SMTP_*` (tamamı `.env.example` içinde açıklanmıştır).

```bash
# 1. Yayın öncesi yedek al ve geri dönüşü doğrula
pnpm backup && pnpm restore:check

# 2. İmajı derle
docker compose -f compose.production.yaml build

# 3. Migration'ları TEK bir kontrollü süreçte uygula
docker compose -f compose.production.yaml run --rm migrate

# 4. Uygulamayı başlat
docker compose -f compose.production.yaml up -d app

# 5. İlk sürümde: yöneticiyi oluştur (trafiği açmadan önce)
docker compose -f compose.production.yaml exec app \
  node node_modules/tsx/dist/cli.mjs scripts/admin-bootstrap.ts

# 6. Sağlık kontrolü, sonra trafiği yönlendir
curl -fsS http://127.0.0.1:3000/api/health
```

Migration adımı ayrı bir tek seferlik iş olarak çalışır; birden fazla kopya
aynı anda migration uygulamaya çalışmaz.

`PUBLIC_SITE_URL` üretimde `https://` olmak ve `localhost` olmamak
zorundadır; aksi halde uygulama açılışta anlaşılır bir hata ile durur.

### Yedekleme ve geri dönüş

```bash
pnpm backup         # backups/<zaman>/ : database.dump + media/ + sağlama toplamları
pnpm restore:check  # en son yedeği izole hedeflere geri yükler ve doğrular
```

`pnpm backup` çıktısı **şifrelenmez** — şifreleme ve sunucu dışına kopyalama
operatörün adımıdır, çünkü anahtar verinin yanında durmamalıdır. Örnek:

```bash
tar -czf - backups/<zaman> | age -r <alıcı-anahtarı> > yedek.tar.gz.age
```

`pnpm restore:check` canlı veritabanına ve canlı bucket'a **asla** yazmaz;
geçici bir veritabanına geri yükler, belge sayıları ile medya sağlama
toplamlarını karşılaştırır, ölçülen süreyi bildirir ve geçici veritabanını siler.

Önerilen politika: günlük yedek, 7 günlük + 4 haftalık kopya, ayda bir
`restore:check`. Sıfır veri kaybı garantisi verilmez.

---

## Mimari (kısa)

- `src/app/(frontend)/` — genel sayfalar, RSS, sitemap, preview, health
- `src/app/(payload)/` — admin paneli ve REST API
- `src/collections/`, `src/globals/` — CMS içerik modeli
- `src/access/` — yetkilendirme kuralları (tek sahip)
- `src/hooks/` — slug geçmişi, yayın doğrulaması, güvenlik kancaları
- `src/lib/queries/` — sunucu tarafı içerik sorguları
- `src/migrations/` — sürüm kontrolündeki migration'lar
- `scripts/` — kurulum, bootstrap, seed, yedekleme, ekran görüntüsü

Genel sayfalar CMS'i her istekte okur (önbellek katmanı yoktur), bu yüzden
yayınlanan içerik anında görünür — ölçüm: 200 ms.

Öne çıkan güvenlik davranışları:

- Genel okumalarda **her zaman** `overrideAccess: false` kullanılır.
- Taslaklar listede, detayda, ilişkilerde, RSS'te ve sitemap'te görünmez.
- Preview hem geçerli sahip oturumu hem de kısa ömürlü imzalı token ister;
  yönlendirme hedefi sabit bir listeden gelir (açık yönlendirme yok).
- Medya varsayılan olarak **private**'tır; herkese açık teslim yalnızca sahip
  açıkça public yaptıktan sonra çalışır. Bir yazı, referans verdiği medya
  public olmadan yayınlanamaz.
- Bir medya dosyasını public'ten private'a çevirmek, **daha önce** o dosyayı
  indirmiş tarayıcı/ara önbellekleri geriye dönük temizlemez; teslim
  `no-store` ile yapılır ama geçmişte dağıtılmış kopyalar geri alınamaz.

---

## Bilinen sınırlar

- Üretime **dağıtım yapılmamıştır**: alan adı, sunucu, S3 ve SMTP bilgileri
  sağlanmadı. Yukarıdaki dağıtım adımları yereldeki üretim-benzeri konteyner
  üzerinde doğrulanmıştır, gerçek bir sunucuda değil.
- Tarayıcı testleri Chromium (masaüstü + mobil) ile çalıştırılmıştır.
  WebKit ve Firefox bu ortamda kurulmadı.
- İçerik Güvenliği Politikası (CSP) henüz açılmadı; `docs/SECURITY.md`
  test edilmemiş CSP'ye karşı uyardığı için diğer güvenlik başlıkları
  uygulanıp CSP sonraya bırakıldı.
