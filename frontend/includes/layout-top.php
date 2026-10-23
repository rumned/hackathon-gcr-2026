<?php
// Expects $pageTitle and $activeModule ('home', 'customers', 'profiles', 'leads', 'opportunities',
// 'stages' or 'outbox') to be set by the page.
$recent    = api_get('/api/recently-viewed', ['module' => 'accounts', 'limit' => 5])['records'] ?? [];
$favorites = api_get('/api/favorites', ['module' => 'accounts'])['records'] ?? [];
$alerts    = api_get('/api/alerts', ['limit' => 1]);
$unread    = (int) ($alerts['unread_count'] ?? 0);
$flash     = take_flash();

// Customer status sections (same as the status tabs on customer.php)
$navStatuses = array_column(api_get('/api/meta/accounts')['fields']['status']['options'] ?? [], 'value');
// customer.php sets $statusFilter; on other pages no status is selected
$navStatus = ($activeModule === 'customers' && isset($statusFilter)) ? $statusFilter : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | CRMPro</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="page-app">
<?php require __DIR__ . '/icons.php'; ?>

<!-- Top navigation -->
<header class="topnav">
  <a href="homepage.php" class="topnav__logo">CRM<span>Pro</span></a>

  <ul class="topnav__modules">
    <li class="menu">
      <a href="customer.php" class="topnav__link<?= $activeModule === 'customers' ? ' is-active' : '' ?>" aria-haspopup="true">
        Customers <svg class="icon icon--sm"><use href="#i-chev"/></svg>
      </a>
      <div class="menu__panel">
        <p class="menu__heading">Actions</p>
        <a href="customer.php?add=1" class="menu__item">Create customer</a>
        <a href="customer.php" class="menu__item">View customers</a>
        <a href="#" class="menu__item">Import customers</a>
        <p class="menu__heading">By status</p>
        <?php foreach ($navStatuses as $s): ?>
          <a href="customer.php?status=<?= e(urlencode($s)) ?>" class="menu__item<?= $navStatus === $s ? ' is-active' : '' ?>"><?= e($s) ?></a>
        <?php endforeach; ?>
        <p class="menu__heading">Recently viewed</p>
        <?php foreach (array_slice($recent, 0, 3) as $r): ?>
          <a href="detail.php?id=<?= e($r['id']) ?>" class="menu__item"><?= e($r['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </li>
    <li><a href="stages.php" class="topnav__link<?= $activeModule === 'stages' ? ' is-active' : '' ?>">Stages</a></li>
    <li><a href="profiles.php" class="topnav__link<?= $activeModule === 'profiles' ? ' is-active' : '' ?>">Profiles</a></li>
    <li><a href="leads.php" class="topnav__link<?= $activeModule === 'leads' ? ' is-active' : '' ?>">Leads</a></li>
    <li><a href="opportunities.php" class="topnav__link<?= $activeModule === 'opportunities' ? ' is-active' : '' ?>">Opportunities</a></li>
    <li><a href="#" class="topnav__link">Calendar</a></li>
    <li class="menu">
      <button class="topnav__link" type="button" aria-haspopup="true">
        More <svg class="icon icon--sm"><use href="#i-chev"/></svg>
      </button>
      <div class="menu__panel">
        <a href="#" class="menu__item">Companies</a>
        <a href="#" class="menu__item">Tasks</a>
        <a href="#" class="menu__item">Reports</a>
        <a href="#" class="menu__item">Cases</a>
        <a href="#" class="menu__item">Calls</a>
        <a href="#" class="menu__item">Meetings</a>
        <a href="#" class="menu__item">Notes</a>
        <a href="#" class="menu__item">Documents</a>
        <a href="#" class="menu__item">Campaigns</a>
        <a href="outbox.php" class="menu__item">Outbox</a>
      </div>
    </li>
  </ul>

  <div class="topnav__tools">
    <div class="menu menu--right">
      <button class="topnav__btn" type="button" aria-haspopup="true">
        <svg class="icon icon--sm"><use href="#i-plus"/></svg> Create
      </button>
      <div class="menu__panel">
        <a href="customer.php?add=1" class="menu__item">Create customer</a>
        <a href="#" class="menu__item">Create contact</a>
        <a href="#" class="menu__item">Create lead</a>
        <a href="#" class="menu__item">Create opportunity</a>
        <a href="#" class="menu__item">Log call</a>
        <a href="#" class="menu__item">Schedule meeting</a>
        <a href="#" class="menu__item">Create task</a>
      </div>
    </div>

    <label class="topnav__search">
      <svg class="icon icon--sm"><use href="#i-search"/></svg>
      <input type="search" placeholder="Search" aria-label="Global search">
    </label>

    <button class="topnav__btn" type="button" aria-label="Alerts, <?= $unread ?> unread">
      <svg class="icon icon--sm"><use href="#i-bell"/></svg>
      <?php if ($unread > 0): ?><span class="topnav__count"><?= $unread ?></span><?php endif; ?>
    </button>

    <div class="menu menu--right">
      <button class="topnav__user" type="button" aria-haspopup="true">
        <span class="avatar avatar--sm"><?= e(initials(user_display_name($currentUser))) ?></span>
        <span class="topnav__user-info">
          <span class="topnav__user-name"><?= e(user_display_name($currentUser)) ?></span>
          <span class="topnav__user-role"><?= e($currentUser['title'] ?? $currentUser['user_name']) ?></span>
        </span>
        <svg class="icon icon--sm"><use href="#i-chev"/></svg>
      </button>
      <div class="menu__panel">
        <a href="#" class="menu__item">Profile</a>
        <a href="#" class="menu__item">Employees</a>
        <a href="#" class="menu__item">Settings</a>
        <a href="#" class="menu__item">About</a>
        <a href="logout.php" class="menu__item">Log out</a>
      </div>
    </div>
  </div>
</header>

<div class="app__body">

  <!-- Sidebar -->
  <aside class="sidebar">
    <section>
      <h2 class="sidebar__title">Actions</h2>
      <ul class="sidebar__list">
        <li><a href="customer.php?add=1" class="sidebar__link">Create customer</a></li>
        <li><a href="customer.php" class="sidebar__link">View customers</a></li>
        <li><a href="stages.php" class="sidebar__link<?= $activeModule === 'stages' ? ' is-active' : '' ?>">View stages</a></li>
        <li><a href="#" class="sidebar__link">Import customers</a></li>
      </ul>
    </section>
    <section>
      <h2 class="sidebar__title">By status</h2>
      <details class="sidebar__dropdown"<?= $navStatus !== null ? ' open' : '' ?>>
        <summary class="sidebar__link sidebar__summary">
          <span><?= $navStatus !== null ? e($navStatus) : 'All stages' ?></span>
          <svg class="icon icon--sm"><use href="#i-chev"/></svg>
        </summary>
        <ul class="sidebar__list sidebar__drop-list">
          <li><a href="customer.php" class="sidebar__link<?= $navStatus === null && $activeModule === 'customers' ? ' is-active' : '' ?>">All</a></li>
          <?php foreach ($navStatuses as $s): ?>
            <li><a href="customer.php?status=<?= e(urlencode($s)) ?>" class="sidebar__link<?= $navStatus === $s ? ' is-active' : '' ?>"><?= e($s) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </details>
    </section>
    <section>
      <h2 class="sidebar__title">Recently viewed</h2>
      <ul class="sidebar__list">
        <?php foreach ($recent as $r): ?>
          <li><a href="detail.php?id=<?= e($r['id']) ?>" class="sidebar__link"><?= e($r['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <section>
      <h2 class="sidebar__title">Favorites</h2>
      <ul class="sidebar__list">
        <?php foreach ($favorites as $f): ?>
          <li><a href="detail.php?id=<?= e($f['id']) ?>" class="sidebar__link"><?= e($f['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </section>
  </aside>

  <main class="main">
<?php if ($flash): ?>
    <p class="flash flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
<?php endif; ?>
