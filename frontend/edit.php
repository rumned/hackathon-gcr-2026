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

$meta = api_get('/api/meta/accounts')['fields'];
$userOptions = array_map(
    fn ($u) => ['value' => $u['id'], 'label' => $u['full_name'] !== '' ? $u['full_name'] : $u['user_name']],
    api_get('/api/users', ['status' => 'Active'])['records'],
);
$accountOptions = array_map(
    fn ($a) => ['value' => $a['id'], 'label' => $a['name']],
    array_filter(api_get('/api/accounts', ['per_page' => 100, 'sort' => 'name', 'dir' => 'asc'])['records'], fn ($a) => $a['id'] !== $c['id']),
);
$campaignOptions = array_map(
    fn ($a) => ['value' => $a['id'], 'label' => $a['name']],
    api_get('/api/campaigns', ['per_page' => 100, 'sort' => 'name', 'dir' => 'asc'])['records'],
);

// Fields on each tab. Fields of a group the Type or Stage hides are disabled, so they are not sent and keep their values.
$overviewKeys = ['name', 'account_type', 'customer_stage', 'contact_name', 'contact_title', 'phone_office', 'phone_mobile', 'website', 'phone_fax',
    'billing_address_street', 'billing_address_city', 'billing_address_state', 'billing_address_postalcode', 'billing_address_country',
    'shipping_address_street', 'shipping_address_city', 'shipping_address_state', 'shipping_address_postalcode', 'shipping_address_country',
    'description', 'assigned_user_id'];
$moreKeys = ['status', 'lead_source', 'industry', 'campaign_id', 'annual_revenue', 'employees', 'parent_id', 'tags'];

$errors = [];
$v = $c;
$v['email'] = $c['email1'];
$v['ship_same'] = shipping_same_as_billing($c);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = [];
    foreach (array_merge($overviewKeys, $moreKeys) as $k) {
        if (array_key_exists($k, $_POST) && $k !== 'tags') {
            $body[$k] = trim((string) $_POST[$k]);
            $v[$k] = $body[$k];
        }
    }
    if (isset($_POST['tags_sent'])) {
        $body['tags'] = implode(', ', array_values(array_filter(array_map('trim', $_POST['tags'] ?? []))));
        $v['tags'] = $body['tags'];
    }
    if (array_key_exists('email', $_POST)) {
        $v['email'] = trim((string) $_POST['email']);
        // the edited address stays primary; the customer's other addresses are kept
        $others = array_values(array_filter($c['email_addresses'], fn ($a) => !$a['primary_address']));
        $body['email_addresses'] = array_merge($v['email'] !== '' ? [$v['email']] : [], array_column($others, 'email_address'));
    }
    if (array_key_exists('ship_same', $_POST) || array_key_exists('shipping_address_street', $_POST)) {
        $v['ship_same'] = isset($_POST['ship_same']);
    }
    if ($v['ship_same'] && array_key_exists('shipping_address_street', $_POST)) {
        foreach (['street', 'city', 'state', 'postalcode', 'country'] as $part) {
            $body['shipping_address_' . $part] = $body['billing_address_' . $part] ?? $c['billing_address_' . $part];
        }
    }

    foreach (['name' => 'Name', 'email' => 'Email', 'phone_office' => 'Office phone'] as $k => $label) {
        if (($k === 'email' ? array_key_exists('email', $_POST) : array_key_exists($k, $body)) && trim((string) $v[$k]) === '') {
            $errors[$k] = $label . ' is required.';
        }
    }
    if (!isset($errors['email']) && array_key_exists('email', $_POST) && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (!$errors) {
        $result = api_send('PUT', '/api/accounts/' . rawurlencode($c['id']), $body);
        if ($result['ok']) {
            flash('success', 'Customer saved.');
            redirect('detail.php?id=' . rawurlencode($c['id']));
        }
        foreach ($result['errors'] ?: ['name' => $result['message']] as $field => $message) {
            $errors[str_starts_with($field, 'email_addresses') ? 'email' : $field] = $message . '.';
        }
    }
}

