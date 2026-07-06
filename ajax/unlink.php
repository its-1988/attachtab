<?php

/**
 * -------------------------------------------------------------------------
 * Attachments tab plugin for GLPI
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2026 by the Attachments tab plugin team.
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 *
 * Detach a document linked directly to a ticket/change/problem.
 * POST: id (glpi_documents_items.id), itemtype, items_id.
 * CSRF is validated by the kernel from the X-Glpi-Csrf-Token header.
 */

use GlpiPlugin\Attachtab\AttachmentsTab;

if (!defined('GLPI_ROOT')) {
    require_once __DIR__ . '/../../../inc/includes.php';
}

header('Content-Type: application/json; charset=UTF-8');

Session::checkLoginUser();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => __('Invalid request', 'attachtab')]);
    return;
}

$result = AttachmentsTab::unlinkDirectDocument(
    (int) ($_POST['id'] ?? 0),
    (string) ($_POST['itemtype'] ?? ''),
    (int) ($_POST['items_id'] ?? 0)
);

if ($result === true) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => $result]);
}
