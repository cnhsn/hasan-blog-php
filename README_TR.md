# Hasan Journal — OpenCode geliştirme paketi

Bu paket, mevcut kişisel siteyi Next.js + Payload CMS + PostgreSQL tabanlı çalışan bir blog ve proje portföyüne dönüştürmek için hazırlanmıştır. **Tamamlanmış CMS uygulaması değildir.** Çalışan uygulamanın üretilmesi için şartname, ajan talimatları, kontrol listeleri ve mevcut sitenin gerçek kaynaklarını içerir.

## Başlatma
1. ZIP'i yeni bir klasöre çıkar. `AGENTS.md` ve `opencode.json` aynı kökte bulunmalı.
2. Terminali bu klasörde aç. OpenCode kurulu ve model sağlayıcın yapılandırılmış olmalı. Yerel tam doğrulama için Docker Engine/Desktop çalışmalı; uygulama geliştirmesi için uygun Node.js ve pnpm gerekir. Agent sürüm uyumluluğunu doğrular; senin adına model/API hesabı açmaz.
3. `opencode` komutunu çalıştır. Kod değişikliği yapabilen Build modunu seç.
4. `prompts/START_TR.md` dosyasının tamamını mesaja yapıştır.
5. Oturum yarıda kalırsa aynı klasörde `prompts/CONTINUE_TR.md` ile devam et. Bitince `prompts/REVIEW_TR.md` ile bağımsız bir son kontrol iste.

`/init` gerekli değildir: hazır AGENTS.md vardır. OpenCode proje kurallarını buradan; ek zorunlu belgeleri opencode.json içinden okur. Paket model seçmez ve mevcut OpenCode izin ayarlarını gevşetmez.

## Beklenen sonuç
- Aynı görsel kimlikte ana sayfa, blog ve projeler; içerik detay sayfaları.
- `/admin` üzerinden içerik, medya, taslak ve yayın yönetimi.
- Yerel PostgreSQL, S3 uyumlu medya servisi ve e-posta test servisi.
- Gerçek veritabanı migration dosyaları, testler, Docker çalıştırma ve dağıtım talimatları.
- İçeriği kod değiştirmeden ekleme; sürüm geçmişi ve güvenli önizleme.

## Hangi dosya ne işe yarar?
| Dosya | Amaç |
|---|---|
| AGENTS.md | OpenCode'un çalışma ve tamamlama kuralları |
| docs/PROJECT_SPEC.md | Ürün kapsamı ve kullanıcı akışları |
| docs/ARCHITECTURE.md | Stack, klasörler, API ve cache kararları |
| docs/CONTENT_MODEL.md | CMS alanları ve doğrulamalar |
| docs/DESIGN_AND_MOTION.md | Mevcut görünüm ve animasyonların korunması |
| docs/SECURITY.md | Yetkilendirme, medya ve preview kuralları |
| docs/IMPLEMENTATION_PLAN.md | Sıralı uygulama aşamaları |
| docs/ACCEPTANCE.md | Ölçülebilir kabul testleri |
| docs/OPERATIONS.md | Yerel kurulum, dağıtım ve yedekleme sözleşmesi |
| docs/PROGRESS.md | Oturumlar arasında güncellenecek durum |
| docs/DECISIONS.md | Kararlar ve sürüm kaydı |
| docs/SOURCES.md | Kontrol edilmiş resmi kaynaklar |
| .env.example | Agent'ın uygulayacağı ortam değişkeni sözleşmesi |
| reference-site/ | Mevcut üç sayfanın HTML/CSS/JS kaynakları |
| prompts/ | Başlatma, devam ve son kontrol mesajları |

## Senden ne gerekir?
Yerel geliştirme gerçek bulut hesabı gerektirmemeli. Üretime almak için alan adı/DNS erişimi, Docker çalıştırabilen sunucu, PostgreSQL ve S3 uyumlu depolama bilgileri, parola sıfırlama için SMTP gerekir. PostgreSQL sunucuda da çalışabilir. Bu bilgiler olmadan agent yerel sistemi tamamlamalı ve üretim adımlarını hazır bırakmalı; canlı yayına aldığını söylememeli.

Üretim e-posta adresini ve şifrelerini sohbete ya da Git'e yazma. Yerel özel `.env` veya sunucu secret ayarlarını kullan. Kullanıcı içerikleri olmadığı için üretimde örnek yazı/proje yayınlanmaz; test verileri yalnızca açıkça etkinleştirilen yerel seed ile oluşturulur.

Mevcut ChatGPT Sites yayını değişmez. `reference-site/` yalnızca tasarım referansıdır; yeni projenin hosting kimliği veya erişim anahtarı değildir. Paket, normal Node.js/Docker ortamında bağımsız bir proje içindir.

Tek prompt, model bağlam sınırı/erişim eksikliği nedeniyle her ortamda tek oturum garantisi vermez. PROGRESS.md ve devam prompt'u işi kaldığı yerden sürdürebilmek içindir.
