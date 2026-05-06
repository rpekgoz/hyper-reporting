/**
 * Hyper Reporting — public/js/report.js
 * Filtre yönetimi, AJAX, Chart.js, DataTables
 * Author: Raşit PEKGÖZ
 */

'use strict';

/* =========================================================
   GLOBALS
   ========================================================= */
if (typeof hrChart === 'undefined') var hrChart = null;
if (typeof hrTable === 'undefined') var hrTable = null;
var hrFilters = window.HR_FILTERS || {};

/* =========================================================
   INIT
   ========================================================= */
$(document).ready(function () {
    // GLPI container white-space reset
    $('.hr-wrap').closest('.page-body, .container-fluid, #page').css({
        'background': '#0f172a',
        'padding': '0'
    });
    $('#page.legacy').css({ 'padding': '0', 'background': '#0f172a' });

    initFilters();
    initExportButton();
    loadReport(window.HR_REPORT || 'open_tickets', buildQueryParams());
});

/* =========================================================
   FİLTRE BAŞLATMA
   ========================================================= */
function initFilters() {
    // Flatpickr — tarih seçici (locale güvenli)
    var fpLocale = 'default';
    try { if (flatpickr.l10ns && flatpickr.l10ns.tr) fpLocale = 'tr'; } catch(e){}
    var fpOpts = { locale: fpLocale, dateFormat: 'Y-m-d', allowInput: true, disableMobile: true };
    flatpickr('#fp-start', fpOpts);
    flatpickr('#fp-end',   fpOpts);

    // Select2 — entity, teknisyen, öncelik (ayrı placeholder'lar)
    $('#filter-entity').select2({
        placeholder: 'Tüm Müşteriler',
        allowClear: true, width: '220px', dropdownAutoWidth: true,
    });
    $('#filter-tech').select2({
        placeholder: 'Tüm Teknisyenler',
        allowClear: true, width: '200px', dropdownAutoWidth: true,
    });
    $('#filter-priority').select2({
        placeholder: 'Tüm Öncelikler',
        allowClear: true, width: '180px', dropdownAutoWidth: true,
    });

    // "Tüm XX" (value='all') seçilince diğerlerini temizle
    ['#filter-entity','#filter-tech','#filter-priority'].forEach(function(sel) {
        $(sel).on('select2:select', function(e) {
            if (e.params.data.id === 'all') {
                $(this).val(['all']).trigger('change');
            } else {
                var vals = ($(this).val() || []).filter(function(v){ return v !== 'all'; });
                $(this).val(vals).trigger('change');
            }
        });
    });

    // Dönem değişince tarih aralığını göster/gizle ve doldur
    $('#filter-period').on('change', function () {
        const val = $(this).val();
        const now  = new Date();
        const pad  = n => String(n).padStart(2, '0');
        const fmt  = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

        let start = '', end = fmt(now);

        switch (val) {
            case 'today':
                start = end;
                break;
            case 'week': {
                const d = new Date(now);
                d.setDate(now.getDate() - 6);
                start = fmt(d);
                break;
            }
            case 'month':
                start = `${now.getFullYear()}-${pad(now.getMonth()+1)}-01`;
                break;
            case 'prev_month': {
                const d = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                const l = new Date(now.getFullYear(), now.getMonth(), 0);
                start = fmt(d); end = fmt(l);
                break;
            }
            case 'q1': {
                const d = new Date(now); d.setMonth(now.getMonth() - 3);
                start = fmt(d);
                break;
            }
            case 'half': {
                const d = new Date(now); d.setMonth(now.getMonth() - 6);
                start = fmt(d);
                break;
            }
            case 'year': {
                const d = new Date(now); d.setFullYear(now.getFullYear() - 1);
                start = fmt(d);
                break;
            }
            case 'all':
                start = '2020-01-01';
                break;
            case 'custom':
                $('#date-range-wrap').removeClass('hidden');
                return;
        }

        $('#date-range-wrap').addClass('hidden');
        document.getElementById('fp-start')._flatpickr.setDate(start);
        document.getElementById('fp-end')._flatpickr.setDate(end);
    });

    // İlk yüklemede dönem işlet
    $('#filter-period').trigger('change');
}

