<?php
// Opportunities: customers that are yet to be contacted.
require __DIR__ . '/includes/bootstrap.php';

$statusOptions = api_get('/api/meta/accounts')['fields']['status']['options'];
$stageOptions = api_get('/api/meta/accounts')['fields']['customer_stage']['options'];

$search = trim($_GET['search'] ?? '');
$pageSize = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));

// Pull candidates (100 at a time) and keep those never contacted, across all pages.
$yetToContact = [];
$total = 0;
for ($p = 1; ; $p++) {
    $list = api_get('/api/accounts', [
        'q' => $search,
        'per_page' => 100,
        'page' => $p,
        'sort' => 'date_modified',
        'dir' => 'desc',
    ]);
    foreach ($list['records'] as $c) {
        if (($c['last_contacted'] ?? null) === null) {
            $yetToContact[] = $c;
        }
    }
    $total = $list['total'];
    if (count($yetToContact) >= 0 && ($p * 100 >= $total || !$list['records'])) {
        break;
    }
    if (count($yetToContact) >= 500) {
        break;
    }
}

$totalCount = count($yetToContact);
$totalPages = max(1, (int) ceil($totalCount / $pageSize));
if ($page > $totalPages) {
    $page = $totalPages;
}
$visible = array_slice($yetToContact, ($page - 1) * $pageSize, $pageSize);
$firstShown = $totalCount ? ($page - 1) * $pageSize + 1 : 0;

$pageTitle = 'Opportunities';
$activeModule = 'opportunities';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="page-head">
  <div>
    <h1 class="page-title">Opportunities</h1>
    <p class="page-sub">Customers that are yet to be contacted.</p>
  </div>
</div>

<div class="toolbar">
  <form method="GET" class="filters">
    <input class="input filters__search" type="text" name="search" placeholder="Search opportunities..."
           value="<?= e($search) ?>" aria-label="Search opportunities">
    <button type="submit" class="btn btn--blue">Filter</button>
    <a href="opportunities.php" class="btn">Reset</a>
  </form>

  <nav class="pager" aria-label="Pages">
    <span class="pager__info">(<?= $firstShown ?> - <?= $firstShown ? $firstShown + count($visible) - 1 : 0 ?> of <?= $totalCount ?>)</span>
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
          <th>Status</th>
          <th>Stage</th>
          <th>Contact</th>
          <th>City</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Date created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($visible as $c): ?>
          <tr>
            <td>
              <div class="customer-name">
                <span class="avatar avatar--soft avatar--sm"><?= e(initials($c['name'])) ?></span>
                <a href="detail.php?id=<?= e($c['id']) ?>"><strong><?= e($c['name']) ?></strong></a>
              </div>
            </td>
            <td><?php if ($c['status'] !== null): ?><span class="status <?= e(status_class($c['status'])) ?>"><?= e($c['status']) ?></span><?php endif; ?></td>
            <td><?php if ($c['customer_stage'] !== null && $c['customer_stage'] !== ''): ?><span class="status <?= e(stage_badge_class($c['customer_stage'])) ?>"><?= e(option_label($stageOptions, $c['customer_stage'])) ?></span><?php endif; ?></td>
            <td><?= e($c['contact_name']) ?></td>
            <td><?= e($c['billing_address_city']) ?></td>
            <td class="email"><?= e($c['email1']) ?></td>
            <td><?= e($c['phone_office']) ?></td>
            <td><?= e(fmt_date($c['date_entered'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (!$visible): ?>
      <div class="empty-state">
        <h3>No opportunities found</h3>
        <p>Every customer has been contacted, or try changing your search.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
