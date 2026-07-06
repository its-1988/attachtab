<?php

/**
 * -------------------------------------------------------------------------
 * Attachments tab plugin for GLPI
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2026 by the Attachments tab plugin team.
 * @license   GPLv3+ https://www.gnu.org/licenses/gpl-3.0.html
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Attachtab;

use CommonGLPI;
use CommonITILObject;
use Document;
use Document_Item;
use Glpi\Application\View\TemplateRenderer;
use Html;
use Session;

/**
 * "Attachments" tab on tickets, changes and problems.
 *
 * The list (and the tab counter) is built with the exact criteria the core
 * timeline uses to collect an ITIL object's documents —
 * CommonITILObject::getAssociatedDocumentsCriteria() — so the tab always
 * agrees with the timeline: direct documents plus those attached to
 * followups, tasks, solutions and validations, visibility rights included.
 *
 * Images embedded in rich text carry timeline_position == NO_TIMELINE (-1),
 * exactly like the core marks them; the tab hides them behind a toggle.
 *
 * Uploads reuse the untouched native form (Document_Item::showAddFormForItem),
 * so rights, CSRF, sha1 dedup and the timeline entry are all core behaviour.
 */
class AttachmentsTab extends CommonGLPI
{
    /** ITIL objects that get the tab. */
    public const TARGET_TYPES = ['Ticket', 'Change', 'Problem'];

    public static function getTypeName($nb = 0)
    {
        return _n('Attachment', 'Attachments', $nb, 'attachtab');
    }

    public static function getIcon(): string
    {
        return 'ti ti-paperclip';
    }