// initSidebar kaldırıldı — yatay nav kullanıyoruz


/* =========================================================
   EXPORT BUTONU
   ========================================================= */
function initExportButton() {
    $('#btn-xlsx').on('click', function (e) {
        e.preventDefault();
        const params = buildQueryParams();
        params.set('report', window.HR_REPORT);
        window.location.href = window.HR_XLSX + '?' + params.toString();
    });
}

/* =========================================================
   QUERY PARAMS
   ========================================================= */
function buildQueryParams() {
    const params = new URLSearchParams();

    // Dönem
    const period = $('#filter-period').val() || 'month';
    params.set('period', period);

    // Tarihler
    const startVal = document.getElementById('fp-start')?.value || hrFilters.date_start;
    const endVal   = document.getElementById('fp-end')?.value   || hrFilters.date_end;
    if (startVal) params.set('date_start', startVal);
    if (endVal)   params.set('date_end',   endVal);

    // Entity multi
    // Boş değer ve 'all' değerini filtreden dışla
    ($('#filter-entity').val() || []).filter(function(v){ return v !== '' && v !== 'all'; }).forEach(function(v){ params.append('entity_ids[]', v); });
    ($('#filter-tech').val() || []).filter(function(v){ return v !== '' && v !== 'all'; }).forEach(function(v){ params.append('tech_ids[]', v); });
    ($('#filter-priority').val() || []).filter(function(v){ return v !== '' && v !== 'all'; }).forEach(function(v){ params.append('priority[]', v); });

    // Tür
    const type = $('#filter-type').val();
    if (type && type !== '0') params.set('type', type);

    return params;
}

/* =========================================================
   RAPOR YÜKLE (AJAX)
   ========================================================= */
function loadReport(reportKey, params) {
    updateActiveFilterBadges();
    $('#btn-apply').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Yükleniyor...');
    $('#hr-loading').show();
    $('#hr-error').addClass('hidden').text('');
    $('#hr-data-table').empty();
    $('#hr-chart-wrap').addClass('hidden');

    if (hrTable) { try { hrTable.destroy(); } catch(e){} hrTable = null; }
    if (hrChart)  { hrChart.destroy(); hrChart = null; }

    params.set('report', reportKey);
    var url = HR_AJAX + '?' + params.toString();

    $.ajax({
        url:      url,
        method:   'GET',
        dataType: 'json',
        success:  function (data) {
            $('#hr-loading').hide();
            $('#btn-apply').prop('disabled', false).html('<i class="fas fa-search"></i> Uygula');
            if (data.error) {
                showError(data.error);
                return;
            }
            updateKpi(data.kpi || data.data || {});
            renderReport(reportKey, data);
        },
        error: function (xhr) {
            $('#hr-loading').hide();
            $('#btn-apply').prop('disabled', false).html('<i class="fas fa-search"></i> Uygula');
            var msg = 'Sunucu hatıası (' + xhr.status + ')';
            try { var j = JSON.parse(xhr.responseText); msg = j.error || msg; } catch(e){}
            showError(msg);
        }
    });
}

function updateActiveFilterBadges() {
    var badges = [];
    var period = $('#filter-period option:selected').text();
    if (period) badges.push('🗓️ ' + period);

    var entities = $('#filter-entity').select2('data') || [];
    entities.filter(function(d){ return d.id && d.id !== 'all'; }).forEach(function(d){
        badges.push('🏢 ' + d.text);
    });

    var techs = $('#filter-tech').select2('data') || [];
    techs.filter(function(d){ return d.id && d.id !== 'all'; }).forEach(function(d){
        badges.push('👤 ' + d.text);
    });

    var prios = $('#filter-priority').select2('data') || [];
    prios.filter(function(d){ return d.id && d.id !== 'all'; }).forEach(function(d){
        badges.push('⚡ ' + d.text);
    });

    var $bar = $('#hr-active-filters');
    if (badges.length === 0) {
        $bar.html('<span style="color:var(--hr-muted);font-size:11px">Filtre uygulanmadı — tüm veriler görünüyor</span>');
    } else {
        var html = badges.map(function(b){
            return '<span style="display:inline-flex;align-items:center;gap:4px;background:rgba(59,130,246,.15);' +
                   'border:1px solid rgba(59,130,246,.3);color:#93c5fd;border-radius:6px;' +
                   'padding:2px 10px;font-size:11px;font-weight:600">' + escHtml(b) + '</span>';
        }).join('');
        $bar.html(html);
    }
}

