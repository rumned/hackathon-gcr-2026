<?php
// Placeholder record. Later, load it from your API (GET /api/contacts/{id}).
$c = [
    'name' => 'Sarah Johnson', 'company' => 'Acme Corp', 'title' => 'Senior Marketing Manager',
    'email' => 'sarah@acme.com', 'phone' => '+1 234 567 890', 'mobile' => '+1 234 567 891',
    'status' => 'New', 'source' => 'Website', 'assigned' => 'Alex Carter', 'industry' => 'Technology',
    'website' => 'www.acme.com', 'location' => 'New York, USA',
    'tags' => ['Enterprise', 'High Value'],
];

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function options(array $list, string $selected) {
    foreach ($list as $o) {
        echo '<option' . ($o === $selected ? ' selected' : '') . '>' . e($o) . '</option>';
    }
}

$statuses   = ['New', 'Contacted', 'Qualified', 'Proposal', 'Won'];
$sources    = ['Website', 'Referral', 'Email campaign', 'Event', 'Cold call'];
$users      = ['Alex Carter', 'Sally', 'Max'];
$industries = ['Technology', 'Retail', 'Finance', 'Healthcare', 'Education'];

$errors = [];
$v = $c;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($c) as $k) {
        if ($k !== 'tags') $v[$k] = trim($_POST[$k] ?? '');
    }
    $v['tags'] = array_values(array_filter(array_map('trim', $_POST['tags'] ?? [])));

    foreach (['name' => 'Full name', 'company' => 'Company', 'email' => 'Email', 'phone' => 'Phone', 'status' => 'Status'] as $k => $label) {
        if ($v[$k] === '') $errors[$k] = $label . ' is required.';
    }
    if (!isset($errors['email']) && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if (!$errors) {
        // Later: send $v to the API (PUT /api/contacts/{id}) before redirecting.
        header('Location: detail.php');
        exit;
    }
}

