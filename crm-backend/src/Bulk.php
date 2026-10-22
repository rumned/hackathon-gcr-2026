<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;
use App\Http\Response;

/**
 * List view "Bulk action" menu (Delete, Export, Mass update) and "Import" from the module menu.
 * Merge and "Add to target list" are in Merge.php and TargetLists.php.
 */
final class Bulk
{
    private const MAX_IDS = 1000;

    /** Body {"ids": [...]}. Ids that don't exist are skipped. */
    public static function delete(string $moduleKey, array $body): array
    {
        $ids = self::ids($body['ids'] ?? null, 'ids');
        $deleted = 0;
        Db::transaction(static function () use ($moduleKey, $ids, &$deleted): void {
            foreach ($ids as $id) {
                if (Records::exists($moduleKey, $id)) {
                    Records::delete($moduleKey, $id);
                    $deleted++;
                }
            }
        });

        return ['deleted' => $deleted];
    }

    /**
     * Body {"ids": [...], "fields": {"assigned_user_id": "...", "account_type": "Customer"}}.
     * All records are updated or, on a validation error, none are.
     */
    public static function update(string $moduleKey, array $body): array
    {
        $ids = self::ids($body['ids'] ?? null, 'ids');
        $fields = $body['fields'] ?? null;
        if (!is_array($fields) || !$fields || array_is_list($fields)) {
            throw new HttpError(422, 'Validation failed', ['errors' => ['fields' => 'Must be an object of field => value']]);
        }
        $updated = 0;
        Db::transaction(static function () use ($moduleKey, $ids, $fields, &$updated): void {
            foreach ($ids as $id) {
                if (Records::exists($moduleKey, $id)) {
                    Records::update($moduleKey, $id, $fields);
                    $updated++;
                }
            }
        });

        return ['updated' => $updated];
    }

    /**
     * CSV download. Query: ids=id1,id2 for selected rows, otherwise every record
     * matching the same q / filter parameters as the list view.
     */
    public static function export(string $moduleKey, array $query): Response
    {
        $module = Modules::get($moduleKey);
        $scope = null;
        if (($query['ids'] ?? '') !== '') {
            $ids = self::ids(explode(',', (string) $query['ids']), 'ids');
            $scope = ['t.id IN (' . Db::marks($ids) . ')', $ids];
        }
        $records = Records::all($moduleKey, $query, $scope);
        $columns = Records::outputColumns($module);

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, $columns, ',', '"', '');
        foreach ($records as $record) {
            fputcsv($handle, array_map(static fn (string $c): string => (string) ($record[$c] ?? ''), $columns), ',', '"', '');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return new Response(200, $csv, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $moduleKey . '.csv"',
        ]);
    }

    /**
     * Body {"csv": "name,billing_address_city,email1\nAcme,London,info@acme.example"}.
     * The header row holds field names; "email1" sets the primary email address.
     * Each row is created on its own, so valid rows are kept when others fail.
     */
    public static function import(string $moduleKey, array $body): array
    {
        $module = Modules::get($moduleKey);
        $csv = $body['csv'] ?? null;
        if (!is_string($csv) || trim($csv) === '') {
            throw new HttpError(422, 'Validation failed', ['errors' => ['csv' => 'Must be the text of a CSV file with a header row']]);
        }
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, $csv);
        rewind($handle);

        $header = fgetcsv($handle, null, ',', '"', '') ?: [];
        $header = array_map(static fn ($h): string => strtolower(trim((string) $h)), $header);
        if ($header) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);   // byte order mark from Excel
        }
        $accepted = array_keys(array_filter($module['fields'], static fn (array $spec): bool => empty($spec['readonly'])));
        if (!empty($module['emails'])) {
            $accepted[] = 'email1';
        }

        $createdIds = [];
        $errors = [];
        $rowNumber = 0;
        while (($cells = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($cells === [null]) {
                continue;   // blank line
            }
            $rowNumber++;
            $input = [];
            foreach ($header as $i => $column) {
                $value = trim((string) ($cells[$i] ?? ''));
                if ($value === '' || !in_array($column, $accepted, true)) {
                    continue;
                }
                if ($column === 'email1') {
                    $input['email_addresses'] = [['email_address' => $value, 'primary_address' => true]];
                } else {
                    $input[$column] = $value;
                }
            }
            try {
                $createdIds[] = Records::create($moduleKey, $input)['id'];
            } catch (HttpError $e) {
                $errors[] = ['row' => $rowNumber, 'error' => $e->getMessage()] + $e->details;
            }
        }
        fclose($handle);

        return [
            'created' => count($createdIds),
            'ids' => $createdIds,
            'errors' => $errors,
            'ignored_columns' => array_values(array_diff($header, $accepted)),
        ];
    }

    /** Checks a list of record ids from a request. */
    public static function ids(mixed $ids, string $key): array
    {
        $valid = is_array($ids) && $ids && count($ids) <= self::MAX_IDS
            && !array_filter($ids, static fn ($id): bool => !is_string($id) || trim($id) === '');
        if (!$valid) {
            throw new HttpError(422, 'Validation failed', ['errors' => [$key => 'Must be a list of 1 to ' . self::MAX_IDS . ' record ids']]);
        }

        return array_values(array_unique(array_map('trim', $ids)));
    }
}