/* =========================================================
   KPI GÜNCELLE
   ========================================================= */
function updateKpi(kpi) {
    animateCount('#kpi-total-val',   kpi.total     ?? '—');
    animateCount('#kpi-open-val',    kpi.open      ?? '—');
    animateCount('#kpi-closed-val',  kpi.closed    ?? '—');
    const sla = kpi.sla_rate != null ? kpi.sla_rate + '%' : '—';
    $('#kpi-sla-val').text(sla);
    const avg = kpi.avg_solve != null ? kpi.avg_solve + 'sa' : '—';
    $('#kpi-avgsolve-val').text(avg);
    animateCount('#kpi-waiting-val', kpi.waiting   ?? '—');
}

function animateCount(sel, target) {
    if (isNaN(target)) { $(sel).text(target); return; }
    const end = parseInt(target, 10);
    $({ n: 0 }).animate({ n: end }, {
        duration: 600, easing: 'swing',
        step: function () { $(sel).text(Math.ceil(this.n)); },
        complete: function () { $(sel).text(end); }
    });
}

/* =========================================================
   RAPOR RENDER
   ========================================================= */
function renderReport(reportKey, data) {
    switch (reportKey) {
        case 'open_tickets':
            renderOpenTickets(data.rows || []);
            break;
        case 'aging':
            renderAging(data.chart || [], data.rows || []);
            break;
        case 'daily_activity':
            renderDailyActivity(data.data || {});
            break;
        case 'waiting':
            renderWaiting(data.rows || []);
            break;
        case 'sla_alarms':
            renderSlaAlarms(data.rows || []);
            break;
        case 'tech_distribution':
            renderTechDistribution(data.rows || []);
            break;
        case 'tech_resolution':
            renderTechResolution(data.rows || []);
            break;
        case 'tech_response':
            renderTechResponse(data.rows || []);
            break;
        case 'tech_load':
            renderTechLoad(data.rows || []);
            break;
        default:
            showError('Bilinmeyen rapor: ' + reportKey);
    }
}

/* =========================================================
   1.1 AÇIK BİLET LİSTESİ
   ========================================================= */
function renderOpenTickets(rows) {
    const cols = [
        { title: 'ID',         data: 'id',            render: r => `<a href="/front/ticket.form.php?id=${r}" target="_blank" style="color:var(--hr-accent)">#${r}</a>` },
        { title: 'Konu',       data: 'name',           render: r => escHtml(r) },
        { title: 'Öncelik',    data: 'priority',       render: (v, _, row) => priorityBadge(v, row.priority_label, row.priority_color) },
        { title: 'Durum',      data: 'status_label',   render: r => `<span class="hr-status-badge">${r}</span>` },
        { title: 'Teknisyen',  data: 'tech_name' },
        { title: 'Talep Eden', data: 'req_name' },
        { title: 'Müşteri',   data: 'entity_name',    defaultContent: '-' },
        { title: 'Kategori',   data: 'category_name',  defaultContent: '-' },
        { title: 'Yaş',        data: 'age_hours',      render: (v, _, row) => `<span class="${row.age_class || ''}">${formatAge(v)}</span>` },
        { title: 'Açılış',     data: 'date',           render: r => fmtDate(r) },
    ];
    buildTable(rows, cols);
}

/* =========================================================
   1.2 YAŞLANMA RAPORU
   ========================================================= */
