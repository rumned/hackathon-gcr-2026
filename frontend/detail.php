<?php
require __DIR__ . '/includes/bootstrap.php';

$id = (string) ($_GET['id'] ?? '');
$c = $id !== '' ? api_get('/api/accounts/' . rawurlencode($id)) : null;

if (!$c) {
    http_response_code(404);
    $pageTitle = 'Customer not found';
    $activeModule = 'customers';
    require __DIR__ . '/includes/layout-top.php';
    echo '<div class="empty-state"><h3>Customer not found</h3><p><a href="customer.php">Back to Customers</a></p></div>';
    require __DIR__ . '/includes/layout-bottom.php';
    exit;
}
$self = 'detail.php?id=' . rawurlencode($c['id']);

/** Quotations of this customer for one deal (to see whether saving the deal created one). */
function deal_quotes(string $accountId, string $opportunityId): array
{
    $quotes = api_get('/api/accounts/' . rawurlencode($accountId) . '/quotes')['records'] ?? [];
    return array_values(array_filter($quotes, fn ($q) => $q['opportunity_id'] === $opportunityId));
}

/** "saved to the outbox for x" / "emailed to x" */
function delivery_text(array $quote): string
{
    $status = $quote['emails'][0]['status'] ?? 'outbox';
    return ($status === 'sent' ? 'emailed to ' : 'saved to the outbox for ') . $quote['sent_to'];
}

/** Flash message after a deal was saved: was a quotation created and sent? */
function quote_flash(string $accountId, array $deal, int $quotesBefore, string $saved): void
{
    $quotes = deal_quotes($accountId, $deal['id']);
    if (count($quotes) > $quotesBefore) {
        $q = $quotes[0];
        if ($q['status'] === 'Sent') {
            flash('success', "$saved Quotation {$q['number']} was created and " . delivery_text($q) . '.');
        } else {
            flash('error', "$saved Quotation {$q['number']} was created but not sent: {$q['send_error']}");
        }
    } elseif ($deal['sales_stage'] === 'Proposal/Price Quote' && ($deal['amount'] === null || $deal['amount'] <= 0)) {
        flash('error', "$saved No quotation was created because this deal has no amount.");
    } else {
        flash('success', $saved);
    }
}

// ---------------------------------------------------------------- form actions
$discountErrors = [];
$discountInput = ['label' => '', 'value' => '', 'type' => 'percent', 'send' => true];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'favorite':
            $result = $c['is_favorite']
                ? api_send('DELETE', '/api/favorites/accounts/' . rawurlencode($c['id']))
                : api_send('POST', '/api/favorites', ['module' => 'accounts', 'record_id' => $c['id']]);
            if (!$result['ok']) {
                flash('error', 'Favorites not updated: ' . $result['message']);
            }
            redirect($self);

        case 'delete':
            $result = api_send('DELETE', '/api/accounts/' . rawurlencode($c['id']));
            if ($result['ok']) {
                flash('success', "Customer {$c['name']} deleted.");
                redirect('customer.php');
            }
            flash('error', 'Customer not deleted: ' . $result['message']);
            redirect($self);

        case 'duplicate':
            $meta = api_get('/api/meta/accounts');
            $copy = [];
            foreach ($meta['fields'] as $field => $spec) {
                if (!$spec['readonly'] && array_key_exists($field, $c)) {
                    $copy[$field] = $c[$field];
                }
            }
            $copy['name'] = mb_substr($c['name'] . ' (copy)', 0, 150);
            $copy['email_addresses'] = $c['email_addresses'];
            $result = api_send('POST', '/api/accounts', $copy);
            if ($result['ok']) {
                flash('success', "Duplicated as {$result['data']['name']}.");
                redirect('detail.php?id=' . rawurlencode($result['data']['id']));
            }
            flash('error', 'Customer not duplicated: ' . $result['message']);
            redirect($self);

        case 'discount':
            $quoteId = (string) ($_POST['quote_id'] ?? '');
            $discountInput = [
                'label' => trim($_POST['label'] ?? ''),
                'value' => trim($_POST['value'] ?? ''),
                'type' => ($_POST['type'] ?? '') === 'amount' ? 'amount' : 'percent',
                'send' => isset($_POST['send']),
            ];
            if ($discountInput['value'] === '') {
                $discountErrors['value'] = 'Enter a discount, or 0 to remove it.';
                break;
            }
            $result = api_send('POST', '/api/quotes/' . rawurlencode($quoteId) . '/discount', $discountInput);
            if ($result['ok']) {
                $q = $result['data'];
                $what = $q['discount_amount'] > 0 ? 'Discount applied to quotation ' . $q['number'] . '.' : 'Discount removed from quotation ' . $q['number'] . '.';
                if (!$discountInput['send']) {
                    flash('success', "$what The updated quotation was not sent.");
                } elseif ($q['status'] === 'Sent') {
                    flash('success', "$what The updated quotation was " . delivery_text($q) . '.');
                } else {
                    flash('error', "$what The updated quotation was not sent: {$q['send_error']}");
                }
                redirect($self);
            }
            $discountErrors = $result['errors'] ?: ['value' => $result['message']];
            break;

        case 'send_quote':
            $result = api_send('POST', '/api/quotes/' . rawurlencode((string) ($_POST['quote_id'] ?? '')) . '/send');
            if ($result['ok'] && $result['data']['status'] === 'Sent') {
                flash('success', "Quotation {$result['data']['number']} was " . delivery_text($result['data']) . '.');
            } else {
                flash('error', 'Quotation not sent: ' . ($result['data']['send_error'] ?? $result['message']));
            }
            redirect($self);

        case 'stage':
            $dealId = (string) ($_POST['opportunity_id'] ?? '');
            $before = count(deal_quotes($c['id'], $dealId));
            $result = api_send('PUT', '/api/opportunities/' . rawurlencode($dealId), ['sales_stage' => $_POST['sales_stage'] ?? '']);
            if ($result['ok']) {
                quote_flash($c['id'], $result['data'], $before, "Stage of {$result['data']['name']} updated.");
            } else {
                flash('error', 'Stage not updated: ' . (implode(' ', $result['errors']) ?: $result['message']));
            }
            redirect($self . '&tab=deals');

        case 'add_deal':
            $result = api_send('POST', '/api/opportunities', [
                'name' => trim($_POST['name'] ?? ''),
                'amount' => trim($_POST['amount'] ?? ''),
                'date_closed' => $_POST['date_closed'] ?? '',
                'sales_stage' => $_POST['sales_stage'] ?? '',
                'account_id' => $c['id'],
            ]);
            if ($result['ok']) {
                quote_flash($c['id'], $result['data'], 0, "Deal {$result['data']['name']} added.");
            } else {
                $labels = ['name' => 'Deal name', 'amount' => 'Amount', 'date_closed' => 'Close date', 'sales_stage' => 'Stage'];
                flash('error', 'Deal not added: ' . (implode(' ', array_map(
                    fn ($field, $message) => ($labels[$field] ?? $field) . ": $message.",
                    array_keys($result['errors']),
                    $result['errors'],
                )) ?: $result['message']));
            }
            redirect($self . '&tab=deals');

        case 'complete':
            $module = $_POST['module'] ?? '';
            if (in_array($module, ['calls', 'meetings', 'tasks'], true)) {
                $status = $module === 'tasks' ? 'Completed' : 'Held';
                $result = api_send('PUT', "/api/$module/" . rawurlencode((string) ($_POST['record_id'] ?? '')), ['status' => $status]);
                if (!$result['ok']) {
                    flash('error', 'Not updated: ' . $result['message']);
                }
            }
            redirect($self . '&tab=tasks');

        case 'add_note':
            $text = trim($_POST['note'] ?? '');
            if ($text !== '') {
                $result = api_send('POST', '/api/notes', [
                    'name' => mb_substr($text, 0, 255),
                    'description' => $text,
                    'parent_type' => 'Accounts',
                    'parent_id' => $c['id'],
                ]);
                if (!$result['ok']) {
                    flash('error', 'Note not saved: ' . $result['message']);
                }
            }
            redirect($self . '&tab=notes');
    }
}

