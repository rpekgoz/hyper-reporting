# Hyper Reporting — Geliştirme İlerleme Kaydı

**Proje:** [rpekgoz/hyper-reporting](https://github.com/rpekgoz/hyper-reporting)
**Geliştirici:** Raşit PEKGÖZ
**Ortam:** DEV → `https://itsm-dev.ultron.com.tr` (PROD'a dokunulmaz)

---

## ✅ FAZ 1 — Tamamlanan Çalışmalar

### Oturum 1 — 2026-05-06 / 2026-05-07

#### Altyapı
- [x] Plugin iskeleti (`setup.php`, `hook.php`, `plugin.xml`)
- [x] GLPI RBAC entegrasyonu — `PluginHyperreportingReport`
- [x] Sıfır veritabanı tablosu mimarisi (canlı sorgu)
- [x] GLPI Tools menüsüne bağlantı
- [x] GitHub reposu: `rpekgoz/hyper-reporting`

#### Raporlar (9 adet — Faz 1 Operasyonel & Teknisyen)
- [x] 1.1 Açık Bilet Listesi
- [x] 1.2 Yaşlanma Raporu (yaş bucket'ları + bar grafik)
- [x] 1.3 Günlük Aktivite (KPI özet)
- [x] 1.4 Bekleyen Biletler
- [x] 1.5 SLA Alarm Raporu
- [x] 2.1 Teknisyen Bilet Dağılımı (stacked bar)
- [x] 2.2 Ortalama Çözüm Süresi
- [x] 2.3 İlk Yanıt Süresi
- [x] 2.4 Anlık Yük Analizi

#### Frontend
- [x] Dark mode UI — Inter font, glassmorphism tarzı kartlar
- [x] Yatay bölüm navigasyonu (GLPI sidebar çakışmasız)
- [x] Rapor alt-sekme navigasyonu
- [x] Flatpickr tarih aralığı seçici
- [x] Select2 çok seçimli filtreler
- [x] Chart.js v4 görselleştirme
- [x] DataTables TR dilli tablo
- [x] KPI kartları (animasyonlu sayaç)
- [x] Excel export butonu (PhpSpreadsheet)

#### Bug Düzeltmeleri (Oturum 1)
| # | Hata | Çözüm |
|---|---|---|
| 1 | `canCreate()` PHP8 imza hatası | `: bool` return type eklendi |
| 2 | `Session::isAdmin()` yok | `Session::haveRight('config', UPDATE)` |
| 3 | `Session::isSuperAdmin()` yok | `Session::haveRight('config', UPDATE)` |
| 4 | `menu_toadd` session check içindeydi | Session check dışına alındı |
| 5 | `Html::header()` 4. param | `'plugins'` olarak düzeltildi |
| 6 | CDN echo Header öncesindeydi | Header sonrasına taşındı |
| 7 | DISTINCT in SELECT | GROUPBY pattern'e çevrildi |
| 8 | `[new QueryExpression()]` sarımı | Direkt QE kullanımı |
| 9 | AND in ON JOIN | CASE WHEN + GROUP BY |
| 10 | GLPI 500 intercept → `{"error":true}` | http_response_code(500) kaldırıldı |
| 11 | Beyaz boşluklar | JS + CSS container padding sıfırlandı |
| 12 | Müşteri ağaç yolu | `completename` → `name` |
| 13 | Select2 placeholder yok | Per-filter Tüm XX placeholder |
| 14 | "Tüm XX" seçeneği dropdown'da yok | `<option value="">` ilk seçenek olarak eklendi |

---

## 🔄 FAZ 1 — Devam Eden / Test Edilecek

- [ ] AJAX hatası kök nedeni tam doğrulama (gerçek hata mesajı görülecek)
- [ ] Excel export test
- [ ] Filtre kombinasyonları test (entity + tech + period)
- [ ] Teknisyen sekme raporları test
- [ ] SLA Alarmları SLA renk badgeleri test

---

## 📋 FAZ 2 — Planlanan (Müşteri / Entity Analitik)

- [ ] 3.1 Müşteri bazlı bilet özeti
- [ ] 3.2 Müşteri SLA uyumu
- [ ] 3.3 Entity karşılaştırmalı analiz
- [ ] 3.4 Müşteri trend grafiği (aylık)
- [ ] Faz 2 CSS bileşenleri

---

## 📋 FAZ 3 — Planlanan (Yönetici Dashboard)

- [ ] Executive summary dashboard
- [ ] Haftalık/aylık özet maili (opsiyonel)
- [ ] Genel müdür rapor çıktısı

---

## 📋 FAZ 4 — Planlanan (Proje Analitik)

- [ ] `glpi_projects` + `glpi_projecttasks_tickets` entegrasyonu
- [ ] Proje bazlı bilet yükü
- [ ] Milestone takibi

---

## 📋 FAZ 5 — Planlanan (Analiz)

- [ ] Veri kaynağı belirlenecek
- [ ] Tahminsel analiz modülleri

---

## 🔧 Teknik Notlar

### GLPI 11 Uyumluluk
```
Session::isAdmin()      → YOK
Session::isSuperAdmin() → YOK
Kullan: Session::haveRight('config', UPDATE)

QueryExpression:
- WHERE içinde DOĞRUDAN: $where[] = new QueryExpression(...)
- Yanlış: $where[] = [new QueryExpression(...)]

JOIN - AND in ON: Desteklenmiyor
Kullan: basit JOIN + GROUP BY + CASE WHEN

http_response_code(500): GLPI framework intercept eder
Kullan: 200 ile JSON error response
```

### DEV Deployment
```bash
# Tek komutla deploy
cd local/hyper-reporting && tar czf /tmp/hr.tar.gz . && \
scp /tmp/hr.tar.gz [SSH_USER]@[DEV_SERVER_IP]:/tmp/ && \
ssh [SSH_USER]@[DEV_SERVER_IP] "sudo tar xzf /tmp/hr.tar.gz -C [GLPI_PLUGINS_PATH]/hyperreporting/ && \
  sudo chown -R www-data:www-data [GLPI_PLUGINS_PATH]/hyperreporting/ && \
  sudo systemctl reload php8.2-fpm"
```

### Mimari Kararlar
- **Sıfır DB tablo:** Plugin kendi tablo oluşturmuyor, tüm veriler GLPI canlı sorgularından
- **Hardcode yok:** Tüm URL'ler `Plugin::getWebDir()`, tüm auth `Session::haveRight()`
- **DEV-FIRST:** PROD'a Faz 1 tamamen olgunlaşmadan dokunulmaz
