<?php
/**
 * Hyper Reporting — inc/datasource.class.php
 * Tüm SQL sorguları merkezi sınıf
 *
 * @author  Raşit PEKGÖZ
 * @license GPLv2+
 */

class PluginHyperreportingDatasource
{
    // -----------------------------------------------------------------------
    // Yardımcı: filtrelerden WHERE koşulları üret
    // -----------------------------------------------------------------------
    private static function buildWhere(array $f, string $alias = 't'): array
    {
        $where = ["{$alias}.is_deleted" => 0];

        // Entity kısıtı
        $allowed = PluginHyperreportingReport::getAllowedEntityIds();
        $entities = (!empty($f['entity_ids']))
            ? array_intersect($f['entity_ids'], $allowed)
            : $allowed;
        if (!empty($entities)) {
            $where["{$alias}.entities_id"] = $entities;
        }

        // Tarih filtresi
        if (!empty($f['date_start'])) {
            $where[] = ["{$alias}.date" => ['>=', $f['date_start'] . ' 00:00:00']];
        }
        if (!empty($f['date_end'])) {
            $where[] = ["{$alias}.date" => ['<=', $f['date_end'] . ' 23:59:59']];
        }

        // Öncelik
        if (!empty($f['priority'])) {
            $where["{$alias}.priority"] = $f['priority'];
        }

        // Tür (1=Arıza, 2=İstek)
        if (!empty($f['type'])) {
            $where["{$alias}.type"] = $f['type'];
        }

        return $where;
    }

    // -----------------------------------------------------------------------
    // 1.1 — Açık Bilet Listesi
    // -----------------------------------------------------------------------
    public static function getOpenTickets(array $f): array
    {
        global $DB;
        $where = self::buildWhere($f);
        $where['t.status'] = [1, 2, 3, 4]; // New, Assigned, Planned, Waiting

        if (!empty($f['status'])) {
            $where['t.status'] = $f['status'];
        }

        $iterator = $DB->request([
            'SELECT'    => [
                't.id', 't.name', 't.date', 't.status', 't.priority', 't.type',
                't.time_to_resolve',
                'e.name AS entity_name',
                'ic.completename AS category_name',
                'u.firstname AS tech_firstname', 'u.realname AS tech_realname',
                'req.firstname AS req_firstname', 'req.realname AS req_realname',
                new QueryExpression('TIMESTAMPDIFF(HOUR, t.date, NOW()) AS age_hours'),
            ],
            'FROM'      => 'glpi_tickets AS t',
            'LEFT JOIN' => [
                'glpi_entities AS e'         => ['ON' => ['e' => 'id', 't' => 'entities_id']],
                'glpi_itilcategories AS ic'  => ['ON' => ['ic' => 'id', 't' => 'itilcategories_id']],
                'glpi_tickets_users AS tu'   => ['ON' => ['tu' => 'tickets_id', 't' => 'id', ['AND' => ['tu.type' => 2]]]],
                'glpi_users AS u'            => ['ON' => ['u' => 'id', 'tu' => 'users_id']],
                'glpi_tickets_users AS tureq'=> ['ON' => ['tureq' => 'tickets_id', 't' => 'id', ['AND' => ['tureq.type' => 1]]]],
                'glpi_users AS req'          => ['ON' => ['req' => 'id', 'tureq' => 'users_id']],
            ],
            'WHERE'     => $where,
            'ORDER'     => 't.date DESC',
            'LIMIT'     => 500,
        ]);

        return iterator_to_array($iterator);
    }