    /* --------------------------------------------------------------------- */
    /* Tab plumbing                                                           */
    /* --------------------------------------------------------------------- */

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            $withtemplate
            || !($item instanceof CommonITILObject)
            || !in_array($item::class, self::TARGET_TYPES, true)
            || $item->isNewItem()
        ) {
            return '';
        }

        $count = 0;
        if (($_SESSION['glpishow_count_on_tabs'] ?? false)) {
            $count = self::countRealAttachments($item);
        }

        return self::createTabEntry(
            self::getTypeName(Session::getPluralNumber()),
            $count,
            null,
            self::getIcon()
        );
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (
            !($item instanceof CommonITILObject)
            || !in_array($item::class, self::TARGET_TYPES, true)
        ) {
            return true;
        }

        // Native "add a document" form. It checks the native rights itself
        // (READ on the object + canAddItem('Document') + Document::canView())
        // and simply renders nothing when they are missing. The upload goes
        // through the core Document form, which links the file to the object
        // and gives it a real timeline position.
        Document_Item::showAddFormForItem($item, $withtemplate);

        $rows         = self::getDocumentRows($item);
        $inline_count = count(array_filter($rows, static fn (array $r) => $r['is_inline']));

        TemplateRenderer::getInstance()->display('@attachtab/tab.html.twig', [
            'item'         => $item,
            'rows'         => $rows,
            'inline_count' => $inline_count,
            'unlink_url'   => $CFG_GLPI['root_doc'] . '/plugins/attachtab/ajax/unlink.php',
        ]);

        return true;
    }

    /* --------------------------------------------------------------------- */
    /* Data                                                                   */
    /* --------------------------------------------------------------------- */

    /**
     * Count "real" attachments (excluding images embedded in text), using the
     * same criteria as the list — one source of truth for every surface.
     */
    public static function countRealAttachments(CommonITILObject $item): int
    {
        /** @var \DBmysql $DB */
        global $DB;

        $di_table = Document_Item::getTable();

        $result = $DB->request([
            'COUNT' => 'cpt',
            'FROM'  => $di_table,
            'WHERE' => [
                // Numeric-key AND group: never merge a criteria array that may
                // carry its own OR with += (it would silently drop clauses).
                $item->getAssociatedDocumentsCriteria(),
                $di_table . '.timeline_position' => ['>', CommonITILObject::NO_TIMELINE],
            ],
        ])->current();

        return (int) ($result['cpt'] ?? 0);
    }

    /**
     * All documents of the ITIL object — direct and from timeline children —
     * as display-ready rows.
     *
     * @return array[] each row: link_id, doc_id, filename, title, mime, source,
     *                 is_inline, is_direct, user, date, download_url
     */
    public static function getDocumentRows(CommonITILObject $item): array
    {
        /** @var \DBmysql $DB */
        /** @var array $CFG_GLPI */
        global $DB, $CFG_GLPI;

        $di_table  = Document_Item::getTable();
        $doc_table = Document::getTable();

        $iterator = $DB->request([
            'SELECT' => [
                $di_table . '.id AS link_id',
                $di_table . '.itemtype AS src_itemtype',
                $di_table . '.items_id AS src_items_id',
                $di_table . '.timeline_position',
                $di_table . '.users_id AS link_users_id',
                $di_table . '.date AS link_date',
                $di_table . '.date_creation AS link_date_creation',
                $doc_table . '.id AS doc_id',
                $doc_table . '.name AS doc_name',
                $doc_table . '.filename AS doc_filename',
                $doc_table . '.mime AS doc_mime',
            ],
            'FROM'       => $di_table,
            'INNER JOIN' => [
                $doc_table => [
                    'ON' => [
                        $doc_table => 'id',
                        $di_table  => 'documents_id',
                    ],
                ],
            ],
            'WHERE'  => [
                $item->getAssociatedDocumentsCriteria(),
            ],
            'ORDER'  => $di_table . '.date_creation DESC',
        ]);

        $fk   = $item::getForeignKeyField();
        $rows = [];

        foreach ($iterator as $data) {
            $date      = $data['link_date'] ?: $data['link_date_creation'];
            $is_direct = ($data['src_itemtype'] === $item::class);

            // The detach button follows the native per-link right — the same
            // gate the core massive-action purge uses (Document_Item PURGE:
            // update right on the document or on the parent object). Only
            // direct links can carry the button at all.
            $can_unlink = false;
            if ($is_direct) {
                $link_check = new Document_Item();
                $can_unlink = $link_check->can((int) $data['link_id'], PURGE);
            }

            $rows[] = [
                'link_id'      => (int) $data['link_id'],
                'doc_id'       => (int) $data['doc_id'],
                'filename'     => $data['doc_filename'] ?: $data['doc_name'],
                'title'        => $data['doc_name'],
                'mime'         => (string) $data['doc_mime'],
                'source'       => self::getSourceLabel((string) $data['src_itemtype']),
                'is_inline'    => ((int) $data['timeline_position'] === CommonITILObject::NO_TIMELINE),
                'is_direct'    => $is_direct,
                'can_unlink'   => $can_unlink,
                'user'         => getUserName((int) $data['link_users_id']),
                'date'         => $date !== null ? Html::convDateTime($date) : '',
                'download_url' => $CFG_GLPI['root_doc'] . '/front/document.send.php?docid='
                    . (int) $data['doc_id'] . '&' . $fk . '=' . $item->getID(),
            ];
        }

        return $rows;
    }

    /**
     * Human label of an attachment's origin — the native type name of the
     * Document_Item row's itemtype (Ticket / Followup / Task / Solution /
     * Validation…), so wording and translations always match core.
     */
    private static function getSourceLabel(string $itemtype): string
    {
        if ($itemtype !== '' && class_exists($itemtype) && method_exists($itemtype, 'getTypeName')) {
            return $itemtype::getTypeName(1);
        }

        return $itemtype;
    }

    /* --------------------------------------------------------------------- */
    /* Unlink (ajax/unlink.php)                                               */
    /* --------------------------------------------------------------------- */

    /**
     * Detach a document that is linked DIRECTLY to the ITIL object. Documents
     * carried by followups/tasks/solutions belong to those timeline entries
     * and are not touched here. The document itself is kept.
     *
     * Rights are the native per-link right — the same gate the core
     * massive-action purge applies to a Document_Item row (PURGE: update
     * right on the document or on the parent object).
     *
     * @return true|string true on success, translated error message otherwise
     */
    public static function unlinkDirectDocument(int $link_id, string $itemtype, int $items_id)
    {
        if ($link_id <= 0 || $items_id <= 0 || !in_array($itemtype, self::TARGET_TYPES, true)) {
            return __('Invalid parameters', 'attachtab');
        }

        $item = getItemForItemtype($itemtype);
        if ($item === false || !$item->getFromDB($items_id)) {
            return __('Item not found', 'attachtab');
        }

        if (!$item->can($items_id, READ)) {
            return __('Access denied', 'attachtab');
        }

        $document_item = new Document_Item();
        if (!$document_item->getFromDB($link_id)) {
            return __('Link not found', 'attachtab');
        }

        // Only links owned by this very object: never let the endpoint reach
        // another object's (or a followup's) attachment.
        if (
            $document_item->fields['itemtype'] !== $itemtype
            || (int) $document_item->fields['items_id'] !== $items_id
        ) {
            return __('Access denied', 'attachtab');
        }

        // Native per-link authorization (Document_Item::canPurgeItem — update
        // right on the document or on the parent object), exactly what the
        // core massive-action purge enforces.
        if (!$document_item->can($link_id, PURGE)) {
            return __('Access denied', 'attachtab');
        }

        if (!$document_item->delete(['id' => $link_id], true)) {
            return __('Deletion failed', 'attachtab');
        }

        return true;
    }
}
