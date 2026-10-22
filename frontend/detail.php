<?php
require __DIR__ . '/includes/bootstrap.php';

$c = find_customer((int)($_GET['id'] ?? 0));

if (!$c) {
    http_response_code(404);
    $pageTitle = 'Customer not found';
    $activeModule = 'customers';
    require __DIR__ . '/includes/layout-top.php';
    echo '<div class="empty-state"><h3>Customer not found</h3><p><a href="customer.php">Back to Customers</a></p></div>';
    require __DIR__ . '/includes/layout-bottom.php';
    exit;
}

// Placeholder related records. Later, replace these with calls to your API.
$first = explode(' ', $c['name'])[0];
$domain = substr(strrchr($c['email'], '@') ?: '@example.com', 1);

$activities = [
    [$first . ' viewed the proposal', '2 minutes ago'],
    ['Alex called ' . $first . ': discussed pricing', 'Yesterday, 3:15 PM'],
    ['Email sent: product overview', 'Mar 18, 2024'],
    ['Lead created from website form', 'Mar 15, 2024'],
];
$deals = [
    [$c['company'] . ' CRM rollout', 'Proposal', '$24,000', 'Apr 30, 2024'],
    ['Marketing automation add-on', 'Qualified', '$8,500', 'May 15, 2024'],
];
$tasks = [
    ['Call ' . $c['name'], 'Today, 10:00 AM', false],
    ['Send contract to ' . $c['company'], 'Tomorrow, 11:00 AM', false],
    ['Share product demo recording', 'Mar 19, 2024', true],
];
$contacts = [
    ['Jane Smith', 'Operations manager', 'jane@' . $domain, '+1 234 567 811'],
    ['Omar Haddad', 'Finance director', 'omar@' . $domain, '+1 234 567 812'],
];