    // -----------------------------------------------------------------------
    // 1.2 — Yaşlanma Raporu (Age buckets)
    // -----------------------------------------------------------------------
    public static function getAgingReport(array $f): array
    {
        $tickets = self::getOpenTickets($f);
        $buckets = [
            'lt24'  => ['label' => '< 24 Saat',  'count' => 0, 'color' => '#22c55e'],
            '24_48' => ['label' => '24–48 Saat',  'count' => 0, 'color' => '#84cc16'],
            '48_72' => ['label' => '48–72 Saat',  'count' => 0, 'color' => '#eab308'],
            '72_7d' => ['label' => '3–7 Gün',     'count' => 0, 'color' => '#f97316'],
            'gt7d'  => ['label' => '> 7 Gün',     'count' => 0, 'color' => '#ef4444'],
        ];

        foreach ($tickets as $t) {
            $h = (int)$t['age_hours'];
            if ($h < 24)       $buckets['lt24']['count']++;
            elseif ($h < 48)   $buckets['24_48']['count']++;
            elseif ($h < 72)   $buckets['48_72']['count']++;
            elseif ($h < 168)  $buckets['72_7d']['count']++;
            else               $buckets['gt7d']['count']++;
        }

        return ['buckets' => array_values($buckets), 'tickets' => $tickets];
    }

    // -----------------------------------------------------------------------
    // 1.3 — Günlük Aktivite Özeti
    // -----------------------------------------------------------------------
    public static function getDailyActivity(array $f): array
    {
        global $DB;
        $allowed = PluginHyperreportingReport::getAllowedEntityIds();
        $today   = date('Y-m-d');

        $base = ['glpi_tickets.is_deleted' => 0, 'glpi_tickets.entities_id' => $allowed];

        $opened = iterator_to_array($DB->request([
            'COUNT'  => 'id',
            'FROM'   => 'glpi_tickets',
            'WHERE'  => $base + [new QueryExpression("DATE(date) = '$today'")],
        ]));

        $closed = iterator_to_array($DB->request([
            'COUNT'  => 'id',
            'FROM'   => 'glpi_tickets',
            'WHERE'  => $base + ['status' => 6, new QueryExpression("DATE(closedate) = '$today'")],
        ]));

        $solved = iterator_to_array($DB->request([
            'COUNT'  => 'id',
            'FROM'   => 'glpi_tickets',
            'WHERE'  => $base + ['status' => 5, new QueryExpression("DATE(solvedate) = '$today'")],
        ]));

        $waiting = iterator_to_array($DB->request([
            'COUNT'  => 'id',
            'FROM'   => 'glpi_tickets',
            'WHERE'  => $base + ['status' => 4],
        ]));

        $open_total = iterator_to_array($DB->request([
            'COUNT'  => 'id',
            'FROM'   => 'glpi_tickets',
            'WHERE'  => $base + ['status' => [1, 2, 3, 4]],
        ]));

        return [
            'opened'     => (int)($opened[0]['COUNT']    ?? 0),
            'closed'     => (int)($closed[0]['COUNT']    ?? 0),
            'solved'     => (int)($solved[0]['COUNT']    ?? 0),
            'waiting'    => (int)($waiting[0]['COUNT']   ?? 0),
            'open_total' => (int)($open_total[0]['COUNT'] ?? 0),
            'date'       => $today,
        ];
    }

    // -----------------------------------------------------------------------
    // 1.4 — Bekleyen Biletler (status=4 Waiting)
    // -----------------------------------------------------------------------
    public static function getWaitingTickets(array $f): array
    {
        $f_waiting = $f;
        $f_waiting['status'] = [4];
        // Tarih filtresini kaldır — tüm bekleyenler görünsün
        unset($f_waiting['date_start'], $f_waiting['date_end']);

        global $DB;
        $where = ['t.is_deleted' => 0, 't.status' => 4];
        $allowed = PluginHyperreportingReport::getAllowedEntityIds();
        $entities = (!empty($f['entity_ids']))
            ? array_intersect($f['entity_ids'], $allowed)
            : $allowed;
        if (!empty($entities)) {
            $where['t.entities_id'] = $entities;
        }

        $iterator = $DB->request([
            'SELECT'    => [
                't.id', 't.name', 't.date', 't.begin_waiting_date', 't.priority',
                'e.name AS entity_name',
                'u.firstname AS tech_firstname', 'u.realname AS tech_realname',
                new QueryExpression('TIMESTAMPDIFF(HOUR, t.begin_waiting_date, NOW()) AS waiting_hours'),
            ],
            'FROM'      => 'glpi_tickets AS t',
            'LEFT JOIN' => [
                'glpi_entities AS e'       => ['ON' => ['e' => 'id', 't' => 'entities_id']],
                'glpi_tickets_users AS tu' => ['ON' => ['tu' => 'tickets_id', 't' => 'id', ['AND' => ['tu.type' => 2]]]],
                'glpi_users AS u'          => ['ON' => ['u' => 'id', 'tu' => 'users_id']],
            ],
            'WHERE'     => $where,
            'ORDER'     => 't.begin_waiting_date ASC',
        ]);

        return iterator_to_array($iterator);
    }

