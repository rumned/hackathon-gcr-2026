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

function options(array $list, string $selected) {
    foreach ($list as $o) {
        echo '<option' . ($o === $selected ? ' selected' : '') . '>' . e($o) . '</option>';
    }
}

// Text fields on each tab
$overviewKeys = ['name', 'company', 'title', 'email', 'phone', 'mobile', 'website', 'fax',
    'billing_street', 'billing_city', 'billing_state', 'billing_postal', 'billing_country',
    'shipping_street', 'shipping_city', 'shipping_state', 'shipping_postal', 'shipping_country',
    'description', 'assigned'];
$moreKeys = ['status', 'source', 'type', 'industry', 'revenue', 'employees', 'member_of', 'campaign'];

$errors = [];
$v = $c;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_merge($overviewKeys, $moreKeys) as $k) {
        $v[$k] = trim($_POST[$k] ?? '');
    }
    $v['ship_same'] = isset($_POST['ship_same']);
    $v['tags'] = array_values(array_filter(array_map('trim', $_POST['tags'] ?? [])));

    foreach (['name' => 'Full name', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Office phone', 'status' => 'Status'] as $k => $label) {
        if ($v[$k] === '') $errors[$k] = $label . ' is required.';
    }
    if (!isset($errors['email']) && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (!$errors) {
        // Later: send $v to the API (PUT /api/contacts/{id}) before redirecting.
        header('Location: detail.php?id=' . $c['id']);
        exit;
    }
}

// Open the More information tab when all errors are on that tab
$tab = ($errors && !array_diff(array_keys($errors), $moreKeys)) ? 'more' : 'overview';

function field_error(array $errors, string $k) {
    if (isset($errors[$k])) echo '<p class="error" id="err-' . $k . '">' . e($errors[$k]) . '</p>';
}
function invalid(array $errors, string $k) {
    return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : '';
}

$pageTitle = 'Edit ' . $c['name'];
$activeModule = 'customers';
require __DIR__ . '/includes/layout-top.php';
?>

<form method="post" novalidate>
  <div class="page-head">
    <p class="breadcrumb">
      <a href="customer.php">Customers</a> /
      <a href="detail.php?id=<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a> / Edit
    </p>
    <div>
      <h1 class="page-title">Edit customer</h1>
      <p class="page-sub">Update customer information and details.</p>
    </div>
    <div class="form-actions">
      <button class="btn btn--gold" type="submit">Save changes</button>
      <a class="btn" href="detail.php?id=<?= (int)$c['id'] ?>">Cancel</a>
    </div>
  </div>

  <div data-tabs>
    <div class="tabs" role="tablist">
      <button class="tab" role="tab" type="button" aria-selected="<?= $tab === 'overview' ? 'true' : 'false' ?>" data-tab="overview">Overview</button>
      <button class="tab" role="tab" type="button" aria-selected="<?= $tab === 'more' ? 'true' : 'false' ?>" data-tab="more">More information</button>
    </div>

    <!-- Overview -->
    <div class="form-grid" role="tabpanel" data-tabpanel="overview"<?= $tab === 'overview' ? '' : ' hidden' ?>>
      <div class="form-field">
        <label class="form-field__label" for="name">Full name <span class="req">*</span></label>
        <input class="input" id="name" name="name" value="<?= e($v['name']) ?>" required<?= invalid($errors, 'name') ?>>
        <?php field_error($errors, 'name'); ?>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="company">Company <span class="req">*</span></label>
        <input class="input" id="company" name="company" value="<?= e($v['company']) ?>" required<?= invalid($errors, 'company') ?>>
        <?php field_error($errors, 'company'); ?>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="title">Job title</label>
        <input class="input" id="title" name="title" value="<?= e($v['title']) ?>">
      </div>
      <div class="form-field">
        <label class="form-field__label" for="phone">Office phone <span class="req">*</span></label>
        <input class="input" id="phone" name="phone" type="tel" value="<?= e($v['phone']) ?>" required<?= invalid($errors, 'phone') ?>>
        <?php field_error($errors, 'phone'); ?>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="email">Email <span class="req">*</span></label>
        <div class="input-group">
          <input class="input" id="email" name="email" type="email" value="<?= e($v['email']) ?>" required<?= invalid($errors, 'email') ?>>
          <button class="btn" type="button">+ Add</button>
        </div>
        <?php field_error($errors, 'email'); ?>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="mobile">Mobile</label>
        <input class="input" id="mobile" name="mobile" type="tel" value="<?= e($v['mobile']) ?>">
      </div>
      <div class="form-field">
        <label class="form-field__label" for="website">Website</label>
        <input class="input" id="website" name="website" value="<?= e($v['website']) ?>">
      </div>
      <div class="form-field">
        <label class="form-field__label" for="fax">Fax</label>
        <input class="input" id="fax" name="fax" type="tel" value="<?= e($v['fax']) ?>">
      </div>

      <fieldset class="form-field address">
        <legend class="form-field__label">Billing address</legend>
        <textarea class="input input--textarea" name="billing_street" aria-label="Billing street"><?= e($v['billing_street']) ?></textarea>
        <div class="address__row">
          <input class="input" name="billing_city" placeholder="City" aria-label="Billing city" value="<?= e($v['billing_city']) ?>">
          <input class="input" name="billing_state" placeholder="State / region" aria-label="Billing state" value="<?= e($v['billing_state']) ?>">
        </div>
        <div class="address__row">
          <input class="input" name="billing_postal" placeholder="Postal code" aria-label="Billing postal code" value="<?= e($v['billing_postal']) ?>">
          <input class="input" name="billing_country" placeholder="Country" aria-label="Billing country" value="<?= e($v['billing_country']) ?>">
        </div>
      </fieldset>

      <fieldset class="form-field address">
        <legend class="form-field__label">Shipping address</legend>
        <label class="checkbox-label"><input type="checkbox" name="ship_same"<?= $v['ship_same'] ? ' checked' : '' ?>> Copy address from billing</label>
        <textarea class="input input--textarea" name="shipping_street" aria-label="Shipping street"><?= e($v['shipping_street']) ?></textarea>
        <div class="address__row">
          <input class="input" name="shipping_city" placeholder="City" aria-label="Shipping city" value="<?= e($v['shipping_city']) ?>">
          <input class="input" name="shipping_state" placeholder="State / region" aria-label="Shipping state" value="<?= e($v['shipping_state']) ?>">
        </div>
        <div class="address__row">
          <input class="input" name="shipping_postal" placeholder="Postal code" aria-label="Shipping postal code" value="<?= e($v['shipping_postal']) ?>">
          <input class="input" name="shipping_country" placeholder="Country" aria-label="Shipping country" value="<?= e($v['shipping_country']) ?>">
        </div>
      </fieldset>

      <div class="form-field form-field--wide">
        <label class="form-field__label" for="description">Description</label>
        <textarea class="input input--textarea" id="description" name="description"><?= e($v['description']) ?></textarea>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="assigned">Assigned to</label>
        <select class="input" id="assigned" name="assigned"><?php options($users, $v['assigned']); ?></select>
      </div>
    </div>

    <!-- More information -->
    <div class="form-grid" role="tabpanel" data-tabpanel="more"<?= $tab === 'more' ? '' : ' hidden' ?>>
      <div class="form-field">
        <label class="form-field__label" for="status">Status <span class="req">*</span></label>
        <select class="input" id="status" name="status"<?= invalid($errors, 'status') ?>><?php options($statuses, $v['status']); ?></select>
        <?php field_error($errors, 'status'); ?>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="source">Source</label>
        <select class="input" id="source" name="source"><?php options($sources, $v['source']); ?></select>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="type">Type</label>
        <select class="input" id="type" name="type"><?php options($types, $v['type']); ?></select>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="industry">Industry</label>
        <select class="input" id="industry" name="industry"><?php options($industries, $v['industry']); ?></select>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="revenue">Annual revenue</label>
        <input class="input" id="revenue" name="revenue" value="<?= e($v['revenue']) ?>">
      </div>
      <div class="form-field">
        <label class="form-field__label" for="employees">Employees</label>
        <input class="input" id="employees" name="employees" type="number" value="<?= e($v['employees']) ?>">
      </div>
      <div class="form-field">
        <label class="form-field__label" for="member_of">Member of</label>
        <div class="input-group">
          <input class="input" id="member_of" name="member_of" value="<?= e($v['member_of']) ?>">
          <button class="btn" type="button">Select</button>
        </div>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="campaign">Campaign</label>
        <div class="input-group">
          <input class="input" id="campaign" name="campaign" value="<?= e($v['campaign']) ?>">
          <button class="btn" type="button">Select</button>
        </div>
      </div>
      <div class="form-field form-field--wide">
        <label class="form-field__label" for="tag-input">Tags</label>
        <div class="tags" id="tags">
          <?php foreach ($v['tags'] as $i => $t): ?>
            <span class="tag<?= $i % 2 ? ' tag--gold' : '' ?>"><?= e($t) ?>
              <button type="button" class="tag-x" aria-label="Remove <?= e($t) ?>">&times;</button>
              <input type="hidden" name="tags[]" value="<?= e($t) ?>">
            </span>
          <?php endforeach; ?>
          <input id="tag-input" class="input tag-input" type="text" placeholder="+ Add tag" maxlength="30">
        </div>
      </div>
    </div>
  </div>

  <div class="form-actions form-actions--bottom">
    <button class="btn btn--gold" type="submit">Save changes</button>
    <a class="btn" href="detail.php?id=<?= (int)$c['id'] ?>">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
