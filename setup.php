<?php
/**
 * Hyper Reporting — setup.php
 * GLPI Plugin kayıt ve başlatma
 *
 * @author  Raşit PEKGÖZ
 * @license GPLv2+
 */

define('PLUGIN_HYPERREPORTING_VERSION', '0.1.0');
define('PLUGIN_HYPERREPORTING_MIN_GLPI', '10.0.0');
define('PLUGIN_HYPERREPORTING_MAX_GLPI', '11.0.99');

function plugin_init_hyperreporting()
{
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['hyperreporting'] = true;

    // menu_toadd koşulsuz kayıt edilmeli — GLPI menüyü session öncesi build eder
    $PLUGIN_HOOKS['menu_toadd']['hyperreporting'] = [
        'tools' => 'PluginHyperreportingReport'
    ];

    if (!Session::getLoginUserID()) {
        return;
    }

    Plugin::registerClass('PluginHyperreportingReport');

    // CSS & JS asset injection
    if (strpos($_SERVER['REQUEST_URI'] ?? '', 'hyperreporting') !== false) {
        $PLUGIN_HOOKS['add_css']['hyperreporting'] = [
            'public/css/report.css'
        ];
        $PLUGIN_HOOKS['add_javascript']['hyperreporting'] = [
            'public/js/report.js'
        ];
    }
}

function plugin_version_hyperreporting()
{
    return [
        'name'         => 'Hyper Reporting',
        'version'      => PLUGIN_HYPERREPORTING_VERSION,
        'author'       => 'Raşit PEKGÖZ',
        'license'      => 'GPLv2+',
        'homepage'     => 'https://github.com/rpekgoz/hyper-reporting',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_HYPERREPORTING_MIN_GLPI,
                'max' => PLUGIN_HYPERREPORTING_MAX_GLPI,
            ],
        ],
    ];
}

function plugin_hyperreporting_check_prerequisites()
{
    if (version_compare(GLPI_VERSION, PLUGIN_HYPERREPORTING_MIN_GLPI, 'lt')) {
        echo 'Bu eklenti GLPI ' . PLUGIN_HYPERREPORTING_MIN_GLPI . ' veya üzerini gerektirir.';
        return false;
    }
    return true;
}

function plugin_hyperreporting_check_config()
{
    return true;
}