    // -----------------------------------------------------------------------
    // 1.5 — SLA Alarm Raporu
    // -----------------------------------------------------------------------
    public static function getSLAAlarms(array $f): array
    {
        global $DB;
        $allowed = PluginHyperreportingReport::getAllowedEntityIds();
        $entities = (!empty($f['entity_ids']))
            ? array_intersect($f['entity_ids'], $allowed)
            : $allowed;

        $where = [
            't.is_deleted'       => 0,
            't.status'           => [1, 2, 3, 4],
            new QueryExpression('t.time_to_resolve IS NOT NULL'),
        ];
        if (!empty($entities)) {
            $where['t.entities_id'] = $entities;
        }

        $iterator = $DB->request([
            'SELECT'    => [
                't.id', 't.name', 't.date', 't.time_to_resolve', 't.priority', 't.status',
                'e.name AS entity_name',
                'u.firstname AS tech_firstname', 'u.realname AS tech_realname',
                'ic.completename AS category_name',
                new QueryExpression('TIMESTAMPDIFF(MINUTE, NOW(), t.time_to_resolve) AS minutes_remaining'),
            ],
            'FROM'      => 'glpi_tickets AS t',
            'LEFT JOIN' => [
                'glpi_entities AS e'       => ['ON' => ['e' => 'id', 't' => 'entities_id']],
                'glpi_tickets_users AS tu' => ['ON' => ['tu' => 'tickets_id', 't' => 'id', ['AND' => ['tu.type' => 2]]]],
                'glpi_users AS u'          => ['ON' => ['u' => 'id', 'tu' => 'users_id']],
                'glpi_itilcategories AS ic'=> ['ON' => ['ic' => 'id', 't' => 'itilcategories_id']],
            ],
            'WHERE'     => $where,
            'ORDER'     => 't.time_to_resolve ASC',
        ]);

        $rows = iterator_to_array($iterator);
        foreach ($rows as &$r) {
            $min = (int)$r['minutes_remaining'];
            if ($min < 0)        $r['sla_status'] = 'breached';
            elseif ($min < 60)   $r['sla_status'] = 'critical';
            elseif ($min < 240)  $r['sla_status'] = 'warning';
            else                 $r['sla_status'] = 'ok';
        }
        return $rows;
    }

