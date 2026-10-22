<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * Global search box in the top bar. Searches the modules marked global_search
 * (accounts, contacts, leads, opportunities, cases): names starting with q.
 */
final class Search
{
    /** Query: q, limit (results per module, default 5). */
    public static function run(array $query): array
    {
        $q = trim((string) ($query['q'] ?? ''));
        if ($q === '') {
            throw new HttpError(400, 'q is required');
        }
        $limit = min(20, max(1, (int) ($query['limit'] ?? 5)));

        $results = [];
        foreach (Modules::all() as $key => $module) {
            if (empty($module['global_search'])) {
                continue;
            }
            $page = Records::list($key, ['q' => $q, 'per_page' => $limit, 'sort' => 'name']);
            $results[] = [
                'module' => $key,
                'label' => $module['label'],
                'total' => $page['total'],
                'records' => array_map(
                    static fn (array $r): array => ['id' => $r['id'], 'name' => $r['name']],
                    $page['records'],
                ),
            ];
        }

        return ['q' => $q, 'results' => $results];
    }
}
