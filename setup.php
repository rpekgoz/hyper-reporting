<?php
/**
 * Hyper Reporting — GLPI Plugin
 * Enterprise-grade, multi-dimensional GLPI ticket & project reporting
 *
 * @author  Raşit PEKGÖZ
 * @license GPLv2+
 * @link    https://github.com/rpekgoz/hyper-reporting
 */

define('PLUGIN_HYPERREPORTING_VERSION', '0.1.0');
define('PLUGIN_HYPERREPORTING_MIN_GLPI', '10.0.0');
define('PLUGIN_HYPERREPORTING_MAX_GLPI', '11.0.99');

function plugin_init_hyperreporting()
{
    global $PLUGIN_HOOKS;
    $PLUGIN_HOOKS['csrf_compliant']['hyperreporting'] = true;

    if (Session::getLoginUserID()) {
        // Sınıflar faz geliştirme sürecinde buraya eklenecek
        // Plugin::registerClass('PluginHyperreportingReport');
    }
}

function plugin_version_hyperreporting()
{
    return [
        'name'           => 'Hyper Reporting',
        'version'        => PLUGIN_HYPERREPORTING_VERSION,
        'author'         => 'Raşit PEKGÖZ',
        'license'        => 'GPLv2+',
        'homepage'       => 'https://github.com/rpekgoz/hyper-reporting',
        'requirements'   => [
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