function field_error(array $errors, string $k) {
    if (isset($errors[$k])) echo '<p class="error" id="err-' . $k . '">' . e($errors[$k]) . '</p>';
}
function invalid(array $errors, string $k) {
    return isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit <?= e($c['name']) ?> | CRMPro</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <symbol id="i-home" viewBox="0 0 24 24"><path d="M3 11l9-8 9 8M5 10v10h14V10"/></symbol>
  <symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-6 7-6s7 2 7 6M16 4a4 4 0 010 8M22 21c0-3-2-5-4-5.5"/></symbol>
  <symbol id="i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></symbol>
  <symbol id="i-building" viewBox="0 0 24 24"><path d="M4 21V3h10v18M14 9h6v12M8 7h2M8 11h2M8 15h2M3 21h18"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 12l3 3 5-6"/></symbol>
  <symbol id="i-cal" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></symbol>
  <symbol id="i-chart" viewBox="0 0 24 24"><path d="M4 20V4M4 20h16M8 16v-5M12 16V8M16 16v-3"/></symbol>
  <symbol id="i-gear" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></symbol>
  <symbol id="i-bell" viewBox="0 0 24 24"><path d="M6 17V11a6 6 0 0112 0v6l2 2H4zM10 21h4"/></symbol>
  <symbol id="i-back" viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6"/></symbol>
  <symbol id="i-chev" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></symbol>
</svg>

<div class="app">
  <aside class="sidebar">
    <div class="logo">CRM<span>Pro</span></div>
    <nav class="nav" aria-label="Main">
      <a href="#"><svg class="icon"><use href="#i-home"/></svg>Dashboard</a>
      <a href="#" class="active" aria-current="page"><svg class="icon"><use href="#i-users"/></svg>Customers</a>
      <a href="#"><svg class="icon"><use href="#i-user"/></svg>Leads</a>
      <a href="#"><svg class="icon"><use href="#i-building"/></svg>Companies</a>
      <a href="#"><svg class="icon"><use href="#i-check"/></svg>Tasks</a>
      <a href="#"><svg class="icon"><use href="#i-cal"/></svg>Calendar</a>
      <a href="#"><svg class="icon"><use href="#i-chart"/></svg>Reports</a>
      <a href="#"><svg class="icon"><use href="#i-gear"/></svg>Settings</a>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <label class="search">
        <svg class="icon"><use href="#i-search"/></svg>
        <input type="search" placeholder="Search customers, companies, or deals..." aria-label="Search">
      </label>
      <div class="spacer"></div>
      <button class="icon-btn" aria-label="Alerts"><svg class="icon"><use href="#i-bell"/></svg></button>
      <div class="user">
        <span class="avatar sm">AC</span>
        <div><div class="name">Alex Carter</div><div class="role">Sales Manager</div></div>
        <svg class="icon"><use href="#i-chev"/></svg>
      </div>
    </header>

    <main class="content">
      <a class="back" href="detail.php"><svg class="icon"><use href="#i-back"/></svg>Back to Customer</a>
      <h1 class="page-title">Edit customer</h1>
      <p class="page-sub">Update customer information and details.</p>

      <form method="post" novalidate>
        <div class="grid">

          <div class="stack">
            <section class="card">
              <h2>Basic information</h2>
              <div class="form-fields">
                <div class="form-field">
                  <label for="name">Full name <span class="req">*</span></label>
                  <input id="name" name="name" value="<?= e($v['name']) ?>" required<?= invalid($errors, 'name') ?>>
                  <?php field_error($errors, 'name'); ?>
                </div>
                <div class="form-field">
                  <label for="company">Company <span class="req">*</span></label>
                  <input id="company" name="company" value="<?= e($v['company']) ?>" required<?= invalid($errors, 'company') ?>>
                  <?php field_error($errors, 'company'); ?>
                </div>
                <div class="form-field">
                  <label for="title">Job title</label>
                  <input id="title" name="title" value="<?= e($v['title']) ?>">
                </div>
              </div>
            </section>

            <section class="card">
              <h2>Contact information</h2>
              <div class="form-fields">
                <div class="form-field">
                  <label for="email">Email <span class="req">*</span></label>
                  <input id="email" name="email" type="email" value="<?= e($v['email']) ?>" required<?= invalid($errors, 'email') ?>>
                  <?php field_error($errors, 'email'); ?>
                </div>
                <div class="form-field">
                  <label for="phone">Phone <span class="req">*</span></label>
                  <input id="phone" name="phone" type="tel" value="<?= e($v['phone']) ?>" required<?= invalid($errors, 'phone') ?>>
                  <?php field_error($errors, 'phone'); ?>
                </div>
                <div class="form-field">
                  <label for="mobile">Mobile</label>
                  <input id="mobile" name="mobile" type="tel" value="<?= e($v['mobile']) ?>">
                </div>
              </div>
            </section>
          </div>

          <section class="card">
            <h2>Additional details</h2>
            <div class="form-fields">
              <div class="form-field">
                <label for="status">Status <span class="req">*</span></label>
                <select id="status" name="status"<?= invalid($errors, 'status') ?>><?php options($statuses, $v['status']); ?></select>
                <?php field_error($errors, 'status'); ?>
              </div>
              <div class="form-field">
                <label for="source">Source</label>
                <select id="source" name="source"><?php options($sources, $v['source']); ?></select>
              </div>
              <div class="form-field">
                <label for="assigned">Assigned to</label>
                <select id="assigned" name="assigned"><?php options($users, $v['assigned']); ?></select>
              </div>
              <div class="form-field">
                <label for="industry">Industry</label>
                <select id="industry" name="industry"><?php options($industries, $v['industry']); ?></select>
              </div>
              <div class="form-field">
                <label for="website">Website</label>
                <input id="website" name="website" value="<?= e($v['website']) ?>">
              </div>
              <div class="form-field">
                <label for="location">Location</label>
                <input id="location" name="location" value="<?= e($v['location']) ?>">
              </div>
              <div class="form-field">
                <label for="tag-input">Tags</label>
                <div class="tags" id="tags">
                  <?php foreach ($v['tags'] as $i => $t): ?>
                    <span class="tag<?= $i % 2 ? ' gold' : '' ?>"><?= e($t) ?>
                      <button type="button" class="tag-x" aria-label="Remove <?= e($t) ?>">&times;</button>
                      <input type="hidden" name="tags[]" value="<?= e($t) ?>">
                    </span>
                  <?php endforeach; ?>
                  <input id="tag-input" class="tag-input" type="text" placeholder="+ Add tag" maxlength="30">
                </div>
              </div>
            </div>
          </section>
        </div>

        <div class="form-actions">
          <a class="btn" href="detail.php">Cancel</a>
          <button class="btn primary" type="submit">Save changes</button>
        </div>
      </form>
    </main>
  </div>
</div>

<script>
  // Tags: press Enter to add, click x to remove
  var tagBox = document.getElementById('tags');
  var tagInput = document.getElementById('tag-input');

  function addTag(text) {
    var count = tagBox.querySelectorAll('.tag').length;
    var chip = document.createElement('span');
    chip.className = 'tag' + (count % 2 ? ' gold' : '');
    chip.append(text + ' ');
    var x = document.createElement('button');
    x.type = 'button'; x.className = 'tag-x';
    x.setAttribute('aria-label', 'Remove ' + text);
    x.innerHTML = '&times;';
    var hidden = document.createElement('input');
    hidden.type = 'hidden'; hidden.name = 'tags[]'; hidden.value = text;
    chip.append(x, hidden);
    tagBox.insertBefore(chip, tagInput);
  }

  tagInput.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault(); // Enter should add a tag, not submit the form
    var text = tagInput.value.trim();
    if (text) addTag(text);
    tagInput.value = '';
  });

  tagBox.addEventListener('click', function (e) {
    var x = e.target.closest('.tag-x');
    if (x) { x.parentElement.remove(); tagInput.focus(); }
  });
</script>
</body>
</html>
