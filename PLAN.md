# 🚀 Ultron Ticket Reporting — Tasarım & Planlama Dökümanı

> **Amaç:** Mevcut GLPI raporlama çözümlerinin çok ötesinde, enterprise-grade, modern, interaktif ve çok katmanlı paydaş ihtiyaçlarını karşılayan GLPI Ticket Raporlama Plugin'i

---

## 🔍 Mevcut GLPI Raporlama Sorunu — Neden Yeni Bir Çözüm?

| Sorun | Detay |
|---|---|
| Statik PNG grafikler | Hover, zoom, drill-down yok |
| Filtre yok | Tarih aralığı dışında hiçbir şey |
| Entity seçimi yok | Tüm şirketleri bir arada gösteriyor |
| Teknisyen bazlı analiz yok | Kim ne yaptı görülmüyor |
| SLA tracking yok | Uyum oranı hiç hesaplanmıyor |
| PDF/Excel çıktısı ilkel | Sadece grafik screenshotı |
| Efor entegrasyonu yok | efforttracker ile bağlantısız |
| Paydaşa özel görünüm yok | Herkes aynı sayfayı görüyor |
| Trend analizi yok | Geçmiş dönem karşılaştırması yok |

---

## 🏗 Veri Modeli — Ne Kullanacağız?

### Mevcut GLPI Tabloları (sorgulayacağımız)

```
glpi_tickets                    → Ana ticket verileri
  ├── status (1-6)              → New/Assigned/Planned/Waiting/Solved/Closed
  ├── priority, urgency, impact → 1-6 skalası
  ├── type (1=Incident, 2=Request)
  ├── solve_delay_stat          → Çözüm süresi (saniye)
  ├── close_delay_stat          → Kapanma süresi (saniye)
  ├── takeintoaccount_delay_stat → İlk yanıt süresi (saniye)
  ├── waiting_duration          → Bekleme süresi (saniye)
  ├── actiontime                → Toplam aksiyon süresi (saniye)
  ├── time_to_resolve           → SLA hedef tarih/saat
  └── time_to_own               → TTO hedef tarih/saat

glpi_tickets_users              → Teknisyen, Talep Eden, Gözlemci
glpi_groups_tickets             → Atanan gruplar
glpi_itilcategories             → ITIL kategori ağacı
glpi_entities                   → Şirket/müşteri hiyerarşisi
glpi_itilfollowups              → Takip notları
glpi_tickettasks                → Alt görevler + süreler
glpi_itilsolutions              → Çözüm kayıtları
glpi_ticketsatisfactions        → Memnuniyet anketi
glpi_slas + glpi_slalevels      → SLA tanımları (PLAT-7x24-P1~P5)
glpi_slalevels_tickets          → Ticket-SLA eşleşmesi
glpi_ticketcosts                → Maliyet kayıtları
glpi_plugin_efforttracker_efforts → Efor kayıtları (kendi pluginimiz)
glpi_contracts + glpi_tickets_contracts → Sözleşme bilgisi
```

### Entity Hiyerarşisi (Prod'dan alınan gerçek veri)

```
itsm (root)
├── Ultron Bilişim
│   ├── Müşteriler
│   │   ├── Sözleşme ── Müşteri_A, Müşteri_B, ...  ← Aktif müşteriler
│   │   ├── Proje    ── Proje müşterileri
│   │   └── Pasif    ── Eski müşteriler
│   ├── Internal IT
│   │   ├── Genel Destek, İzleme Alarmları, Yedekleme Alarmları
│   └── Bulutmix
└── Prime Teknoloji
    └── Çaykur, Dhmi, Ktü, İnferatech
```

---

## 👥 Paydaş Analizi — Kim Ne Görmek İster?

### 🔧 Teknisyen
**Motivasyon:** Kendi yükümdeki bitleri anlamak, SLA'mı kaçırıyor muyum?

