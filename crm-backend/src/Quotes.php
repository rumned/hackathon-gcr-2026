<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * Quotations.
 *
 * Auto-send: when an opportunity enters the "Proposal/Price Quote" sales stage
 * (created in that stage, or moved into it), a quotation is created from the
 * opportunity's name and amount and emailed to the primary email address of the
 * opportunity's account. Records.php calls opportunitySaved() after every save.
 *
 * Discount: applyDiscount() sets a percent or fixed discount (with an optional
 * promotion name) on a quotation and, by default, emails the updated quotation.
 *
 * Status: Draft (created, not sent yet), Sent, Not sent (see send_error).
 */
final class Quotes
{
    public const PROPOSAL_STAGE = 'Proposal/Price Quote';
    private const VALID_DAYS = 30;

    /** "Q-1001" */
    public static function number(int $quoteNumber): string
    {
        return 'Q-' . $quoteNumber;
    }

    // ------------------------------------------------------------------ auto-send

    /** $stageBefore is null when the opportunity was just created. */
    public static function opportunitySaved(string $opportunityId, ?string $stageBefore): void
    {
        $opportunity = Records::get('opportunities', $opportunityId);
        if ($opportunity['sales_stage'] !== self::PROPOSAL_STAGE || $stageBefore === self::PROPOSAL_STAGE) {
            return;
        }
        $notify = static function (string $reason) use ($opportunity): void {
            Alerts::notify(
                'opportunities',
                $opportunity['id'],
                (string) ($opportunity['assigned_user_id'] ?? Auth::userId()),
                "Quotation not sent: {$opportunity['name']}",
                $reason,
            );
        };
        if ($opportunity['account_id'] === null) {
            $notify('This opportunity has no customer (account), so there is nobody to send the quotation to.');

            return;
        }
        if ($opportunity['amount'] === null || (float) $opportunity['amount'] <= 0) {
            $notify('This opportunity has no amount, so no quotation was created.');

            return;
        }

        $quoteId = self::createFromOpportunity($opportunity);
        self::send($quoteId);
    }

    // ------------------------------------------------------------------ API

    /** GET /api/accounts/{id}/quotes: the account's quotations, newest first. */
    public static function forAccount(string $accountId): array
    {
        if (!Records::exists('accounts', $accountId)) {
            throw new HttpError(404, 'Record not found');
        }
        $ids = array_column(Db::all(
            'SELECT id FROM quotes WHERE account_id = ? AND deleted = 0 ORDER BY quote_number DESC',
            [$accountId],
        ), 'id');

        return ['records' => array_map([self::class, 'get'], $ids)];
    }

    /** GET /api/quotes/{id} */
    public static function get(string $id): array
    {
        $row = Db::one('SELECT * FROM quotes WHERE id = ? AND deleted = 0', [$id]);
        if ($row === null) {
            throw new HttpError(404, 'Quotation not found');
        }
        $items = Db::all(
            'SELECT name, quantity, unit_price, total FROM quote_items WHERE quote_id = ? ORDER BY position, id',
            [$id],
        );
        $emails = Db::all(
            'SELECT id, name, to_addrs, status, date_sent FROM emails WHERE quote_id = ? AND deleted = 0 ORDER BY date_sent DESC, id DESC',
            [$id],
        );
        $accountName = $row['account_id'] !== null ? (Records::names('accounts', [$row['account_id']])[$row['account_id']] ?? null) : null;
        $opportunityName = $row['opportunity_id'] !== null
            ? (Records::names('opportunities', [$row['opportunity_id']])[$row['opportunity_id']] ?? null)
            : null;
        $userNames = Users::names([$row['assigned_user_id']]);

        return [
            'id' => $row['id'],
            'quote_number' => (int) $row['quote_number'],
            'number' => self::number((int) $row['quote_number']),
            'name' => $row['name'],
            'status' => $row['status'],
            'send_error' => $row['send_error'],
            'account_id' => $row['account_id'],
            'account_name' => $accountName,
            'opportunity_id' => $row['opportunity_id'],
            'opportunity_name' => $opportunityName,
            'subtotal' => (float) $row['subtotal'],
            'discount_type' => $row['discount_type'],
            'discount_value' => $row['discount_value'] === null ? null : (float) $row['discount_value'],
            'discount_label' => $row['discount_label'],
            'discount_amount' => (float) $row['discount_amount'],
            'total' => (float) $row['total'],
            'valid_until' => $row['valid_until'],
            'date_sent' => $row['date_sent'],
            'sent_to' => $row['sent_to'],
            'items' => array_map(static fn (array $i): array => [
                'name' => $i['name'],
                'quantity' => (float) $i['quantity'],
                'unit_price' => (float) $i['unit_price'],
                'total' => (float) $i['total'],
            ], $items),
            'emails' => $emails,
            'assigned_user_id' => $row['assigned_user_id'],
            'assigned_user_name' => $userNames[$row['assigned_user_id'] ?? ''] ?? null,
            'date_entered' => $row['date_entered'],
            'date_modified' => $row['date_modified'],
        ];
    }