function renderAging(buckets, rows) {
    if (buckets.length) {
        $('#hr-chart-wrap').removeClass('hidden');
        const ctx = document.getElementById('hr-main-chart').getContext('2d');
        hrChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels:   buckets.map(b => b.label),
                datasets: [{
                    label:           'Bilet Sayısı',
                    data:            buckets.map(b => b.count),
                    backgroundColor: buckets.map(b => b.color + 'cc'),
                    borderColor:     buckets.map(b => b.color),
                    borderWidth:     2, borderRadius: 6,
                }]
            },
            options: chartOptions('Yaşlanma Dağılımı')
        });
    }
    renderOpenTickets(rows);
}

/* =========================================================
   1.3 GÜNLÜK AKTİVİTE
   ========================================================= */
function renderDailyActivity(d) {
    animateCount('#kpi-total-val',  d.open_total ?? '—');
    animateCount('#kpi-open-val',   d.opened     ?? '—');
    animateCount('#kpi-closed-val', d.closed     ?? '—');
    animateCount('#kpi-waiting-val',d.waiting    ?? '—');
    $('#kpi-avgsolve-val').text('—');
    $('#kpi-sla-val').text('—');

    const html = `
    <div style="padding:20px;color:var(--hr-muted);text-align:center;font-size:14px;">
      <i class="fas fa-calendar-day" style="font-size:48px;color:var(--hr-accent);margin-bottom:12px;display:block"></i>
      <strong style="color:var(--hr-text);font-size:18px">${d.date || '—'}</strong> tarihi günlük özet KPI'lar yukarıda gösterilmektedir.
    </div>`;
    $('#hr-data-table').html(html);
}

/* =========================================================
   1.4 BEKLEYEN BİLETLER
   ========================================================= */
function renderWaiting(rows) {
    const cols = [
        { title: 'ID',         data: 'id',           render: r => `<a href="/front/ticket.form.php?id=${r}" target="_blank" style="color:var(--hr-accent)">#${r}</a>` },
        { title: 'Konu',       data: 'name',          render: r => escHtml(r) },
        { title: 'Öncelik',    data: 'priority',      render: (v, _, row) => priorityBadge(v, row.priority_label, row.priority_color) },
        { title: 'Teknisyen',  data: 'tech_name',     defaultContent: '-' },
        { title: 'Müşteri',   data: 'entity_name',   defaultContent: '-' },
        { title: 'Bekleme',    data: 'waiting_hours', render: v => `<span class="${ageClass(v)}">${formatAge(v)}</span>` },
        { title: 'Açılış',     data: 'date',          render: r => fmtDate(r) },
    ];
    buildTable(rows, cols);
}

/* =========================================================
   1.5 SLA ALARMLAR
   ========================================================= */
function renderSlaAlarms(rows) {
    const cols = [
        { title: 'ID',        data: 'id',              render: r => `<a href="/front/ticket.form.php?id=${r}" target="_blank" style="color:var(--hr-accent)">#${r}</a>` },
        { title: 'Konu',      data: 'name',             render: r => escHtml(r) },
        { title: 'Öncelik',   data: 'priority',         render: (v, _, row) => priorityBadge(v, row.priority_label, row.priority_color) },
        { title: 'Teknisyen', data: 'tech_name',        defaultContent: '-' },
        { title: 'Müşteri',  data: 'entity_name',      defaultContent: '-' },
        { title: 'SLA Hedef', data: 'time_to_resolve',  render: r => fmtDate(r) },
        { title: 'Kalan',     data: 'remaining_label',  render: (v, _, row) => `<span class="sla-${row.sla_status}">${v}</span>` },
    ];
    buildTable(rows, cols);
}

/* =========================================================
   2.1 TEKNİSYEN BİLET DAĞILIMI
   ========================================================= */
