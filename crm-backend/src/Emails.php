<?php

declare(strict_types=1);

namespace App;

/**
 * Email addresses work as in SuiteCRM 7: each address is one row in email_addresses,
 * linked to records through email_addr_bean_rel with a primary_address flag.
 */
final class Emails
{
    /** record id => [['email_address' => ..., 'primary_address' => bool], ...], primary first. */
    public static function forRecords(string $moduleLabel, array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $rows = Db::all(
            'SELECT r.bean_id, e.email_address, r.primary_address FROM email_addr_bean_rel r'
            . ' JOIN email_addresses e ON e.id = r.email_address_id AND e.deleted = 0'
            . ' WHERE r.bean_module = ? AND r.deleted = 0 AND r.bean_id IN (' . Db::marks($ids) . ')'
            . ' ORDER BY r.primary_address DESC, r.date_created, e.email_address',
            [$moduleLabel, ...$ids],
        );
        $out = [];
        foreach ($rows as $row) {
            $out[$row['bean_id']][] = [
                'email_address' => $row['email_address'],
                'primary_address' => (bool) $row['primary_address'],
            ];
        }

        return $out;
    }

    /**
     * Validates the "email_addresses" input. Items can be objects
     * ({"email_address": "...", "primary_address": true}) or plain strings.
     * The first address is primary when none is marked.
     */
    public static function clean(mixed $input, array &$errors): array
    {
        if (!is_array($input) || !array_is_list($input)) {
            $errors['email_addresses'] = 'Must be a list';

            return [];
        }
        $list = [];
        $seen = [];
        $hasPrimary = false;
        foreach ($input as $i => $item) {
            $address = is_array($item) ? ($item['email_address'] ?? null) : $item;
            $address = is_string($address) ? trim($address) : null;
            if ($address === null || $address === '' || filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
                $errors["email_addresses.$i"] = 'Invalid email address';
                continue;
            }
            if (isset($seen[strtoupper($address)])) {
                continue;
            }
            $seen[strtoupper($address)] = true;
            $primary = !$hasPrimary && is_array($item) && !empty($item['primary_address']);
            $hasPrimary = $hasPrimary || $primary;
            $list[] = ['email_address' => $address, 'primary_address' => $primary];
        }
        if ($list && !$hasPrimary) {
            $list[0]['primary_address'] = true;
        }

        return $list;
    }

    /** Replaces the record's email addresses with $list (output of clean()). */
    public static function save(string $moduleLabel, string $recordId, array $list): void
    {
        Db::run('DELETE FROM email_addr_bean_rel WHERE bean_module = ? AND bean_id = ?', [$moduleLabel, $recordId]);
        $now = Util::now();
        foreach ($list as $item) {
            $caps = strtoupper($item['email_address']);
            $emailId = Db::value('SELECT id FROM email_addresses WHERE email_address_caps = ? AND deleted = 0 LIMIT 1', [$caps]);
            if ($emailId === null) {
                $emailId = Util::uuid();
                Db::insert('email_addresses', [
                    'id' => $emailId,
                    'email_address' => $item['email_address'],
                    'email_address_caps' => $caps,
                    'date_created' => $now,
                    'date_modified' => $now,
                ]);
            }
            Db::insert('email_addr_bean_rel', [
                'id' => Util::uuid(),
                'email_address_id' => $emailId,
                'bean_id' => $recordId,
                'bean_module' => $moduleLabel,
                'primary_address' => $item['primary_address'] ? 1 : 0,
                'date_created' => $now,
                'date_modified' => $now,
            ]);
        }
    }

    /** Merge: the kept record gets the addresses of the merged-away records as extra addresses. */
    public static function merge(string $moduleLabel, array $duplicateIds, string $primaryId): void
    {
        $all = self::forRecords($moduleLabel, [$primaryId, ...$duplicateIds]);
        $list = $all[$primaryId] ?? [];
        foreach ($duplicateIds as $id) {
            foreach ($all[$id] ?? [] as $item) {
                $list[] = ['email_address' => $item['email_address'], 'primary_address' => false];
            }
        }
        $errors = [];
        self::save($moduleLabel, $primaryId, self::clean($list, $errors));
        Db::run(
            'DELETE FROM email_addr_bean_rel WHERE bean_module = ? AND bean_id IN (' . Db::marks($duplicateIds) . ')',
            [$moduleLabel, ...$duplicateIds],
        );
    }
}
