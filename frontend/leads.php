<?php
// Leads: customers on the Lead sales status.
require __DIR__ . '/includes/bootstrap.php';

$statusOptions = api_get('/api/meta/accounts')['fields']['status']['options'];
$stageOptions = api_get('/api/meta/accounts')['fields']['customer_stage']['options'];
$typeOptions = api_get('/api/meta/accounts')['fields']['account_type']['options'];

$search = trim($_GET['search'] ?? '');
$pageSize = 20;
$listQuery = [
    'q' => $search,
    'per_page' => $pageSize,
    'page' => max(1, (int) ($_GET['page'] ?? 1)),
    'sort' => 'date_modified',
    'dir' => 'desc',
    'filter' => ['status' => 'Lead'],
];
$list = api_get('/api/accounts', $listQuery);
$total = $list['total'];
$page = $list['page'];
$totalPages = max(1, (int) ceil($total / $pageSize));
$shown = count($list['records']);
$firstShown = $total ? ($page - 1) * $pageSize + 1 : 0;

$pageTitle = 'Leads';
$activeModule = 'leads';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="page-head">
  <div>
    <h1 class="page-title">Leads</h1>
    <p class="page-sub">Customers on the Lead sales status.</p>
  </div>
</div>

<div class="toolbar">
  <form method="GET" class="filters">
    <input class="input filters__search" type="text" name="search" placeholder="Search leads..."
           value="<?= e($search) ?>" aria-label="Search leads">
    <button type="submit" class="btn btn--blue">Filter</button>
    <a href="leads.php" class="btn">Reset</a>
  </form>

  <nav class="pager" aria-label="Pages">
    <span class="pager__info">(<?= $firstShown ?> - <?= $firstShown ? $firstShown + $shown - 1 : 0 ?> of <?= $total ?>)</span>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a class="mini-btn<?= $page === $i ? ' is-current' : '' ?>" href="?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>"
         <?= $page === $i ? 'aria-current="page"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>
  </nav>
</div>

<div class="table-card">
  <div class="table-scroll">
    <table class="table table--list">
      <thead>
        <tr>
          <th>Name</th>
          <th>Stage</th>
          <th>Contact</th>
          <th>City</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Last action</th>
          <th>User</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list['records'] as $c): ?>
          <tr>
            <td>
              <div class="customer-name">
                <span class="avatar avatar--soft avatar--sm"><?= e(initials($c['name'])) ?></span>
                <a href="detail.php?id=<?= e($c['id']) ?>"><strong><?= e($c['name']) ?></strong></a>
              </div>
            </td>
            <td><?php if ($c['customer_stage'] !== null && $c['customer_stage'] !== ''): ?><span class="status <?= e(stage_badge_class($c['customer_stage'])) ?>"><?= e(option_label($stageOptions, $c['customer_stage'])) ?></span><?php endif; ?></td>
            <td><?= e($c['contact_name']) ?></td>
            <td><?= e($c['billing_address_city']) ?></td>
            <td class="email"><?= e($c['email1']) ?></td>
            <td><?= e($c['phone_office']) ?></td>
            <td><?= ($c['last_contacted'] ?? null) !== null ? e(fmt_date($c['last_contacted'])) : '<span class="muted">Not contacted yet</span>' ?></td>
            <td><?= e($c['assigned_user_name']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$list['records']): ?>
      <div class="empty-state">
        <h3>No leads found</h3>
        <p>Try changing your search.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