function renderTechDistribution(rows) {
    if (rows.length) {
        $('#hr-chart-wrap').removeClass('hidden');
        const ctx = document.getElementById('hr-main-chart').getContext('2d');
        hrChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels:   rows.map(r => r.tech_name),
                datasets: [
                    { label: 'Açık',      data: rows.map(r => r.open_count),   backgroundColor: '#f97316cc', borderColor: '#f97316', borderWidth: 2, borderRadius: 4 },
                    { label: 'Çözüldü',   data: rows.map(r => r.solved_count), backgroundColor: '#22c55ecc', borderColor: '#22c55e', borderWidth: 2, borderRadius: 4 },
                    { label: 'Kapatıldı', data: rows.map(r => r.closed_count), backgroundColor: '#3b82f6cc', borderColor: '#3b82f6', borderWidth: 2, borderRadius: 4 },
                ]
            },
            options: chartOptions('Teknisyen Bilet Dağılımı', true)
        });
    }
    const cols = [
        { title: 'Teknisyen',  data: 'tech_name' },
        { title: 'Toplam',     data: 'total',        className: 'dt-center' },
        { title: 'Açık',       data: 'open_count',   render: v => `<span style="color:var(--hr-orange)">${v}</span>`, className: 'dt-center' },
        { title: 'Çözüldü',    data: 'solved_count', render: v => `<span style="color:var(--hr-green)">${v}</span>`,  className: 'dt-center' },
        { title: 'Kapatıldı',  data: 'closed_count', render: v => `<span style="color:var(--hr-accent)">${v}</span>`, className: 'dt-center' },
    ];
    buildTable(rows, cols);
}

/* =========================================================
   2.2 ORTALAMA ÇÖZÜM SÜRESİ
   ========================================================= */
function renderTechResolution(rows) {
    if (rows.length) {
        $('#hr-chart-wrap').removeClass('hidden');
        const ctx = document.getElementById('hr-main-chart').getContext('2d');
        hrChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels:   rows.map(r => r.tech_name),
                datasets: [{
                    label:           'Ort. Çözüm (Saat)',
                    data:            rows.map(r => r.avg_solve_hours),
                    backgroundColor: '#6366f1cc',
                    borderColor:     '#6366f1',
                    borderWidth:     2, borderRadius: 6,
                }]
            },
            options: chartOptions('Ortalama Çözüm Süresi (Saat)')
        });
    }
    const cols = [
        { title: 'Teknisyen',      data: 'tech_name' },
        { title: 'Bilet Sayısı',   data: 'ticket_count',    className: 'dt-center' },
        { title: 'Ort. Çözüm (sa)',data: 'avg_solve_hours', className: 'dt-center' },
        { title: 'Min (sa)',        data: 'min_solve_hours', className: 'dt-center' },
        { title: 'Max (sa)',        data: 'max_solve_hours', className: 'dt-center' },
    ];
    buildTable(rows, cols);
}

/* =========================================================
   2.3 İLK YANIT SÜRESİ
   ========================================================= */
function renderTechResponse(rows) {
    if (rows.length) {
        $('#hr-chart-wrap').removeClass('hidden');
        const ctx = document.getElementById('hr-main-chart').getContext('2d');
        hrChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels:   rows.map(r => r.tech_name),
                datasets: [{
                    label:           'Ort. Yanıt (Dakika)',
                    data:            rows.map(r => r.avg_response_min),
                    backgroundColor: '#a855f7cc',
                    borderColor:     '#a855f7',
                    borderWidth:     2, borderRadius: 6,
                }]
            },
            options: chartOptions('Ortalama İlk Yanıt Süresi (Dakika)')
        });
    }
    const cols = [
        { title: 'Teknisyen',        data: 'tech_name' },
        { title: 'Bilet Sayısı',     data: 'ticket_count',     className: 'dt-center' },
        { title: 'Ort. Yanıt (dk)',  data: 'avg_response_min', className: 'dt-center' },
    ];
    buildTable(rows, cols);
}

/* =========================================================
   2.4 ANLÍK YÜK ANALİZİ
   ========================================================= */
