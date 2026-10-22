<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * "Add to target list" bulk action. Target lists themselves are the
 * prospect_lists module (created with POST /api/prospect_lists).
 */
final class TargetLists
{
    /** Body {"module": "accounts", "ids": [...]}. Records already in the list are skipped. */
    public static function add(string $listId, array $body): array
    {
        if (!Records::exists('prospect_lists', $listId)) {
            throw new HttpError(404, 'Target list not found');
        }
        $moduleKey = $body['module'] ?? null;
        if (!in_array($moduleKey, Modules::TARGET_LIST_MEMBERS, true)) {
            throw new HttpError(422, 'Validation failed', ['errors' => ['module' => 'Must be one of: ' . implode(', ', Modules::TARGET_LIST_MEMBERS)]]);
        }
        $ids = Bulk::ids($body['ids'] ?? null, 'ids');
        $label = Modules::get($moduleKey)['label'];

        $added = 0;
        Db::transaction(static function () use ($listId, $moduleKey, $ids, $label, &$added): void {
            foreach ($ids as $id) {
                if (!Records::exists($moduleKey, $id)) {
                    continue;
                }
                $inList = Db::value(
                    'SELECT 1 FROM prospect_lists_prospects WHERE prospect_list_id = ? AND related_id = ?',
                    [$listId, $id],
                ) !== null;
                if ($inList) {
                    continue;
                }
                Db::insert('prospect_lists_prospects', [
                    'id' => Util::uuid(),
                    'prospect_list_id' => $listId,
                    'related_id' => $id,
                    'related_type' => $label,
                    'date_modified' => Util::now(),
                ]);
                $added++;
            }
        });

        return ['added' => $added];
    }

    public static function members(string $listId): array
    {
        if (!Records::exists('prospect_lists', $listId)) {
            throw new HttpError(404, 'Target list not found');
        }
        $rows = Db::all(
            'SELECT related_type, related_id, date_modified AS date_added FROM prospect_lists_prospects'
            . ' WHERE prospect_list_id = ? ORDER BY date_modified DESC, id',
            [$listId],
        );

        return ['records' => Records::resolveRefs($rows, 'related_type', 'related_id')];
    }

    /** Merge: list memberships of the duplicates move to the kept record. */
    public static function merge(array $duplicateIds, string $primaryId): void
    {
        foreach ($duplicateIds as $id) {
            Db::run(
                'DELETE d FROM prospect_lists_prospects d JOIN prospect_lists_prospects p'
                . ' ON p.prospect_list_id = d.prospect_list_id AND p.related_id = ? WHERE d.related_id = ?',
                [$primaryId, $id],
            );
            Db::run('UPDATE prospect_lists_prospects SET related_id = ? WHERE related_id = ?', [$primaryId, $id]);
        }
    }
}
