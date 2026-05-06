# Hyper Reporting — GLPI Plugin

> **Enterprise-grade, çok boyutlu GLPI raporlama plugin'i.**
> 54+ rapor, 10 bölüm, 6 paydaş profili, interaktif grafikler, PDF & Excel çıktısı.
> Modern arayüz, gerçek zamanlı filtreler, branded çıktılar.

---

## ✨ Özellikler (Planlanan)

| Bölüm | İçerik | Rapor |
|---|---|---|
| 1. Operasyonel | Açık biletler, yaşlanma, SLA alarm | 7 |
| 2. Teknisyen | Performans, yük, SLA skoru, ranking | 7 |
| 3. Müşteri | Entity bazlı KPI, memnuniyet, portföy | 7 |
| 4. Kategori | Dağılım, tekrarlayan sorunlar, trend | 5 |
| 5. SLA & KPI | TTO/TTR analizi, ihlal detayı | 6 |
| 6. Yönetici | Executive dashboard, branded PDF | 4 |
| 7. Efor | efforttracker entegrasyonu, maliyet | 4 |
| 8. Trend | 12 aylık trend, heatmap, tahmin | 4 |
| 9. Proje | Gantt, gecikme analizi, milestone | 10 |
| 10. Analiz | *(Veri kaynağı belirleniyor)* | TBD |

---

## 🔧 Gereksinimler

- **GLPI** ≥ 10.0 (GLPI 11 desteklenir)
- **PHP** ≥ 8.0
- Önerilen: `efforttracker` plugin (Efor raporları için)

---

## 🚀 Kurulum

Bkz. [INSTALL.md](INSTALL.md)

---

## 📐 Teknik Mimari

Bkz. [PLAN.md](PLAN.md) — Tam rapor kataloğu, filtre sistemi, teknik stack

## 📈 Geliştirme İlerlemesi

Bkz. [PROGRESS.md](PROGRESS.md) — Faz bazlı ilerleme takibi

---

## 🗺 Geliştirme Fazları

```
Faz 0 ✅  Planlama & Kurulum
Faz 1 🔄  Temel Altyapı + Operasyonel Raporlar
Faz 2 ⬜  Müşteri & SLA Raporları
Faz 3 ⬜  Analitik & Yönetici Dashboard
Faz 4 ⬜  Proje Raporları
Faz 5 ⬜  Analiz Raporları
```

> **Geliştirme Kuralı:** DEV'de olgunlaşmadan PROD'a geçilmez.

---

## 👤 Geliştirici

**Raşit PEKGÖZ** — [github.com/rpekgoz](https://github.com/rpekgoz)

## 📄 Lisans

GPLv2+