// ---------------------------------------------------------------- data for the page
$meta = api_get('/api/meta/accounts');
$stageOptions = api_get('/api/meta/opportunities')['fields']['sales_stage']['options'];
$base = '/api/accounts/' . rawurlencode($c['id']);

$contacts   = api_get("$base/subpanels/contacts", ['per_page' => 50])['records'];
$deals      = api_get("$base/subpanels/opportunities", ['per_page' => 50, 'sort' => 'date_closed', 'dir' => 'asc'])['records'];
$openItems  = api_get("$base/subpanels/activities", ['per_page' => 50])['records'];
$history    = api_get("$base/subpanels/history", ['per_page' => 50])['records'];
$leads      = api_get("$base/subpanels/leads", ['per_page' => 50])['records'];
$cases      = api_get("$base/subpanels/cases", ['per_page' => 50])['records'];
$quotes     = api_get("$base/quotes")['records'];
$notes      = api_get('/api/notes', [
    'filter' => ['parent_type' => 'Accounts', 'parent_id' => $c['id']],
    'sort' => 'date_entered',
    'dir' => 'desc',
    'per_page' => 50,
])['records'];

$caseFields = $cases ? api_get('/api/meta/cases')['fields'] : null;

$type = $c['account_type'];
$stage = $c['customer_stage'];
$shows = fn (string $group): bool => type_shows($type, $stage, $group);
$showQuotes = $shows('quotes') || $quotes;
$showCompany = $shows('industry') || $shows('company_size') || $shows('member_of');
$showLeadDetails = $shows('status') || $shows('lead_source') || $shows('campaign');

// Purchases: the customer's won deals
$purchases = array_values(array_filter($deals, fn ($d) => $d['sales_stage'] === 'Closed Won'));
$purchaseDates = array_filter(array_column($purchases, 'date_closed'));
$purchaseTotal = array_sum(array_map(fn ($d) => (float) $d['amount'], $purchases));
$quote = $quotes[0] ?? null;
$quotesByDeal = [];
foreach (array_reverse($quotes) as $q) {
    $quotesByDeal[$q['opportunity_id']] = $q;   // newest quotation per deal
}

// Last contact: the latest call, meeting or email in the history
$contactDates = array_column(array_filter($history, fn ($h) => in_array($h['type'], ['Call', 'Meeting', 'Email'], true) && $h['date'] !== null), 'date');
$lastContact = $contactDates ? max($contactDates) : null;