    /**
     * POST /api/quotes/{id}/discount
     * Body {"type": "percent" | "amount", "value": 10, "label": "Autumn promo", "send": true}.
     * A value of 0 removes the discount. "send" defaults to true.
     */
    public static function applyDiscount(string $id, array $body): array
    {
        $quote = self::get($id);
        $type = $body['type'] ?? null;
        $value = $body['value'] ?? null;
        $label = trim((string) ($body['label'] ?? ''));
        $send = !array_key_exists('send', $body) || filter_var($body['send'], FILTER_VALIDATE_BOOLEAN);

        $errors = [];
        if (!in_array($type, ['percent', 'amount'], true)) {
            $errors['type'] = 'Must be percent or amount';
        }
        if (!is_numeric($value) || (float) $value < 0) {
            $errors['value'] = 'Must be a number of 0 or more';
        } elseif ($type === 'percent' && (float) $value > 100) {
            $errors['value'] = 'A percent discount can be at most 100';
        } elseif ($type === 'amount' && (float) $value > $quote['subtotal']) {
            $errors['value'] = 'The discount can be at most the subtotal';
        }
        if (mb_strlen($label) > 100) {
            $errors['label'] = 'Must be at most 100 characters';
        }
        if ($errors) {
            throw new HttpError(422, 'Validation failed', ['errors' => $errors]);
        }

        $value = round((float) $value, 2);
        if ($value == 0) {
            $changes = ['discount_type' => null, 'discount_value' => null, 'discount_label' => null, 'discount_amount' => 0];
        } else {
            $amount = $type === 'percent' ? round($quote['subtotal'] * $value / 100, 2) : $value;
            $changes = [
                'discount_type' => $type,
                'discount_value' => $value,
                'discount_label' => $label !== '' ? $label : null,
                'discount_amount' => $amount,
            ];
        }
        $changes['total'] = round($quote['subtotal'] - $changes['discount_amount'], 2);
        Db::update('quotes', $changes + ['date_modified' => Util::now(), 'modified_user_id' => Auth::userId()], 'id = ?', [$id]);

        return $send ? self::send($id) : self::get($id);
    }

    /** POST /api/quotes/{id}/send: emails the quotation (again) to the account's primary address. */
    public static function send(string $id): array
    {
        $quote = self::get($id);
        $account = $quote['account_id'] !== null && Records::exists('accounts', $quote['account_id'])
            ? Records::get('accounts', $quote['account_id'])
            : null;
        $now = Util::now();
        $userId = Auth::userId();

        $error = match (true) {
            $account === null => 'The customer for this quotation no longer exists.',
            ($account['email1'] ?? null) === null => "{$account['name']} has no email address.",
            default => null,
        };
        if ($error === null) {
            [$subject, $body] = self::compose($quote, $account);
            try {
                Mailer::send($account['email1'], $subject, $body, 'Accounts', $account['id'], $id);
            } catch (\Throwable $e) {
                error_log((string) $e);
                $error = 'The email could not be sent: ' . $e->getMessage();
            }
        }

        if ($error !== null) {
            Db::update('quotes', [
                'status' => 'Not sent',
                'send_error' => mb_substr($error, 0, 255),
                'date_modified' => $now,
                'modified_user_id' => $userId,
            ], 'id = ?', [$id]);
            Alerts::notify(
                'accounts',
                (string) $quote['account_id'],
                (string) ($quote['assigned_user_id'] ?? $userId),
                "Quotation {$quote['number']} not sent",
                $error,
            );
        } else {
            Db::update('quotes', [
                'status' => 'Sent',
                'send_error' => null,
                'date_sent' => $now,
                'sent_to' => $account['email1'],
                'date_modified' => $now,
                'modified_user_id' => $userId,
            ], 'id = ?', [$id]);
        }

        return self::get($id);
    }

