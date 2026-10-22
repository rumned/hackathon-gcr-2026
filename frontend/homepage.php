<?php
require __DIR__ . '/includes/bootstrap.php';

// Mock data for calls table (from the original HomePage)
$calls = [
    ["subject" => "Get more information on the proposed deal", "related" => "A.G. Parr PLC", "date" => "2026-11-09 17:30", "accept" => "Accepted", "status" => "Planned"],
    ["subject" => "Bad time, will call back", "related" => "Draft Diversified Energy Inc", "date" => "2027-02-10 12:00", "accept" => "Accepted", "status" => "Planned"],
    ["subject" => "Discuss review process", "related" => "Sandeon Consolidation Corp", "date" => "2027-01-26 17:00", "accept" => "Accepted", "status" => "Planned"],
    ["subject" => "Get more information on the proposed deal", "related" => "Kings Royalty Trust", "date" => "2027-06-27 14:15", "accept" => "Accepted", "status" => "Planned"],
    ["subject" => "Get more information on the proposed deal", "related" => "Constrata Trust LLC", "date" => "2027-08-25 17:45", "accept" => "Accepted", "status" => "Planned"]
];

// Mock data for the wireframe's dashlets
$meetings = [
    ['Quarterly review', '2026-10-27 09:00', 'TechFlow'],
    ['Product demo', '2026-10-29 15:00', 'CloudSync'],
];
$opportunities = [
    ['Acme CRM rollout', 'Acme Corp', 'Proposal', 24000],
    ['Marketing automation add-on', 'Acme Corp', 'Qualified', 8500],
    ['Q4 renewal', 'NextGen', 'Negotiation', 24000],
    ['Fleet tracking', 'DataHub', 'Proposal', 11500],
];
$leads = [
    ['Tom Becker', 'New', 'Website'],
    ['Aisha Rahman', 'Assigned', 'Trade show'],
];

// Pipeline totals by sales stage
$stages = ['Qualified' => 0, 'Proposal' => 0, 'Negotiation' => 0];
foreach ($opportunities as $o) {
    $stages[$o[2]] += $o[3];
}
$maxStage = max($stages);

$myCustomers = array_slice(array_values(array_filter(customers(), fn($c) => $c['assigned'] === 'Alex Carter')), 0, 3);

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
            <th>Accept?</th>
            <th>Status <svg class="icon icon--xs"><use href="#i-sort"/></svg></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($calls as $call): ?>
          <tr>
            <td><button class="row-close" type="button" aria-label="Close call"><svg class="icon"><use href="#i-x"/></svg></button></td>
            <td><a href="#"><?= e($call['subject']) ?></a></td>
            <td><a href="#"><?= e($call['related']) ?></a></td>
            <td><?= e($call['date']) ?></td>
            <td><?= e($call['accept']) ?></td>
            <td><?= e($call['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <footer class="dashlet__foot">
      <button class="mini-btn" type="button" aria-label="First page"><svg class="icon"><use href="#i-first"/></svg></button>
      <button class="mini-btn" type="button" aria-label="Previous page"><svg class="icon"><use href="#i-back"/></svg></button>
      <span>(1 - 5 of 7)</span>
      <button class="mini-btn" type="button" aria-label="Next page"><svg class="icon"><use href="#i-next"/></svg></button>
      <button class="mini-btn" type="button" aria-label="Last page"><svg class="icon"><use href="#i-last"/></svg></button>
    </footer>
  </article>

  <!-- My activity stream (original HomePage) -->
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
          <?php foreach ($meetings as $m): ?>
            <tr><td><?= e($m[0]) ?></td><td><?= e($m[1]) ?></td><td><?= e($m[2]) ?></td></tr>
          <?php endforeach; ?>
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
        <thead><tr><th>Name</th><th>Company</th><th>Sales stage</th><th>Amount</th></tr></thead>
        <tbody>
          <?php foreach ($opportunities as $o): ?>
            <tr><td><?= e($o[0]) ?></td><td><?= e($o[1]) ?></td><td><?= e($o[2]) ?></td><td>$<?= number_format($o[3]) ?></td></tr>
          <?php endforeach; ?>
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
      <ul class="bars">
        <?php foreach ($stages as $stage => $amount): ?>
          <li class="bar">
            <span><?= e($stage) ?></span>
            <span class="bar__track"><span class="bar__fill" style="--pct: <?= round($amount / $maxStage * 100) ?>%"></span></span>
            <span class="bar__value">$<?= number_format($amount) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
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
        <thead><tr><th>Name</th><th>Company</th><th>City</th><th>Phone</th></tr></thead>
        <tbody>
          <?php foreach ($myCustomers as $c): ?>
            <tr>
              <td><a href="detail.php?id=<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a></td>
              <td><?= e($c['company']) ?></td>
              <td><?= e($c['billing_city']) ?></td>
              <td><?= e($c['phone']) ?></td>
            </tr>
          <?php endforeach; ?>
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
          <?php foreach ($leads as $l): ?>
            <tr><td><?= e($l[0]) ?></td><td><?= e($l[1]) ?></td><td><?= e($l[2]) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </article>

</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