$billing = address_lines($c, 'billing');
$shipping = address_lines($c, 'shipping');

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
        <span class="status status-<?= e(strtolower($c['status'])) ?>"><?= e($c['status']) ?></span>
        <button class="mini-btn" type="button" aria-label="Add to favorites"><svg class="icon"><use href="#i-star"/></svg></button>
      </div>
      <div class="profile__meta">
        <span><svg class="icon"><use href="#i-building"/></svg><?= e($c['company']) ?></span>
        <?php if ($c['title'] !== ''): ?>
          <span><svg class="icon"><use href="#i-brief"/></svg><?= e($c['title']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <div class="page-head__actions">
      <a class="btn btn--gold" href="edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
      <div class="menu menu--right">
        <button class="btn" type="button" aria-haspopup="true">
          Actions <svg class="icon icon--sm"><use href="#i-chev"/></svg>
        </button>
        <div class="menu__panel">
          <button class="menu__item" type="button" data-action="duplicate">Duplicate</button>
          <button class="menu__item" type="button" data-action="find-duplicates">Find duplicates</button>
          <button class="menu__item" type="button" data-action="changelog">View change log</button>
          <button class="menu__item menu__item--danger" type="button" data-action="delete">Delete</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div data-tabs>
  <div class="tabs" role="tablist">
    <button class="tab" role="tab" type="button" aria-selected="true" data-tab="overview">Overview</button>
    <button class="tab" role="tab" type="button" aria-selected="false" data-tab="more">More information</button>
    <button class="tab" role="tab" type="button" aria-selected="false" data-tab="activity">Activity</button>
    <button class="tab" role="tab" type="button" aria-selected="false" data-tab="deals">Deals</button>
    <button class="tab" role="tab" type="button" aria-selected="false" data-tab="tasks">Tasks</button>
    <button class="tab" role="tab" type="button" aria-selected="false" data-tab="notes">Notes</button>
  </div>

  <!-- Overview -->
  <dl class="fields" role="tabpanel" data-tabpanel="overview">
    <div class="fields__item"><dt class="fields__label">Full name</dt><dd class="fields__value"><?= e($c['name']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Company</dt><dd class="fields__value"><a href="#"><?= e($c['company']) ?></a></dd></div>
    <div class="fields__item"><dt class="fields__label">Job title</dt><dd class="fields__value"><?= e($c['title']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Office phone</dt><dd class="fields__value"><?= e($c['phone']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Email</dt><dd class="fields__value"><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></dd></div>
    <div class="fields__item"><dt class="fields__label">Mobile</dt><dd class="fields__value"><?= e($c['mobile']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Website</dt><dd class="fields__value">
      <?php if ($c['website'] !== ''): ?><a href="https://<?= e($c['website']) ?>"><?= e($c['website']) ?></a><?php endif; ?>
    </dd></div>
    <div class="fields__item"><dt class="fields__label">Fax</dt><dd class="fields__value"><?= e($c['fax']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Billing address</dt><dd class="fields__value"><?= implode('<br>', array_map('e', $billing)) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Shipping address</dt><dd class="fields__value">
      <?= $c['ship_same'] ? 'Same as billing' : implode('<br>', array_map('e', $shipping)) ?>
    </dd></div>
    <div class="fields__item fields__item--wide"><dt class="fields__label">Description</dt><dd class="fields__value"><?= e($c['description']) ?></dd></div>
    <div class="fields__item fields__item--wide"><dt class="fields__label">Assigned to</dt><dd class="fields__value">
      <?php if ($c['assigned'] !== ''): ?>
        <span class="inline-user"><span class="avatar avatar--xs"><?= e(initials($c['assigned'])) ?></span><?= e($c['assigned']) ?></span>
      <?php endif; ?>
    </dd></div>
  </dl>

  <!-- More information -->
  <dl class="fields" role="tabpanel" data-tabpanel="more" hidden>
    <div class="fields__item"><dt class="fields__label">Status</dt><dd class="fields__value"><span class="status status-<?= e(strtolower($c['status'])) ?>"><?= e($c['status']) ?></span></dd></div>
    <div class="fields__item"><dt class="fields__label">Source</dt><dd class="fields__value"><?= e($c['source']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Type</dt><dd class="fields__value"><?= e($c['type']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Industry</dt><dd class="fields__value"><?= e($c['industry']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Annual revenue</dt><dd class="fields__value"><?= e($c['revenue']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Employees</dt><dd class="fields__value"><?= e($c['employees']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Member of</dt><dd class="fields__value"><?= e($c['member_of']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Campaign</dt><dd class="fields__value"><?= e($c['campaign']) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Date created</dt><dd class="fields__value"><?= e(fmt_date($c['created'])) ?></dd></div>
    <div class="fields__item"><dt class="fields__label">Date modified</dt><dd class="fields__value"><?= e(fmt_date($c['modified'])) ?></dd></div>
    <div class="fields__item fields__item--wide"><dt class="fields__label">Last contact</dt><dd class="fields__value"><?= e(fmt_date($c['last_contact'])) ?></dd></div>
    <div class="fields__item fields__item--wide"><dt class="fields__label">Tags</dt><dd class="fields__value">
      <div class="tags">
        <?php foreach ($c['tags'] as $i => $t): ?>
          <span class="tag<?= $i % 2 ? ' tag--gold' : '' ?>"><?= e($t) ?></span>
        <?php endforeach; ?>
        <button class="tag tag--add" type="button">+ Add Tag</button>
      </div>
    </dd></div>
  </dl>

  <!-- Activity -->
  <section class="card" role="tabpanel" data-tabpanel="activity" hidden>
    <h2>Activity</h2>
    <ul class="timeline">
      <?php foreach ($activities as $a): ?>
        <li><span><?= e($a[0]) ?></span><time><?= e($a[1]) ?></time></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <!-- Deals -->
  <section class="card" role="tabpanel" data-tabpanel="deals" hidden>
    <h2>Deals</h2>
    <div class="table-scroll">
      <table class="table table--plain">
        <thead><tr><th>Deal</th><th>Stage</th><th>Amount</th><th>Close date</th></tr></thead>
        <tbody>
          <?php foreach ($deals as $d): ?>
            <tr>
              <td><?= e($d[0]) ?></td>
              <td><span class="status status-<?= e(strtolower($d[1])) ?>"><?= e($d[1]) ?></span></td>
              <td><?= e($d[2]) ?></td>
              <td><?= e($d[3]) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Tasks -->
  <section class="card" role="tabpanel" data-tabpanel="tasks" hidden>
    <h2>Tasks</h2>
    <ul class="task-list">
      <?php foreach ($tasks as $t): ?>
        <li>
          <label>
            <input type="checkbox"<?= $t[2] ? ' checked' : '' ?>>
            <span class="task-title"><?= e($t[0]) ?></span>
          </label>
          <time><?= e($t[1]) ?></time>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <!-- Notes -->
  <section class="card" role="tabpanel" data-tabpanel="notes" hidden>
    <h2>Notes</h2>
    <form class="note-form">
      <input class="input" type="text" placeholder="Add a note..." aria-label="Add a note">
      <button class="btn btn--gold" type="submit">Add</button>
    </form>
    <ul class="note-list"></ul>
  </section>
</div>

<!-- Subpanels: related records not already covered by the tabs -->
<div class="subpanels">
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
      <div class="table-scroll">
        <table class="table">
          <thead><tr><th>Name</th><th>Title</th><th>Email</th><th>Office phone</th></tr></thead>
          <tbody>
            <?php foreach ($contacts as $ct): ?>
              <tr><td><?= e($ct[0]) ?></td><td><?= e($ct[1]) ?></td><td><?= e($ct[2]) ?></td><td><?= e($ct[3]) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </details>

  <details class="subpanel">
    <summary class="subpanel__summary">
      <span class="subpanel__name"><svg class="icon icon--sm subpanel__chev"><use href="#i-chev"/></svg>Leads</span>
      <span class="subpanel__count">0 records</span>
    </summary>
    <div class="subpanel__body">
      <div class="toolbar">
        <button class="btn" type="button">Create</button>
        <button class="btn" type="button">Select</button>
      </div>
      <p class="empty">No leads linked to this customer.</p>
    </div>
  </details>

  <details class="subpanel">
    <summary class="subpanel__summary">
      <span class="subpanel__name"><svg class="icon icon--sm subpanel__chev"><use href="#i-chev"/></svg>Cases</span>
      <span class="subpanel__count">0 records</span>
    </summary>
    <div class="subpanel__body">
      <div class="toolbar">
        <button class="btn" type="button">Create</button>
        <button class="btn" type="button">Select</button>
      </div>
      <p class="empty">No cases linked to this customer.</p>
    </div>
  </details>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
