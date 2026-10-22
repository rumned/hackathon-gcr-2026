<?php

declare(strict_types=1);

namespace App;

final class Users
{
    /** id => user_name, for "Assigned to", "created by" and "modified by" columns. */
    public static function names(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn ($id): bool => is_string($id) && $id !== '')));
        if (!$ids) {
            return [];
        }
        $rows = Db::all('SELECT id, user_name FROM users WHERE id IN (' . Db::marks($ids) . ')', $ids);

        return array_column($rows, 'user_name', 'id');
    }

    public static function isActive(string $id): bool
    {
        return Db::value("SELECT 1 FROM users WHERE id = ? AND deleted = 0 AND status = 'Active'", [$id]) !== null;
    }

    /** Users list. Query: q (user name or name starts with), status (Active / Inactive). */
    public static function list(array $query): array
    {
        $where = ['deleted = 0'];
        $params = [];
        if (($query['status'] ?? '') !== '') {
            $where[] = 'status = ?';
            $params[] = (string) $query['status'];
        }
        $q = trim((string) ($query['q'] ?? ''));
        if ($q !== '') {
            $like = Util::likePrefix($q);
            $where[] = '(user_name LIKE ? OR first_name LIKE ? OR last_name LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        $rows = Db::all('SELECT * FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY user_name LIMIT 200', $params);

        return ['records' => array_map([self::class, 'toPublic'], $rows)];
    }

    /** User fields that are safe to send to the browser (no password hash). */
    public static function toPublic(array $user): array
    {
        return [
            'id' => $user['id'],
            'user_name' => $user['user_name'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'full_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'title' => $user['title'],
            'department' => $user['department'],
            'phone_work' => $user['phone_work'],
            'is_admin' => (bool) $user['is_admin'],
            'status' => $user['status'],
        ];
    }
}
