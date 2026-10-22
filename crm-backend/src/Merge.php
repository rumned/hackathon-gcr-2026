<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * "Merge" bulk action: keeps one record, moves everything linked to the
 * duplicates onto it, then deletes the duplicates.
 */
final class Merge
{
    /**
     * Body {"primary_id": "...", "duplicate_ids": [...], "fields": {...optional values for the kept record}}
     */
    public static function run(string $moduleKey, array $body): array
    {
        $module = Modules::get($moduleKey);
        $primaryId = $body['primary_id'] ?? null;
        $duplicateIds = Bulk::ids($body['duplicate_ids'] ?? null, 'duplicate_ids');
        $fields = $body['fields'] ?? [];

        $errors = [];
        if (!is_string($primaryId) || !Records::exists($moduleKey, $primaryId)) {
            $errors['primary_id'] = 'No ' . $module['label'] . ' record with this id';
        } elseif (in_array($primaryId, $duplicateIds, true)) {
            $errors['duplicate_ids'] = 'Must not contain primary_id';
        }
        foreach ($duplicateIds as $id) {
            if (!Records::exists($moduleKey, $id)) {
                $errors['duplicate_ids'] = "No {$module['label']} record with id $id";
            }
        }
        if (!is_array($fields)) {
            $errors['fields'] = 'Must be an object of field => value';
        }
        if ($errors) {
            throw new HttpError(422, 'Validation failed', ['errors' => $errors]);
        }

        Db::transaction(static function () use ($module, $moduleKey, $primaryId, $duplicateIds, $fields): void {
            if ($fields) {
                Records::update($moduleKey, $primaryId, $fields);
            }
            $marks = Db::marks($duplicateIds);
            foreach (Modules::all() as $other) {
                // relate fields pointing at this module, e.g. leads.account_id, accounts.parent_id
                foreach ($other['fields'] as $field => $spec) {
                    if ($spec['type'] === 'relate' && $spec['module'] === $moduleKey) {
                        Db::run(
                            "UPDATE `{$other['table']}` SET `$field` = ? WHERE `$field` IN ($marks) AND id <> ?",
                            [$primaryId, ...$duplicateIds, $primaryId],
                        );
                    }
                }
                // calls, meetings, tasks and notes attached to a duplicate
                if (isset($other['fields']['parent_type'])) {
                    Db::run(
                        "UPDATE `{$other['table']}` SET parent_id = ? WHERE parent_type = ? AND parent_id IN ($marks)",
                        [$primaryId, $module['label'], ...$duplicateIds],
                    );
                }
            }
            Relationships::merge($moduleKey, $duplicateIds, $primaryId);
            TargetLists::merge($duplicateIds, $primaryId);
            if (!empty($module['emails'])) {
                Emails::merge($module['label'], $duplicateIds, $primaryId);
            }
            foreach ($duplicateIds as $id) {
                Records::delete($moduleKey, $id);
            }
        });

        return Records::get($moduleKey, $primaryId);
    }
}
