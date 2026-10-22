<?php
// Shared setup for the logged-in pages: API client, login check and helpers.
// Data comes from the CRM backend API (see includes/api.php and config.php).

require __DIR__ . '/api.php';
require __DIR__ . '/customer-types.php';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Errors the page can't recover from (backend down, unexpected API error): show a short page.
set_exception_handler(static function (Throwable $error): void {
    error_log((string) $error);
    http_response_code(500);
    $message = $error instanceof ApiError ? $error->getMessage() : 'Something went wrong while loading this page.';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Error | CRMPro</title><link rel="stylesheet" href="assets/style.css"></head>'
        . '<body class="page-app"><main class="main"><div class="empty-state"><h3>This page could not be loaded</h3>'
        . '<p>' . e($message) . '</p><p><a href="homepage.php">Back to Home</a></p></div></main></body></html>';
});

if (!api_logged_in()) {
    header('Location: login.php');
    exit;
}
$currentUser = $_SESSION['user'];

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** One-time message shown at the top of the next page. $type: success or error. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

/** "2026-10-14 09:00:00" or "2026-10-14" -> "Oct 14, 2026" */
function fmt_date(?string $value): string
{
    return ($value === null || $value === '') ? '' : date('M j, Y', strtotime($value));
}

/** "2026-10-23 10:00:00" -> "2026-10-23 10:00" (UTC, as stored by the backend) */
function fmt_datetime(?string $value): string
{
    return ($value === null || $value === '') ? '' : substr($value, 0, 16);
}

function money($value): string
{
    return $value === null ? '' : '$' . number_format((float) $value, 2);
}

function initials(string $name): string
{
    $initials = '';
    foreach (explode(' ', $name) as $part) {
        if ($part !== '') {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
    }

    return mb_substr($initials, 0, 2);
}

/** Address lines for the detail page; $prefix is billing_address or shipping_address. */
function address_lines(array $c, string $prefix): array
{
    $state = (string) ($c[$prefix . '_state'] ?? '');
    $cityLine = trim(($c[$prefix . '_city'] ?? '') . ($state !== '' ? ', ' . $state : '') . ' ' . ($c[$prefix . '_postalcode'] ?? ''));

    return array_values(array_filter(
        [(string) ($c[$prefix . '_street'] ?? ''), $cityLine, (string) ($c[$prefix . '_country'] ?? '')],
        static fn ($line) => trim($line, ', ') !== '',
    ));
}

/** True when the shipping address is filled in and equal to the billing address. */
function shipping_same_as_billing(array $c): bool
{
    $same = true;
    $filled = false;
    foreach (['street', 'city', 'state', 'postalcode', 'country'] as $part) {
        $billing = (string) ($c['billing_address_' . $part] ?? '');
        $same = $same && $billing === (string) ($c['shipping_address_' . $part] ?? '');
        $filled = $filled || $billing !== '';
    }

    return $same && $filled;
}

/** Comma-separated tags -> list */
function tag_list(?string $tags): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $tags)), static fn ($t) => $t !== ''));
}

/** <option> list. $options: [['value' => ..., 'label' => ...], ...] as returned by /api/meta. */
function options_html(array $options, ?string $selected, bool $blank = true): string
{
    $html = $blank ? '<option value="">—</option>' : '';
    foreach ($options as $option) {
        $value = (string) $option['value'];
        $html .= '<option value="' . e($value) . '"' . ($value === (string) $selected ? ' selected' : '') . '>' . e($option['label']) . '</option>';
    }

    return $html;
}

/** Option label for a stored dropdown value, e.g. "Id. Decision Makers" -> "Identifying Decision Makers". */
function option_label(array $options, ?string $value): string
{
    foreach ($options as $option) {
        if ((string) $option['value'] === (string) $value) {
            return (string) $option['label'];
        }
    }

    return (string) $value;
}

/** Badge CSS suffix for a stored dropdown value, e.g. "Closed Won" -> "closed-won". */
function badge_slug(?string $value): string
{
    $slug = strtolower((string) $value);
    $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);

    return trim($slug, '-');
}

/** Display name of the logged-in user. */
function user_display_name(array $user): string
{
    return $user['full_name'] !== '' ? $user['full_name'] : $user['user_name'];
}
