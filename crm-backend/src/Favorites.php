<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/** Favorite star on the detail view and the Favorites list in the sidebar. */
final class Favorites
{
    public static function list(array $query): array
    {
        $sql = 'SELECT parent_type, parent_id, date_entered FROM favorites WHERE assigned_user_id = ?';
        $params = [Auth::userId()];
        if (($query['module'] ?? '') !== '') {
            $sql .= ' AND parent_type = ?';
            $params[] = Modules::get((string) $query['module'])['label'];
        }
        $rows = Db::all($sql . ' ORDER BY date_entered DESC, id', $params);

        return ['records' => Records::resolveRefs($rows, 'parent_type', 'parent_id')];
    }

    public static function add(array $body): void
    {
        $moduleKey = (string) ($body['module'] ?? '');
        $recordId = (string) ($body['record_id'] ?? '');
        $module = Modules::get($moduleKey);
        if (!Records::exists($moduleKey, $recordId)) {
            throw new HttpError(404, 'Record not found');
        }
        $userId = Auth::userId();
        if (self::isFavorite($module['label'], $recordId)) {
            return;
        }
        Db::insert('favorites', [
            'id' => Util::uuid(),
            'assigned_user_id' => $userId,
            'parent_type' => $module['label'],
            'parent_id' => $recordId,
            'date_entered' => Util::now(),
        ]);
    }

    public static function remove(string $moduleKey, string $recordId): void
    {
        $module = Modules::get($moduleKey);
        Db::run(
            'DELETE FROM favorites WHERE assigned_user_id = ? AND parent_type = ? AND parent_id = ?',
            [Auth::userId(), $module['label'], $recordId],
        );
    }

    public static function isFavorite(string $moduleLabel, string $recordId): bool
    {
        return Db::value(
            'SELECT 1 FROM favorites WHERE assigned_user_id = ? AND parent_type = ? AND parent_id = ?',
            [Auth::userId(), $moduleLabel, $recordId],
        ) !== null;
    }

    /** Called when a record is deleted. */
    public static function removeRecord(string $moduleLabel, string $recordId): void
    {
        Db::run('DELETE FROM favorites WHERE parent_type = ? AND parent_id = ?', [$moduleLabel, $recordId]);
    }
}
