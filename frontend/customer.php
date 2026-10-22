<?php
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'New';

        if (
            $name !== '' &&
            $company !== '' &&
            $phone !== '' &&
            filter_var($email, FILTER_VALIDATE_EMAIL) &&
            in_array($status, $statuses, true)
        ) {
            $ids = array_column($_SESSION['customers'], 'id');
            $nextId = empty($ids) ? 1 : max($ids) + 1;

            $_SESSION['customers'][] = [
                'id' => $nextId,
                'name' => $name,
                'company' => $company,
                'email' => $email,
                'phone' => $phone,
                'status' => $status,
                'created' => date('Y-m-d'),
                'modified' => date('Y-m-d'),
            ] + blank_customer();
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $_SESSION['customers'] = array_values(array_filter(
            $_SESSION['customers'],
            fn($c) => $c['id'] !== $id
        ));
    }

    header('Location: customer.php');
    exit;
}

$customers = customers();
$tabStatuses = array_merge(['All'], $statuses);

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'All';
$companyFilter = $_GET['company'] ?? 'All';

if (!in_array($statusFilter, $tabStatuses, true)) {
    $statusFilter = 'All';
}

$companies = array_unique(array_column($customers, 'company'));
sort($companies);

$filtered = array_values(array_filter($customers, function($c) use (
    $search, $statusFilter, $companyFilter
) {
    $matchesSearch =
        $search === '' ||
        stripos($c['name'], $search) !== false ||
        stripos($c['company'], $search) !== false ||
        stripos($c['email'], $search) !== false;

    $matchesStatus =
        $statusFilter === 'All' || $c['status'] === $statusFilter;

    $matchesCompany =
        $companyFilter === 'All' || $c['company'] === $companyFilter;

    return $matchesSearch && $matchesStatus && $matchesCompany;
}));

$pageSize = 5;
$totalPages = max(1, (int)ceil(count($filtered) / $pageSize));
$page = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
$visible = array_slice($filtered, ($page - 1) * $pageSize, $pageSize);
$firstShown = count($filtered) ? ($page - 1) * $pageSize + 1 : 0;
$lastShown = ($page - 1) * $pageSize + count($visible);

// Current filters as URL parameters, without the "open the Add modal" flag
function listUrl(array $changes) {
    $params = $_GET;
    unset($params['add']);
    return '?' . http_build_query(array_merge($params, $changes));
}

$pageTitle = 'Customers';
$activeModule = 'customers';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="page-head">
  <div>
    <h1 class="page-title">Customers</h1>
    <p class="page-sub">Manage your customer relationships.</p>
  </div>
  <button class="btn btn--gold" type="button" data-open-modal="customerModal">
    <svg class="icon icon--sm"><use href="#i-plus"/></svg> Add Customer
  </button>
</div>

<div class="status-tabs">
  <?php foreach ($tabStatuses as $s): ?>
    <?php
    $count = $s === 'All'
        ? count($customers)
        : count(array_filter($customers, fn($c) => $c['status'] === $s));
    ?>
    <a href="<?= e(listUrl(['status' => $s, 'page' => 1])) ?>"
       class="status-tab<?= $statusFilter === $s ? ' is-selected' : '' ?>">
      <?= e($s) ?> (<?= $count ?>)
    </a>
  <?php endforeach; ?>
</div>

