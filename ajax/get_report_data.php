<?php
/**
 * Hyper Reporting — ajax/get_report_data.php
 * JSON API endpoint — tüm raporlar için
 *
 * @author  Raşit PEKGÖZ
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

header('Content-Type: application/json; charset=utf-8');

include_once(GLPI_ROOT . '/plugins/hyperreporting/inc/report.class.php');
include_once(GLPI_ROOT . '/plugins/hyperreporting/inc/datasource.class.php');

$report  = $_GET['report'] ?? 'open_tickets';
$filters = PluginHyperreportingReport::getFilters();

try {
    $data = match($report) {
        'open_tickets'    => [
            'type'    => 'table',
            'kpi'     => PluginHyperreportingDatasource::getKpiSummary($filters),
            'rows'    => array_map(fn($r) => array_merge($r, [
                'status_label'   => PluginHyperreportingDatasource::getStatusLabel((int)$r['status']),
                'priority_label' => PluginHyperreportingDatasource::getPriorityLabel((int)$r['priority']),
                'priority_color' => PluginHyperreportingDatasource::getPriorityColor((int)$r['priority']),
                'tech_name'      => trim(($r['tech_firstname'] ?? '') . ' ' . ($r['tech_realname'] ?? '')) ?: '-',
                'req_name'       => trim(($r['req_firstname']  ?? '') . ' ' . ($r['req_realname']  ?? '')) ?: '-',
            ]), PluginHyperreportingDatasource::getOpenTickets($filters)),
        ],

        'aging'           => [
            'type'    => 'mixed',
            'kpi'     => PluginHyperreportingDatasource::getKpiSummary($filters),
            'chart'   => PluginHyperreportingDatasource::getAgingReport($filters)['buckets'],
            'rows'    => array_map(fn($r) => array_merge($r, [
                'age_label'      => self_formatAge((int)$r['age_hours']),
                'age_class'      => self_ageClass((int)$r['age_hours']),
                'status_label'   => PluginHyperreportingDatasource::getStatusLabel((int)$r['status']),
                'priority_label' => PluginHyperreportingDatasource::getPriorityLabel((int)$r['priority']),
                'priority_color' => PluginHyperreportingDatasource::getPriorityColor((int)$r['priority']),
                'tech_name'      => trim(($r['tech_firstname'] ?? '') . ' ' . ($r['tech_realname'] ?? '')) ?: '-',
                'req_name'       => trim(($r['req_firstname']  ?? '') . ' ' . ($r['req_realname']  ?? '')) ?: '-',
            ]), PluginHyperreportingDatasource::getAgingReport($filters)['tickets']),
        ],

        'daily_activity'  => [
            'type'   => 'kpi_only',
            'data'   => PluginHyperreportingDatasource::getDailyActivity($filters),
        ],

        'waiting'         => [
            'type'   => 'table',
            'kpi'    => PluginHyperreportingDatasource::getKpiSummary($filters),
            'rows'   => array_map(fn($r) => array_merge($r, [
                'waiting_label'  => self_formatAge((int)$r['waiting_hours']),
                'priority_label' => PluginHyperreportingDatasource::getPriorityLabel((int)$r['priority']),
                'priority_color' => PluginHyperreportingDatasource::getPriorityColor((int)$r['priority']),
                'tech_name'      => trim(($r['tech_firstname'] ?? '') . ' ' . ($r['tech_realname'] ?? '')) ?: '-',
            ]), PluginHyperreportingDatasource::getWaitingTickets($filters)),
        ],

        'sla_alarms'      => [
            'type'   => 'table',
            'kpi'    => PluginHyperreportingDatasource::getKpiSummary($filters),
            'rows'   => array_map(fn($r) => array_merge($r, [
                'priority_label' => PluginHyperreportingDatasource::getPriorityLabel((int)$r['priority']),
                'priority_color' => PluginHyperreportingDatasource::getPriorityColor((int)$r['priority']),
                'tech_name'      => trim(($r['tech_firstname'] ?? '') . ' ' . ($r['tech_realname'] ?? '')) ?: '-',
                'remaining_label'=> self_formatMinutes((int)$r['minutes_remaining']),
            ]), PluginHyperreportingDatasource::getSLAAlarms($filters)),
        ],

        'tech_distribution' => [
            'type'   => 'chart_table',
            'kpi'    => PluginHyperreportingDatasource::getKpiSummary($filters),
            'rows'   => PluginHyperreportingDatasource::getTechTicketDistribution($filters),
        ],

        'tech_resolution'  => [
            'type'   => 'chart_table',
            'kpi'    => PluginHyperreportingDatasource::getKpiSummary($filters),
            'rows'   => PluginHyperreportingDatasource::getTechResolutionTime($filters),
        ],

        'tech_response'    => [
            'type'   => 'chart_table',
            'kpi'    => PluginHyperreportingDatasource::getKpiSummary($filters),
            'rows'   => PluginHyperreportingDatasource::getTechFirstResponseTime($filters),
        ],

        'tech_load'        => [
            'type'   => 'chart_table',
            'kpi'    => PluginHyperreportingDatasource::getKpiSummary($filters),
            'rows'   => PluginHyperreportingDatasource::getTechCurrentLoad($filters),
        ],

        default => ['error' => 'Bilinmeyen rapor: ' . htmlspecialchars($report)],
    };
} catch (Throwable $e) {
    // NOT setting 500: GLPI intercepts 500 and replaces with {"error":true}
    echo json_encode([
        'error' => '[' . basename($e->getFile()) . ':' . $e->getLine() . '] ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// -----------------------------------------------------------------------
// Yardımcı fonksiyonlar
// -----------------------------------------------------------------------
function self_formatAge(int $hours): string
{
    if ($hours < 1)   return $hours . ' dk';
    if ($hours < 24)  return $hours . ' sa';
    $d = intdiv($hours, 24);
    $h = $hours % 24;
    return $h > 0 ? "{$d}g {$h}sa" : "{$d} gün";
}

function self_formatMinutes(int $minutes): string
{
    if ($minutes < 0) return 'İHLAL: ' . self_formatAge((int)abs($minutes / 60));
    if ($minutes < 60) return $minutes . ' dk kaldı';
    return self_formatAge((int)($minutes / 60)) . ' kaldı';
}

function self_ageClass(int $hours): string
{
    if ($hours < 24)  return 'age-ok';
    if ($hours < 48)  return 'age-warn-low';
    if ($hours < 72)  return 'age-warn';
    if ($hours < 168) return 'age-danger';
    return 'age-critical';
}
