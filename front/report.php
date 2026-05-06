<?php
/**
 * Hyper Reporting — front/report.php
 * @author Raşit PEKGÖZ
 */
include('../../../inc/includes.php');
Session::checkLoginUser();

include_once(GLPI_ROOT . '/plugins/hyperreporting/inc/report.class.php');
include_once(GLPI_ROOT . '/plugins/hyperreporting/inc/datasource.class.php');

$filters  = PluginHyperreportingReport::getFilters();
$entities = PluginHyperreportingDatasource::getEntityOptions();
$techs    = PluginHyperreportingDatasource::getTechOptions();

Html::header('Hyper Reporting', $_SERVER['PHP_SELF'], 'tools', 'plugins');

$baseUrl = Plugin::getWebDir('hyperreporting');
$ajaxUrl = $baseUrl . '/ajax/get_report_data.php';
$xlsxUrl = $baseUrl . '/ajax/export_xlsx.php';

echo '
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="' . $baseUrl . '/public/css/report.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
';

$sections = [
    'operational' => ['icon'=>'fa-list-alt', 'label'=>'Operasyonel', 'reports'=>[
        'open_tickets'   => ['icon'=>'fa-ticket-alt',      'label'=>'Açık Bilet Listesi'],
        'aging'          => ['icon'=>'fa-hourglass-half',  'label'=>'Yaşlanma'],
        'daily_activity' => ['icon'=>'fa-calendar-day',    'label'=>'Günlük Aktivite'],
        'waiting'        => ['icon'=>'fa-pause-circle',    'label'=>'Bekleyenler'],
        'sla_alarms'     => ['icon'=>'fa-exclamation-triangle','label'=>'SLA Alarmları'],
    ]],
    'performance' => ['icon'=>'fa-user-clock','label'=>'Teknisyen','reports'=>[
        'tech_distribution'=>['icon'=>'fa-chart-bar',     'label'=>'Bilet Dağılımı'],
        'tech_resolution'  =>['icon'=>'fa-stopwatch',     'label'=>'Çözüm Süresi'],
        'tech_response'    =>['icon'=>'fa-bolt',          'label'=>'İlk Yanıt'],
        'tech_load'        =>['icon'=>'fa-weight-hanging','label'=>'Anlık Yük'],
    ]],
];

