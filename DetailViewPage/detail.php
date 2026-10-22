<?php
// Placeholder data. Later, replace this with a call to your API (e.g. GET /api/contacts/{id}).
$c = [
    'name'     => 'Sarah Johnson',
    'initials' => 'SJ',
    'status'   => 'New',
    'company'  => 'Acme Corp',
    'title'    => 'Senior Marketing Manager',
    'email'    => 'sarah@acme.com',
    'phone'    => '+1 234 567 890',
    'mobile'   => '+1 234 567 891',
    'location' => 'New York, USA',
    'industry' => 'Technology',
    'website'  => 'www.acme.com',
    'address'  => '123 Business Ave, New York, NY 10001',
    'source'   => 'Website',
    'assigned' => 'Alex Carter',
    'created'  => 'Mar 15, 2024',
    'last_contact' => 'Mar 20, 2024',
    'tags'     => ['Enterprise', 'High Value'],
];

$activities = [
    ['Sarah viewed the proposal', '2 minutes ago'],
    ['Alex called Sarah: discussed pricing', 'Yesterday, 3:15 PM'],
    ['Email sent: product overview', 'Mar 18, 2024'],
    ['Lead created from website form', 'Mar 15, 2024'],
];
$deals = [
    ['Acme CRM rollout', 'Proposal', '$24,000', 'Apr 30, 2024'],
    ['Marketing automation add-on', 'Qualified', '$8,500', 'May 15, 2024'],
];
$tasks = [
    ['Call Sarah Johnson', 'Today, 10:00 AM', false],
    ['Send contract to Acme Corp', 'Tomorrow, 11:00 AM', false],
    ['Share product demo recording', 'Mar 19, 2024', true],
];

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($c["name"]) ?> | CRMPro</title>
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
  <symbol id="i-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></symbol>
  <symbol id="i-phone" viewBox="0 0 24 24"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/></symbol>
  <symbol id="i-mobile" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></symbol>
  <symbol id="i-pin" viewBox="0 0 24 24"><path d="M12 22s7-6 7-12a7 7 0 10-14 0c0 6 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/></symbol>
  <symbol id="i-brief" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V4h6v3M3 13h18"/></symbol>
  <symbol id="i-globe" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></symbol>
  <symbol id="i-chev" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></symbol>
</svg>