| İhtiyaç | Rapor |
|---|---|
| Bana atanan açık biletler | Yaşlanma göstergeli liste |
| Bugün/Bu hafta kapattıklarım | Aktivite özeti |
| SLA durumum | Yeşil/sarı/kırmızı |
| Efor harcadığım süre | efforttracker entegrasyonu |

### 👨‍💼 Supervisor / Takım Lideri
**Motivasyon:** Ekip performansını yönetmek, darboğazları tespit etmek

| İhtiyaç | Rapor |
|---|---|
| Kim kaç bilet aldı/kapattı | Teknisyen yük analizi |
| En yavaş çözen kim | Ortalama çözüm süresi sıralaması |
| Backlog durumu | Yaşlanma raporu |
| SLA uyumu | Ekip bazlı SLA skoru |
| Kategori bazlı dağılım | Hangi konular yoğun |

### 🏢 Super-Admin / IT Direktörü (Ultron)
**Motivasyon:** Şirket geneli operasyonu yönetmek, raporlama yapabilmek

| İhtiyaç | Rapor |
|---|---|
| Müşteri bazlı performans | Her müşteri için KPI |
| Teknisyen karşılaştırması | Ranking |
| SLA ihlal analizi | Hangi müşteri, hangi öncelik |
| Trend analizi | Son 12 ay bilet hacmi |
| Efor & maliyet | efforttracker + ticketcost |
| Heatmap | Hangi gün/saat en yoğun |

### 👔 Genel Müdür / C-Level (Ultron)
**Motivasyon:** Üst düzey KPI kartları, sunum için özet, trend yeter

| İhtiyaç | Rapor |
|---|---|
| Toplam ticket hacmi | KPI kartı |
| Ortalama çözüm süresi | KPI kartı |
| SLA uyum oranı % | Büyük rakam, renk kodlu |
| Müşteri memnuniyeti | Yıldız/puan göstergesi |
| Aylık büyüme trendi | Sparkline grafik |
| PDF sunum raporu | Branding'li tek sayfalık özet |

### 🏭 Müşteri BT Yöneticisi
**Motivasyon:** Kendi şirketime ait biletleri görmek, hangi konular sık tekrarlıyor

| İhtiyaç | Rapor |
|---|---|
| Şirketimin tüm biletleri | Filtrelenebilir liste |
| Kategori dağılımı | Hangi konuda kaç bilet |
| Çözüm süreleri | Hizmet kalitesi değerlendirmesi |
| SLA uyumu | Bize söz verilen süre tutuldu mu |
| Bekleyen biletler | Şu an açık ne var |

### 👑 Müşteri Patronu / Yönetim Kurulu
**Motivasyon:** Dışarıdan aldığı IT hizmetinin özeti, sözleşme karşılığı alınan değer

| İhtiyaç | Rapor |
|---|---|
| Periyodik PDF | Aylık/çeyreklik hizmet raporu |
| Kaç sorun çözüldü | Toplam kapatılan |
| Ortalama yanıt süresi | Kaç dakikada yanıt aldık |
| Kritik olaylar | Yüksek öncelikli biletler özeti |
| Karşılaştırma | Bu ay vs geçen ay |

---

## 📊 Rapor Kataloğu

### BÖLÜM 1 — Operasyonel Raporlar
*Hedef: Teknisyen, Supervisor | Kullanım: Günlük*

| # | Rapor Adı | Tip | Çıktı |
|---|---|---|---|
| 1.1 | **Açık Bilet Listesi** | Tablo | Ekran + Excel |
| 1.2 | **Yaşlanma Raporu** | Tablo + Bar | Ekran + Excel + PDF |
| 1.3 | **Günlük Aktivite Özeti** | KPI kartları | Ekran |
| 1.4 | **Bekleyen Biletler** | Tablo | Ekran + Excel |
| 1.5 | **SLA Alarm Raporu** | Tablo (kırmızı vurgulu) | Ekran + Excel |
| 1.6 | **Yeniden Açılan Biletler** | Tablo | Ekran + Excel |
| 1.7 | **Haftanın Özeti** | Mixed | Ekran + PDF |