    // -----------------------------------------------------------------------
    // 2.1 — Teknisyen Bilet Dağılımı
    // -----------------------------------------------------------------------
    public static function getTechTicketDistribution(array $f): array
    {
        global $DB;
        $where = self::buildWhere($f);

        if (!empty($f['status'])) {
            $where['t.status'] = $f['status'];
        }

        $iterator = $DB->request([
            'SELECT'    => [
                'u.id AS user_id',
                new QueryExpression("CONCAT(u.firstname, ' ', u.realname) AS tech_name"),
                new QueryExpression('COUNT(DISTINCT t.id) AS total'),
                new QueryExpression('SUM(CASE WHEN t.status IN (1,2,3,4) THEN 1 ELSE 0 END) AS open_count'),
                new QueryExpression('SUM(CASE WHEN t.status = 6 THEN 1 ELSE 0 END) AS closed_count'),
                new QueryExpression('SUM(CASE WHEN t.status = 5 THEN 1 ELSE 0 END) AS solved_count'),
            ],
            'FROM'      => 'glpi_tickets AS t',
            'INNER JOIN'=> [
                'glpi_tickets_users AS tu' => ['ON' => ['tu' => 'tickets_id', 't' => 'id', ['AND' => ['tu.type' => 2]]]],
                'glpi_users AS u'          => ['ON' => ['u' => 'id', 'tu' => 'users_id']],
            ],
            'WHERE'     => $where,
            'GROUPBY'   => ['u.id', 'u.firstname', 'u.realname'],
            'ORDER'     => 'total DESC',
        ]);

        return iterator_to_array($iterator);
    }

    // -----------------------------------------------------------------------
    // 2.2 — Ortalama Çözüm Süresi (Teknisyen bazlı)
    // -----------------------------------------------------------------------
    public static function getTechResolutionTime(array $f): array
    {
        global $DB;
        $where = self::buildWhere($f);
        $where['t.status'] = [5, 6];
        $where[] = [new QueryExpression('t.solve_delay_stat > 0')];

        $iterator = $DB->request([
            'SELECT'    => [
                'u.id AS user_id',
                new QueryExpression("CONCAT(u.firstname, ' ', u.realname) AS tech_name"),
                new QueryExpression('COUNT(DISTINCT t.id) AS ticket_count'),
                new QueryExpression('ROUND(AVG(t.solve_delay_stat) / 3600, 1) AS avg_solve_hours'),
                new QueryExpression('ROUND(MIN(t.solve_delay_stat) / 3600, 1) AS min_solve_hours'),
                new QueryExpression('ROUND(MAX(t.solve_delay_stat) / 3600, 1) AS max_solve_hours'),
            ],
            'FROM'      => 'glpi_tickets AS t',
            'INNER JOIN'=> [
                'glpi_tickets_users AS tu' => ['ON' => ['tu' => 'tickets_id', 't' => 'id', ['AND' => ['tu.type' => 2]]]],
                'glpi_users AS u'          => ['ON' => ['u' => 'id', 'tu' => 'users_id']],
            ],
            'WHERE'     => $where,
            'GROUPBY'   => ['u.id', 'u.firstname', 'u.realname'],
            'ORDER'     => 'avg_solve_hours ASC',
        ]);

        return iterator_to_array($iterator);
    }

    // -----------------------------------------------------------------------
    // 2.3 — İlk Yanıt Süresi (Teknisyen bazlı)
    // -----------------------------------------------------------------------
    public static function getTechFirstResponseTime(array $f): array
    {
        global $DB;
        $where = self::buildWhere($f);
        $where[] = [new QueryExpression('t.takeintoaccount_delay_stat > 0')];

        $iterator = $DB->request([
            'SELECT'    => [
                'u.id AS user_id',
                new QueryExpression("CONCAT(u.firstname, ' ', u.realname) AS tech_name"),
                new QueryExpression('COUNT(DISTINCT t.id) AS ticket_count'),
                new QueryExpression('ROUND(AVG(t.takeintoaccount_delay_stat) / 60, 1) AS avg_response_min'),
            ],
            'FROM'      => 'glpi_tickets AS t',
            'INNER JOIN'=> [
                'glpi_tickets_users AS tu' => ['ON' => ['tu' => 'tickets_id', 't' => 'id', ['AND' => ['tu.type' => 2]]]],
                'glpi_users AS u'          => ['ON' => ['u' => 'id', 'tu' => 'users_id']],
            ],
            'WHERE'     => $where,
            'GROUPBY'   => ['u.id', 'u.firstname', 'u.realname'],
            'ORDER'     => 'avg_response_min ASC',
        ]);

        return iterator_to_array($iterator);
    }

