<?php

/**
 * -------------------------------------------------------------------------
 * Attachments tab plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * Adds an "Attachments" tab to tickets, changes and problems, listing every
 * file attached to the object — directly or via followups, tasks, solutions
 * and validations — with a filter for images embedded in text, plus the
 * native upload form to add new files.
 *
 * @copyright Copyright (C) 2026 by the Attachments tab plugin team.
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Attachtab\AttachmentsTab;

define('PLUGIN_ATTACHTAB_VERSION', '1.0.0');

// Minimal GLPI version, inclusive.
define('PLUGIN_ATTACHTAB_MIN_GLPI', '11.0.7');
// Maximum GLPI version, exclusive.
define('PLUGIN_ATTACHTAB_MAX_GLPI', '11.0.99');

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_attachtab()
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['attachtab'] = true;

    // The whole plugin is this tab. No DB access here: plugin_init runs in
    // every lifecycle state (not installed / inactive / active).
    Plugin::registerClass(AttachmentsTab::class, [
        'addtabon' => AttachmentsTab::TARGET_TYPES,
    ]);
}

/**
 * Get the name and the version of the plugin.
 * REQUIRED
 *
 * @return array
 */
function plugin_version_attachtab()
{
    return [
        'name'         => __('Attachments tab', 'attachtab'),
        'version'      => PLUGIN_ATTACHTAB_VERSION,
        'author'       => 'by Claude',
        'license'      => 'GPLv3+',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_ATTACHTAB_MIN_GLPI,
                'max' => PLUGIN_ATTACHTAB_MAX_GLPI,
            ],
            'php'  => [
                'min' => '8.2',
            ],
        ],
    ];
}

/**
 * Check pre-requisites before install.
 *
 * @return boolean
 */
function plugin_attachtab_check_prerequisites()
{
    return true;
}

/**
 * Check configuration process.
 *
 * @param boolean $verbose
 *
 * @return boolean
 */
function plugin_attachtab_check_config($verbose = false)
{
    return true;
}