$type = ($v['account_type'] ?? '') !== '' ? $v['account_type'] : null;
$stage = ($v['customer_stage'] ?? '') !== '' ? $v['customer_stage'] : null;
$tab = ($errors && !array_diff(array_keys($errors), $moreKeys)) || (!$errors && ($_GET['tab'] ?? '') === 'more') ? 'more' : 'overview';

function field_error(array $errors, string $k): void
{
    if (isset($errors[$k])) {
        echo '<p class="error" id="err-' . e($k) . '">' . e($errors[$k]) . '</p>';
    }
}
function invalid(array $errors, string $k): string
{
    return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . e($k) . '"' : '';
}
/** Attributes for a field group the current Type or Stage hides. */
function group_attrs(?string $type, ?string $stage, string $group): string
{
    return ' data-group="' . e($group) . '"' . (type_shows($type, $stage, $group) ? '' : ' hidden disabled');
}

$pageTitle = 'Edit ' . $c['name'];
$activeModule = 'customers';
require __DIR__ . '/includes/layout-top.php';
?>

<form method="post" novalidate data-type-rules="<?= e(type_rules_json()) ?>">
  <div class="page-head">
    <p class="breadcrumb">
      <a href="customer.php">Customers</a> /
      <a href="detail.php?id=<?= e($c['id']) ?>"><?= e($c['name']) ?></a> / Edit
    </p>
    <div>
      <h1 class="page-title">Edit customer</h1>
      <p class="page-sub">Update customer information and details. The fields shown depend on the Type and Stage.</p>
    </div>
    <div class="form-actions">
      <button class="btn btn--gold" type="submit">Save changes</button>
      <a class="btn" href="detail.php?id=<?= e($c['id']) ?>">Cancel</a>
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
        <label class="form-field__label" for="name">Name <span class="req">*</span></label>
        <input class="input" id="name" name="name" maxlength="150" value="<?= e($v['name']) ?>" required<?= invalid($errors, 'name') ?>>
        <?php field_error($errors, 'name'); ?>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="account_type">Type</label>
        <select class="input" id="account_type" name="account_type" data-kind-select<?= invalid($errors, 'account_type') ?>><?= options_html($meta['account_type']['options'], $v['account_type']) ?></select>
        <?php field_error($errors, 'account_type'); ?>
      </div>
      <div class="form-field">
        <label class="form-field__label" for="customer_stage">Stage</label>
        <select class="input" id="customer_stage" name="customer_stage" data-stage-select<?= invalid($errors, 'customer_stage') ?>><?= options_html($meta['customer_stage']['options'], $v['customer_stage']) ?></select>
        <?php field_error($errors, 'customer_stage'); ?>
      </div>

      <fieldset class="form-field"<?= group_attrs($type, $stage, 'contact_person') ?>>
        <label class="form-field__label" for="contact_name">Contact name</label>
        <input class="input" id="contact_name" name="contact_name" maxlength="150" value="<?= e($v['contact_name']) ?>"<?= invalid($errors, 'contact_name') ?>>
        <?php field_error($errors, 'contact_name'); ?>
      </fieldset>
      <fieldset class="form-field"<?= group_attrs($type, $stage, 'contact_person') ?>>
        <label class="form-field__label" for="contact_title">Job title</label>
        <input class="input" id="contact_title" name="contact_title" maxlength="100" value="<?= e($v['contact_title']) ?>"<?= invalid($errors, 'contact_title') ?>>
        <?php field_error($errors, 'contact_title'); ?>
      </fieldset>
      <fieldset class="form-field">
        <label class="form-field__label" for="email">Email <span class="req">*</span></label>
        <div class="input-group">
          <input class="input" id="email" name="email" type="email" value="<?= e($v['email']) ?>" required<?= invalid($errors, 'email') ?>>
          <button class="btn" type="button">+ Add</button>
        </div>
        <?php field_error($errors, 'email'); ?>
      </fieldset>
      <fieldset class="form-field">
        <label class="form-field__label" for="phone_office">Office phone <span class="req">*</span></label>
        <input class="input" id="phone_office" name="phone_office" type="tel" maxlength="100" value="<?= e($v['phone_office']) ?>" required<?= invalid($errors, 'phone_office') ?>>
        <?php field_error($errors, 'phone_office'); ?>
      </fieldset>
      <fieldset class="form-field">
        <label class="form-field__label" for="phone_mobile">Mobile</label>
        <input class="input" id="phone_mobile" name="phone_mobile" type="tel" maxlength="100" value="<?= e($v['phone_mobile']) ?>"<?= invalid($errors, 'phone_mobile') ?>>
        <?php field_error($errors, 'phone_mobile'); ?>
      </fieldset>
      <fieldset class="form-field"<?= group_attrs($type, $stage, 'website') ?>>
        <label class="form-field__label" for="website">Website</label>
        <input class="input" id="website" name="website" maxlength="255" value="<?= e($v['website']) ?>"<?= invalid($errors, 'website') ?>>
        <?php field_error($errors, 'website'); ?>
      </fieldset>
      <fieldset class="form-field"<?= group_attrs($type, $stage, 'fax') ?>>
        <label class="form-field__label" for="phone_fax">Fax</label>
        <input class="input" id="phone_fax" name="phone_fax" type="tel" maxlength="100" value="<?= e($v['phone_fax']) ?>"<?= invalid($errors, 'phone_fax') ?>>
        <?php field_error($errors, 'phone_fax'); ?>
      </fieldset>

      <fieldset class="form-field address"<?= group_attrs($type, $stage, 'billing') ?>>
        <legend class="form-field__label">Billing address</legend>
        <textarea class="input input--textarea" name="billing_address_street" maxlength="150" aria-label="Billing street"><?= e($v['billing_address_street']) ?></textarea>
        <div class="address__row">
          <input class="input" name="billing_address_city" placeholder="City" aria-label="Billing city" value="<?= e($v['billing_address_city']) ?>">
          <input class="input" name="billing_address_state" placeholder="State / region" aria-label="Billing state" value="<?= e($v['billing_address_state']) ?>">
        </div>
        <div class="address__row">
          <input class="input" name="billing_address_postalcode" placeholder="Postal code" aria-label="Billing postal code" value="<?= e($v['billing_address_postalcode']) ?>">
          <input class="input" name="billing_address_country" placeholder="Country" aria-label="Billing country" value="<?= e($v['billing_address_country']) ?>">
        </div>
      </fieldset>

      <fieldset class="form-field address"<?= group_attrs($type, $stage, 'shipping') ?>>
        <legend class="form-field__label">Shipping address</legend>
        <label class="checkbox-label"><input type="checkbox" name="ship_same"<?= $v['ship_same'] ? ' checked' : '' ?>> Copy address from billing</label>
        <textarea class="input input--textarea" name="shipping_address_street" maxlength="150" aria-label="Shipping street"><?= e($v['shipping_address_street']) ?></textarea>
        <div class="address__row">
          <input class="input" name="shipping_address_city" placeholder="City" aria-label="Shipping city" value="<?= e($v['shipping_address_city']) ?>">
          <input class="input" name="shipping_address_state" placeholder="State / region" aria-label="Shipping state" value="<?= e($v['shipping_address_state']) ?>">
        </div>
        <div class="address__row">
          <input class="input" name="shipping_address_postalcode" placeholder="Postal code" aria-label="Shipping postal code" value="<?= e($v['shipping_address_postalcode']) ?>">
          <input class="input" name="shipping_address_country" placeholder="Country" aria-label="Shipping country" value="<?= e($v['shipping_address_country']) ?>">
        </div>
      </fieldset>

      <fieldset class="form-field form-field--wide">
        <label class="form-field__label" for="description">Description</label>
        <textarea class="input input--textarea" id="description" name="description"><?= e($v['description']) ?></textarea>
      </fieldset>
      <fieldset class="form-field">
        <label class="form-field__label" for="assigned_user_id">Assigned to</label>
        <select class="input" id="assigned_user_id" name="assigned_user_id"<?= invalid($errors, 'assigned_user_id') ?>><?= options_html($userOptions, $v['assigned_user_id'], false) ?></select>
        <?php field_error($errors, 'assigned_user_id'); ?>
      </fieldset>
    </div>

    <!-- More information -->
    <div class="form-grid" role="tabpanel" data-tabpanel="more"<?= $tab === 'more' ? '' : ' hidden' ?>>
      <fieldset class="form-field"<?= group_attrs($type, $stage, 'status') ?>>
        <label class="form-field__label" for="status">Status</label>
        <select class="input" id="status" name="status"<?= invalid($errors, 'status') ?>><?= options_html($meta['status']['options'], $v['status']) ?></select>
        <?php field_error($errors, 'status'); ?>
      </fieldset>
      <fieldset class="form-field"<?= group_attrs($type, $stage, 'lead_source') ?>>
        <label class="form-field__label" for="lead_source">Lead source</label>
        <select class="input" id="lead_source" name="lead_source"<?= invalid($errors, 'lead_source') ?>><?= options_html($meta['lead_source']['options'], $v['lead_source']) ?></select>
        <?php field_error($errors, 'lead_source'); ?>
      </fieldset>
      <fieldset class="form-field"<?= group_attrs($type, $stage, 'industry') ?>>
        <label class="form-field__label" for="industry">Industry</label>
        <select class="input" id="industry" name="industry"<?= invalid($errors, 'industry') ?>><?= options_html($meta['industry']['options'], $v['industry']) ?></select>
        <?php field_error($errors, 'industry'); ?>
      </fieldset>
      <fieldset class="form-field"<?= group_attrs($type, $stage, 'campaign') ?>>
        <label class="form-field__label" for="campaign_id">Campaign</label>
        <select class="input" id="campaign_id" name="campaign_id"<?= invalid($errors, 'campaign_id') ?>><?= options_html($campaignOptions, $v['campaign_id']) ?></select>
        <?php field_error($errors, 'campaign_id'); ?>
      </fieldset>
      <fieldset class="form-field"<?= group_attrs($type, $stage, 'company_size') ?>>
        <label class="form-field__label" for="annual_revenue">Annual revenue</label>
        <input class="input" id="annual_revenue" name="annual_revenue" maxlength="100" value="<?= e($v['annual_revenue']) ?>"<?= invalid($errors, 'annual_revenue') ?>>
        <?php field_error($errors, 'annual_revenue'); ?>
      </fieldset>
      <fieldset class="form-field"<?= group_attrs($type, $stage, 'company_size') ?>>
        <label class="form-field__label" for="employees">Employees</label>
        <input class="input" id="employees" name="employees" maxlength="10" value="<?= e($v['employees']) ?>"<?= invalid($errors, 'employees') ?>>
        <?php field_error($errors, 'employees'); ?>
      </fieldset>
      <fieldset class="form-field"<?= group_attrs($type, $stage, 'member_of') ?>>
        <label class="form-field__label" for="parent_id">Member of</label>
        <select class="input" id="parent_id" name="parent_id"<?= invalid($errors, 'parent_id') ?>><?= options_html($accountOptions, $v['parent_id']) ?></select>
        <?php field_error($errors, 'parent_id'); ?>
      </fieldset>
      <fieldset class="form-field form-field--wide">
        <label class="form-field__label" for="tag-input">Tags</label>
        <input type="hidden" name="tags_sent" value="1">
        <div class="tags" id="tags">
          <?php foreach (tag_list($v['tags']) as $i => $t): ?>
            <span class="tag<?= $i % 2 ? ' tag--gold' : '' ?>"><?= e($t) ?>
              <button type="button" class="tag-x" aria-label="Remove <?= e($t) ?>">&times;</button>
              <input type="hidden" name="tags[]" value="<?= e($t) ?>">
            </span>
          <?php endforeach; ?>
          <input id="tag-input" class="input tag-input" type="text" placeholder="+ Add tag" maxlength="30">
        </div>
        <?php field_error($errors, 'tags'); ?>
      </fieldset>
    </div>
  </div>

  <div class="form-actions form-actions--bottom">
    <button class="btn btn--gold" type="submit">Save changes</button>
    <a class="btn" href="detail.php?id=<?= e($c['id']) ?>">Cancel</a>
  </div>
</form>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
