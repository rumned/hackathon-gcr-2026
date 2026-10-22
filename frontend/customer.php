<?php
require __DIR__ . '/includes/bootstrap.php';

$meta = api_get('/api/meta/accounts');
$statusOptions = $meta['fields']['status']['options'];
$typeOptions = $meta['fields']['account_type']['options'];
$statuses = array_column($statusOptions, 'value');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'New';

        if ($name === '' || $contact === '' || $email === '' || $phone === '') {
            flash('error', 'Customer not added: fill in every field.');
        } else {
            $result = api_send('POST', '/api/accounts', [
                'name' => $name,
                'contact_name' => $contact,
                'email_addresses' => [$email],
                'phone_office' => $phone,
                'status' => $status,
            ]);
            if ($result['ok']) {
                flash('success', "Customer {$name} added.");
            } else {
                flash('error', 'Customer not added: ' . ($result['errors'] ? implode(' ', array_map(
                    fn ($field, $message) => (str_starts_with($field, 'email') ? 'Email' : $field) . ': ' . $message . '.',
                    array_keys($result['errors']),
                    $result['errors'],
                )) : $result['message']));
            }
        }
    }

    if ($action === 'delete') {
        $result = api_send('DELETE', '/api/accounts/' . rawurlencode((string) ($_POST['id'] ?? '')));
        flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Customer deleted.' : 'Customer not deleted: ' . $result['message']);
    }

    redirect('customer.php');
}

$tabStatuses = array_merge(['All'], $statuses);

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'All';
$typeFilter = $_GET['type'] ?? 'All';

if (!in_array($statusFilter, $tabStatuses, true)) {
    $statusFilter = 'All';
}

// Search and type filter apply to every status tab; the tab adds the status filter
$baseQuery = ['q' => $search];
if ($typeFilter !== 'All') {
    $baseQuery['filter']['account_type'] = $typeFilter;
}

$counts = [];
foreach ($tabStatuses as $s) {
    $query = $baseQuery + ['per_page' => 1];
    if ($s !== 'All') {
        $query['filter']['status'] = $s;
    }
    $counts[$s] = api_get('/api/accounts', $query)['total'];
}

$pageSize = 5;
$listQuery = $baseQuery + ['per_page' => $pageSize, 'page' => max(1, (int) ($_GET['page'] ?? 1))];
if ($statusFilter !== 'All') {
    $listQuery['filter']['status'] = $statusFilter;
}
$list = api_get('/api/accounts', $listQuery);
$totalPages = max(1, (int) ceil($list['total'] / $pageSize));
if ($list['page'] > $totalPages) {
    $listQuery['page'] = $totalPages;
    $list = api_get('/api/accounts', $listQuery);
}
$page = $list['page'];
$visible = $list['records'];
$firstShown = $list['total'] ? ($page - 1) * $pageSize + 1 : 0;
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
    <a href="<?= e(listUrl(['status' => $s, 'page' => 1])) ?>"
       class="status-tab<?= $statusFilter === $s ? ' is-selected' : '' ?>">
      <?= e($s) ?> (<?= $counts[$s] ?>)
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

    <select class="input filters__select" name="type" aria-label="Type">
      <option value="All">All Types</option>
      <?php foreach ($typeOptions as $option): ?>
        <option value="<?= e($option['value']) ?>"<?= $typeFilter === $option['value'] ? ' selected' : '' ?>><?= e($option['label']) ?></option>
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

    <span class="pager__info">(<?= $firstShown ?> - <?= $lastShown ?> of <?= $list['total'] ?>)</span>
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
          <th>Contact</th>
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
              <a class="mini-btn" href="edit.php?id=<?= e($c['id']) ?>" aria-label="Edit <?= e($c['name']) ?>">
                <svg class="icon"><use href="#i-edit"/></svg>
              </a>
            </td>
            <td>
              <div class="customer-name">
                <span class="avatar avatar--soft avatar--sm"><?= e(initials($c['name'])) ?></span>
                <a href="detail.php?id=<?= e($c['id']) ?>"><strong><?= e($c['name']) ?></strong></a>
              </div>
            </td>
            <td><?= e($c['contact_name']) ?></td>
            <td><?= e($c['billing_address_city']) ?></td>
            <td><?= e($c['billing_address_country']) ?></td>
            <td class="email"><?= e($c['email1']) ?></td>
            <td><?= e($c['phone_office']) ?></td>
            <td><?php if ($c['status'] !== null): ?><span class="status status-<?= e(strtolower($c['status'])) ?>"><?= e($c['status']) ?></span><?php endif; ?></td>
            <td><?= e($c['assigned_user_name']) ?></td>
            <td><?= e(fmt_date($c['date_entered'])) ?></td>
            <td>
              <div class="row-actions">
                <a class="btn btn--sm" href="detail.php?id=<?= e($c['id']) ?>">View</a>
                <form method="POST" data-confirm="Delete this customer?">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= e($c['id']) ?>">
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

      <label for="add-name">Customer name</label>
      <input class="input" id="add-name" type="text" name="name" maxlength="150" required>

      <label for="add-contact">Contact name</label>
      <input class="input" id="add-contact" type="text" name="contact_name" maxlength="150" required>

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