function renderTechLoad(rows) {
    if (rows.length) {
        $('#hr-chart-wrap').removeClass('hidden');
        const ctx = document.getElementById('hr-main-chart').getContext('2d');
        hrChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels:   rows.map(r => r.tech_name),
                datasets: [
                    { label: 'Açık Bilet',    data: rows.map(r => r.open_count),    backgroundColor: '#3b82f6cc', borderColor: '#3b82f6', borderWidth: 2, borderRadius: 4 },
                    { label: 'Yüksek Öncelik',data: rows.map(r => r.high_priority), backgroundColor: '#f97316cc', borderColor: '#f97316', borderWidth: 2, borderRadius: 4 },
                    { label: 'SLA İhlali',    data: rows.map(r => r.sla_breached),  backgroundColor: '#ef4444cc', borderColor: '#ef4444', borderWidth: 2, borderRadius: 4 },
                ]
            },
            options: chartOptions('Anlık Teknisyen Yük Durumu', true)
        });
    }
    const cols = [
        { title: 'Teknisyen',      data: 'tech_name' },
        { title: 'Açık Bilet',     data: 'open_count',    render: v => `<span style="color:var(--hr-accent)">${v}</span>`,  className: 'dt-center' },
        { title: 'Yüksek Öncelik', data: 'high_priority', render: v => v > 0 ? `<span style="color:var(--hr-orange)">${v}</span>` : v, className: 'dt-center' },
        { title: 'SLA İhlali',     data: 'sla_breached',  render: v => v > 0 ? `<span class="sla-breached">${v}</span>` : v,           className: 'dt-center' },
    ];
    buildTable(rows, cols);
}

/* =========================================================
   DATATABLE OLUŞTUR
   ========================================================= */
function buildTable(rows, columns) {
    if (hrTable) { try { hrTable.destroy(); } catch(e){} hrTable = null; }
    $('#hr-data-table').empty();

    hrTable = $('#hr-data-table').DataTable({
        data:     rows,
        columns:  columns,
        language: {
            url:        'https://cdn.datatables.net/plug-ins/1.13.8/i18n/tr.json',
            searchPlaceholder: 'Ara...',
        },
        pageLength:      25,
        lengthMenu:      [10, 25, 50, 100],
        responsive:      true,
        order:           [[0, 'desc']],
        dom: '<"hr-dt-top"lf>rt<"hr-dt-bottom"ip>',
    });
}

/* =========================================================
   CHART AYARLARI
   ========================================================= */
function chartOptions(title, stacked = false) {
    return {
        responsive: true, maintainAspectRatio: true,
        plugins: {
            legend: { labels: { color: '#94a3b8', font: { family: 'Inter', size: 12 } } },
            title:  { display: false },
            tooltip: {
                backgroundColor: '#1e293b', borderColor: '#334155', borderWidth: 1,
                titleColor: '#f1f5f9', bodyColor: '#94a3b8',
            }
        },
        scales: {
            x: {
                stacked,
                grid:  { color: 'rgba(51,65,85,.5)' },
                ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 } }
            },
            y: {
                stacked,
                grid:  { color: 'rgba(51,65,85,.5)' },
                ticks: { color: '#94a3b8', font: { family: 'Inter', size: 11 } },
                beginAtZero: true,
            }
        }
    };
}

/* =========================================================
   YARDIMCILAR
   ========================================================= */
function showError(msg) {
    $('#hr-error').removeClass('hidden').html('<i class="fas fa-exclamation-circle"></i> ' + escHtml(msg));
}

function escHtml(str) {
    if (!str) return '-';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fmtDate(str) {
    if (!str) return '-';
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString('tr-TR', { day:'2-digit', month:'2-digit', year:'numeric' })
         + ' ' + d.toLocaleTimeString('tr-TR', { hour:'2-digit', minute:'2-digit' });
}

function formatAge(hours) {
    hours = parseInt(hours, 10) || 0;
    if (hours < 1)   return hours + ' dk';
    if (hours < 24)  return hours + ' sa';
    const d = Math.floor(hours / 24), h = hours % 24;
    return h > 0 ? `${d}g ${h}sa` : `${d} gün`;
}

function ageClass(hours) {
    hours = parseInt(hours, 10) || 0;
    if (hours < 24)  return 'age-ok';
    if (hours < 48)  return 'age-warn-low';
    if (hours < 72)  return 'age-warn';
    if (hours < 168) return 'age-danger';
    return 'age-critical';
}

function priorityBadge(val, label, color) {
    return `<span class="hr-priority" style="background:${color}22;color:${color};border:1px solid ${color}55">${label || val}</span>`;
}
