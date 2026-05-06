# PROGRESS.md — Hyper Reporting Geliştirme İlerleme Takibi

> Bu dosya her geliştirme oturumunda güncellenir.
> Tamamlanan adımlar ✅, devam edenler 🔄, bekleyenler ⬜ olarak işaretlenir.

---

## 📊 Genel Durum

| Faz | Ad | Durum | Tamamlanma |
|---|---|---|---|
| Faz 0 | Proje Kurulumu & Planlama | ✅ Tamamlandı | %100 |
| Faz 1 | Temel Altyapı & Operasyonel Raporlar | ⬜ Bekliyor | %0 |
| Faz 2 | Müşteri & SLA Raporları | ⬜ Bekliyor | %0 |
| Faz 3 | Analitik & Yönetici Dashboard | ⬜ Bekliyor | %0 |
| Faz 4 | Proje Raporları | ⬜ Bekliyor | %0 |
| Faz 5 | Analiz Raporları | ⬜ Bekliyor | %0 |

---

## ✅ FAZ 0 — Proje Kurulumu & Planlama (TAMAMLANDI)

### Yapılanlar
- [x] mreporting plugin analizi (prod sunucudan incelendi)
- [x] GLPI veri modeli derinlemesine incelendi
  - [x] glpi_tickets tüm kolonları haritalandı
  - [x] Entity hiyerarşisi çıkarıldı (5 seviye)
  - [x] SLA yapısı analiz edildi (PLAT-7x24-P1~P5)
  - [x] Proje tabloları incelendi
  - [x] efforttracker entegrasyon noktası belirlendi
- [x] 6 paydaş profili analiz edildi
- [x] 54+ rapor kataloğu hazırlandı (10 bölüm)
- [x] Teknik stack belirlendi
- [x] Geliştirme fazları planlandı
- [x] Plugin iskelet oluşturuldu (setup.php, hook.php)
- [x] Proje dizini yapılandırıldı
- [x] GitHub repo bağlantısı kuruldu

### Teknik Kararlar (Faz 0'da alınan)
- **DB:** Yeni tablo yok — tamamen sorgu tabanlı
- **Frontend:** Chart.js v3 + DataTables.js + Flatpickr + Select2
- **PDF:** mPDF (branded çıktı için)
- **Excel:** PhpSpreadsheet (multi-sheet)
- **RBAC:** GLPI Session + Profile bazlı erişim kontrolü
- **Dev-first:** DEV'de olgunlaşmadan PROD'a geçilmez

---

## ⬜ FAZ 1 — Temel Altyapı & Operasyonel Raporlar

### Hedef Raporlar
- [ ] 1.1 Açık Bilet Listesi
- [ ] 1.2 Yaşlanma Raporu
- [ ] 1.3 Günlük Aktivite Özeti
- [ ] 1.4 Bekleyen Biletler
- [ ] 1.5 SLA Alarm Raporu
- [ ] 2.1 Teknisyen Bilet Dağılımı
- [ ] 2.2 Ortalama Çözüm Süresi
- [ ] 2.3 İlk Yanıt Süresi
- [ ] 2.4 Anlık Yük Analizi

### Altyapı Görevleri
- [ ] Ana rapor arayüzü (front/report.php)
- [ ] Filtre sistemi (JS + PHP)
- [ ] AJAX veri endpoint (ajax/get_report_data.php)
- [ ] Chart.js entegrasyonu
- [ ] DataTables entegrasyonu
- [ ] Excel export motoru (PhpSpreadsheet)
- [ ] PDF export motoru (mPDF)
- [ ] RBAC kontrol katmanı

---

## ⬜ FAZ 2 — Müşteri & SLA Raporları

- [ ] 3.1–3.7 Müşteri/Entity raporları
- [ ] 5.1–5.6 SLA & KPI raporları
- [ ] Branded müşteri PDF şablonu

---

## ⬜ FAZ 3 — Analitik & Yönetici Dashboard

- [ ] 4.1–4.5 Kategori analizi
- [ ] 6.1–6.4 Yönetici dashboard
- [ ] 7.1–7.4 Efor & maliyet
- [ ] 8.1–8.4 Trend & heatmap

---

## ⬜ FAZ 4 — Proje Raporları

- [ ] 9.1 Proje Durum Panosu
- [ ] 9.2 Proje İlerleme Raporu
- [ ] 9.3 Gecikme Analizi
- [ ] 9.4 Görev Tipi Dağılımı
- [ ] 9.5 Planlanan vs Harcanan Süre
- [ ] 9.6 Milestone Takip
- [ ] 9.7 Proje–Ticket Entegrasyon
- [ ] 9.8 Ekip & Yük Analizi
- [ ] 9.9 Müşteri Proje Portföyü
- [ ] 9.10 Proje Detay PDF

---

## ⬜ FAZ 5 — Analiz Raporları

- [ ] Veri kaynağı belirlenmesi bekleniyor
- [ ] 10.x raporlar (TBD)

---

## 📝 Oturum Notları

### 2026-05-07 — Oturum #1
- Proje planlandı ve onaylandı
- mreporting analiz edildi, yetersizlikleri belgelendi
- GLPI prod veri modeli incelendi (gerçek verilerle)
- 54+ rapor kataloğu oluşturuldu
- Plugin iskelet hazırlandı
- GitHub repo: https://github.com/rpekgoz/hyper-reporting.git
- Dev dizin: `/var/www/glpi/plugins/hyperreporting`
- **Sonraki adım:** Faz 1 altyapı + ilk raporlar

---

## 🔖 Önemli Referanslar

| Kaynak | Detay |
|---|---|
| GitHub | https://github.com/rpekgoz/hyper-reporting |
| Dev Sunucu | 10.42.2.146 — `/var/www/glpi/plugins/hyperreporting` |
| Prod Sunucu | 10.42.2.149 — Faz tamamlanana kadar DOKUNMA |
| PLAN.md | Bu dizinde — 54+ rapor kataloğu |
| GLPI Versiyon | 11.0.6 (her iki sunucu) |
| PHP Versiyon | 8.3.x |