    // -----------------------------------------------------------------------
    // 2.4 — Anlık Yük Analizi (şu an kimde kaç açık bilet)
    // -----------------------------------------------------------------------
    public static function getTechCurrentLoad(array $f): array
    {
        global $DB;
        $allowed = PluginHyperreportingReport::getAllowedEntityIds();

        $iterator = $DB->request([
            'SELECT'    => [
                'u.id AS user_id',
                new QueryExpression("CONCAT(u.firstname, ' ', u.realname) AS tech_name"),
                new QueryExpression('COUNT(DISTINCT t.id) AS open_count'),
                new QueryExpression('SUM(CASE WHEN t.priority >= 5 THEN 1 ELSE 0 END) AS high_priority'),
                new QueryExpression('SUM(CASE WHEN t.time_to_resolve < NOW() AND t.time_to_resolve IS NOT NULL THEN 1 ELSE 0 END) AS sla_breached'),
            ],
            'FROM'      => 'glpi_tickets AS t',
            'INNER JOIN'=> [
                'glpi_tickets_users AS tu' => ['ON' => ['tu' => 'tickets_id', 't' => 'id', ['AND' => ['tu.type' => 2]]]],
                'glpi_users AS u'          => ['ON' => ['u' => 'id', 'tu' => 'users_id']],
            ],
            'WHERE'     => [
                't.is_deleted'   => 0,
                't.status'       => [1, 2, 3, 4],
                't.entities_id'  => $allowed,
            ],
            'GROUPBY'   => ['u.id', 'u.firstname', 'u.realname'],
            'ORDER'     => 'open_count DESC',
        ]);

        return iterator_to_array($iterator);
    }

    // -----------------------------------------------------------------------
    // KPI Kartları için özet istatistikler
    // -----------------------------------------------------------------------
    public static function getKpiSummary(array $f): array
    {
        global $DB;
        $allowed = PluginHyperreportingReport::getAllowedEntityIds();
        $entities = (!empty($f['entity_ids']))
            ? array_intersect($f['entity_ids'], $allowed)
            : $allowed;

        $base = ['t.is_deleted' => 0];
        if (!empty($entities)) {
            $base['t.entities_id'] = $entities;
        }
        if (!empty($f['date_start'])) {
            $base[] = ['t.date' => ['>=', $f['date_start'] . ' 00:00:00']];
        }
        if (!empty($f['date_end'])) {
            $base[] = ['t.date' => ['<=', $f['date_end'] . ' 23:59:59']];
        }

        // Toplam
        $total_r = iterator_to_array($DB->request(['COUNT' => 'id', 'FROM' => 'glpi_tickets AS t', 'WHERE' => $base]));
        $total   = (int)($total_r[0]['COUNT'] ?? 0);

        // Açık
        $open_r  = iterator_to_array($DB->request(['COUNT' => 'id', 'FROM' => 'glpi_tickets AS t', 'WHERE' => $base + ['t.status' => [1,2,3,4]]]));
        $open    = (int)($open_r[0]['COUNT'] ?? 0);

        // Kapalı
        $closed_r = iterator_to_array($DB->request(['COUNT' => 'id', 'FROM' => 'glpi_tickets AS t', 'WHERE' => $base + ['t.status' => 6]]));
        $closed   = (int)($closed_r[0]['COUNT'] ?? 0);

        // SLA uyumu
        $sla_base  = $base;
        $sla_base[] = [new QueryExpression('t.time_to_resolve IS NOT NULL')];
        $sla_base[] = ['t.status' => [5, 6]];
        $sla_total_r = iterator_to_array($DB->request(['COUNT' => 'id', 'FROM' => 'glpi_tickets AS t', 'WHERE' => $sla_base]));
        $sla_total   = (int)($sla_total_r[0]['COUNT'] ?? 0);

        $sla_ok_base  = $sla_base;
        $sla_ok_base[] = [new QueryExpression('t.solvedate <= t.time_to_resolve')];
        $sla_ok_r      = iterator_to_array($DB->request(['COUNT' => 'id', 'FROM' => 'glpi_tickets AS t', 'WHERE' => $sla_ok_base]));
        $sla_ok        = (int)($sla_ok_r[0]['COUNT'] ?? 0);
        $sla_rate      = $sla_total > 0 ? round($sla_ok / $sla_total * 100, 1) : null;

        // Ortalama çözüm süresi
        $avg_r = iterator_to_array($DB->request([
            'SELECT' => [new QueryExpression('ROUND(AVG(t.solve_delay_stat)/3600, 1) AS avg_h')],
            'FROM'   => 'glpi_tickets AS t',
            'WHERE'  => $base + ['t.status' => [5,6], new QueryExpression('t.solve_delay_stat > 0')],
        ]));
        $avg_solve = $avg_r[0]['avg_h'] ?? null;

        // Bekleyen (waiting)
        $wait_r = iterator_to_array($DB->request(['COUNT' => 'id', 'FROM' => 'glpi_tickets AS t', 'WHERE' => ['t.is_deleted' => 0, 't.status' => 4, 't.entities_id' => $entities]]));
        $waiting = (int)($wait_r[0]['COUNT'] ?? 0);

        return compact('total', 'open', 'closed', 'sla_rate', 'avg_solve', 'waiting', 'sla_ok', 'sla_total');
    }