### BÖLÜM 2 — Teknisyen Performans Raporları
*Hedef: Supervisor, IT Direktörü | Kullanım: Haftalık/Aylık*

| # | Rapor Adı | Tip | Çıktı |
|---|---|---|---|
| 2.1 | **Teknisyen Bilet Dağılımı** | Bar + Tablo | Ekran + Excel + PDF |
| 2.2 | **Ortalama Çözüm Süresi** | Bar (sıralı) | Ekran + PDF |
| 2.3 | **İlk Yanıt Süresi** | Bar (sıralı) | Ekran + PDF |
| 2.4 | **Anlık Yük Analizi** | Treemap/Bar | Ekran |
| 2.5 | **Teknisyen SLA Uyum Skoru** | Pie + Tablo | Ekran + PDF |
| 2.6 | **Efor Bazlı Verimlilik** | Bar (efor/bilet) | Ekran + Excel |
| 2.7 | **Teknisyen Ranking** | Sıralı tablo + rozetler | Ekran + PDF |

### BÖLÜM 3 — Müşteri (Entity) Bazlı Raporlar
*Hedef: IT Direktörü, Müşteri BT Yöneticisi | Kullanım: Aylık*

| # | Rapor Adı | Tip | Çıktı |
|---|---|---|---|
| 3.1 | **Müşteri Bilet Dağılımı** | Bar + Tablo | Ekran + Excel + PDF |
| 3.2 | **Müşteri SLA Uyum Raporu** | Gauge + Tablo | Ekran + PDF |
| 3.3 | **Müşteri Detay Listesi** | Tablo (drilldown) | Ekran + Excel |
| 3.4 | **Müşteri Karşılaştırma** | Multi-bar | Ekran + PDF |
| 3.5 | **Müşteri Memnuniyet Raporu** | Yıldız + Trend | Ekran + PDF |
| 3.6 | **Müşteri Periyodik Raporu** | Tam sayfalı PDF | PDF (branded) |
| 3.7 | **Sözleşme Kapsama Analizi** | Tablo | Ekran + Excel |

### BÖLÜM 4 — Kategori & Problem Analizi
*Hedef: Supervisor, IT Direktörü | Kullanım: Aylık*

| # | Rapor Adı | Tip | Çıktı |
|---|---|---|---|
| 4.1 | **Kategori Dağılımı** | Pie + Tablo | Ekran + PDF |
| 4.2 | **Tekrarlayan Sorun Analizi** | Tablo (grouped) | Ekran + Excel |
| 4.3 | **Kategori Trend** | Line (12 ay) | Ekran + PDF |
| 4.4 | **Öncelik Dağılımı** | Pie + Donut | Ekran + PDF |
| 4.5 | **Tip Analizi (Incident vs Request)** | Stacked bar | Ekran + PDF |

### BÖLÜM 5 — SLA & KPI Raporları
*Hedef: IT Direktörü, Genel Müdür | Kullanım: Haftalık/Aylık*

| # | Rapor Adı | Tip | Çıktı |
|---|---|---|---|
| 5.1 | **SLA Uyum Özeti** | Büyük gauge | Ekran + PDF |
| 5.2 | **SLA İhlal Detayı** | Tablo (kırmızı) | Ekran + Excel |
| 5.3 | **TTO vs TTR Analizi** | Grouped bar | Ekran + PDF |
| 5.4 | **Çözüm Süresi Trendi (12 ay)** | Line | Ekran + PDF |
| 5.5 | **Önceliğe Göre SLA Uyumu** | Heatmap tablosu | Ekran + PDF |
| 5.6 | **Bekleme Süresi Analizi** | Histogram | Ekran + PDF |

### BÖLÜM 6 — Yönetici / Eksekütif Raporlar
*Hedef: Genel Müdür, Müşteri Patronu | Kullanım: Aylık/Çeyreklik*

