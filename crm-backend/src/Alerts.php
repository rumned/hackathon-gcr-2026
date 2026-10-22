<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * "Alerts (3)" in the top bar. An alert is created when a user assigns
 * a record to another user, or when a quotation could not be sent.
 */
final class Alerts
{
    public static function assigned(string $moduleKey, string $recordId, string $toUserId, string $byUserId): void
    {
        $module = Modules::get($moduleKey);
        $recordName = Records::names($moduleKey, [$recordId])[$recordId] ?? '';
        $byName = Users::names([$byUserId])[$byUserId] ?? 'Another user';
        $now = Util::now();
        Db::insert('alerts', [
            'id' => Util::uuid(),
            'name' => mb_substr("{$module['singular']} assigned to you: $recordName", 0, 255),
            'description' => "$byName assigned this {$module['singular']} to you.",
            'assigned_user_id' => $toUserId,
            'is_read' => 0,
            'target_module' => $module['label'],
            'target_id' => $recordId,
            'type' => 'info',
            'date_entered' => $now,
            'date_modified' => $now,
            'created_by' => $byUserId,
        ]);
    }

    /** Any other alert, e.g. a quotation that could not be sent. */
    public static function notify(string $moduleKey, string $recordId, string $toUserId, string $name, string $description, string $type = 'warning'): void
    {
        $module = Modules::get($moduleKey);
        $now = Util::now();
        Db::insert('alerts', [
            'id' => Util::uuid(),
            'name' => mb_substr($name, 0, 255),
            'description' => $description,
            'assigned_user_id' => $toUserId,
            'is_read' => 0,
            'target_module' => $module['label'],
            'target_id' => $recordId,
            'type' => $type,
            'date_entered' => $now,
            'date_modified' => $now,
            'created_by' => Auth::userId(),
        ]);
    }

    public static function list(array $query): array
    {
        $userId = Auth::userId();
        $limit = min(100, max(1, (int) ($query['limit'] ?? 20)));
        $unread = (int) Db::value(
            'SELECT COUNT(*) FROM alerts WHERE assigned_user_id = ? AND is_read = 0 AND deleted = 0',
            [$userId],
        );
        $rows = Db::all(
            'SELECT id, name, description, is_read, target_module, target_id, type, date_entered FROM alerts'
            . " WHERE assigned_user_id = ? AND deleted = 0 ORDER BY is_read, date_entered DESC LIMIT $limit",
            [$userId],
        );

        return [
            'unread_count' => $unread,
            'records' => array_map(static fn (array $row): array => [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'is_read' => (bool) $row['is_read'],
                'type' => $row['type'],
                'module' => Modules::keyForLabel($row['target_module']),
                'record_id' => $row['target_id'],
                'date_entered' => $row['date_entered'],
            ], $rows),
        ];
    }

    public static function markRead(string $alertId): void
    {
        $userId = Auth::userId();
        $found = Db::value(
            'SELECT 1 FROM alerts WHERE id = ? AND assigned_user_id = ? AND deleted = 0',
            [$alertId, $userId],
        );
        if ($found === null) {
            throw new HttpError(404, 'Alert not found');
        }
        Db::run('UPDATE alerts SET is_read = 1, date_modified = ? WHERE id = ?', [Util::now(), $alertId]);
    }
}
