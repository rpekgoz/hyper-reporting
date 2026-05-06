<?php
/**
 * Hyper Reporting — ajax/export_xlsx.php
 * PhpSpreadsheet ile Excel export (GLPI bundled)
 *
 * @author  Raşit PEKGÖZ
 */

include('../../../inc/includes.php');
Session::checkLoginUser();

include_once(GLPI_ROOT . '/plugins/hyperreporting/inc/report.class.php');
include_once(GLPI_ROOT . '/plugins/hyperreporting/inc/datasource.class.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

$report  = $_GET['report'] ?? 'open_tickets';
$filters = PluginHyperreportingReport::getFilters();

// Veriyi al
$rows    = [];
$headers = [];
$title   = 'Hyper Reporting';

switch ($report) {
    case 'open_tickets':
        $title   = 'Açık Bilet Listesi';
        $data    = PluginHyperreportingDatasource::getOpenTickets($filters);
        $headers = ['ID', 'Konu', 'Açılış Tarihi', 'Durum', 'Öncelik', 'Teknisyen', 'Müşteri', 'Kategori', 'Yaş (Saat)'];
        foreach ($data as $r) {
            $rows[] = [
                '#' . $r['id'], $r['name'],
                date('d.m.Y H:i', strtotime($r['date'])),
                PluginHyperreportingDatasource::getStatusLabel((int)$r['status']),
                PluginHyperreportingDatasource::getPriorityLabel((int)$r['priority']),
                trim(($r['tech_firstname'] ?? '') . ' ' . ($r['tech_realname'] ?? '')) ?: '-',
                $r['entity_name'] ?? '-', $r['category_name'] ?? '-', $r['age_hours'] ?? 0,
            ];
        }
        break;

    case 'tech_distribution':
        $title   = 'Teknisyen Bilet Dağılımı';
        $data    = PluginHyperreportingDatasource::getTechTicketDistribution($filters);
        $headers = ['Teknisyen', 'Toplam', 'Açık', 'Çözüldü', 'Kapatıldı'];
        foreach ($data as $r) {
            $rows[] = [$r['tech_name'], $r['total'], $r['open_count'], $r['solved_count'], $r['closed_count']];
        }
        break;

    case 'tech_resolution':
        $title   = 'Ortalama Çözüm Süresi';
        $data    = PluginHyperreportingDatasource::getTechResolutionTime($filters);
        $headers = ['Teknisyen', 'Bilet Sayısı', 'Ort. Çözüm (Saat)', 'Min (Saat)', 'Max (Saat)'];
        foreach ($data as $r) {
            $rows[] = [$r['tech_name'], $r['ticket_count'], $r['avg_solve_hours'], $r['min_solve_hours'], $r['max_solve_hours']];
        }
        break;

    case 'tech_response':
        $title   = 'İlk Yanıt Süresi';
        $data    = PluginHyperreportingDatasource::getTechFirstResponseTime($filters);
        $headers = ['Teknisyen', 'Bilet Sayısı', 'Ort. Yanıt (Dakika)'];
        foreach ($data as $r) {
            $rows[] = [$r['tech_name'], $r['ticket_count'], $r['avg_response_min']];
        }
        break;

    case 'sla_alarms':
        $title   = 'SLA Alarm Raporu';
        $data    = PluginHyperreportingDatasource::getSLAAlarms($filters);
        $headers = ['ID', 'Konu', 'Öncelik', 'SLA Hedef', 'Kalan (Dk)', 'Durum', 'Teknisyen', 'Müşteri'];
        foreach ($data as $r) {
            $rows[] = [
                '#' . $r['id'], $r['name'],
                PluginHyperreportingDatasource::getPriorityLabel((int)$r['priority']),
                $r['time_to_resolve'] ? date('d.m.Y H:i', strtotime($r['time_to_resolve'])) : '-',
                $r['minutes_remaining'], strtoupper($r['sla_status']),
                trim(($r['tech_firstname'] ?? '') . ' ' . ($r['tech_realname'] ?? '')) ?: '-',
                $r['entity_name'] ?? '-',
            ];
        }
        break;

    default:
        $title   = 'Açık Bilet Listesi';
        $data    = PluginHyperreportingDatasource::getOpenTickets($filters);
        $headers = ['ID', 'Konu', 'Tarih', 'Durum', 'Öncelik', 'Teknisyen', 'Müşteri'];
        foreach ($data as $r) {
            $rows[] = ['#' . $r['id'], $r['name'], date('d.m.Y H:i', strtotime($r['date'])),
                PluginHyperreportingDatasource::getStatusLabel((int)$r['status']),
                PluginHyperreportingDatasource::getPriorityLabel((int)$r['priority']),
                trim(($r['tech_firstname'] ?? '') . ' ' . ($r['tech_realname'] ?? '')) ?: '-',
                $r['entity_name'] ?? '-'];
        }
}

// Spreadsheet oluştur
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle(mb_substr($title, 0, 31));

// Başlık satırı
$sheet->fromArray([["Hyper Reporting — $title"]], null, 'A1');
$sheet->mergeCells('A1:' . chr(64 + count($headers)) . '1');
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);
$sheet->getRowDimension(1)->setRowHeight(24);

// Filtre bilgisi
$filterInfo = "Tarih: {$filters['date_start']} → {$filters['date_end']}  |  Oluşturuldu: " . date('d.m.Y H:i');
$sheet->fromArray([[$filterInfo]], null, 'A2');
$sheet->mergeCells('A2:' . chr(64 + count($headers)) . '2');
$sheet->getStyle('A2')->applyFromArray([
    'font'      => ['italic' => true, 'color' => ['rgb' => '555555']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'f1f5f9']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
]);

// Kolon başlıkları (satır 3)
$sheet->fromArray([$headers], null, 'A3');
$lastCol = chr(64 + count($headers));
$sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563eb']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '93c5fd']]],
]);

// Veri satırları (satır 4'ten itibaren)
$rowNum = 4;
foreach ($rows as $i => $row) {
    $sheet->fromArray([$row], null, "A{$rowNum}");
    $bg = ($i % 2 === 0) ? 'FFFFFF' : 'f8fafc';
    $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
        'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e2e8f0']]],
    ]);
    $rowNum++;
}

// Kolon genişlikleri otomatik
foreach (range('A', $lastCol) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Footer
$sheet->fromArray([['© Hyper Reporting — Raşit PEKGÖZ']], null, "A{$rowNum}");
$sheet->mergeCells("A{$rowNum}:{$lastCol}{$rowNum}");
$sheet->getStyle("A{$rowNum}")->getFont()->setItalic(true)->setColor(
    (new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB('94a3b8')
);

// Output
$filename = 'hyper-reporting-' . $report . '-' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