    // -----------------------------------------------------------------------
    // Filtre dropdown verileri
    // -----------------------------------------------------------------------
    public static function getEntityOptions(): array
    {
        global $DB;
        $allowed = PluginHyperreportingReport::getAllowedEntityIds();
        $rows = $DB->request([
            'SELECT'  => ['id', 'name', 'completename'],
            'FROM'    => 'glpi_entities',
            'WHERE'   => ['id' => $allowed],
            'ORDER'   => 'completename ASC',
        ]);
        return iterator_to_array($rows);
    }

    public static function getTechOptions(): array
    {
        global $DB;
        $rows = $DB->request([
            'SELECT'     => ['DISTINCT u.id', 'u.firstname', 'u.realname'],
            'FROM'       => 'glpi_tickets_users AS tu',
            'INNER JOIN' => ['glpi_users AS u' => ['ON' => ['u' => 'id', 'tu' => 'users_id']]],
            'WHERE'      => ['tu.type' => 2, 'u.is_deleted' => 0, 'u.is_active' => 1],
            'ORDER'      => 'u.realname ASC',
        ]);
        return iterator_to_array($rows);
    }

    public static function getCategoryOptions(): array
    {
        global $DB;
        $rows = $DB->request([
            'SELECT' => ['id', 'completename'],
            'FROM'   => 'glpi_itilcategories',
            'WHERE'  => ['is_helpdeskvisible' => 1],
            'ORDER'  => 'completename ASC',
        ]);
        return iterator_to_array($rows);
    }

    // Durum etiket metni
    public static function getStatusLabel(int $status): string
    {
        return match($status) {
            1 => 'Yeni', 2 => 'Atandı', 3 => 'Planlandı',
            4 => 'Bekliyor', 5 => 'Çözüldü', 6 => 'Kapatıldı',
            default => 'Bilinmiyor'
        };
    }

    public static function getPriorityLabel(int $p): string
    {
        return match($p) {
            1 => 'Çok Düşük', 2 => 'Düşük', 3 => 'Orta',
            4 => 'Yüksek', 5 => 'Çok Yüksek', 6 => 'Büyük Felaket',
            default => '-'
        };
    }

    public static function getPriorityColor(int $p): string
    {
        return match($p) {
            1 => '#94a3b8', 2 => '#60a5fa', 3 => '#facc15',
            4 => '#fb923c', 5 => '#f87171', 6 => '#dc2626',
            default => '#94a3b8'
        };
    }
}
