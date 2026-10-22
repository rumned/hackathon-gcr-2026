<?php

declare(strict_types=1);

namespace App;

/**
 * "Recently viewed" in the sidebar and in the module menu,
 * stored in the tracker table like SuiteCRM 7. Opening a record's detail view records it.
 */
final class Tracker
{
    public static function record(string $moduleKey, string $recordId, string $summary): void
    {
        $module = Modules::get($moduleKey);
        $userId = Auth::userId();
        // One row per user and record, so a record moves to the top when viewed again.
        Db::run('DELETE FROM tracker WHERE user_id = ? AND module_name = ? AND item_id = ?', [$userId, $module['label'], $recordId]);
        Db::insert('tracker', [
            'user_id' => $userId,
            'module_name' => $module['label'],
            'item_id' => $recordId,
            'item_summary' => mb_substr($summary, 0, 255),
            'date_modified' => Util::now(),
            'action' => 'detailview',
            'visible' => 1,
            'deleted' => 0,
        ]);
    }

    /** Query: module (optional, e.g. accounts), limit (default 10). */
    public static function recent(array $query): array
    {
        $limit = min(50, max(1, (int) ($query['limit'] ?? 10)));
        $sql = 'SELECT module_name, item_id, date_modified AS date_viewed FROM tracker'
            . ' WHERE user_id = ? AND visible = 1 AND deleted = 0';
        $params = [Auth::userId()];
        if (($query['module'] ?? '') !== '') {
            $sql .= ' AND module_name = ?';
            $params[] = Modules::get((string) $query['module'])['label'];
        }
        $rows = Db::all($sql . " ORDER BY date_modified DESC, id DESC LIMIT $limit", $params);

        return ['records' => Records::resolveRefs($rows, 'module_name', 'item_id')];
    }

    /** Called when a record is deleted. */
    public static function removeRecord(string $moduleLabel, string $recordId): void
    {
        Db::run('UPDATE tracker SET deleted = 1 WHERE module_name = ? AND item_id = ?', [$moduleLabel, $recordId]);
    }
}