| # | Rapor Adı | Tip | Çıktı |
|---|---|---|---|
| 6.1 | **Yönetici Özet Panosu** | KPI dashboard | Ekran |
| 6.2 | **Aylık Operasyonel Rapor** | Mixed (full) | PDF (branded) |
| 6.3 | **Çeyreklik Trend Raporu** | Mixed | PDF (branded) |
| 6.4 | **Müşteri Sunum Raporu** | Branded PDF | PDF |

### BÖLÜM 7 — Efor & Maliyet Raporları
*Hedef: IT Direktörü, Genel Müdür | Kullanım: Aylık*

| # | Rapor Adı | Tip | Çıktı |
|---|---|---|---|
| 7.1 | **Bilet Başına Efor** | Bar | Ekran + Excel |
| 7.2 | **Müşteri Bazlı Toplam Efor** | Bar | Ekran + Excel + PDF |
| 7.3 | **Teknisyen Efor Karşılaştırması** | Stacked bar | Ekran + PDF |
| 7.4 | **Kategori Bazlı Efor** | Pie | Ekran + PDF |

### BÖLÜM 8 — Trend & Tahmin Raporları
*Hedef: IT Direktörü, Genel Müdür | Kullanım: Aylık*

| # | Rapor Adı | Tip | Çıktı |
|---|---|---|---|
| 8.1 | **Aylık Bilet Hacim Trendi (12 ay)** | Line | Ekran + PDF |
| 8.2 | **Gün/Saat Yoğunluk Haritası** | Heatmap | Ekran + PDF |
| 8.3 | **Öncelik Trendi** | Stacked line | Ekran + PDF |
| 8.4 | **Açık vs Kapalı Karşılaştırma** | Dual-axis line | Ekran + PDF |

### BÖLÜM 9 — Proje Raporları
*Hedef: IT Direktörü, PM, Genel Müdür, Müşteri | Kullanım: Haftalık/Aylık*

> **Veri Kaynakları:** `glpi_projects`, `glpi_projecttasks`, `glpi_projecttaskteams`,
> `glpi_projectteams`, `glpi_projectcosts`, `glpi_projecttasks_tickets`,
> `glpi_projectstates` (New/Processing/Closed/Taslak), `glpi_projecttypes` (Müşteri Projesi / İç Proje)

