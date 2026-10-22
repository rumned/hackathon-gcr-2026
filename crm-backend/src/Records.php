<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * List, read, create, update and delete records of any module defined in Modules.
 *
 * $scope arguments are an extra [sql, params] condition on the module table
 * (aliased t), used by subpanels and dashlets, e.g. ['t.assigned_user_id = ?', [$id]].
 */
final class Records
{
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;

    // ------------------------------------------------------------------ reading

    /**
     * List view. Query parameters:
     *   page, per_page          pagination (per_page max 100)
     *   sort, dir               any column field, name, date_entered or date_modified; asc / desc
     *   q                       basic search: search_fields start with q
     *   filter[field]=value     text fields: starts with; other fields: equals;
     *                           filter[field][]=a&filter[field][]=b: one of;
     *                           filter[date_field][from]=...&filter[date_field][to]=...: range;
     *                           filter[email]=...: an email address starts with
     */
    public static function list(string $moduleKey, array $query, ?array $scope = null): array
    {
        $module = Modules::get($moduleKey);
        [$where, $params] = self::where($module, $query, $scope);
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($query['per_page'] ?? self::DEFAULT_PER_PAGE)));
        $offset = ($page - 1) * $perPage;

        $total = (int) Db::value("SELECT COUNT(*) FROM `{$module['table']}` t WHERE $where", $params);
        $rows = Db::all(
            'SELECT ' . self::selectList($module) . " FROM `{$module['table']}` t WHERE $where"
            . ' ORDER BY ' . self::orderBy($module, $query) . " LIMIT $perPage OFFSET $offset",
            $params,
        );

        return [
            'records' => self::hydrate($module, $rows),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /** Every record matching the list filters, without pagination (used by export). */
    public static function all(string $moduleKey, array $query, ?array $scope = null, int $max = 10000): array
    {
        $module = Modules::get($moduleKey);
        [$where, $params] = self::where($module, $query, $scope);
        $rows = Db::all(
            'SELECT ' . self::selectList($module) . " FROM `{$module['table']}` t WHERE $where"
            . ' ORDER BY ' . self::orderBy($module, $query) . " LIMIT $max",
            $params,
        );

        return self::hydrate($module, $rows);
    }

    /** One record for the detail and edit views. */
    public static function get(string $moduleKey, string $id): array
    {
        $module = Modules::get($moduleKey);
        $row = self::find($module, $id);
        if ($row === null) {
            throw new HttpError(404, 'Record not found');
        }

        return self::hydrate($module, [$row])[0];
    }

    public static function exists(string $moduleKey, string $id): bool
    {
        $module = Modules::get($moduleKey);

        return Db::value("SELECT 1 FROM `{$module['table']}` WHERE id = ? AND deleted = 0", [$id]) !== null;
    }

    /** id => display name, for records that are not deleted. */
    public static function names(string $moduleKey, array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn ($id): bool => is_string($id) && $id !== '')));
        if (!$ids) {
            return [];
        }
        $module = Modules::get($moduleKey);
        $rows = Db::all(
            'SELECT t.id, ' . Modules::nameSql($module) . " AS name FROM `{$module['table']}` t"
            . ' WHERE t.deleted = 0 AND t.id IN (' . Db::marks($ids) . ')',
            $ids,
        );

        return array_column($rows, 'name', 'id');
    }

    /**
     * For rows that point at records by module label + id (favorites, recently viewed,
     * alerts, target list members): adds module key and current name, and drops rows
     * whose record no longer exists.
     */
    public static function resolveRefs(array $rows, string $labelColumn, string $idColumn): array
    {
        $idsByModule = [];
        foreach ($rows as $row) {
            $key = Modules::keyForLabel($row[$labelColumn]);
            if ($key !== null) {
                $idsByModule[$key][] = $row[$idColumn];
            }
        }
        $names = [];
        foreach ($idsByModule as $key => $ids) {
            $names[$key] = self::names($key, $ids);
        }

        $out = [];
        foreach ($rows as $row) {
            $key = Modules::keyForLabel($row[$labelColumn]);
            if ($key === null || !array_key_exists($row[$idColumn], $names[$key])) {
                continue;
            }
            $extra = array_diff_key($row, [$labelColumn => true, $idColumn => true]);
            $out[] = ['module' => $key, 'id' => $row[$idColumn], 'name' => $names[$key][$row[$idColumn]]] + $extra;
        }

        return $out;
    }

    /** Output keys of a record, in order (also the CSV export columns). */
    public static function outputColumns(array $module): array
    {
        $columns = ['id'];
        if (!empty($module['person'])) {
            $columns[] = 'name';
        }
        foreach ($module['fields'] as $field => $spec) {
            $columns[] = $field;
            if (in_array($spec['type'], ['user', 'relate', 'parent_id', 'link'], true)) {
                $columns[] = self::nameKey($field, $spec);
            }
        }
        if (!empty($module['emails'])) {
            $columns[] = 'email1';
        }

        return array_merge($columns, ['date_entered', 'created_by', 'created_by_name', 'date_modified', 'modified_user_id', 'modified_by_name']);
    }

    // ------------------------------------------------------------------ writing

    /**
     * Creates a record. Body: field => value, plus for accounts, contacts and leads
     * "email_addresses": [{"email_address": "...", "primary_address": true}, ...].
     * Keys that are not editable fields are ignored.
     */
    public static function create(string $moduleKey, array $input): array
    {
        $module = Modules::get($moduleKey);
        $userId = Auth::userId();
        [$values, $links, $emails] = self::clean($module, $input, null);

        foreach ($module['fields'] as $field => $spec) {
            if (array_key_exists('default', $spec) && !array_key_exists($field, $values)) {
                $values[$field] = $spec['default'];
            }
        }
        $values['assigned_user_id'] ??= $userId;

        $now = Util::now();
        $id = Util::uuid();
        $row = ['id' => $id] + $values + [
            'date_entered' => $now,
            'date_modified' => $now,
            'created_by' => $userId,
            'modified_user_id' => $userId,
            'deleted' => 0,
        ];
        self::computeFields($module, $row);

        Db::transaction(static function () use ($module, $moduleKey, $row, $links, $emails, $userId, $id): void {
            Db::insert($module['table'], $row);
            foreach ($links as $field => $accountId) {
                Relationships::setLhs($module['fields'][$field]['relationship'], $id, $accountId);
            }
            if ($emails !== null) {
                Emails::save($module['label'], $id, $emails);
            }
            if ($row['assigned_user_id'] !== $userId) {
                Alerts::assigned($moduleKey, $id, $row['assigned_user_id'], $userId);
            }
        });
        if ($moduleKey === 'opportunities') {
            Quotes::opportunitySaved($id, null);   // auto-send a quotation in the Proposal stage
        }

        return self::get($moduleKey, $id);
    }

    /** Updates the fields present in $input; other fields keep their values. */
    public static function update(string $moduleKey, string $id, array $input): array
    {
        $module = Modules::get($moduleKey);
        $existing = self::find($module, $id);
        if ($existing === null) {
            throw new HttpError(404, 'Record not found');
        }
        $userId = Auth::userId();
        [$values, $links, $emails] = self::clean($module, $input, $existing);

        $changes = [];
        foreach ($values as $field => $value) {
            if (!self::sameValue($module['fields'][$field]['type'], $existing[$field], $value)) {
                $changes[$field] = $value;
            }
        }
        if (isset($module['fields']['date_end'])) {
            $merged = array_merge($existing, $changes);
            self::computeFields($module, $merged);
            if ($merged['date_end'] !== $existing['date_end']) {
                $changes['date_end'] = $merged['date_end'];
            }
        }

        Db::transaction(static function () use ($module, $moduleKey, $id, $existing, $changes, $links, $emails, $userId): void {
            if ($changes || $links || $emails !== null) {
                Db::update(
                    $module['table'],
                    $changes + ['date_modified' => Util::now(), 'modified_user_id' => $userId],
                    'id = ?',
                    [$id],
                );
            }
            if (!empty($module['audited']) && $changes) {
                Audit::write($module, $id, $existing, $changes, $userId);
            }
            foreach ($links as $field => $accountId) {
                Relationships::setLhs($module['fields'][$field]['relationship'], $id, $accountId);
            }
            if ($emails !== null) {
                Emails::save($module['label'], $id, $emails);
            }
            $newAssignee = $changes['assigned_user_id'] ?? null;
            if ($newAssignee !== null && $newAssignee !== $userId) {
                Alerts::assigned($moduleKey, $id, $newAssignee, $userId);
            }
        });
        if ($moduleKey === 'opportunities') {
            Quotes::opportunitySaved($id, $existing['sales_stage']);   // auto-send on entering the Proposal stage
        }

        return self::get($moduleKey, $id);
    }

    /** Soft delete (deleted = 1), as SuiteCRM does. */
    public static function delete(string $moduleKey, string $id): void
    {
        $module = Modules::get($moduleKey);
        if (!self::exists($moduleKey, $id)) {
            throw new HttpError(404, 'Record not found');
        }
        $userId = Auth::userId();
        Db::transaction(static function () use ($module, $id, $userId): void {
            Db::run(
                "UPDATE `{$module['table']}` SET deleted = 1, date_modified = ?, modified_user_id = ? WHERE id = ?",
                [Util::now(), $userId, $id],
            );
            Favorites::removeRecord($module['label'], $id);
            Tracker::removeRecord($module['label'], $id);
        });
    }

    // ------------------------------------------------------------------ detail view actions

    /** "Find duplicates": records matching any of the module's duplicate rules, or sharing an email address. */
    public static function duplicates(string $moduleKey, string $id): array
    {
        $module = Modules::get($moduleKey);
        $row = self::find($module, $id);
        if ($row === null) {
            throw new HttpError(404, 'Record not found');
        }
        $conditions = [];
        $params = [];
        foreach ($module['duplicate_rules'] ?? [] as $rule) {
            $parts = [];
            $ruleParams = [];
            foreach ($rule as $field) {
                $value = trim((string) ($row[$field] ?? ''));
                if ($value === '') {
                    continue 2;
                }
                $parts[] = "TRIM(t.`$field`) = ?";
                $ruleParams[] = $value;
            }
            $conditions[] = '(' . implode(' AND ', $parts) . ')';
            array_push($params, ...$ruleParams);
        }
        if (!empty($module['emails'])) {
            $conditions[] = 't.id IN (SELECT r2.bean_id FROM email_addr_bean_rel r2'
                . ' JOIN email_addr_bean_rel r1 ON r1.email_address_id = r2.email_address_id'
                . ' WHERE r1.bean_module = ? AND r1.bean_id = ? AND r1.deleted = 0 AND r2.bean_module = ? AND r2.deleted = 0)';
            array_push($params, $module['label'], $id, $module['label']);
        }
        if (!$conditions) {
            return ['records' => []];
        }
        $rows = Db::all(
            'SELECT ' . self::selectList($module) . " FROM `{$module['table']}` t"
            . ' WHERE t.deleted = 0 AND t.id <> ? AND (' . implode(' OR ', $conditions) . ')'
            . ' ORDER BY t.date_entered DESC LIMIT 50',
            [$id, ...$params],
        );

        return ['records' => self::hydrate($module, $rows)];
    }

    /** "View change log" */
    public static function changeLog(string $moduleKey, string $id): array
    {
        $module = Modules::get($moduleKey);
        if (empty($module['audited'])) {
            throw new HttpError(404, "{$module['label']} have no change log");
        }
        if (!self::exists($moduleKey, $id)) {
            throw new HttpError(404, 'Record not found');
        }

        return ['records' => Audit::read($module, $id)];
    }

    // ------------------------------------------------------------------ internals

    private static function find(array $module, string $id): ?array
    {
        return Db::one(
            'SELECT ' . self::selectList($module) . " FROM `{$module['table']}` t WHERE t.id = ? AND t.deleted = 0",
            [$id],
        );
    }

    private static function selectList(array $module): string
    {
        return !empty($module['person']) ? 't.*, ' . Modules::nameSql($module) . ' AS name' : 't.*';
    }

    private static function nameKey(string $field, array $spec): string
    {
        return $spec['name_key'] ?? match ($spec['type']) {
            'user' => 'assigned_user_name',
            'parent_id' => 'parent_name',
            default => preg_replace('/_id$/', '', $field) . '_name',
        };
    }

    /** Turns database rows into API records: types, related names, email addresses. */
    private static function hydrate(array $module, array $rows): array
    {
        if (!$rows) {
            return [];
        }
        $ids = array_column($rows, 'id');

        $userIds = [];
        foreach ($rows as $row) {
            array_push($userIds, $row['assigned_user_id'] ?? null, $row['created_by'], $row['modified_user_id']);
        }
        $userNames = Users::names($userIds);

        $relatedNames = [];
        $linkedIds = [];
        $parentNames = [];
        foreach ($module['fields'] as $field => $spec) {
            if ($spec['type'] === 'relate') {
                $relatedNames[$field] = self::names($spec['module'], array_column($rows, $field));
            } elseif ($spec['type'] === 'link') {
                $relationship = Modules::relationship($spec['relationship']);
                $linkedIds[$field] = Relationships::lhsFor($spec['relationship'], $ids);
                $relatedNames[$field] = self::names($relationship['lhs_module'], array_values($linkedIds[$field]));
            } elseif ($spec['type'] === 'parent_id') {
                $refs = array_filter($rows, static fn (array $r): bool => $r['parent_id'] !== null);
                foreach (self::resolveRefs(array_map(
                    static fn (array $r): array => ['parent_type' => $r['parent_type'], 'parent_id' => $r['parent_id']],
                    $refs,
                ), 'parent_type', 'parent_id') as $ref) {
                    $parentNames[$ref['module'] . ':' . $ref['id']] = $ref['name'];
                }
            }
        }
        $emails = !empty($module['emails']) ? Emails::forRecords($module['label'], $ids) : [];
        $lastContacted = $module['table'] === 'accounts' ? self::lastContacted($ids) : [];

        $records = [];
        foreach ($rows as $row) {
            $record = ['id' => $row['id']];
            if (!empty($module['person'])) {
                $record['name'] = $row['name'];
            }
            foreach ($module['fields'] as $field => $spec) {
                if ($spec['type'] === 'link') {
                    $accountId = $linkedIds[$field][$row['id']] ?? null;
                    $record[$field] = $accountId;
                    $record[self::nameKey($field, $spec)] = $accountId !== null ? ($relatedNames[$field][$accountId] ?? null) : null;
                    continue;
                }
                $value = $row[$field];
                $record[$field] = match ($spec['type']) {
                    'int' => $value === null ? null : (int) $value,
                    'decimal' => $value === null ? null : (float) $value,
                    default => $value,
                };
                if ($spec['type'] === 'user') {
                    $record[self::nameKey($field, $spec)] = $userNames[$value ?? ''] ?? null;
                } elseif ($spec['type'] === 'relate') {
                    $record[self::nameKey($field, $spec)] = $value !== null ? ($relatedNames[$field][$value] ?? null) : null;
                } elseif ($spec['type'] === 'parent_id') {
                    $parentKey = Modules::keyForLabel($row['parent_type']) . ':' . $value;
                    $record[self::nameKey($field, $spec)] = $value !== null ? ($parentNames[$parentKey] ?? null) : null;
                }
            }
            if (!empty($module['emails'])) {
                $list = $emails[$row['id']] ?? [];
                $record['email1'] = $list[0]['email_address'] ?? null;   // primary address is listed first
                $record['email_addresses'] = $list;
            }
            if ($module['table'] === 'accounts') {
                $record['last_contacted'] = $lastContacted[$row['id']] ?? null;
            }
            $record['date_entered'] = $row['date_entered'];
            $record['created_by'] = $row['created_by'];
            $record['created_by_name'] = $userNames[$row['created_by'] ?? ''] ?? null;
            $record['date_modified'] = $row['date_modified'];
            $record['modified_user_id'] = $row['modified_user_id'];
            $record['modified_by_name'] = $userNames[$row['modified_user_id'] ?? ''] ?? null;
            $records[] = $record;
        }

        return $records;
    }

    /**
     * Last contact with each account: the latest held call or meeting, or quotation email,
     * linked to the account (parent_type Accounts). Account id => datetime; accounts with none are left out.
     */
    private static function lastContacted(array $ids): array
    {
        $marks = Db::marks($ids);
        $rows = Db::all(
            "SELECT parent_id, MAX(d) AS last FROM ("
            . " SELECT parent_id, date_start AS d FROM calls WHERE deleted = 0 AND status = 'Held' AND parent_type = 'Accounts' AND parent_id IN ($marks)"
            . " UNION ALL SELECT parent_id, date_start FROM meetings WHERE deleted = 0 AND status = 'Held' AND parent_type = 'Accounts' AND parent_id IN ($marks)"
            . " UNION ALL SELECT parent_id, date_sent FROM emails WHERE deleted = 0 AND parent_type = 'Accounts' AND parent_id IN ($marks)"
            . ") x WHERE d IS NOT NULL GROUP BY parent_id",
            [...$ids, ...$ids, ...$ids],
        );

        return array_column($rows, 'last', 'parent_id');
    }

    private static function where(array $module, array $query, ?array $scope): array
    {
        $where = ['t.deleted = 0'];
        $params = [];
        if ($scope !== null) {
            $where[] = '(' . $scope[0] . ')';
            array_push($params, ...$scope[1]);
        }

        $q = trim((string) ($query['q'] ?? ''));
        if ($q !== '') {
            $like = Util::likePrefix($q);
            $or = [];
            foreach ($module['search_fields'] as $field) {
                $or[] = "t.`$field` LIKE ?";
                $params[] = $like;
            }
            if (!empty($module['person'])) {
                $or[] = Modules::nameSql($module) . ' LIKE ?';
                $params[] = $like;
            }
            $where[] = '(' . implode(' OR ', $or) . ')';
        }

        $filters = $query['filter'] ?? [];
        if (!is_array($filters)) {
            throw new HttpError(400, 'Use filter[field]=value');
        }
        foreach ($filters as $field => $value) {
            if ($value === '' || $value === null || $value === []) {
                continue;
            }
            [$sql, $filterParams] = self::filterCondition($module, (string) $field, $value);
            $where[] = $sql;
            array_push($params, ...$filterParams);
        }

        return [implode(' AND ', $where), $params];
    }

    private static function filterCondition(array $module, string $field, mixed $value): array
    {
        if ($field === 'email' && !empty($module['emails'])) {
            return [
                't.id IN (SELECT r.bean_id FROM email_addr_bean_rel r JOIN email_addresses e ON e.id = r.email_address_id'
                . ' WHERE r.bean_module = ? AND r.deleted = 0 AND e.email_address_caps LIKE ?)',
                [$module['label'], Util::likePrefix(strtoupper(self::scalar($value)))],
            ];
        }
        $spec = $module['fields'][$field]
            ?? (in_array($field, ['date_entered', 'date_modified'], true) ? ['type' => 'datetime'] : null);
        if ($spec === null) {
            throw new HttpError(400, "Unknown filter field: $field");
        }
        $type = $spec['type'];

        if ($type === 'link') {
            $relationship = Modules::relationship($spec['relationship']);

            return [
                "t.id IN (SELECT r.`{$relationship['rhs_key']}` FROM `{$relationship['table']}` r WHERE r.`{$relationship['lhs_key']}` = ?)",
                [self::scalar($value)],
            ];
        }
        if ($type === 'date' || $type === 'datetime') {
            if (!is_array($value)) {
                return ["DATE(t.`$field`) = ?", [self::filterDate($value, '')]];
            }
            $parts = [];
            $params = [];
            if (($value['from'] ?? '') !== '') {
                $parts[] = "t.`$field` >= ?";
                $params[] = self::filterDate($value['from'], ' 00:00:00');
            }
            if (($value['to'] ?? '') !== '') {
                $parts[] = "t.`$field` <= ?";
                $params[] = self::filterDate($value['to'], ' 23:59:59');
            }
            if (!$parts) {
                throw new HttpError(400, "Use filter[$field][from] and/or filter[$field][to]");
            }

            return [implode(' AND ', $parts), $params];
        }
        if (is_array($value)) {
            $values = array_map([self::class, 'scalar'], array_values($value));

            return ["t.`$field` IN (" . Db::marks($values) . ')', $values];
        }
        if ($type === 'varchar' || $type === 'text') {
            return ["t.`$field` LIKE ?", [Util::likePrefix(self::scalar($value))]];
        }

        return ["t.`$field` = ?", [self::scalar($value)]];
    }

    private static function scalar(mixed $value): string
    {
        if (!is_scalar($value)) {
            throw new HttpError(400, 'Invalid filter value');
        }

        return (string) $value;
    }

    /** Accepts YYYY-MM-DD or YYYY-MM-DD HH:MM:SS; a date alone gets $timeSuffix. */
    private static function filterDate(mixed $value, string $timeSuffix): string
    {
        $value = self::scalar($value);
        if (self::parseDate($value) !== null) {
            return $value . $timeSuffix;
        }
        if ($timeSuffix !== '' && ($dateTime = self::parseDateTime($value)) !== null) {
            return $dateTime;
        }
        throw new HttpError(400, "Invalid date in filter: $value");
    }

    private static function orderBy(array $module, array $query): string
    {
        if (!isset($query['sort']) || $query['sort'] === '') {
            return 't.date_entered DESC, t.id DESC';
        }
        $sort = (string) $query['sort'];
        $dir = strtolower((string) ($query['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        if ($sort === 'name' && !empty($module['person'])) {
            return "t.last_name $dir, t.first_name $dir, t.id $dir";
        }
        $isColumn = isset($module['fields'][$sort]) && $module['fields'][$sort]['type'] !== 'link';
        if (!$isColumn && !in_array($sort, ['date_entered', 'date_modified'], true)) {
            throw new HttpError(400, "Cannot sort by $sort");
        }

        return "t.`$sort` $dir, t.id $dir";
    }

    /**
     * Checks input against the field definitions.
     * Returns [column values, link field values, email list or null when not sent].
     * $existing is the current row on update and null on create.
     */
    private static function clean(array $module, array $input, ?array $existing): array
    {
        $isCreate = $existing === null;
        $values = [];
        $links = [];
        $errors = [];

        foreach ($module['fields'] as $field => $spec) {
            if (!empty($spec['readonly'])) {
                continue;
            }
            if (!array_key_exists($field, $input)) {
                if ($isCreate && !empty($spec['required']) && !array_key_exists('default', $spec)) {
                    $errors[$field] = 'Required';
                }
                continue;
            }
            $value = is_string($input[$field]) ? trim($input[$field]) : $input[$field];
            if ($value === '' || $value === null) {
                if (!empty($spec['required'])) {
                    $errors[$field] = 'Required';
                } elseif ($spec['type'] === 'link') {
                    $links[$field] = null;
                } else {
                    $values[$field] = null;
                }
                continue;
            }
            $error = null;
            $clean = self::cleanValue($spec, $value, $error);
            if ($error !== null) {
                $errors[$field] = $error;
            } elseif ($spec['type'] === 'link') {
                $links[$field] = $clean;
            } else {
                $values[$field] = $clean;
            }
        }

        // calls, meetings, tasks, notes: parent_id has to be a record of the module named in parent_type
        $parentSent = isset($module['fields']['parent_type'])
            && (array_key_exists('parent_id', $values) || array_key_exists('parent_type', $values));
        if ($parentSent && !isset($errors['parent_id']) && !isset($errors['parent_type'])) {
            $parentType = array_key_exists('parent_type', $values) ? $values['parent_type'] : ($existing['parent_type'] ?? null);
            $parentId = array_key_exists('parent_id', $values) ? $values['parent_id'] : ($existing['parent_id'] ?? null);
            if ($parentId !== null && $parentType === null) {
                $errors['parent_type'] = 'Required when parent_id is set';
            } elseif ($parentId !== null && !self::exists(Modules::keyForLabel($parentType), $parentId)) {
                $errors['parent_id'] = "No $parentType record with this id";
            }
        }

        $emails = null;
        if (!empty($module['emails']) && array_key_exists('email_addresses', $input)) {
            $emails = Emails::clean($input['email_addresses'], $errors);
        }

        if ($errors) {
            throw new HttpError(422, 'Validation failed', ['errors' => $errors]);
        }

        return [$values, $links, $emails];
    }

    private static function cleanValue(array $spec, mixed $value, ?string &$error): mixed
    {
        switch ($spec['type']) {
            case 'varchar':
            case 'text':
                if (!is_string($value) && !is_int($value) && !is_float($value)) {
                    $error = 'Must be text';

                    return null;
                }
                $value = (string) $value;
                if (isset($spec['len']) && mb_strlen($value) > $spec['len']) {
                    $error = "Must be at most {$spec['len']} characters";

                    return null;
                }

                return $value;

            case 'enum':
            case 'parent_type':
                if (!is_string($value) || !array_key_exists($value, $spec['options'])) {
                    $error = 'Must be one of: ' . implode(', ', array_keys($spec['options']));

                    return null;
                }

                return $value;

            case 'int':
                if (is_int($value) || (is_string($value) && preg_match('/^-?\d+$/', $value))) {
                    return (int) $value;
                }
                $error = 'Must be a whole number';

                return null;

            case 'decimal':
                if ((is_int($value) || is_float($value) || is_string($value)) && is_numeric($value)) {
                    return (string) $value;
                }
                $error = 'Must be a number';

                return null;

            case 'date':
                $date = is_string($value) ? self::parseDate($value) : null;
                if ($date === null) {
                    $error = 'Must be a date (YYYY-MM-DD)';
                }

                return $date;

            case 'datetime':
                $dateTime = is_string($value) ? self::parseDateTime($value) : null;
                if ($dateTime === null) {
                    $error = 'Must be a date and time in UTC (YYYY-MM-DD HH:MM:SS)';
                }

                return $dateTime;

            case 'user':
                if (!is_string($value) || !Users::isActive($value)) {
                    $error = 'No active user with this id';

                    return null;
                }

                return $value;

            case 'relate':
                if (!is_string($value) || !self::exists($spec['module'], $value)) {
                    $error = 'No ' . Modules::get($spec['module'])['label'] . ' record with this id';

                    return null;
                }

                return $value;

            case 'link':
                $module = Modules::relationship($spec['relationship'])['lhs_module'];
                if (!is_string($value) || !self::exists($module, $value)) {
                    $error = 'No ' . Modules::get($module)['label'] . ' record with this id';

                    return null;
                }

                return $value;

            case 'parent_id':
                if (!is_string($value)) {
                    $error = 'Must be a record id';

                    return null;
                }

                return $value;   // checked against parent_type in clean()
        }
        $error = 'Unsupported field type';

        return null;
    }

    private static function parseDate(string $value): ?string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));

        return ($date !== false && $date->format('Y-m-d') === $value) ? $value : null;
    }

    /** Accepts "2026-10-23 10:00", "2026-10-23 10:00:00" or "2026-10-23T10:00:00Z". */
    private static function parseDateTime(string $value): ?string
    {
        if (!preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})(:\d{2})?Z?$/', $value, $m)) {
            return null;
        }
        $normalized = $m[1] . ' ' . $m[2] . (($m[3] ?? '') !== '' ? $m[3] : ':00');
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $normalized, new \DateTimeZone('UTC'));

        return ($date !== false && $date->format('Y-m-d H:i:s') === $normalized) ? $normalized : null;
    }

    private static function sameValue(string $type, mixed $old, mixed $new): bool
    {
        if ($old === null || $new === null) {
            return $old === $new;
        }

        return match ($type) {
            'decimal' => (float) $old === (float) $new,
            'int' => (int) $old === (int) $new,
            default => (string) $old === (string) $new,
        };
    }

    /** Calls and meetings: date_end = date_start + duration. */
    private static function computeFields(array $module, array &$row): void
    {
        if (!isset($module['fields']['date_end'])) {
            return;
        }
        if (($row['date_start'] ?? null) === null) {
            $row['date_end'] = null;

            return;
        }
        $minutes = (int) ($row['duration_hours'] ?? 0) * 60 + (int) ($row['duration_minutes'] ?? 0);
        $row['date_end'] = (new \DateTimeImmutable($row['date_start'], new \DateTimeZone('UTC')))
            ->modify("+$minutes minutes")
            ->format('Y-m-d H:i:s');
    }
}
