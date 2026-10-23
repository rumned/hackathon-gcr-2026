<?php
// Profiles: customer profiles — every customer with contact details and last action date.
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
    'sort' => 'name',
    'dir' => 'asc',
];
$list = api_get('/api/accounts', $listQuery);
$total = $list['total'];
$page = $list['page'];
$totalPages = max(1, (int) ceil($total / $pageSize));
$shown = count($list['records']);
$firstShown = $total ? ($page - 1) * $pageSize + 1 : 0;

$pageTitle = 'Profiles';
$activeModule = 'profiles';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="page-head">
  <div>
    <h1 class="page-title">Profiles</h1>
    <p class="page-sub">Customer profiles: contact details and last action dates.</p>
  </div>
  <a class="btn btn--gold" href="customer.php?add=1">
    <svg class="icon icon--sm"><use href="#i-plus"/></svg> Add Customer
  </a>
</div>

<div class="profiles">
  <?php foreach ($list['records'] as $c): ?>
    <?php
      $city = trim((string) ($c['billing_address_city'] ?? ''));
      $country = trim((string) ($c['billing_address_country'] ?? ''));
      $place = trim($city . ($city !== '' && $country !== '' ? ', ' : '') . $country);
      $lastAction = $c['last_contacted'] ?? $c['date_modified'] ?? null;
    ?>
    <article class="profile-card">
      <header class="profile-card__head">
        <span class="avatar avatar--soft avatar--sm"><?= e(initials($c['name'])) ?></span>
        <div class="profile-card__title">
          <a href="detail.php?id=<?= e($c['id']) ?>"><strong><?= e($c['name']) ?></strong></a>
          <span class="muted"><?= e($c['assigned_user_name'] ?? '') ?></span>
        </div>
      </header>
      <p class="profile-card__badges">
        <?php if ($c['customer_stage'] !== null && $c['customer_stage'] !== ''): ?><span class="status <?= e(stage_badge_class($c['customer_stage'])) ?>"><?= e(option_label($stageOptions, $c['customer_stage'])) ?></span><?php endif; ?>
        <?php if ($c['status'] !== null): ?><span class="status <?= e(status_class($c['status'])) ?>"><?= e($c['status']) ?></span><?php endif; ?>
      </p>
      <dl class="profile-card__details">
        <?php if (($c['contact_name'] ?? '') !== ''): ?>
          <div><dt>Contact</dt><dd><?= e($c['contact_name']) ?><?= ($c['contact_title'] ?? '') !== '' ? ' · ' . e($c['contact_title']) : '' ?></dd></div>
        <?php endif; ?>
        <?php if (($c['email1'] ?? null) !== null && $c['email1'] !== ''): ?>
          <div><dt>Email</dt><dd class="email"><a href="mailto:<?= e($c['email1']) ?>"><?= e($c['email1']) ?></a></dd></div>
        <?php endif; ?>
        <?php if (($c['phone_office'] ?? '') !== '' || ($c['phone_mobile'] ?? '') !== ''): ?>
          <div><dt>Phone</dt><dd><?= e(trim(trim((string) ($c['phone_office'] ?? '')) . (($c['phone_office'] ?? '') !== '' && ($c['phone_mobile'] ?? '') !== '' ? ' · ' : '') . trim((string) ($c['phone_mobile'] ?? '')))) ?></dd></div>
        <?php endif; ?>
        <?php if ($place !== ''): ?>
          <div><dt>City</dt><dd><?= e($place) ?></dd></div>
        <?php endif; ?>
        <div><dt>Last action</dt><dd><?= $lastAction !== null ? e(fmt_date($lastAction)) : '<span class="muted">Not contacted yet</span>' ?></dd></div>
      </dl>
      <div class="profile-card__actions">
        <a class="btn btn--sm" href="detail.php?id=<?= e($c['id']) ?>">View</a>
        <a class="btn btn--sm" href="edit.php?id=<?= e($c['id']) ?>">Edit</a>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if (!$list['records']): ?>
    <div class="empty-state">
      <h3>No profiles found</h3>
      <p>Try changing your search.</p>
    </div>
  <?php endif; ?>
</div>

<nav class="pager pager--below" aria-label="Pages">
  <span class="pager__info">(<?= $firstShown ?> - <?= $firstShown ? $firstShown + $shown - 1 : 0 ?> of <?= $total ?>)</span>
  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a class="mini-btn<?= $page === $i ? ' is-current' : '' ?>" href="?<?= e(http_build_query(array_merge($_GET, ['page' => $i]))) ?>"
       <?= $page === $i ? 'aria-current="page"' : '' ?>><?= $i ?></a>
  <?php endfor; ?>
</nav>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