**Gerçek Proje Yapısı (Prod'dan):**
```
Proje Tipleri  : Müşteri Projesi | İç Proje
Proje Durumları: New (🟢) | Processing (🟡) | Closed (🔴) | Taslak (🟣)
Görev Tipleri  : Analiz → Tasarım/Planlama → Kurulum/Uygulama → Test/Doğrulama
                 → Eğitim → Destek/Operasyon → Raporlama → Dokümantasyon
                 → Migration → Yedekleme → Optimizasyon → Toplantı
Alanlar        : plan_start/end_date, real_start/end_date, percent_done,
                 planned_duration, effective_duration, is_milestone
```

| # | Rapor Adı | Tip | Çıktı |
|---|---|---|---|
| 9.1 | **Proje Durum Panosu** | KPI kartları + renk kodlu tablo | Ekran |
| 9.2 | **Proje İlerleme Raporu** | Progress bar + Gantt benzeri | Ekran + PDF |
| 9.3 | **Gecikme Analizi (Schedule Variance)** | Tablo (plan vs gerçek gün farkı) | Ekran + Excel + PDF |
| 9.4 | **Görev Tipi Dağılımı** | Stacked bar (Analiz/Kurulum/Test...) | Ekran + PDF |
| 9.5 | **Planlanan vs Harcanan Süre** | Grouped bar (planned_duration vs effective_duration) | Ekran + PDF |
| 9.6 | **Milestone Takip Raporu** | Zaman çizelgesi | Ekran + PDF |
| 9.7 | **Proje–Ticket Entegrasyon Raporu** | Tablo (proje → bağlı ticketlar) | Ekran + Excel |
| 9.8 | **Ekip & Yük Analizi** | Treemap (kim hangi projede) | Ekran + PDF |
| 9.9 | **Müşteri Proje Portföyü** | Entity bazlı proje listesi | Ekran + PDF |
| 9.10 | **Proje Detay PDF** | Tek proje tam rapor (brief) | PDF (branded) |

### BÖLÜM 10 — Analiz Raporları
*Hedef: Belirlenecek | Veri Kaynağı: Belirlenecek*

> ⏳ Bu bölümün veri kaynakları ve rapor içerikleri kullanıcı tarafından iletilecektir.
> Bölüm, diğer tüm fazlar tamamlandıktan sonra ekleme olarak geliştirilecektir.

| # | Rapor Adı | Tip | Çıktı |
|---|---|---|---|
| 10.x | *(İçerik belirleniyor)* | TBD | TBD |

---

## 🔍 Filtre Sistemi

### Temel Filtreler (Her raporda)
```
[Tarih Aralığı ▼]  [Şirket/Entity ▼]  [Teknisyen ▼]  [Kategori ▼]
Bugün / Bu Hafta / Bu Ay / Geçen Ay / Son 3 Ay / Son 6 Ay / Son 12 Ay / Özel

[Tür: Tümü | Arıza | İstek]  [Öncelik: 1-6 multi]  [Durum: multi-select]
```

### Gelişmiş Filtreler (Açılır panel)
```
[Grup ▼]  [SLA ▼]  [Konum ▼]  [Sözleşme ▼]  [Talep Tipi ▼]
[SLA: Tümü | Uyumlu | İhlal Eden | SLA'sız]
[Efor: Var | Yok | Tümü]
```

### RBAC Filtre Kısıtları
```
Super-Admin    → Tüm filtreler, tüm entity'ler
IT Direktörü   → Tüm filtreler, tüm entity'ler
Supervisor     → Kendi grubunun teknisyenleri, kendi entity'leri
Technician     → Sadece kendisi, kendi müşterileri
Müşteri profil → Sadece kendi entity'si, Bölüm 3 ve 6 erişimi
```

---

## 🎨 Teknik Stack

### Frontend
| Kütüphane | Kullanım |
|---|---|
| **Chart.js v3** | Bar, Line, Pie, Doughnut, Radar grafikleri |
| **DataTables.js** | Sortable, searchable, paginated tablolar |
| **Select2** | Çoklu seçim dropdown'ları |
| **Flatpickr** | Tarih aralığı seçici |
| **Font Awesome 5** | İkonlar |

### Backend (PHP)
| Kütüphane | Kullanım |
|---|---|
| **GLPI DBmysqlIterator** | Tüm DB sorguları |
| **PhpSpreadsheet** | XLSX export (multi-sheet, formüllü) |
| **mPDF veya TCPDF** | PDF export (branded, sayfalı) |
| **GLPI RBAC API** | Profil bazlı erişim kontrolü |

### Yeni DB Tablosu Gerekmez
> Plugin tamamen sorgu tabanlı çalışır. Sadece raporun kaydedilmesi  
> istenirse `glpi_plugin_ultronticketreporting_saved_filters` tablosu eklenir.

---

## 🗂 Dosya Yapısı (Planlanan)

```
ultronticketreporting/
├── ajax/
│   ├── get_report_data.php       API endpoint — tüm raporlar için
│   ├── export_pdf.php            PDF çıktı
│   └── export_xlsx.php           Excel çıktı
├── front/
│   └── report.php                Ana rapor arayüzü
├── inc/
│   ├── report.class.php          Rapor yönlendirme + RBAC
│   ├── datasource.class.php      Tüm SQL sorguları
│   ├── export_pdf.class.php      PDF üretici
│   └── export_xlsx.class.php     XLSX üretici
├── public/
│   ├── css/report.css            Modern arayüz stilleri
│   └── js/
│       ├── report.js             Ana JS (filtreler, AJAX)
│       ├── charts.js             Chart.js konfigürasyonu
│       └── datatables.js         Tablo konfigürasyonu
├── docs/
│   └── mockup.png
├── hook.php
├── setup.php
├── README.md
├── INSTALL.md
└── IMPLEMENTATION.md
```

---

## 🖥 Arayüz Tasarımı

```
┌────────────────────────────────────────────────────────────────┐
│  📊 Ultron Ticket Reporting                                    │
│                                                                │
│  [Operasyonel ▼] [Performans ▼] [Müşteri ▼] [SLA ▼]         │
│  [Efor ▼] [Trend ▼] [Yönetici ▼]                             │
│                                                                │
│  ┌──────────────────────── FİLTRELER ──────────────────────┐  │
│  │ Tarih: [Bu Ay ▼]  Entity: [Tümü ▼]  Teknisyen: [Tümü ▼]│  │
│  │ Tür: [●Tümü ○Arıza ○İstek]  Öncelik: [□1□2■3■4□5□6]   │  │
│  │ [Gelişmiş ▼]               [🔍 Uygula] [⬇PDF] [⬇Excel]│  │
│  └─────────────────────────────────────────────────────────┘  │
│                                                                │
│  ┌─KPI─┐ ┌─KPI─┐ ┌─KPI─┐ ┌─KPI─┐                           │
│  │ 608 │ │ 96% │ │9.7h │ │ 4.2 │                           │
│  │Bilet│ │ SLA │ │Çözüm│ │⭐Mec│                           │
│  └─────┘ └─────┘ └─────┘ └─────┘                           │
│                                                                │
│  [📊 Bar Grafik]              [🥧 Pie Grafik]                 │
│                                                                │
│  [📋 Detay Tablo — DataTables, sıralanabilir, aranabilir]    │
└────────────────────────────────────────────────────────────────┘
```

---

## ⚡ Geliştirme Öncelik Sırası

### Faz 1 — Temel (MVP)
1. Plugin iskelet (setup.php, hook.php, RBAC) ✅ Hazır
2. Ana rapor arayüzü + filtre sistemi
3. Bölüm 1: Operasyonel raporlar (1.1 - 1.5)
4. Bölüm 2: Teknisyen performans (2.1 - 2.4)
5. Excel + PDF export motoru

### Faz 2 — Müşteri & SLA
6. Bölüm 3: Müşteri/Entity raporları
7. Bölüm 5: SLA & KPI raporları
8. Branded müşteri PDF raporu

### Faz 3 — Analitik & Yönetici
9. Bölüm 4: Kategori analizi
10. Bölüm 6: Yönetici dashboard
11. Bölüm 7: Efor & maliyet
12. Bölüm 8: Trend & heatmap

### Faz 4 — Proje
13. Bölüm 9: Proje raporları (9.1 - 9.10)
    - Proje durum panosu + ilerleme
    - Gecikme analizi (plan vs gerçek)
    - Milestone takip
    - Proje–Ticket entegrasyonu
    - Branded proje detay PDF

### Faz 5 — Analiz (Veri kaynağı belirlenecek)
14. Bölüm 10: Analiz raporları

---

## 📋 Toplam Rapor Sayısı

| Bölüm | Rapor Sayısı |
|---|---|
| 1. Operasyonel | 7 |
| 2. Teknisyen | 7 |
| 3. Müşteri/Entity | 7 |
| 4. Kategori | 5 |
| 5. SLA & KPI | 6 |
| 6. Yönetici | 4 |
| 7. Efor & Maliyet | 4 |
| 8. Trend | 4 |
| 9. Proje | 10 |
| 10. Analiz | TBD |
| **TOPLAM** | **54+ rapor** |

> Her rapor: Ekran görünümü + filtreleme + Excel + PDF = 4 çıktı modu
