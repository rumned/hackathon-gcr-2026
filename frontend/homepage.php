<?php
require __DIR__ . '/includes/bootstrap.php';

// Dashlet data for the logged-in user, from the backend
$calls         = api_get('/api/dashlets/my-calls');
$meetings      = api_get('/api/dashlets/my-meetings');
$opportunities = api_get('/api/dashlets/my-open-opportunities');
$pipeline      = api_get('/api/dashlets/pipeline-by-sales-stage');
$myCustomers   = api_get('/api/dashlets/my-accounts');
$leads         = api_get('/api/dashlets/my-leads');

$stages = array_values(array_filter($pipeline['stages'], fn ($s) => $s['count'] > 0));
$maxStage = $stages ? max(array_column($stages, 'amount')) : 0;

/** "(1 - 5 of 7)" */
function range_text(array $page): string
{
    $first = $page['total'] ? ($page['page'] - 1) * $page['per_page'] + 1 : 0;
    return '(' . $first . ' - ' . ($first ? $first + count($page['records']) - 1 : 0) . ' of ' . $page['total'] . ')';
}

/** Link to the related record when it is a customer. */
function related_link(array $row): string
{
    if (($row['parent_name'] ?? null) === null) {
        return '';
    }
    return ($row['parent_type'] ?? '') === 'Accounts'
        ? '<a href="detail.php?id=' . e($row['parent_id']) . '">' . e($row['parent_name']) . '</a>'
        : e($row['parent_name']);
}

$pageTitle = 'Dashboard';
$activeModule = 'home';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="page-head">
  <h1 class="page-title">Home</h1>
  <div class="menu menu--right">
    <button class="btn btn--blue" type="button" aria-haspopup="true">
      Actions <svg class="icon icon--sm"><use href="#i-chev"/></svg>
    </button>
    <div class="menu__panel">
      <a href="#" class="menu__item">Add dashlet</a>
      <a href="#" class="menu__item">Add tab</a>
      <a href="#" class="menu__item">Edit tabs</a>
    </div>
  </div>
</div>

<div class="tabs tabs--dashboard" role="tablist">
  <button class="tab" role="tab" type="button" aria-selected="true">SUITECRM DASHBOARD</button>
  <button class="tab" role="tab" type="button" aria-selected="false" aria-label="Add dashboard tab">
    <svg class="icon icon--sm"><use href="#i-plus"/></svg>
  </button>
</div>

