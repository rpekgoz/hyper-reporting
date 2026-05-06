<?php
/**
 * Hyper Reporting — inc/report.class.php
 * Ana sınıf: GLPI menü kaydı, RBAC, sayfa render
 *
 * @author  Raşit PEKGÖZ
 * @license GPLv2+
 */

class PluginHyperreportingReport extends CommonGLPI
{
    static function getTypeName($nb = 0)
    {
        return 'Hyper Reporting';
    }

    static function getMenuName()
    {
        return 'Hyper Reporting';
    }

    static function getMenuContent()
    {
        return [
            'title' => 'Hyper Reporting',
            'page'  => '/plugins/hyperreporting/front/report.php',
            'icon'  => 'fas fa-chart-bar',
        ];
    }

    static function canCreate(): bool { return Session::haveRight('config', READ); }
    static function canView(): bool   { return Session::getLoginUserID() > 0; }

    // -----------------------------------------------------------------------
    // RBAC — Kullanıcının görebileceği entity listesi
    // -----------------------------------------------------------------------

    static function getAllowedEntityIds(): array
    {
        if (Session::isAdmin() || Session::isSuperAdmin()) {
            // Super-admin: tüm aktif entity'ler
            global $DB;
            $rows = $DB->request(['SELECT' => ['id'], 'FROM' => 'glpi_entities']);
            return array_column(iterator_to_array($rows), 'id');
        }
        // Normal kullanıcı: GLPI'nin profil bazlı entity listesi
        return array_keys($_SESSION['glpiactiveentities'] ?? [0 => 0]);
    }

    static function getAllowedTechIds(): array
    {
        global $DB;
        $uid = Session::getLoginUserID();

        if (Session::isAdmin() || Session::isSuperAdmin()) {
            // Tüm aktif teknisyenleri getir
            $rows = $DB->request([
                'SELECT'     => ['DISTINCT glpi_tickets_users.users_id'],
                'FROM'       => 'glpi_tickets_users',
                'WHERE'      => ['type' => 2],
            ]);
            return array_column(iterator_to_array($rows), 'users_id');
        }

        // Supervisor → kendi grubundaki userlar (basit: tüm teknisyenler)
        // Technician → sadece kendisi
        return [$uid];
    }

    // -----------------------------------------------------------------------
    // Filtre: GET/SESSION kaynaklı filtre değerlerini oku
    // -----------------------------------------------------------------------

    static function getFilters(): array
    {
        $now = date('Y-m-d');
        return [
            'period'      => $_GET['period']      ?? 'month',
            'date_start'  => $_GET['date_start']  ?? date('Y-m-01'),
            'date_end'    => $_GET['date_end']     ?? $now,
            'entity_ids'  => isset($_GET['entity_ids'])
                             ? array_filter(array_map('intval', (array)$_GET['entity_ids']))
                             : [],
            'tech_ids'    => isset($_GET['tech_ids'])
                             ? array_filter(array_map('intval', (array)$_GET['tech_ids']))
                             : [],
            'cat_id'      => (int)($_GET['cat_id']   ?? 0),
            'priority'    => isset($_GET['priority'])
                             ? array_filter(array_map('intval', (array)$_GET['priority']))
                             : [],
            'type'        => (int)($_GET['type']     ?? 0),
            'status'      => isset($_GET['status'])
                             ? array_filter(array_map('intval', (array)$_GET['status']))
                             : [],
            'report'      => $_GET['report']      ?? 'open_tickets',
            'section'     => $_GET['section']     ?? 'operational',
        ];
    }
}
