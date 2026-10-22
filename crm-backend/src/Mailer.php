<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * Every email the backend sends goes through send(), which keeps a copy in the
 * emails table (the outbox shown by GET /api/outbox).
 *
 * config.php 'mail' => ['transport' => 'outbox', 'from_address' => ..., 'from_name' => ...]
 *   transport 'outbox': the email is only saved; nothing leaves the server.
 * To send real email, add a transport in deliver() (for example SMTP with your
 * account details) and set 'transport' to its name.
 */
final class Mailer
{
    private const DEFAULTS = [
        'transport' => 'outbox',
        'from_address' => 'quotes@crmpro.example',
        'from_name' => 'CRMPro',
    ];

    /**
     * Sends one plain-text email and records it.
     * Returns the stored email: id, status ('outbox' or 'sent'), date_sent.
     */
    public static function send(
        string $to,
        string $subject,
        string $body,
        ?string $parentType = null,
        ?string $parentId = null,
        ?string $quoteId = null,
    ): array {
        $config = self::config();
        $status = self::deliver($config, $to, $subject, $body);

        $now = Util::now();
        $userId = Auth::userId();
        $email = [
            'id' => Util::uuid(),
            'name' => mb_substr($subject, 0, 255),
            'date_entered' => $now,
            'date_modified' => $now,
            'created_by' => $userId,
            'modified_user_id' => $userId,
            'assigned_user_id' => $userId,
            'deleted' => 0,
            'date_sent' => $now,
            'status' => $status,
            'type' => 'out',
            'from_addr' => self::fromHeader($config),
            'to_addrs' => $to,
            'description' => $body,
            'parent_type' => $parentType,
            'parent_id' => $parentId,
            'quote_id' => $quoteId,
        ];
        Db::insert('emails', $email);

        return ['id' => $email['id'], 'status' => $status, 'date_sent' => $now];
    }

    /** Outbox list. Query: page, per_page (default 10), quote_id, parent_id. Newest first. */
    public static function list(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;

        $where = ['e.deleted = 0'];
        $params = [];
        foreach (['quote_id', 'parent_id'] as $field) {
            if (($query[$field] ?? '') !== '') {
                $where[] = "e.$field = ?";
                $params[] = (string) $query[$field];
            }
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) Db::value("SELECT COUNT(*) FROM emails e WHERE $whereSql", $params);
        $rows = Db::all(
            'SELECT e.id, e.name, e.date_sent, e.status, e.from_addr, e.to_addrs, e.parent_type, e.parent_id, e.quote_id,'
            . ' q.quote_number FROM emails e LEFT JOIN quotes q ON q.id = e.quote_id'
            . " WHERE $whereSql ORDER BY e.date_sent DESC, e.id DESC LIMIT $perPage OFFSET $offset",
            $params,
        );

        return ['records' => self::withParentNames($rows), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    /** One email with its body. */
    public static function get(string $id): array
    {
        $row = Db::one(
            'SELECT e.id, e.name, e.date_sent, e.status, e.from_addr, e.to_addrs, e.description, e.parent_type,'
            . ' e.parent_id, e.quote_id, q.quote_number FROM emails e LEFT JOIN quotes q ON q.id = e.quote_id'
            . ' WHERE e.id = ? AND e.deleted = 0',
            [$id],
        );
        if ($row === null) {
            throw new HttpError(404, 'Email not found');
        }

        return self::withParentNames([$row])[0];
    }

    // ------------------------------------------------------------------ internals

    private static function config(): array
    {
        $config = Config::get('mail', []);

        return (is_array($config) ? $config : []) + self::DEFAULTS;
    }

    /** Hands the email to the configured transport. Returns the status to store. */
    private static function deliver(array $config, string $to, string $subject, string $body): string
    {
        return match ($config['transport']) {
            'outbox' => 'outbox',
            default => throw new \RuntimeException("Unknown mail transport: {$config['transport']}"),
        };
    }

    private static function fromHeader(array $config): string
    {
        return $config['from_name'] !== ''
            ? "{$config['from_name']} <{$config['from_address']}>"
            : (string) $config['from_address'];
    }

    /** Adds module, parent_name and quote number (Q-1001) to email rows. */
    private static function withParentNames(array $rows): array
    {
        $names = [];
        foreach (Records::resolveRefs(
            array_values(array_filter($rows, static fn (array $r): bool => $r['parent_id'] !== null)),
            'parent_type',
            'parent_id',
        ) as $ref) {
            $names[$ref['id']] = ['module' => $ref['module'], 'name' => $ref['name']];
        }

        return array_map(static function (array $row) use ($names): array {
            $parent = $names[$row['parent_id'] ?? ''] ?? null;
            $row['parent_module'] = $parent['module'] ?? null;
            $row['parent_name'] = $parent['name'] ?? null;
            $row['quote_number'] = $row['quote_number'] !== null ? Quotes::number((int) $row['quote_number']) : null;
            unset($row['parent_type']);

            return $row;
        }, $rows);
    }
}
