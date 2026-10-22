<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * Per-user settings stored as JSON by category, e.g.
 *   "Home"               dashboard tabs and which dashlets are on each ("Add dashlets", "Add tab", "Edit tabs")
 *   "Accounts_ListView"  columns chosen with the list view's "Columns" button
 * The backend stores whatever JSON the frontend sends; the frontend decides its shape.
 */
final class Preferences
{
    private const MAX_BYTES = 65535;

    public static function get(string $category): array
    {
        self::checkCategory($category);
        $contents = Db::value(
            'SELECT contents FROM user_preferences WHERE assigned_user_id = ? AND category = ? AND deleted = 0',
            [Auth::userId(), $category],
        );

        return ['category' => $category, 'contents' => $contents === null ? null : json_decode($contents, true)];
    }

    /** Body {"contents": <any JSON>} */
    public static function put(string $category, array $body): array
    {
        self::checkCategory($category);
        if (!array_key_exists('contents', $body)) {
            throw new HttpError(422, 'Validation failed', ['errors' => ['contents' => 'Required']]);
        }
        $json = json_encode($body['contents'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (strlen($json) > self::MAX_BYTES) {
            throw new HttpError(422, 'Validation failed', ['errors' => ['contents' => 'Too large']]);
        }
        $userId = Auth::userId();
        $now = Util::now();
        $updated = Db::run(
            'UPDATE user_preferences SET contents = ?, date_modified = ?, deleted = 0 WHERE assigned_user_id = ? AND category = ?',
            [$json, $now, $userId, $category],
        );
        $exists = $updated > 0 || Db::value(
            'SELECT 1 FROM user_preferences WHERE assigned_user_id = ? AND category = ?',
            [$userId, $category],
        ) !== null;
        if (!$exists) {
            Db::insert('user_preferences', [
                'id' => Util::uuid(),
                'category' => $category,
                'assigned_user_id' => $userId,
                'contents' => $json,
                'date_entered' => $now,
                'date_modified' => $now,
            ]);
        }

        return self::get($category);
    }

    private static function checkCategory(string $category): void
    {
        if (!preg_match('/^[A-Za-z0-9_]{1,50}$/', $category)) {
            throw new HttpError(400, 'Category may contain letters, numbers and _ (max 50)');
        }
    }
}
