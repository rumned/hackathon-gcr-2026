<?php
// Sales status (accounts.status): the stages of the sales tracker, in order.
// The stages themselves come from the backend (GET /api/meta/accounts, field "status").

const WON_STATUS = 'Won';

/** CSS class of a status badge: "Follow-up 2" -> "status-follow-up-2" */
function status_class(?string $status): string
{
    return 'status-' . trim(preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $status)), '-');
}

/** The stage after $status, or null at the last stage. A customer with no status is before the first stage. */
function next_status(array $statusOptions, ?string $status): ?string
{
    $order = array_column($statusOptions, 'value');
    if ($status === null || $status === '') {
        return $order[0] ?? null;
    }
    $index = array_search($status, $order, true);

    return $index === false ? null : ($order[$index + 1] ?? null);
}

/** The stage before $status, or null at the first stage. */
function prev_status(array $statusOptions, ?string $status): ?string
{
    $order = array_column($statusOptions, 'value');
    $index = array_search($status, $order, true);

    return $index === false || $index < 1 ? null : $order[$index - 1];
}

/** Stage badge classes: Existing is solid, Lead (potential) is soft. */
function stage_badge_class(?string $stage): string
{
    return $stage === 'Existing' ? 'status-stage-existing' : 'status-stage-lead';
}

/** Short label that fits on the prev/next card buttons. */
function move_short(string $status): string
{
    return $status === WON_STATUS ? 'Won' : $status;
}

/**
 * Moves a customer to another stage. Moving to Won also sets the customer's Stage to Existing,
 * so a won lead leaves the Leads page. Returns the api_send() result.
 */
function move_customer(string $id, string $status): array
{
    $fields = ['status' => $status];
    if ($status === WON_STATUS) {
        $fields['customer_stage'] = 'Existing';
    }

    return api_send('PUT', '/api/accounts/' . rawurlencode($id), $fields);
}