<div class="app">

  <!-- Sidebar -->
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
    <!-- Top bar -->
    <header class="topbar">
      <label class="search">
        <svg class="icon"><use href="#i-search"/></svg>
        <input type="search" placeholder="Search customers, companies, or deals..." aria-label="Search">
      </label>
      <div class="spacer"></div>
      <button class="icon-btn" aria-label="Alerts"><svg class="icon"><use href="#i-bell"/></svg></button>
      <div class="user">
        <span class="avatar sm">AC</span>
        <div>
          <div class="name">Alex Carter</div>
          <div class="role">Sales Manager</div>
        </div>
        <svg class="icon"><use href="#i-chev"/></svg>
      </div>
    </header>

    <main class="content">
      <a class="back" href="customers.php"><svg class="icon"><use href="#i-back"/></svg>Back to Customers</a>

      <!-- Profile header -->
      <section class="profile">
        <span class="avatar lg" aria-hidden="true"><?= e($c["initials"]) ?></span>
        <div class="profile-info">
          <h1><?= e($c["name"]) ?></h1><span class="badge"><?= e($c["status"]) ?></span>
          <div class="profile-meta">
            <span><svg class="icon"><use href="#i-building"/></svg><?= e($c["company"]) ?></span>
            <span><svg class="icon"><use href="#i-brief"/></svg><?= e($c["title"]) ?></span>
          </div>
        </div>
        <div class="actions">
          <a class="btn primary" href="edit.php">Edit</a>
          <div class="dropdown">
            <button class="btn" id="more-btn" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="more-menu">More <svg class="icon"><use href="#i-chev"/></svg></button>
            <ul class="menu" id="more-menu" role="menu" hidden>
              <li role="none"><button role="menuitem" type="button" data-action="duplicate">Duplicate</button></li>
              <li role="none"><button role="menuitem" type="button" data-action="find-duplicates">Find duplicates</button></li>
              <li role="none"><button role="menuitem" type="button" data-action="changelog">View change log</button></li>
              <li role="none"><button role="menuitem" type="button" data-action="delete" class="danger">Delete</button></li>
            </ul>
          </div>
        </div>
      </section>

      <!-- Tabs -->
      <div class="tabs" role="tablist">
        <button role="tab" id="t-overview" data-tab="overview" aria-controls="tab-overview" aria-selected="true">Overview</button>
        <button role="tab" id="t-activity" data-tab="activity" aria-controls="tab-activity" aria-selected="false">Activity</button>
        <button role="tab" id="t-deals" data-tab="deals" aria-controls="tab-deals" aria-selected="false">Deals</button>
        <button role="tab" id="t-tasks" data-tab="tasks" aria-controls="tab-tasks" aria-selected="false">Tasks</button>
        <button role="tab" id="t-notes" data-tab="notes" aria-controls="tab-notes" aria-selected="false">Notes</button>
      </div>

      <!-- Overview -->
      <div class="panel" id="tab-overview" role="tabpanel" aria-labelledby="t-overview">
      <div class="grid">
        <div class="stack">
          <section class="card">
            <h2>Contact information</h2>
            <dl class="fields">
              <div class="field"><svg class="icon"><use href="#i-mail"/></svg><div><dt>Email</dt><dd><a href="mailto:<?= e($c["email"]) ?>"><?= e($c["email"]) ?></a></dd></div></div>
              <div class="field"><svg class="icon"><use href="#i-phone"/></svg><div><dt>Phone</dt><dd><?= e($c["phone"]) ?></dd></div></div>
              <div class="field"><svg class="icon"><use href="#i-mobile"/></svg><div><dt>Mobile</dt><dd><?= e($c["mobile"]) ?></dd></div></div>
              <div class="field"><svg class="icon"><use href="#i-pin"/></svg><div><dt>Location</dt><dd><?= e($c["location"]) ?></dd></div></div>
            </dl>
          </section>

          <section class="card">
            <h2>Company information</h2>
            <dl class="fields">
              <div class="field"><svg class="icon"><use href="#i-building"/></svg><div><dt>Company</dt><dd><a href="#"><?= e($c["company"]) ?></a></dd></div></div>
              <div class="field"><svg class="icon"><use href="#i-brief"/></svg><div><dt>Industry</dt><dd><?= e($c["industry"]) ?></dd></div></div>
              <div class="field"><svg class="icon"><use href="#i-globe"/></svg><div><dt>Website</dt><dd><a href="https://<?= e($c["website"]) ?>"><?= e($c["website"]) ?></a></dd></div></div>
              <div class="field"><svg class="icon"><use href="#i-pin"/></svg><div><dt>Address</dt><dd><?= e($c["address"]) ?></dd></div></div>
            </dl>
          </section>
        </div>

        <div class="stack">
          <section class="card">
            <h2>Lead details</h2>
            <dl class="rows">
              <div class="row"><dt>Status</dt><dd><span class="badge"><?= e($c["status"]) ?></span></dd></div>
              <div class="row"><dt>Source</dt><dd><?= e($c["source"]) ?></dd></div>
              <div class="row"><dt>Assigned to</dt><dd class="link"><span class="avatar xs">AC</span><?= e($c["assigned"]) ?></dd></div>
              <div class="row"><dt>Created date</dt><dd><?= e($c["created"]) ?></dd></div>
              <div class="row"><dt>Last contact</dt><dd><?= e($c["last_contact"]) ?></dd></div>
            </dl>
          </section>

          <section class="card">
            <h2>Tags</h2>
            <div class="tags">
              <?php foreach ($c['tags'] as $i => $t): ?>
                <span class="tag<?= $i % 2 ? ' gold' : '' ?>"><?= e($t) ?></span>
              <?php endforeach; ?>
              <button class="tag add" type="button">+ Add Tag</button>
            </div>
          </section>

          <section class="card">
            <h2>Notes</h2>
            <form class="note-form">
              <input type="text" placeholder="Add a note..." aria-label="Add a note">
              <button class="btn primary" type="submit">Add</button>
            </form>
            <ul class="note-list"></ul>
          </section>
        </div>
      </div>
      </div>

      <!-- Activity -->
      <div class="panel" id="tab-activity" role="tabpanel" aria-labelledby="t-activity" hidden>
        <section class="card">
          <h2>Activity</h2>
          <ul class="timeline">
            <?php foreach ($activities as $a): ?>
              <li><span><?= e($a[0]) ?></span><time><?= e($a[1]) ?></time></li>
            <?php endforeach; ?>
          </ul>
        </section>
      </div>

      <!-- Deals -->
      <div class="panel" id="tab-deals" role="tabpanel" aria-labelledby="t-deals" hidden>
        <section class="card">
          <h2>Deals</h2>
          <div class="table-wrap">
            <table class="table">
              <thead><tr><th>Deal</th><th>Stage</th><th>Amount</th><th>Close date</th></tr></thead>
              <tbody>
                <?php foreach ($deals as $d): ?>
                  <tr><td><?= e($d[0]) ?></td><td><span class="badge"><?= e($d[1]) ?></span></td><td><?= e($d[2]) ?></td><td><?= e($d[3]) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <!-- Tasks -->
      <div class="panel" id="tab-tasks" role="tabpanel" aria-labelledby="t-tasks" hidden>
        <section class="card">
          <h2>Tasks</h2>
          <ul class="task-list">
            <?php foreach ($tasks as $i => $t): ?>
              <li>
                <label>
                  <input type="checkbox" <?= $t[2] ? 'checked' : '' ?>>
                  <span class="task-title"><?= e($t[0]) ?></span>
                </label>
                <time><?= e($t[1]) ?></time>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      </div>

      <!-- Notes -->
      <div class="panel" id="tab-notes" role="tabpanel" aria-labelledby="t-notes" hidden>
        <section class="card">
          <h2>Notes</h2>
          <form class="note-form">
            <input type="text" placeholder="Add a note..." aria-label="Add a note">
            <button class="btn primary" type="submit">Add</button>
          </form>
          <ul class="note-list"></ul>
        </section>
      </div>
    </main>
  </div>