    // ------------------------------------------------------------------ internals

    private static function createFromOpportunity(array $opportunity): string
    {
        $id = Util::uuid();
        $now = Util::now();
        $userId = Auth::userId();
        $amount = round((float) $opportunity['amount'], 2);

        Db::transaction(static function () use ($id, $now, $userId, $amount, $opportunity): void {
            Db::insert('quotes', [
                'id' => $id,
                'name' => mb_substr('Quotation: ' . $opportunity['name'], 0, 255),
                'date_entered' => $now,
                'date_modified' => $now,
                'created_by' => $userId,
                'modified_user_id' => $userId,
                'deleted' => 0,
                'assigned_user_id' => $opportunity['assigned_user_id'] ?? $userId,
                'account_id' => $opportunity['account_id'],
                'opportunity_id' => $opportunity['id'],
                'status' => 'Draft',
                'subtotal' => $amount,
                'discount_amount' => 0,
                'total' => $amount,
                'valid_until' => gmdate('Y-m-d', strtotime('+' . self::VALID_DAYS . ' days')),
            ]);
            Db::insert('quote_items', [
                'id' => Util::uuid(),
                'quote_id' => $id,
                'position' => 1,
                'name' => $opportunity['name'],
                'quantity' => 1,
                'unit_price' => $amount,
                'total' => $amount,
            ]);
        });

        return $id;
    }

    /** Subject and plain-text body of the quotation email. */
    private static function compose(array $quote, array $account): array
    {
        $isUpdate = $quote['emails'] !== [];
        $subject = ($isUpdate ? 'Updated quotation ' : 'Quotation ') . "{$quote['number']} for {$account['name']}";
        $greeting = trim((string) ($account['contact_name'] ?? '')) !== '' ? $account['contact_name'] : $account['name'];
        $sender = $quote['assigned_user_id'] !== null ? (self::userFullName($quote['assigned_user_id']) ?? 'CRMPro') : 'CRMPro';

        $lines = [];
        $lines[] = "Dear $greeting,";
        $lines[] = '';
        $lines[] = $isUpdate
            ? "Please find our updated quotation {$quote['number']} below."
            : "Please find our quotation {$quote['number']} below.";
        $lines[] = '';
        $lines[] = str_pad('Item', 34) . str_pad('Qty', 6, ' ', STR_PAD_LEFT) . str_pad('Unit price', 16, ' ', STR_PAD_LEFT) . str_pad('Total', 16, ' ', STR_PAD_LEFT);
        foreach ($quote['items'] as $item) {
            $lines[] = str_pad(mb_strimwidth($item['name'], 0, 33, '…'), 34)
                . str_pad(self::quantity($item['quantity']), 6, ' ', STR_PAD_LEFT)
                . str_pad(self::money($item['unit_price']), 16, ' ', STR_PAD_LEFT)
                . str_pad(self::money($item['total']), 16, ' ', STR_PAD_LEFT);
        }
        $lines[] = '';
        $lines[] = 'Subtotal: ' . self::money($quote['subtotal']);
        if ($quote['discount_amount'] > 0) {
            $what = $quote['discount_type'] === 'percent' ? self::quantity($quote['discount_value']) . '%' : self::money($quote['discount_value']);
            $label = $quote['discount_label'] !== null ? "{$quote['discount_label']}, " : '';
            $lines[] = "Discount ($label$what): -" . self::money($quote['discount_amount']);
        }
        $lines[] = 'Total: ' . self::money($quote['total']);
        $lines[] = '';
        if ($quote['valid_until'] !== null) {
            $lines[] = 'This quotation is valid until ' . date('j M Y', strtotime($quote['valid_until'])) . '.';
            $lines[] = '';
        }
        $lines[] = 'Kind regards,';
        $lines[] = $sender;
        $lines[] = 'CRMPro';

        return [$subject, implode("\n", $lines)];
    }

    private static function money(float $value): string
    {
        return '$' . number_format($value, 2);
    }

    private static function quantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private static function userFullName(string $userId): ?string
    {
        $user = Db::one('SELECT first_name, last_name, user_name FROM users WHERE id = ?', [$userId]);
        if ($user === null) {
            return null;
        }
        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

        return $name !== '' ? $name : $user['user_name'];
    }
}