<div class="toolbar">
  <div class="menu">
    <button class="btn" type="button" aria-haspopup="true">
      Bulk action <svg class="icon icon--sm"><use href="#i-chev"/></svg>
    </button>
    <div class="menu__panel">
      <a href="#" class="menu__item">Delete</a>
      <a href="#" class="menu__item">Export</a>
      <a href="#" class="menu__item">Mass update</a>
      <a href="#" class="menu__item">Merge</a>
      <a href="#" class="menu__item">Add to target list</a>
    </div>
  </div>

  <form method="GET" class="filters">
    <input class="input filters__search" type="text" name="search" placeholder="Search customers..."
           value="<?= e($search) ?>" aria-label="Search customers">

    <select class="input filters__select" name="company" aria-label="Company">
      <option value="All">All Companies</option>
      <?php foreach ($companies as $company): ?>
        <option value="<?= e($company) ?>"<?= $companyFilter === $company ? ' selected' : '' ?>><?= e($company) ?></option>
      <?php endforeach; ?>
    </select>

    <select class="input filters__select" name="status" aria-label="Status">
      <?php foreach ($tabStatuses as $s): ?>
        <option value="<?= e($s) ?>"<?= $statusFilter === $s ? ' selected' : '' ?>><?= $s === 'All' ? 'All Status' : e($s) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn--blue">Filter</button>
    <a href="customer.php" class="btn">Reset</a>
  </form>

  <button class="btn" type="button">Columns</button>

  <nav class="pager" aria-label="Pages">
    <?php if ($page > 1): ?>
      <a class="mini-btn" href="<?= e(listUrl(['page' => 1])) ?>" aria-label="First page"><svg class="icon"><use href="#i-first"/></svg></a>
      <a class="mini-btn" href="<?= e(listUrl(['page' => $page - 1])) ?>" aria-label="Previous page"><svg class="icon"><use href="#i-back"/></svg></a>
    <?php else: ?>
      <span class="mini-btn is-disabled"><svg class="icon"><use href="#i-first"/></svg></span>
      <span class="mini-btn is-disabled"><svg class="icon"><use href="#i-back"/></svg></span>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a class="mini-btn<?= $page === $i ? ' is-current' : '' ?>" href="<?= e(listUrl(['page' => $i])) ?>"
         <?= $page === $i ? 'aria-current="page"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>

    <?php if ($page < $totalPages): ?>
      <a class="mini-btn" href="<?= e(listUrl(['page' => $page + 1])) ?>" aria-label="Next page"><svg class="icon"><use href="#i-next"/></svg></a>
      <a class="mini-btn" href="<?= e(listUrl(['page' => $totalPages])) ?>" aria-label="Last page"><svg class="icon"><use href="#i-last"/></svg></a>
    <?php else: ?>
      <span class="mini-btn is-disabled"><svg class="icon"><use href="#i-next"/></svg></span>
      <span class="mini-btn is-disabled"><svg class="icon"><use href="#i-last"/></svg></span>
    <?php endif; ?>

    <span class="pager__info">(<?= $firstShown ?> - <?= $lastShown ?> of <?= count($filtered) ?>)</span>
  </nav>
</div>

<div class="table-card">
  <div class="table-scroll">
    <table class="table table--list">
      <thead>
        <tr>
          <th class="table__narrow"><input type="checkbox" aria-label="Select all" data-select-all></th>
          <th class="table__narrow"><span class="visually-hidden">Edit</span></th>
          <th>Name</th>
          <th>Company</th>
          <th>City</th>
          <th>Billing country</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Status</th>
          <th>User</th>
          <th>Date created</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($visible as $c): ?>
          <tr>
            <td><input type="checkbox" aria-label="Select <?= e($c['name']) ?>" data-row-select></td>
            <td>
              <a class="mini-btn" href="edit.php?id=<?= (int)$c['id'] ?>" aria-label="Edit <?= e($c['name']) ?>">
                <svg class="icon"><use href="#i-edit"/></svg>
              </a>
            </td>
            <td>
              <div class="customer-name">
                <span class="avatar avatar--soft avatar--sm"><?= e(initials($c['name'])) ?></span>
                <a href="detail.php?id=<?= (int)$c['id'] ?>"><strong><?= e($c['name']) ?></strong></a>
              </div>
            </td>
            <td><?= e($c['company']) ?></td>
            <td><?= e($c['billing_city']) ?></td>
            <td><?= e($c['billing_country']) ?></td>
            <td class="email"><?= e($c['email']) ?></td>
            <td><?= e($c['phone']) ?></td>
            <td><span class="status status-<?= e(strtolower($c['status'])) ?>"><?= e($c['status']) ?></span></td>
            <td><?= e($c['assigned']) ?></td>
            <td><?= e(fmt_date($c['created'])) ?></td>
            <td>
              <div class="row-actions">
                <a class="btn btn--sm" href="detail.php?id=<?= (int)$c['id'] ?>">View</a>
                <form method="POST" data-confirm="Delete this customer?">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="mini-btn" type="submit" aria-label="Delete <?= e($c['name']) ?>">
                    <svg class="icon"><use href="#i-x"/></svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if (empty($visible)): ?>
      <div class="empty-state">
        <h3>No customers found</h3>
        <p>Try changing your search or filters.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="modal-backdrop<?= isset($_GET['add']) ? ' show' : '' ?>" id="customerModal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="customerModalTitle">
    <h2 id="customerModalTitle">Add Customer</h2>
    <p>Enter the new customer's information.</p>

    <form method="POST">
      <input type="hidden" name="action" value="add">

      <label for="add-name">Full Name</label>
      <input class="input" id="add-name" type="text" name="name" required>

      <label for="add-company">Company</label>
      <input class="input" id="add-company" type="text" name="company" required>

      <label for="add-email">Email</label>
      <input class="input" id="add-email" type="email" name="email" required>

      <label for="add-phone">Phone</label>
      <input class="input" id="add-phone" type="text" name="phone" required>

      <label for="add-status">Status</label>
      <select class="input" id="add-status" name="status">
        <?php foreach ($statuses as $s): ?>
          <option value="<?= e($s) ?>"><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>

      <div class="modal-actions">
        <button type="button" class="btn" data-close-modal>Cancel</button>
        <button type="submit" class="btn btn--gold">Save Customer</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
