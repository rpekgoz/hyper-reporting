<?php
/**
 * Hyper Reporting — front/report.php
 * Ana rapor arayüzü
 *
 * @author  Raşit PEKGÖZ
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

include_once(GLPI_ROOT . '/plugins/hyperreporting/inc/report.class.php');
include_once(GLPI_ROOT . '/plugins/hyperreporting/inc/datasource.class.php');

$filters  = PluginHyperreportingReport::getFilters();
$entities = PluginHyperreportingDatasource::getEntityOptions();
$techs    = PluginHyperreportingDatasource::getTechOptions();
$cats     = PluginHyperreportingDatasource::getCategoryOptions();

Html::header('Hyper Reporting', $_SERVER['PHP_SELF'], 'tools', 'PluginHyperreportingReport');

// CDN yüklemeleri
echo <<<HTML
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/plugins/hyperreporting/public/css/report.css">
HTML;

$baseUrl  = Plugin::getWebDir('hyperreporting');
$ajaxUrl  = $baseUrl . '/ajax/get_report_data.php';
$xlsxUrl  = $baseUrl . '/ajax/export_xlsx.php';

// Section & report menu yapısı
$sections = [
    'operational' => [
        'icon'    => 'fa-list-alt',
        'label'   => 'Operasyonel',
        'reports' => [
            'open_tickets'   => ['icon' => 'fa-ticket-alt',     'label' => 'Açık Bilet Listesi'],
            'aging'          => ['icon' => 'fa-hourglass-half', 'label' => 'Yaşlanma Raporu'],
            'daily_activity' => ['icon' => 'fa-calendar-day',   'label' => 'Günlük Aktivite'],
            'waiting'        => ['icon' => 'fa-pause-circle',    'label' => 'Bekleyen Biletler'],
            'sla_alarms'     => ['icon' => 'fa-exclamation-triangle','label' => 'SLA Alarmları'],
        ],
    ],
    'performance' => [
        'icon'    => 'fa-user-clock',
        'label'   => 'Teknisyen',
        'reports' => [
            'tech_distribution' => ['icon' => 'fa-chart-bar',   'label' => 'Bilet Dağılımı'],
            'tech_resolution'   => ['icon' => 'fa-stopwatch',   'label' => 'Çözüm Süresi'],
            'tech_response'     => ['icon' => 'fa-bolt',         'label' => 'İlk Yanıt Süresi'],
            'tech_load'         => ['icon' => 'fa-weight-hanging','label' => 'Anlık Yük Analizi'],
        ],
    ],
];

$curSection = $filters['section'];
$curReport  = $filters['report'];

// Aktif rapor etiketini bul
$activeLabel = 'Rapor';
foreach ($sections as $sk => $sv) {
    foreach ($sv['reports'] as $rk => $rv) {
        if ($rk === $curReport) { $activeLabel = $rv['label']; }
    }
}
?>
<div class="hr-wrap">

  <!-- ====== SIDEBAR ====== -->
  <aside class="hr-sidebar">
    <div class="hr-logo">
      <i class="fas fa-chart-bar"></i>
      <span>Hyper<strong>Reporting</strong></span>
    </div>

    <nav class="hr-nav">
      <?php foreach ($sections as $sKey => $sec): ?>
      <div class="hr-nav-section <?= $curSection === $sKey ? 'active-section' : '' ?>">
        <div class="hr-nav-section-title" data-section="<?= $sKey ?>">
          <i class="fas <?= $sec['icon'] ?>"></i> <?= $sec['label'] ?>
          <i class="fas fa-chevron-down hr-chevron"></i>
        </div>
        <div class="hr-nav-items <?= $curSection === $sKey ? 'open' : '' ?>">
          <?php foreach ($sec['reports'] as $rKey => $rep): ?>
          <a href="?section=<?= $sKey ?>&report=<?= $rKey ?>"
             class="hr-nav-item <?= $curReport === $rKey ? 'active' : '' ?>">
            <i class="fas <?= $rep['icon'] ?>"></i> <?= $rep['label'] ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Gelmekte olan bölümler -->
      <div class="hr-nav-section coming-soon-section">
        <div class="hr-nav-section-title">
          <i class="fas fa-building"></i> Müşteri
          <span class="hr-badge">Faz 2</span>
        </div>
      </div>
      <div class="hr-nav-section coming-soon-section">
        <div class="hr-nav-section-title">
          <i class="fas fa-tasks"></i> SLA & KPI
          <span class="hr-badge">Faz 2</span>
        </div>
      </div>
      <div class="hr-nav-section coming-soon-section">
        <div class="hr-nav-section-title">
          <i class="fas fa-crown"></i> Yönetici
          <span class="hr-badge">Faz 3</span>
        </div>
      </div>
      <div class="hr-nav-section coming-soon-section">
        <div class="hr-nav-section-title">
          <i class="fas fa-project-diagram"></i> Proje
          <span class="hr-badge">Faz 4</span>
        </div>
      </div>
    </nav>

    <div class="hr-sidebar-footer">v<?= PLUGIN_HYPERREPORTING_VERSION ?> · Raşit PEKGÖZ</div>
  </aside>

  <!-- ====== MAIN ====== -->
  <main class="hr-main">

    <!-- Başlık -->
    <div class="hr-topbar">
      <h1 class="hr-page-title">
        <i class="fas fa-chart-bar"></i> <?= htmlspecialchars($activeLabel) ?>
      </h1>
      <div class="hr-export-btns">
        <a id="btn-xlsx" href="#" class="hr-btn hr-btn-green">
          <i class="fas fa-file-excel"></i> Excel
        </a>
      </div>
    </div>

    <!-- Filtreler -->
    <form id="hr-filter-form" method="GET" action="">
      <input type="hidden" name="section" value="<?= $curSection ?>">
      <input type="hidden" name="report"  value="<?= $curReport ?>">

      <div class="hr-filters">
        <!-- Dönem -->
        <div class="hr-filter-group">
          <label>Dönem</label>
          <select name="period" id="filter-period" class="hr-select">
            <option value="today"      <?= $filters['period']==='today'      ?'selected':'' ?>>Bugün</option>
            <option value="week"       <?= $filters['period']==='week'       ?'selected':'' ?>>Bu Hafta</option>
            <option value="month"      <?= $filters['period']==='month'      ?'selected':'' ?>>Bu Ay</option>
            <option value="prev_month" <?= $filters['period']==='prev_month' ?'selected':'' ?>>Geçen Ay</option>
            <option value="q1"         <?= $filters['period']==='q1'         ?'selected':'' ?>>Son 3 Ay</option>
            <option value="half"       <?= $filters['period']==='half'       ?'selected':'' ?>>Son 6 Ay</option>
            <option value="year"       <?= $filters['period']==='year'       ?'selected':'' ?>>Son 12 Ay</option>
            <option value="custom"     <?= $filters['period']==='custom'     ?'selected':'' ?>>Özel Aralık</option>
            <option value="all"        <?= $filters['period']==='all'        ?'selected':'' ?>>Tüm Zamanlar</option>
          </select>
        </div>

        <!-- Özel tarih -->
        <div class="hr-filter-group date-range-group <?= $filters['period']==='custom' ? '' : 'hidden' ?>" id="date-range-wrap">
          <label>Başlangıç</label>
          <input type="text" name="date_start" id="fp-start" class="hr-input" value="<?= $filters['date_start'] ?>">
          <label style="margin-left:4px">Bitiş</label>
          <input type="text" name="date_end" id="fp-end" class="hr-input" value="<?= $filters['date_end'] ?>">
        </div>

        <!-- Müşteri -->
        <div class="hr-filter-group">
          <label>Müşteri</label>
          <select name="entity_ids[]" id="filter-entity" class="hr-select2" multiple>
            <?php foreach ($entities as $e): ?>
            <option value="<?= $e['id'] ?>"
              <?= in_array($e['id'], $filters['entity_ids']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($e['completename']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Teknisyen -->
        <div class="hr-filter-group">
          <label>Teknisyen</label>
          <select name="tech_ids[]" id="filter-tech" class="hr-select2" multiple>
            <?php foreach ($techs as $t): ?>
            <option value="<?= $t['id'] ?>"
              <?= in_array($t['id'], $filters['tech_ids']) ? 'selected' : '' ?>>
              <?= htmlspecialchars(trim($t['firstname'] . ' ' . $t['realname'])) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Öncelik -->
        <div class="hr-filter-group">
          <label>Öncelik</label>
          <select name="priority[]" id="filter-priority" class="hr-select2" multiple>
            <option value="1" <?= in_array(1,$filters['priority'])?'selected':'' ?>>Çok Düşük</option>
            <option value="2" <?= in_array(2,$filters['priority'])?'selected':'' ?>>Düşük</option>
            <option value="3" <?= in_array(3,$filters['priority'])?'selected':'' ?>>Orta</option>
            <option value="4" <?= in_array(4,$filters['priority'])?'selected':'' ?>>Yüksek</option>
            <option value="5" <?= in_array(5,$filters['priority'])?'selected':'' ?>>Çok Yüksek</option>
            <option value="6" <?= in_array(6,$filters['priority'])?'selected':'' ?>>Kritik</option>
          </select>
        </div>

        <!-- Tür -->
        <div class="hr-filter-group">
          <label>Tür</label>
          <select name="type" id="filter-type" class="hr-select">
            <option value="0" <?= $filters['type']===0?'selected':'' ?>>Tümü</option>
            <option value="1" <?= $filters['type']===1?'selected':'' ?>>Arıza</option>
            <option value="2" <?= $filters['type']===2?'selected':'' ?>>İstek</option>
          </select>
        </div>

        <div class="hr-filter-actions">
          <button type="submit" class="hr-btn hr-btn-primary">
            <i class="fas fa-search"></i> Uygula
          </button>
          <a href="?section=<?= $curSection ?>&report=<?= $curReport ?>" class="hr-btn hr-btn-ghost">
            <i class="fas fa-redo"></i>
          </a>
        </div>
      </div>
    </form>

    <!-- KPI Kartları -->
    <div id="hr-kpi-bar" class="hr-kpi-bar">
      <div class="hr-kpi-card" id="kpi-total">
        <div class="hr-kpi-icon"><i class="fas fa-ticket-alt"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-total-val">—</span><span class="hr-kpi-lbl">Toplam Bilet</span></div>
      </div>
      <div class="hr-kpi-card" id="kpi-open">
        <div class="hr-kpi-icon kpi-orange"><i class="fas fa-folder-open"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-open-val">—</span><span class="hr-kpi-lbl">Açık</span></div>
      </div>
      <div class="hr-kpi-card" id="kpi-closed">
        <div class="hr-kpi-icon kpi-green"><i class="fas fa-check-circle"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-closed-val">—</span><span class="hr-kpi-lbl">Kapatıldı</span></div>
      </div>
      <div class="hr-kpi-card" id="kpi-sla">
        <div class="hr-kpi-icon kpi-blue"><i class="fas fa-shield-alt"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-sla-val">—</span><span class="hr-kpi-lbl">SLA Uyumu</span></div>
      </div>
      <div class="hr-kpi-card" id="kpi-avgsolve">
        <div class="hr-kpi-icon kpi-purple"><i class="fas fa-stopwatch"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-avgsolve-val">—</span><span class="hr-kpi-lbl">Ort. Çözüm (sa)</span></div>
      </div>
      <div class="hr-kpi-card" id="kpi-waiting">
        <div class="hr-kpi-icon kpi-yellow"><i class="fas fa-pause-circle"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-waiting-val">—</span><span class="hr-kpi-lbl">Bekliyor</span></div>
      </div>
    </div>

    <!-- Grafik alanı -->
    <div id="hr-chart-wrap" class="hr-chart-wrap hidden">
      <canvas id="hr-main-chart"></canvas>
    </div>

    <!-- Tablo alanı -->
    <div class="hr-table-wrap">
      <div id="hr-loading" class="hr-loading"><i class="fas fa-spinner fa-spin"></i> Yükleniyor...</div>
      <div id="hr-error" class="hr-error hidden"></div>
      <table id="hr-data-table" class="display" style="width:100%"></table>
    </div>

  </main>
</div>

<script>
const HR_AJAX = '<?= $ajaxUrl ?>';
const HR_XLSX = '<?= $xlsxUrl ?>';
const HR_REPORT  = '<?= $curReport ?>';
const HR_FILTERS = <?= json_encode($filters, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= $baseUrl ?>/public/js/report.js"></script>

<?php Html::footer(); ?>
