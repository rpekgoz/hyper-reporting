<?php
/**
 * Hyper Reporting — hook.php
 *
 * @author  Raşit PEKGÖZ
 * @license GPLv2+
 */

function plugin_hyperreporting_install()
{
    global $DB;
    // Tablolar faz geliştirme sürecinde buraya eklenecek
    return true;
}

function plugin_hyperreporting_uninstall()
{
    global $DB;
    // DROP TABLE komutları buraya gelecek
    return true;
}
