<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * Data for the Home dashboard dashlets. Each one is a separate endpoint so the
 * dashlet's refresh button only reloads that dashlet.
 * Query: page, per_page (default 5).
 */
final class Dashlets
{
    public static function get(string $name, array $query): array
    {
        $userId = Auth::userId();
        $paging = ['page' => $query['page'] ?? 1, 'per_page' => $query['per_page'] ?? 5];
        $closed = Modules::CLOSED_SALES_STAGES;

        return match ($name) {
            'my-calls' => Records::list(
                'calls',
                $paging + ['sort' => 'date_start', 'dir' => 'asc'],
                ["t.assigned_user_id = ? AND t.status = 'Planned'", [$userId]],
            ),
            'my-meetings' => Records::list(
                'meetings',
                $paging + ['sort' => 'date_start', 'dir' => 'asc'],
                ["t.assigned_user_id = ? AND t.status = 'Planned'", [$userId]],
            ),
            'my-open-opportunities' => Records::list(
                'opportunities',
                $paging + ['sort' => 'date_closed', 'dir' => 'asc'],
                ['t.assigned_user_id = ? AND t.sales_stage NOT IN (' . Db::marks($closed) . ')', [$userId, ...$closed]],
            ),
            'my-accounts' => Records::list(
                'accounts',
                $paging + ['sort' => 'name', 'dir' => 'asc'],
                ['t.assigned_user_id = ?', [$userId]],
            ),
            'my-leads' => Records::list(
                'leads',
                $paging + ['sort' => 'date_entered', 'dir' => 'desc'],
                ['t.assigned_user_id = ?', [$userId]],
            ),
            'pipeline-by-sales-stage' => self::pipeline($userId),
            default => throw new HttpError(404, "Unknown dashlet: $name"),
        };
    }

    /** Count and total amount of the user's open opportunities per sales stage, in stage order. */
    private static function pipeline(string $userId): array
    {
        $closed = Modules::CLOSED_SALES_STAGES;
        $rows = Db::all(
            'SELECT sales_stage, COUNT(*) AS count, COALESCE(SUM(amount), 0) AS amount FROM opportunities'
            . ' WHERE deleted = 0 AND assigned_user_id = ? AND sales_stage NOT IN (' . Db::marks($closed) . ')'
            . ' GROUP BY sales_stage',
            [$userId, ...$closed],
        );
        $byStage = array_column($rows, null, 'sales_stage');

        $stages = [];
        foreach (Modules::get('opportunities')['fields']['sales_stage']['options'] as $stage => $label) {
            if (in_array($stage, $closed, true)) {
                continue;
            }
            $stages[] = [
                'sales_stage' => $stage,
                'label' => $label,
                'count' => (int) ($byStage[$stage]['count'] ?? 0),
                'amount' => (float) ($byStage[$stage]['amount'] ?? 0),
            ];
        }

        return ['stages' => $stages, 'total_amount' => array_sum(array_column($stages, 'amount'))];
    }
}
