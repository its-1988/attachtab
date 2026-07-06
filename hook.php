<?php

/**
 * -------------------------------------------------------------------------
 * Attachments tab plugin for GLPI
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2026 by the Attachments tab plugin team.
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

/**
 * Plugin install process.
 *
 * The plugin stores nothing of its own: everything shown is native
 * Document / Document_Item data. Nothing to create.
 *
 * @return boolean
 */
function plugin_attachtab_install()
{
    return true;
}

/**
 * Plugin uninstall process.
 *
 * No tables, no config — nothing to drop.
 *
 * @return boolean
 */
function plugin_attachtab_uninstall()
{
    return true;
}