$billing = address_lines($c, 'billing_address');
$shipping = address_lines($c, 'shipping_address');
$tags = tag_list($c['tags']);
// Tabs this customer's Type and Stage show
$tabs = array_keys(array_filter([
    'overview' => true,
    'company' => $showCompany,
    'lead' => $showLeadDetails,
    'purchases' => $shows('purchases'),
    'more' => true,
    'activity' => true,
    'deals' => true,
    'tasks' => true,
    'notes' => true,
]));
$activeTab = in_array($_GET['tab'] ?? '', $tabs, true) ? $_GET['tab'] : 'overview';
$panel = fn (string $tab): string => $tab === $activeTab ? '' : ' hidden';
$selected = fn (string $tab): string => $tab === $activeTab ? 'true' : 'false';

$show = $_GET['show'] ?? '';
$changeLog = $show === 'changelog' ? (api_get("$base/changelog")['records'] ?? []) : null;
$duplicates = $show === 'duplicates' ? (api_get("$base/duplicates")['records'] ?? []) : null;

$pageTitle = $c['name'];
$activeModule = 'customers';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="page-head">
  <p class="breadcrumb"><a href="customer.php">Customers</a> / <?= e($c['name']) ?></p>

  <div class="profile">
    <span class="avatar avatar--lg" aria-hidden="true"><?= e(initials($c['name'])) ?></span>
    <div class="profile__info">
      <div class="title-row">
        <h1 class="page-title"><?= e($c['name']) ?></h1>
        <?php if ($type !== null): ?><span class="status status-type-<?= e(badge_slug($type)) ?>"><?= e(option_label($meta['fields']['account_type']['options'], $type)) ?></span><?php endif; ?>
        <?php if ($stage !== null): ?><span class="status status-stage-<?= e(badge_slug($stage)) ?>"><?= e(option_label($meta['fields']['customer_stage']['options'], $stage)) ?></span><?php endif; ?>
        <?php if ($shows('status') && $c['status'] !== null): ?><span class="status status-<?= e(strtolower($c['status'])) ?>"><?= e($c['status']) ?></span><?php endif; ?>
        <form method="post">
          <input type="hidden" name="action" value="favorite">
          <button class="mini-btn<?= $c['is_favorite'] ? ' is-on' : '' ?>" type="submit"
                  aria-pressed="<?= $c['is_favorite'] ? 'true' : 'false' ?>" aria-label="Favorite">
            <svg class="icon"><use href="#i-star"/></svg>
          </button>
        </form>
      </div>
      <?php if ($shows('contact_person') && ($c['contact_name'] !== null || $c['contact_title'] !== null)): ?>
        <div class="profile__meta">
          <?php if ($c['contact_name'] !== null): ?><span><svg class="icon"><use href="#i-user"/></svg><?= e($c['contact_name']) ?></span><?php endif; ?>
          <?php if ($c['contact_title'] !== null): ?><span><svg class="icon"><use href="#i-brief"/></svg><?= e($c['contact_title']) ?></span><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="page-head__actions">
      <a class="btn btn--gold" href="edit.php?id=<?= e($c['id']) ?>">Edit</a>
      <div class="menu menu--right">
        <button class="btn" type="button" aria-haspopup="true">
          Actions <svg class="icon icon--sm"><use href="#i-chev"/></svg>
        </button>
        <div class="menu__panel">
          <form method="post"><input type="hidden" name="action" value="duplicate"><button class="menu__item" type="submit">Duplicate</button></form>
          <a class="menu__item" href="<?= e($self) ?>&amp;show=duplicates">Find duplicates</a>
          <a class="menu__item" href="<?= e($self) ?>&amp;show=changelog">View change log</a>
          <form method="post" data-confirm="Delete this customer?"><input type="hidden" name="action" value="delete"><button class="menu__item menu__item--danger" type="submit">Delete</button></form>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="detail-layout<?= $showQuotes ? ' detail-layout--aside' : '' ?>">

  <?php if ($showQuotes): ?>
  <!-- Quotation panel, next to the customer details -->
  <aside class="quote-panel" aria-labelledby="quote-title">
    <?php if ($quote): ?>
      <div class="quote-panel__head">
        <h2 id="quote-title">Quotation <?= e($quote['number']) ?></h2>
        <span class="status status-quote-<?= e(strtolower(str_replace(' ', '-', $quote['status']))) ?>"><?= e($quote['status']) ?></span>
      </div>
      <p class="quote-panel__sub"><?= e($quote['opportunity_name'] ?? $quote['name']) ?></p>
      <dl class="quote-sums">
        <div><dt>Subtotal</dt><dd><?= e(money($quote['subtotal'])) ?></dd></div>
        <?php if ($quote['discount_amount'] > 0): ?>
          <div class="quote-sums__discount">
            <?php
            $parts = array_filter([
                $quote['discount_label'],
                $quote['discount_type'] === 'percent' ? rtrim(rtrim(number_format($quote['discount_value'], 2), '0'), '.') . '%' : null,
            ]);
            ?>
            <dt>Discount<?= $parts ? ' (' . e(implode(', ', $parts)) . ')' : '' ?></dt>
            <dd>-<?= e(money($quote['discount_amount'])) ?></dd>
          </div>
        <?php endif; ?>
        <div class="quote-sums__total"><dt>Total</dt><dd><?= e(money($quote['total'])) ?></dd></div>
      </dl>
      <?php if ($quote['valid_until'] !== null): ?>
        <p class="quote-panel__note">Valid until <?= e(fmt_date($quote['valid_until'])) ?></p>
      <?php endif; ?>
      <?php if ($quote['status'] === 'Sent'): ?>
        <p class="quote-panel__note">Last sent <?= e(fmt_datetime($quote['date_sent'])) ?>, <?= e(delivery_text($quote)) ?></p>
      <?php elseif ($quote['send_error'] !== null): ?>
        <p class="quote-panel__note quote-panel__note--error">Not sent: <?= e($quote['send_error']) ?></p>
      <?php endif; ?>
      <div class="quote-panel__actions">
        <button class="btn btn--gold" type="button" data-open-modal="discountModal">Apply discount</button>
        <form method="post">
          <input type="hidden" name="action" value="send_quote">
          <input type="hidden" name="quote_id" value="<?= e($quote['id']) ?>">
          <button class="btn" type="submit"><?= $quote['status'] === 'Sent' ? 'Resend' : 'Send' ?></button>
        </form>
        <?php if ($quote['emails']): ?>
          <a class="btn" href="outbox.php?id=<?= e($quote['emails'][0]['id']) ?>">View email</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <h2 id="quote-title" class="quote-panel__title">Quotation</h2>
      <p class="quote-panel__note">No quotation yet. One is created and emailed automatically when a deal for this customer moves to the Proposal/Price Quote stage in the Deals tab.</p>
    <?php endif; ?>
  </aside>
  <?php endif; ?>

  <div class="detail-main" data-tabs>
    <div class="tabs" role="tablist">
      <button class="tab" role="tab" type="button" aria-selected="<?= $selected('overview') ?>" data-tab="overview">Overview</button>
      <?php if ($showCompany): ?>
        <button class="tab" role="tab" type="button" aria-selected="<?= $selected('company') ?>" data-tab="company">Company</button>
      <?php endif; ?>
      <?php if ($showLeadDetails): ?>
        <button class="tab" role="tab" type="button" aria-selected="<?= $selected('lead') ?>" data-tab="lead">Lead details</button>
      <?php endif; ?>
      <?php if ($shows('purchases')): ?>
        <button class="tab" role="tab" type="button" aria-selected="<?= $selected('purchases') ?>" data-tab="purchases">Purchases</button>
      <?php endif; ?>
      <button class="tab" role="tab" type="button" aria-selected="<?= $selected('more') ?>" data-tab="more">More information</button>
      <button class="tab" role="tab" type="button" aria-selected="<?= $selected('activity') ?>" data-tab="activity">Activity</button>
      <button class="tab" role="tab" type="button" aria-selected="<?= $selected('deals') ?>" data-tab="deals">Deals</button>
      <button class="tab" role="tab" type="button" aria-selected="<?= $selected('tasks') ?>" data-tab="tasks">Tasks</button>
      <button class="tab" role="tab" type="button" aria-selected="<?= $selected('notes') ?>" data-tab="notes">Notes</button>
    </div>

    <!-- Overview: common details plus the ones this customer's Type and Stage use -->
    <dl class="fields" role="tabpanel" data-tabpanel="overview"<?= $panel('overview') ?>>
      <div class="fields__item"><dt class="fields__label">Name</dt><dd class="fields__value"><?= e($c['name']) ?></dd></div>
      <div class="fields__item"><dt class="fields__label">Type</dt><dd class="fields__value"><?php if ($type !== null && $type !== ''): ?><span class="status status-type-<?= e(badge_slug($type)) ?>"><?= e(option_label($meta['fields']['account_type']['options'], $type)) ?></span><?php endif; ?></dd></div>
      <div class="fields__item"><dt class="fields__label">Stage</dt><dd class="fields__value"><?php if ($stage !== null && $stage !== ''): ?><span class="status status-stage-<?= e(badge_slug($stage)) ?>"><?= e(option_label($meta['fields']['customer_stage']['options'], $stage)) ?></span><?php endif; ?></dd></div>
      <?php if ($shows('contact_person')): ?>
        <div class="fields__item"><dt class="fields__label">Contact name</dt><dd class="fields__value"><?= e($c['contact_name']) ?></dd></div>
        <div class="fields__item"><dt class="fields__label">Job title</dt><dd class="fields__value"><?= e($c['contact_title']) ?></dd></div>
      <?php endif; ?>
        <div class="fields__item"><dt class="fields__label">Email</dt><dd class="fields__value">
          <?php if ($c['email1'] !== null): ?><a href="mailto:<?= e($c['email1']) ?>"><?= e($c['email1']) ?></a><?php endif; ?>
        </dd></div>
        <div class="fields__item"><dt class="fields__label">Office phone</dt><dd class="fields__value"><?= e($c['phone_office']) ?></dd></div>
        <div class="fields__item"><dt class="fields__label">Mobile</dt><dd class="fields__value"><?= e($c['phone_mobile']) ?></dd></div>
      <?php if ($shows('website')): ?>
        <div class="fields__item"><dt class="fields__label">Website</dt><dd class="fields__value">
          <?php if ($c['website'] !== null): ?>
            <a href="<?= e(preg_match('#^https?://#i', $c['website']) ? $c['website'] : 'https://' . $c['website']) ?>"><?= e($c['website']) ?></a>
          <?php endif; ?>
        </dd></div>
      <?php endif; ?>
      <?php if ($shows('fax')): ?>
        <div class="fields__item"><dt class="fields__label">Fax</dt><dd class="fields__value"><?= e($c['phone_fax']) ?></dd></div>
      <?php endif; ?>
      <?php if ($shows('billing')): ?>
        <div class="fields__item"><dt class="fields__label">Billing address</dt><dd class="fields__value"><?= implode('<br>', array_map('e', $billing)) ?></dd></div>
      <?php endif; ?>
      <?php if ($shows('shipping')): ?>
        <div class="fields__item"><dt class="fields__label">Shipping address</dt><dd class="fields__value">
          <?= shipping_same_as_billing($c) ? 'Same as billing' : implode('<br>', array_map('e', $shipping)) ?>
        </dd></div>
      <?php endif; ?>
      <?php if ($shows('description')): ?>
        <div class="fields__item fields__item--wide"><dt class="fields__label">Description</dt><dd class="fields__value"><?= nl2br(e($c['description'])) ?></dd></div>
      <?php endif; ?>
      <?php if ($shows('assigned')): ?>
        <div class="fields__item fields__item--wide"><dt class="fields__label">Assigned to</dt><dd class="fields__value">
          <?php if ($c['assigned_user_name'] !== null): ?>
            <span class="inline-user"><span class="avatar avatar--xs"><?= e(initials($c['assigned_user_name'])) ?></span><?= e($c['assigned_user_name']) ?></span>
          <?php endif; ?>
        </dd></div>
      <?php endif; ?>
    </dl>

    <?php if ($showCompany): ?>
    <!-- Company: Business and Enterprise customers -->
    <dl class="fields" role="tabpanel" data-tabpanel="company"<?= $panel('company') ?>>
      <?php if ($shows('industry')): ?>
        <div class="fields__item"><dt class="fields__label">Industry</dt><dd class="fields__value"><?= e($c['industry']) ?></dd></div>
      <?php endif; ?>
      <?php if ($shows('company_size')): ?>
        <div class="fields__item"><dt class="fields__label">Annual revenue</dt><dd class="fields__value"><?= e($c['annual_revenue']) ?></dd></div>
        <div class="fields__item"><dt class="fields__label">Employees</dt><dd class="fields__value"><?= e($c['employees']) ?></dd></div>
      <?php endif; ?>
      <?php if ($shows('member_of')): ?>
        <div class="fields__item"><dt class="fields__label">Member of</dt><dd class="fields__value">
          <?php if ($c['parent_id'] !== null): ?><a href="detail.php?id=<?= e($c['parent_id']) ?>"><?= e($c['parent_name']) ?></a><?php endif; ?>
        </dd></div>
      <?php endif; ?>
    </dl>
    <?php endif; ?>

    <?php if ($showLeadDetails): ?>
    <!-- Lead details: potential customers -->
    <dl class="fields" role="tabpanel" data-tabpanel="lead"<?= $panel('lead') ?>>
      <?php if ($shows('status')): ?>
        <div class="fields__item"><dt class="fields__label">Status</dt><dd class="fields__value">
          <?php if ($c['status'] !== null): ?><span class="status status-<?= e(strtolower($c['status'])) ?>"><?= e($c['status']) ?></span><?php endif; ?>
        </dd></div>
      <?php endif; ?>
      <?php if ($shows('lead_source')): ?>
        <div class="fields__item"><dt class="fields__label">Lead source</dt><dd class="fields__value"><?= e($c['lead_source']) ?></dd></div>
      <?php endif; ?>
      <?php if ($shows('campaign')): ?>
        <div class="fields__item"><dt class="fields__label">Campaign</dt><dd class="fields__value"><?= e($c['campaign_name']) ?></dd></div>
      <?php endif; ?>
    </dl>
    <?php endif; ?>

    <?php if ($shows('purchases')): ?>
    <!-- Purchases: won deals of existing customers -->
    <section class="card" role="tabpanel" data-tabpanel="purchases"<?= $panel('purchases') ?>>
      <h2>Purchases</h2>
      <dl class="fields">
        <div class="fields__item"><dt class="fields__label">Won deals</dt><dd class="fields__value"><?= count($purchases) ?></dd></div>
        <div class="fields__item"><dt class="fields__label">Total purchased</dt><dd class="fields__value"><?= e(money($purchaseTotal)) ?></dd></div>
        <div class="fields__item"><dt class="fields__label">First purchase</dt><dd class="fields__value"><?= e(fmt_date($purchaseDates ? min($purchaseDates) : null)) ?></dd></div>
        <div class="fields__item"><dt class="fields__label">Last purchase</dt><dd class="fields__value"><?= e(fmt_date($purchaseDates ? max($purchaseDates) : null)) ?></dd></div>
      </dl>
      <?php if ($purchases): ?>
        <div class="table-scroll">
          <table class="table table--plain">
            <thead><tr><th>Deal</th><th>Amount</th><th>Close date</th></tr></thead>
            <tbody>
              <?php foreach (array_reverse($purchases) as $p): ?>
                <tr><td><?= e($p['name']) ?></td><td><?= e(money($p['amount'])) ?></td><td><?= e(fmt_date($p['date_closed'])) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="empty">No won deals yet.</p>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- More information -->
    <dl class="fields" role="tabpanel" data-tabpanel="more"<?= $panel('more') ?>>
      <div class="fields__item"><dt class="fields__label">Last contact</dt><dd class="fields__value"><?= e(fmt_date($lastContact)) ?></dd></div>
      <div class="fields__item"><dt class="fields__label">Date created</dt><dd class="fields__value"><?= e(fmt_date($c['date_entered'])) ?><?= $c['created_by_name'] ? ' by ' . e($c['created_by_name']) : '' ?></dd></div>
      <div class="fields__item"><dt class="fields__label">Date modified</dt><dd class="fields__value"><?= e(fmt_date($c['date_modified'])) ?><?= $c['modified_by_name'] ? ' by ' . e($c['modified_by_name']) : '' ?></dd></div>
      <?php if ($shows('tags')): ?>
        <div class="fields__item fields__item--wide"><dt class="fields__label">Tags</dt><dd class="fields__value">
          <div class="tags">
            <?php foreach ($tags as $i => $t): ?>
              <span class="tag<?= $i % 2 ? ' tag--gold' : '' ?>"><?= e($t) ?></span>
            <?php endforeach; ?>
            <a class="tag tag--add" href="edit.php?id=<?= e($c['id']) ?>&amp;tab=more">+ Add Tag</a>
          </div>
        </dd></div>
      <?php endif; ?>
    </dl>

    <!-- Activity: history (held calls and meetings, completed tasks, notes, sent emails) -->
    <section class="card" role="tabpanel" data-tabpanel="activity"<?= $panel('activity') ?>>
      <h2>Activity</h2>
      <?php if ($history): ?>
        <ul class="timeline">
          <?php foreach ($history as $h): ?>
            <li>
              <span><span class="timeline__type"><?= e($h['type']) ?></span>
                <?php if ($h['module'] === 'emails'): ?><a href="outbox.php?id=<?= e($h['id']) ?>"><?= e($h['name']) ?></a><?php else: ?><?= e($h['name']) ?><?php endif; ?></span>
              <time><?= e(fmt_datetime($h['date'] ?? $h['date_modified'])) ?></time>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="empty">No activity yet.</p>
      <?php endif; ?>
    </section>

    <!-- Deals: the customer's opportunities. Moving one to Proposal/Price Quote sends a quotation. -->
    <section class="card" role="tabpanel" data-tabpanel="deals"<?= $panel('deals') ?>>
      <h2>Deals</h2>
      <?php if ($deals): ?>
        <div class="table-scroll">
          <table class="table table--plain">
            <thead><tr><th>Deal</th><th>Stage</th><th>Amount</th><th>Close date</th><th>Quotation</th></tr></thead>
            <tbody>
              <?php foreach ($deals as $d): ?>
                <tr>
                  <td><?= e($d['name']) ?></td>
                  <td>
                    <form class="inline-form" method="post">
                      <input type="hidden" name="action" value="stage">
                      <input type="hidden" name="opportunity_id" value="<?= e($d['id']) ?>">
                      <select class="input" name="sales_stage" aria-label="Stage of <?= e($d['name']) ?>"><?= options_html($stageOptions, $d['sales_stage'], false) ?></select>
                      <button class="btn btn--sm" type="submit">Save</button>
                    </form>
                  </td>
                  <td><?= e(money($d['amount'])) ?></td>
                  <td><?= e(fmt_date($d['date_closed'])) ?></td>
                  <td>
                    <?php if (isset($quotesByDeal[$d['id']])): $dq = $quotesByDeal[$d['id']]; ?>
                      <?= e($dq['number']) ?> <span class="status status-quote-<?= e(strtolower(str_replace(' ', '-', $dq['status']))) ?>"><?= e($dq['status']) ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="empty">No deals yet.</p>
      <?php endif; ?>

      <h3 class="card__subtitle">Add deal</h3>
      <form class="deal-form" method="post">
        <input type="hidden" name="action" value="add_deal">
        <div class="form-field">
          <label class="form-field__label" for="deal-name">Deal name</label>
          <input class="input" id="deal-name" name="name" maxlength="50" required>
        </div>
        <div class="form-field">
          <label class="form-field__label" for="deal-amount">Amount ($)</label>
          <input class="input" id="deal-amount" name="amount" type="number" min="0" step="0.01" required>
        </div>
        <div class="form-field">
          <label class="form-field__label" for="deal-close">Close date</label>
          <input class="input" id="deal-close" name="date_closed" type="date" required>
        </div>
        <div class="form-field">
          <label class="form-field__label" for="deal-stage">Stage</label>
          <select class="input" id="deal-stage" name="sales_stage"><?= options_html($stageOptions, 'Prospecting', false) ?></select>
        </div>
        <button class="btn btn--blue" type="submit">Add deal</button>
      </form>
      <p class="field-hint">A deal saved in the Proposal/Price Quote stage gets a quotation, emailed to the customer automatically.</p>
    </section>

    <!-- Tasks: open calls, meetings and tasks -->
    <section class="card" role="tabpanel" data-tabpanel="tasks"<?= $panel('tasks') ?>>
      <h2>Tasks</h2>
      <?php if ($openItems): ?>
        <ul class="task-list">
          <?php foreach ($openItems as $t): ?>
            <li>
              <form method="post" class="task-form">
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="module" value="<?= e($t['module']) ?>">
                <input type="hidden" name="record_id" value="<?= e($t['id']) ?>">
                <label>
                  <input type="checkbox" data-autosubmit
                         aria-label="Mark <?= e($t['name']) ?> as <?= $t['module'] === 'tasks' ? 'completed' : 'held' ?>">
                  <span class="task-title"><span class="timeline__type"><?= e($t['type']) ?></span> <?= e($t['name']) ?></span>
                </label>
              </form>
              <time><?= e(fmt_datetime($t['date'])) ?></time>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="empty">No open calls, meetings or tasks.</p>
      <?php endif; ?>
    </section>

    <!-- Notes -->
    <section class="card" role="tabpanel" data-tabpanel="notes"<?= $panel('notes') ?>>
      <h2>Notes</h2>
      <form class="note-form" method="post">
        <input type="hidden" name="action" value="add_note">
        <input class="input" type="text" name="note" placeholder="Add a note..." aria-label="Add a note" maxlength="2000">
        <button class="btn btn--gold" type="submit">Add</button>
      </form>
      <ul class="note-list">
        <?php foreach ($notes as $n): ?>
          <li>
            <time><?= e(fmt_datetime($n['date_entered'])) ?><?= $n['created_by_name'] ? ' by ' . e($n['created_by_name']) : '' ?></time>
            <p><?= nl2br(e($n['description'] ?? $n['name'])) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </div>