$curSection = $filters['section'];
$curReport  = $filters['report'];
$activeLabel = 'Rapor';
foreach ($sections as $sk => $sv) {
    foreach ($sv['reports'] as $rk => $rv) {
        if ($rk === $curReport) $activeLabel = $rv['label'];
    }
}
?>
<div class="hr-wrap">

  <!-- TOP BAR -->
  <div class="hr-topbar">
    <h1 class="hr-page-title">
      <i class="fas fa-chart-bar"></i>
      Hyper Reporting
      <span style="color:var(--hr-muted);font-size:14px;font-weight:400;margin-left:8px">/ <?= htmlspecialchars($activeLabel) ?></span>
    </h1>
    <div class="hr-export-btns">
      <a id="btn-xlsx" href="#" class="hr-btn hr-btn-green"><i class="fas fa-file-excel"></i> Excel</a>
    </div>
  </div>

  <!-- SECTION NAV -->
  <div class="hr-section-nav">
    <?php foreach ($sections as $sk => $sv): ?>
    <a href="?section=<?= $sk ?>&report=<?= array_key_first($sv['reports']) ?>"
       class="hr-section-btn <?= $curSection === $sk ? 'active' : '' ?>">
      <i class="fas <?= $sv['icon'] ?>"></i> <?= $sv['label'] ?>
    </a>
    <?php endforeach; ?>
    <span class="hr-section-btn disabled"><i class="fas fa-building"></i> Müşteri <span class="hr-badge">Faz 2</span></span>
    <span class="hr-section-btn disabled"><i class="fas fa-shield-alt"></i> SLA & KPI <span class="hr-badge">Faz 2</span></span>
    <span class="hr-section-btn disabled"><i class="fas fa-crown"></i> Yönetici <span class="hr-badge">Faz 3</span></span>
    <span class="hr-section-btn disabled"><i class="fas fa-project-diagram"></i> Proje <span class="hr-badge">Faz 4</span></span>
    <span class="hr-section-btn disabled"><i class="fas fa-brain"></i> Analiz <span class="hr-badge">Faz 5</span></span>
    <span style="margin-left:auto;font-size:10px;color:var(--hr-muted);padding:7px 8px;">v<?= PLUGIN_HYPERREPORTING_VERSION ?> · Raşit PEKGÖZ</span>
  </div>

  <!-- REPORT NAV -->
  <?php if (isset($sections[$curSection])): ?>
  <div class="hr-report-nav">
    <?php foreach ($sections[$curSection]['reports'] as $rk => $rv): ?>
    <a href="?section=<?= $curSection ?>&report=<?= $rk ?>"
       class="hr-report-btn <?= $curReport === $rk ? 'active' : '' ?>">
      <i class="fas <?= $rv['icon'] ?>"></i> <?= $rv['label'] ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- CONTENT -->
  <div class="hr-content">

    <!-- FİLTRELER -->
    <form id="hr-filter-form" method="GET" action="">
      <input type="hidden" name="section" value="<?= $curSection ?>">
      <input type="hidden" name="report"  value="<?= $curReport ?>">
      <div class="hr-filters">
        <div class="hr-filter-group">
          <label>Dönem</label>
          <select name="period" id="filter-period" class="hr-select">
            <?php foreach ([
              'today'=>'Bugün','week'=>'Bu Hafta','month'=>'Bu Ay',
              'prev_month'=>'Geçen Ay','q1'=>'Son 3 Ay','half'=>'Son 6 Ay',
              'year'=>'Son 12 Ay','all'=>'Tüm Zamanlar','custom'=>'Özel'
            ] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $filters['period']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="hr-filter-group date-range-group <?= $filters['period']==='custom'?'':'hidden' ?>" id="date-range-wrap">
          <label>Başlangıç</label>
          <input type="text" name="date_start" id="fp-start" class="hr-input" value="<?= $filters['date_start'] ?>">
          <label>Bitiş</label>
          <input type="text" name="date_end" id="fp-end" class="hr-input" value="<?= $filters['date_end'] ?>">
        </div>
        <div class="hr-filter-group">
          <label>Müşteri</label>
          <select name="entity_ids[]" id="filter-entity" class="hr-select2" multiple>
            <option value="all">— Tüm Müşteriler —</option>
            <?php foreach ($entities as $e): ?>
            <option value="<?= $e['id'] ?>" <?= in_array($e['id'],$filters['entity_ids'])?'selected':'' ?>>
              <?= htmlspecialchars($e['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="hr-filter-group">
          <label>Teknisyen</label>
          <select name="tech_ids[]" id="filter-tech" class="hr-select2" multiple>
            <option value="all">— Tüm Teknisyenler —</option>
            <?php foreach ($techs as $t): ?>
            <option value="<?= $t['id'] ?>" <?= in_array($t['id'],$filters['tech_ids'])?'selected':'' ?>>
              <?= htmlspecialchars(trim($t['firstname'].' '.$t['realname'])) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="hr-filter-group">
          <label>Öncelik</label>
          <select name="priority[]" id="filter-priority" class="hr-select2" multiple>
            <option value="all">— Tüm Öncelikler —</option>
            <?php foreach ([1=>'Çok Düşük',2=>'Düşük',3=>'Orta',4=>'Yüksek',5=>'Çok Yüksek',6=>'Kritik'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= in_array($v,$filters['priority'])?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="hr-filter-group">
          <label>Tür</label>
          <select name="type" id="filter-type" class="hr-select">
            <option value="0" <?= $filters['type']===0?'selected':'' ?>>Tümü</option>
            <option value="1" <?= $filters['type']===1?'selected':'' ?>>Arıza</option>
            <option value="2" <?= $filters['type']===2?'selected':'' ?>>İstek</option>
          </select>
        </div>
        <div class="hr-filter-actions">
          <button type="submit" id="btn-apply" class="hr-btn hr-btn-primary"><i class="fas fa-search"></i> Uygula</button>
          <a href="?section=<?= $curSection ?>&report=<?= $curReport ?>" class="hr-btn hr-btn-ghost"><i class="fas fa-redo"></i></a>
        </div>
      </div>
    </form>

    <!-- AKTİF FİLTRE BAR -->
    <div id="hr-active-filters" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;min-height:24px;margin-bottom:12px;padding:4px 0">
      <span style="color:var(--hr-muted);font-size:11px">Filtreler yükleniyor...</span>
    </div>

    <!-- KPI -->
    <div class="hr-kpi-bar">
      <div class="hr-kpi-card">
        <div class="hr-kpi-icon"><i class="fas fa-ticket-alt"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-total-val">—</span><span class="hr-kpi-lbl">Toplam Bilet</span></div>
      </div>
      <div class="hr-kpi-card">
        <div class="hr-kpi-icon kpi-orange"><i class="fas fa-folder-open"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-open-val">—</span><span class="hr-kpi-lbl">Açık</span></div>
      </div>
      <div class="hr-kpi-card">
        <div class="hr-kpi-icon kpi-green"><i class="fas fa-check-circle"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-closed-val">—</span><span class="hr-kpi-lbl">Kapatıldı</span></div>
      </div>
      <div class="hr-kpi-card">
        <div class="hr-kpi-icon kpi-blue"><i class="fas fa-shield-alt"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-sla-val">—</span><span class="hr-kpi-lbl">SLA Uyumu</span></div>
      </div>
      <div class="hr-kpi-card">
        <div class="hr-kpi-icon kpi-purple"><i class="fas fa-stopwatch"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-avgsolve-val">—</span><span class="hr-kpi-lbl">Ort. Çözüm (sa)</span></div>
      </div>
      <div class="hr-kpi-card">
        <div class="hr-kpi-icon kpi-yellow"><i class="fas fa-pause-circle"></i></div>
        <div class="hr-kpi-body"><span class="hr-kpi-val" id="kpi-waiting-val">—</span><span class="hr-kpi-lbl">Bekliyor</span></div>
      </div>
    </div>

    <!-- CHART -->
    <div id="hr-chart-wrap" class="hr-chart-wrap hidden">
      <canvas id="hr-main-chart"></canvas>
    </div>

    <!-- TABLE -->
    <div class="hr-table-wrap">
      <div id="hr-loading" class="hr-loading"><i class="fas fa-spinner fa-spin"></i> Yükleniyor...</div>
      <div id="hr-error" class="hr-error hidden"></div>
      <table id="hr-data-table" style="width:100%"></table>
    </div>

  </div><!-- /hr-content -->
</div><!-- /hr-wrap -->

<script>
var HR_AJAX    = '<?= $ajaxUrl ?>';
var HR_XLSX    = '<?= $xlsxUrl ?>';
var HR_REPORT  = '<?= $curReport ?>';
var HR_FILTERS = <?= json_encode($filters, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= $baseUrl ?>/public/js/report.js"></script>
<?php Html::footer(); ?>
