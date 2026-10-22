<?php

declare(strict_types=1);

namespace App;

/**
 * Field changes for "View change log", stored in <table>_audit like SuiteCRM 7.
 * Every changed field of an audited module is logged.
 */
final class Audit
{
    public static function write(array $module, string $recordId, array $before, array $changes, string $userId): void
    {
        $now = Util::now();
        foreach ($changes as $field => $after) {
            $type = $module['fields'][$field]['type'];
            $isText = $type === 'text';
            Db::insert($module['table'] . '_audit', [
                'id' => Util::uuid(),
                'parent_id' => $recordId,
                'date_created' => $now,
                'created_by' => $userId,
                'field_name' => $field,
                'data_type' => $type,
                'before_value_string' => $isText ? null : self::short($before[$field]),
                'after_value_string' => $isText ? null : self::short($after),
                'before_value_text' => $isText ? $before[$field] : null,
                'after_value_text' => $isText ? $after : null,
            ]);
        }
    }

    public static function read(array $module, string $recordId): array
    {
        $rows = Db::all(
            "SELECT * FROM `{$module['table']}_audit` WHERE parent_id = ? ORDER BY date_created DESC, field_name",
            [$recordId],
        );
        $users = Users::names(array_column($rows, 'created_by'));

        return array_map(static fn (array $row): array => [
            'id' => $row['id'],
            'field_name' => $row['field_name'],
            'data_type' => $row['data_type'],
            'before' => $row['before_value_text'] ?? $row['before_value_string'],
            'after' => $row['after_value_text'] ?? $row['after_value_string'],
            'date_created' => $row['date_created'],
            'created_by' => $row['created_by'],
            'created_by_name' => $users[$row['created_by'] ?? ''] ?? null,
        ], $rows);
    }

    private static function short(mixed $value): ?string
    {
        return $value === null ? null : mb_substr((string) $value, 0, 255);
    }
}
