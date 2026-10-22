<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * Subpanels under the detail view (Activities, History, Contacts, Opportunities, Leads, Cases).
 * Subpanel types from Modules:
 *   activities    open calls / meetings (Planned) and tasks (not Completed / Deferred)
 *   history       held / not held calls and meetings, completed / deferred tasks, notes
 *   relationship  records linked through a relationship table
 *   foreign_key   records whose 'field' holds this record's id
 * The "Create" button in a subpanel is a normal create (POST /api/{module}) with
 * account_id or parent_type + parent_id set; "Select" is link() below.
 */
final class Subpanels
{
    private const ACTIVITY_SQL = [
        'calls' => "SELECT id, 'calls' AS module, 'Call' AS type, name, status, date_start AS date, date_modified, assigned_user_id FROM calls",
        'meetings' => "SELECT id, 'meetings' AS module, 'Meeting' AS type, name, status, date_start AS date, date_modified, assigned_user_id FROM meetings",
        'tasks' => "SELECT id, 'tasks' AS module, 'Task' AS type, name, status, date_due AS date, date_modified, assigned_user_id FROM tasks",
        'notes' => "SELECT id, 'notes' AS module, 'Note' AS type, name, NULL AS status, NULL AS date, date_modified, assigned_user_id FROM notes",
    ];

    public static function list(string $moduleKey, string $id, string $name, array $query): array
    {
        [$module, $panel] = self::panel($moduleKey, $id, $name);

        switch ($panel['type']) {
            case 'activities':
            case 'history':
                return self::activities($module, $id, $panel['type'] === 'history', $query);

            case 'relationship':
                $rel = Modules::relationship($panel['relationship']);
                if ($rel['lhs_module'] === $moduleKey) {
                    return Records::list($rel['rhs_module'], $query, [
                        "t.id IN (SELECT r.`{$rel['rhs_key']}` FROM `{$rel['table']}` r WHERE r.`{$rel['lhs_key']}` = ?)",
                        [$id],
                    ]);
                }

                return Records::list($rel['lhs_module'], $query, [
                    "t.id IN (SELECT r.`{$rel['lhs_key']}` FROM `{$rel['table']}` r WHERE r.`{$rel['rhs_key']}` = ?)",
                    [$id],
                ]);

            default: // foreign_key
                return Records::list($panel['module'], $query, ["t.`{$panel['field']}` = ?", [$id]]);
        }
    }

    /** "Select" button: body {"record_id": "..."} */
    public static function link(string $moduleKey, string $id, string $name, array $body): void
    {
        [, $panel] = self::panel($moduleKey, $id, $name);
        $recordId = $body['record_id'] ?? null;
        if (!is_string($recordId) || $recordId === '') {
            throw new HttpError(422, 'Validation failed', ['errors' => ['record_id' => 'Required']]);
        }

        if ($panel['type'] === 'relationship') {
            $rel = Modules::relationship($panel['relationship']);
            $isLhs = $rel['lhs_module'] === $moduleKey;
            self::requireRecord($isLhs ? $rel['rhs_module'] : $rel['lhs_module'], $recordId);
            if ($isLhs) {
                Relationships::setLhs($rel['name'], $recordId, $id);
            } else {
                Relationships::setLhs($rel['name'], $id, $recordId);
            }

            return;
        }
        if ($panel['type'] === 'foreign_key') {
            self::requireRecord($panel['module'], $recordId);
            $table = Modules::get($panel['module'])['table'];
            Db::run("UPDATE `$table` SET `{$panel['field']}` = ? WHERE id = ?", [$id, $recordId]);

            return;
        }
        throw new HttpError(400, 'Activities and history are linked by setting parent_type and parent_id on the call, meeting, task or note');
    }

    /** Removes the link between this record and $recordId (the record itself is kept). */
    public static function unlink(string $moduleKey, string $id, string $name, string $recordId): void
    {
        [, $panel] = self::panel($moduleKey, $id, $name);

        if ($panel['type'] === 'relationship') {
            $rel = Modules::relationship($panel['relationship']);
            $unlinked = $rel['lhs_module'] === $moduleKey
                ? Relationships::unlink($rel['name'], $id, $recordId)
                : Relationships::unlink($rel['name'], $recordId, $id);
        } elseif ($panel['type'] === 'foreign_key') {
            $table = Modules::get($panel['module'])['table'];
            $unlinked = Db::run(
                "UPDATE `$table` SET `{$panel['field']}` = NULL WHERE id = ? AND `{$panel['field']}` = ?",
                [$recordId, $id],
            ) > 0;
        } else {
            throw new HttpError(400, 'Activities and history are unlinked by clearing parent_id on the call, meeting, task or note');
        }
        if (!$unlinked) {
            throw new HttpError(404, 'These records are not linked');
        }
    }

    private static function panel(string $moduleKey, string $id, string $name): array
    {
        $module = Modules::get($moduleKey);
        $panel = $module['subpanels'][$name] ?? null;
        if ($panel === null) {
            throw new HttpError(404, "Unknown subpanel: $name");
        }
        if (!Records::exists($moduleKey, $id)) {
            throw new HttpError(404, 'Record not found');
        }

        return [$module, $panel];
    }

    private static function requireRecord(string $moduleKey, string $id): void
    {
        if (!Records::exists($moduleKey, $id)) {
            throw new HttpError(422, 'Validation failed', ['errors' => ['record_id' => 'No ' . Modules::get($moduleKey)['label'] . ' record with this id']]);
        }
    }

    private static function activities(array $module, string $id, bool $history, array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $parent = ' WHERE deleted = 0 AND parent_type = ? AND parent_id = ?';

        if ($history) {
            $parts = [
                self::ACTIVITY_SQL['calls'] . $parent . " AND status IN ('Held', 'Not Held')",
                self::ACTIVITY_SQL['meetings'] . $parent . " AND status IN ('Held', 'Not Held')",
                self::ACTIVITY_SQL['tasks'] . $parent . " AND status IN ('Completed', 'Deferred')",
                self::ACTIVITY_SQL['notes'] . $parent,
            ];
            $order = 'date_modified DESC, id';
        } else {
            $parts = [
                self::ACTIVITY_SQL['calls'] . $parent . " AND status = 'Planned'",
                self::ACTIVITY_SQL['meetings'] . $parent . " AND status = 'Planned'",
                self::ACTIVITY_SQL['tasks'] . $parent . " AND (status IS NULL OR status NOT IN ('Completed', 'Deferred'))",
            ];
            $order = 'date IS NULL, date, id';   // undated tasks last
        }
        $union = implode(' UNION ALL ', $parts);
        $params = [];
        foreach ($parts as $unused) {
            array_push($params, $module['label'], $id);
        }

        $total = (int) Db::value("SELECT COUNT(*) FROM ($union) x", $params);
        $rows = Db::all("SELECT * FROM ($union) x ORDER BY $order LIMIT $perPage OFFSET $offset", $params);
        $users = Users::names(array_column($rows, 'assigned_user_id'));
        foreach ($rows as &$row) {
            $row['assigned_user_name'] = $users[$row['assigned_user_id'] ?? ''] ?? null;
        }
        unset($row);

        return ['records' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }
}