<div class="dashlets">

  <!-- My calls (original HomePage) -->
  <article class="dashlet">
    <header class="dashlet__head">
      <h2 class="dashlet__title"><svg class="icon icon--sm"><use href="#i-phone"/></svg> My calls</h2>
      <div class="dashlet__tools">
        <button class="mini-btn" type="button" aria-label="Refresh"><svg class="icon"><use href="#i-refresh"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Edit dashlet"><svg class="icon"><use href="#i-gear"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Remove dashlet"><svg class="icon"><use href="#i-x"/></svg></button>
      </div>
    </header>
    <div class="dashlet__body">
      <table class="table table--dashlet">
        <thead>
          <tr>
            <th>Close</th>
            <th>Subject <svg class="icon icon--xs"><use href="#i-sort"/></svg></th>
            <th>Related to</th>
            <th>Start date <svg class="icon icon--xs"><use href="#i-sort"/></svg></th>
            <th>Status <svg class="icon icon--xs"><use href="#i-sort"/></svg></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($calls['records'] as $call): ?>
          <tr>
            <td><button class="row-close" type="button" aria-label="Close call"><svg class="icon"><use href="#i-x"/></svg></button></td>
            <td><?= e($call['name']) ?></td>
            <td><?= related_link($call) ?></td>
            <td><?= e(fmt_datetime($call['date_start'])) ?></td>
            <td><?= e($call['status']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$calls['records']): ?>
            <tr><td colspan="5">No planned calls.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <footer class="dashlet__foot">
      <button class="mini-btn" type="button" aria-label="First page"><svg class="icon"><use href="#i-first"/></svg></button>
      <button class="mini-btn" type="button" aria-label="Previous page"><svg class="icon"><use href="#i-back"/></svg></button>
      <span><?= e(range_text($calls)) ?></span>
      <button class="mini-btn" type="button" aria-label="Next page"><svg class="icon"><use href="#i-next"/></svg></button>
      <button class="mini-btn" type="button" aria-label="Last page"><svg class="icon"><use href="#i-last"/></svg></button>
    </footer>
  </article>

  <!-- My activity stream (original HomePage; sample content, the backend has no activity stream) -->
  <article class="dashlet">
    <header class="dashlet__head">
      <h2 class="dashlet__title"><svg class="icon icon--sm"><use href="#i-bell"/></svg> My activity stream</h2>
      <div class="dashlet__tools">
        <button class="mini-btn" type="button" aria-label="Refresh"><svg class="icon"><use href="#i-refresh"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Edit dashlet"><svg class="icon"><use href="#i-gear"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Remove dashlet"><svg class="icon"><use href="#i-x"/></svg></button>
      </div>
    </header>
    <div class="dashlet__body dashlet__body--padded">
      <div class="activity-item">
        <div class="activity-avatar"><svg class="icon"><use href="#i-user"/></svg></div>
        <div class="activity-content">
          <strong>Status Update</strong>
          <span class="time">0h 10 Minutes ago</span>
          <p>Will Westin created a new opportunity TEST with <a href="#">B.C. Investing International</a> for amount $200,000.00</p>
          <div class="activity-actions">
            <button class="act-btn" type="button" aria-label="Like"><svg class="icon"><use href="#i-thumb"/></svg></button>
            <button class="act-btn" type="button" aria-label="Reply"><svg class="icon"><use href="#i-reply"/></svg></button>
          </div>
        </div>
      </div>
      <div class="activity-item">
        <div class="activity-avatar">
          <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100" alt="User">
        </div>
        <div class="activity-content">
          <strong>Task Complete</strong>
          <span class="time">0h 10 Minutes ago</span>
          <p>Will Westin created a new contact TEST TEST</p>
          <div class="activity-actions">
            <button class="act-btn" type="button" aria-label="Like"><svg class="icon"><use href="#i-thumb"/></svg></button>
            <button class="act-btn" type="button" aria-label="Reply"><svg class="icon"><use href="#i-reply"/></svg></button>
          </div>
        </div>
      </div>
    </div>
  </article>

  <!-- My meetings (wireframe) -->
  <article class="dashlet">
    <header class="dashlet__head">
      <h2 class="dashlet__title"><svg class="icon icon--sm"><use href="#i-cal"/></svg> My meetings</h2>
      <div class="dashlet__tools">
        <button class="mini-btn" type="button" aria-label="Refresh"><svg class="icon"><use href="#i-refresh"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Edit dashlet"><svg class="icon"><use href="#i-gear"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Remove dashlet"><svg class="icon"><use href="#i-x"/></svg></button>
      </div>
    </header>
    <div class="dashlet__body">
      <table class="table table--dashlet">
        <thead><tr><th>Subject</th><th>Start date</th><th>Related to</th></tr></thead>
        <tbody>
          <?php foreach ($meetings['records'] as $m): ?>
            <tr><td><?= e($m['name']) ?></td><td><?= e(fmt_datetime($m['date_start'])) ?></td><td><?= related_link($m) ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$meetings['records']): ?>
            <tr><td colspan="3">No planned meetings.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <!-- My open opportunities (wireframe) -->
  <article class="dashlet">
    <header class="dashlet__head">
      <h2 class="dashlet__title"><svg class="icon icon--sm"><use href="#i-brief"/></svg> My open opportunities</h2>
      <div class="dashlet__tools">
        <button class="mini-btn" type="button" aria-label="Refresh"><svg class="icon"><use href="#i-refresh"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Edit dashlet"><svg class="icon"><use href="#i-gear"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Remove dashlet"><svg class="icon"><use href="#i-x"/></svg></button>
      </div>
    </header>
    <div class="dashlet__body">
      <table class="table table--dashlet">
        <thead><tr><th>Name</th><th>Customer</th><th>Sales stage</th><th>Amount</th></tr></thead>
        <tbody>
          <?php foreach ($opportunities['records'] as $o): ?>
            <tr>
              <td><?= e($o['name']) ?></td>
              <td><?php if ($o['account_id']): ?><a href="detail.php?id=<?= e($o['account_id']) ?>"><?= e($o['account_name']) ?></a><?php endif; ?></td>
              <td><?= e($o['sales_stage']) ?></td>
              <td><?= e(money($o['amount'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$opportunities['records']): ?>
            <tr><td colspan="4">No open opportunities.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <!-- My pipeline by sales stage (wireframe) -->
  <article class="dashlet">
    <header class="dashlet__head">
      <h2 class="dashlet__title"><svg class="icon icon--sm"><use href="#i-chart"/></svg> My pipeline by sales stage</h2>
      <div class="dashlet__tools">
        <button class="mini-btn" type="button" aria-label="Refresh"><svg class="icon"><use href="#i-refresh"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Edit dashlet"><svg class="icon"><use href="#i-gear"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Remove dashlet"><svg class="icon"><use href="#i-x"/></svg></button>
      </div>
    </header>
    <div class="dashlet__body dashlet__body--padded">
      <?php if ($stages): ?>
        <ul class="bars">
          <?php foreach ($stages as $stage): ?>
            <li class="bar">
              <span><?= e($stage['label']) ?></span>
              <span class="bar__track"><span class="bar__fill" style="--pct: <?= $maxStage > 0 ? round($stage['amount'] / $maxStage * 100) : 0 ?>%"></span></span>
              <span class="bar__value"><?= e(money($stage['amount'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p class="empty">No open opportunities.</p>
      <?php endif; ?>
    </div>
  </article>

  <!-- My customers (wireframe's "My accounts") -->
  <article class="dashlet">
    <header class="dashlet__head">
      <h2 class="dashlet__title"><svg class="icon icon--sm"><use href="#i-users"/></svg> My customers</h2>
      <div class="dashlet__tools">
        <button class="mini-btn" type="button" aria-label="Refresh"><svg class="icon"><use href="#i-refresh"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Edit dashlet"><svg class="icon"><use href="#i-gear"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Remove dashlet"><svg class="icon"><use href="#i-x"/></svg></button>
      </div>
    </header>
    <div class="dashlet__body">
      <table class="table table--dashlet">
        <thead><tr><th>Name</th><th>Type</th><th>City</th><th>Phone</th></tr></thead>
        <tbody>
          <?php foreach ($myCustomers['records'] as $c): ?>
            <tr>
              <td><a href="detail.php?id=<?= e($c['id']) ?>"><?= e($c['name']) ?></a></td>
              <td><?= e($c['account_type']) ?></td>
              <td><?= e($c['billing_address_city']) ?></td>
              <td><?= e($c['phone_office']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$myCustomers['records']): ?>
            <tr><td colspan="4">No customers assigned to you.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <!-- My leads (wireframe) -->
  <article class="dashlet">
    <header class="dashlet__head">
      <h2 class="dashlet__title"><svg class="icon icon--sm"><use href="#i-user"/></svg> My leads</h2>
      <div class="dashlet__tools">
        <button class="mini-btn" type="button" aria-label="Refresh"><svg class="icon"><use href="#i-refresh"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Edit dashlet"><svg class="icon"><use href="#i-gear"/></svg></button>
        <button class="mini-btn" type="button" aria-label="Remove dashlet"><svg class="icon"><use href="#i-x"/></svg></button>
      </div>
    </header>
    <div class="dashlet__body">
      <table class="table table--dashlet">
        <thead><tr><th>Name</th><th>Status</th><th>Lead source</th></tr></thead>
        <tbody>
          <?php foreach ($leads['records'] as $l): ?>
            <tr><td><?= e($l['name']) ?></td><td><?= e($l['status']) ?></td><td><?= e($l['lead_source']) ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$leads['records']): ?>
            <tr><td colspan="3">No leads assigned to you.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