</div>

<script>
  // Tabs: show the matching panel
  var tabs = document.querySelectorAll('.tabs [role="tab"]');
  function showTab(name) {
    tabs.forEach(function (t) {
      t.setAttribute('aria-selected', t.dataset.tab === name ? 'true' : 'false');
    });
    document.querySelectorAll('.panel').forEach(function (p) {
      p.hidden = p.id !== 'tab-' + name;
    });
  }
  tabs.forEach(function (t) {
    t.addEventListener('click', function () { showTab(t.dataset.tab); });
  });

  // Notes: shared by the Overview card and the Notes tab (not saved yet)
  var notes = [];
  function renderNotes() {
    document.querySelectorAll('.note-list').forEach(function (list) {
      list.replaceChildren();
      notes.forEach(function (n) {
        var li = document.createElement('li');
        var time = document.createElement('time');
        var p = document.createElement('p');
        time.textContent = n.time;
        p.textContent = n.text;
        li.append(time, p);
        list.append(li);
      });
    });
  }
  document.querySelectorAll('.note-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = form.querySelector('input');
      var text = input.value.trim();
      if (!text) return;
      notes.unshift({ text: text, time: new Date().toLocaleString() });
      input.value = '';
      renderNotes();
    });
  });

  // "More" dropdown
  var moreBtn = document.getElementById('more-btn');
  var moreMenu = document.getElementById('more-menu');
  function setMenu(open) {
    moreMenu.hidden = !open;
    moreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  moreBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    setMenu(moreMenu.hidden);
  });
  document.addEventListener('click', function () { setMenu(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { setMenu(false); moreBtn.focus(); }
  });
  moreMenu.addEventListener('click', function (e) {
    var item = e.target.closest('[data-action]');
    if (!item) return;
    setMenu(false);
    var action = item.dataset.action;
    if (action === 'delete') {
      if (confirm('Delete this customer?')) {
        // Later: fetch('/api/contacts/ID', { method: 'DELETE', credentials: 'include' })
        alert('Delete is not connected to the API yet.');
      }
    } else {
      alert(item.textContent + ' is not connected to the API yet.');
    }
  });
</script>
</body>
</html>