</div>

<!-- Subpanels: related records not already covered by the tabs -->
<div class="subpanels">
  <?php if ($showQuotes): ?>
  <details class="subpanel" open>
    <summary class="subpanel__summary">
      <span class="subpanel__name"><svg class="icon icon--sm subpanel__chev"><use href="#i-chev"/></svg>Quotations</span>
      <span class="subpanel__count"><?= count($quotes) ?> records</span>
    </summary>
    <div class="subpanel__body">
      <?php if ($quotes): ?>
        <div class="table-scroll">
          <table class="table">
            <thead><tr><th>Number</th><th>Deal</th><th>Status</th><th>Subtotal</th><th>Discount</th><th>Total</th><th>Last sent</th></tr></thead>
            <tbody>
              <?php foreach ($quotes as $q): ?>
                <tr>
                  <td><?= e($q['number']) ?></td>
                  <td><?= e($q['opportunity_name']) ?></td>
                  <td><span class="status status-quote-<?= e(strtolower(str_replace(' ', '-', $q['status']))) ?>"><?= e($q['status']) ?></span></td>
                  <td><?= e(money($q['subtotal'])) ?></td>
                  <td><?= $q['discount_amount'] > 0 ? '-' . e(money($q['discount_amount'])) : '' ?></td>
                  <td><?= e(money($q['total'])) ?></td>
                  <td><?php if ($q['emails']): ?><a href="outbox.php?id=<?= e($q['emails'][0]['id']) ?>"><?= e(fmt_datetime($q['emails'][0]['date_sent'])) ?></a><?php endif; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="empty">No quotations for this customer.</p>
      <?php endif; ?>
    </div>
  </details>
  <?php endif; ?>

  <?php if ($shows('contacts')): ?>
  <details class="subpanel" open>
    <summary class="subpanel__summary">
      <span class="subpanel__name"><svg class="icon icon--sm subpanel__chev"><use href="#i-chev"/></svg>Contacts</span>
      <span class="subpanel__count"><?= count($contacts) ?> records</span>
    </summary>
    <div class="subpanel__body">
      <div class="toolbar">
        <button class="btn" type="button">Create</button>
        <button class="btn" type="button">Select</button>
      </div>
      <?php if ($contacts): ?>
        <div class="table-scroll">
          <table class="table">
            <thead><tr><th>Name</th><th>Title</th><th>Email</th><th>Office phone</th></tr></thead>
            <tbody>
              <?php foreach ($contacts as $ct): ?>
                <tr><td><?= e($ct['name']) ?></td><td><?= e($ct['title']) ?></td><td><?= e($ct['email1']) ?></td><td><?= e($ct['phone_work']) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="empty">No contacts linked to this customer.</p>
      <?php endif; ?>
    </div>
  </details>
  <?php endif; ?>

  <?php if ($shows('leads')): ?>
  <details class="subpanel"<?= $leads ? ' open' : '' ?>>
    <summary class="subpanel__summary">
      <span class="subpanel__name"><svg class="icon icon--sm subpanel__chev"><use href="#i-chev"/></svg>Leads</span>
      <span class="subpanel__count"><?= count($leads) ?> records</span>
    </summary>
    <div class="subpanel__body">
      <div class="toolbar">
        <button class="btn" type="button">Create</button>
        <button class="btn" type="button">Select</button>
      </div>
      <?php if ($leads): ?>
        <div class="table-scroll">
          <table class="table">
            <thead><tr><th>Name</th><th>Status</th><th>Lead source</th></tr></thead>
            <tbody>
              <?php foreach ($leads as $l): ?>
                <tr><td><?= e($l['name']) ?></td><td><?= e($l['status']) ?></td><td><?= e($l['lead_source']) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="empty">No leads linked to this customer.</p>
      <?php endif; ?>
    </div>
  </details>
  <?php endif; ?>

  <?php if ($shows('cases')): ?>
  <details class="subpanel"<?= $cases ? ' open' : '' ?>>
    <summary class="subpanel__summary">
      <span class="subpanel__name"><svg class="icon icon--sm subpanel__chev"><use href="#i-chev"/></svg>Cases</span>
      <span class="subpanel__count"><?= count($cases) ?> records</span>
    </summary>
    <div class="subpanel__body">
      <div class="toolbar">
        <button class="btn" type="button">Create</button>
        <button class="btn" type="button">Select</button>
      </div>
      <?php if ($cases): ?>
        <div class="table-scroll">
          <table class="table">
            <thead><tr><th>Number</th><th>Subject</th><th>Status</th><th>Priority</th></tr></thead>
            <tbody>
              <?php foreach ($cases as $cs): ?>
                <tr>
                  <td><?= e($cs['case_number']) ?></td>
                  <td><?= e($cs['name']) ?></td>
                  <td><?= e(option_label($caseFields['status']['options'], $cs['status'])) ?></td>
                  <td><?= e(option_label($caseFields['priority']['options'], $cs['priority'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="empty">No cases linked to this customer.</p>
      <?php endif; ?>
    </div>
  </details>
  <?php endif; ?>
</div>

<?php if ($quote): ?>
<!-- Apply discount to the latest quotation -->
<div class="modal-backdrop<?= $discountErrors ? ' show' : '' ?>" id="discountModal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="discountTitle">
    <h2 id="discountTitle">Apply discount</h2>
    <p>Quotation <?= e($quote['number']) ?>, subtotal <?= e(money($quote['subtotal'])) ?>.</p>

    <form method="post">
      <input type="hidden" name="action" value="discount">
      <input type="hidden" name="quote_id" value="<?= e($quote['id']) ?>">

      <label for="discount-label">Promotion name (optional)</label>
      <input class="input" id="discount-label" name="label" maxlength="100" placeholder="e.g. Autumn promo"
             value="<?= e($discountErrors ? $discountInput['label'] : ($quote['discount_label'] ?? '')) ?>">

      <label for="discount-value">Discount</label>
      <div class="input-group">
        <input class="input" id="discount-value" name="value" type="number" min="0" step="0.01" required
               value="<?= e($discountErrors ? $discountInput['value'] : ($quote['discount_value'] ?? '')) ?>"
               <?= isset($discountErrors['value']) ? 'aria-invalid="true" aria-describedby="err-discount"' : '' ?>>
        <?php $discountType = $discountErrors ? $discountInput['type'] : ($quote['discount_type'] ?? 'percent'); ?>
        <select class="input discount-type" name="type" aria-label="Discount type">
          <option value="percent"<?= $discountType === 'percent' ? ' selected' : '' ?>>%</option>
          <option value="amount"<?= $discountType === 'amount' ? ' selected' : '' ?>>$ amount</option>
        </select>
      </div>
      <?php if ($discountErrors): ?>
        <p class="error" id="err-discount"><?= e(implode(' ', $discountErrors)) ?></p>
      <?php endif; ?>
      <p class="field-hint">Enter 0 to remove the discount.</p>

      <label class="checkbox-label">
        <input type="checkbox" name="send" value="1"<?= (!$discountErrors || $discountInput['send']) ? ' checked' : '' ?>>
        Email the updated quotation to the customer
      </label>

      <div class="modal-actions">
        <button type="button" class="btn" data-close-modal>Cancel</button>
        <button type="submit" class="btn btn--gold">Apply discount</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($changeLog !== null): ?>
<div class="modal-backdrop show" id="changeLogModal">
  <div class="modal modal--wide" role="dialog" aria-modal="true" aria-labelledby="changeLogTitle">
    <h2 id="changeLogTitle">Change log</h2>
    <?php if ($changeLog): ?>
      <div class="table-scroll">
        <table class="table">
          <thead><tr><th>Field</th><th>Before</th><th>After</th><th>Changed</th><th>By</th></tr></thead>
          <tbody>
            <?php foreach ($changeLog as $entry): ?>
              <tr>
                <td><?= e($entry['field_name']) ?></td>
                <td><?= e($entry['before']) ?></td>
                <td><?= e($entry['after']) ?></td>
                <td><?= e(fmt_datetime($entry['date_created'])) ?></td>
                <td><?= e($entry['created_by_name']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p>No changes recorded for this customer.</p>
    <?php endif; ?>
    <div class="modal-actions"><a class="btn" href="<?= e($self) ?>">Close</a></div>
  </div>
</div>
<?php endif; ?>

<?php if ($duplicates !== null): ?>
<div class="modal-backdrop show" id="duplicatesModal">
  <div class="modal modal--wide" role="dialog" aria-modal="true" aria-labelledby="duplicatesTitle">
    <h2 id="duplicatesTitle">Possible duplicates</h2>
    <?php if ($duplicates): ?>
      <p>Customers with the same name or office phone, or a shared email address.</p>
      <ul class="sidebar__list">
        <?php foreach ($duplicates as $dup): ?>
          <li><a class="sidebar__link" href="detail.php?id=<?= e($dup['id']) ?>"><?= e($dup['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No duplicates found.</p>
    <?php endif; ?>
    <div class="modal-actions"><a class="btn" href="<?= e($self) ?>">Close</a></div>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
