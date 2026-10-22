<?php
// Expects $pageTitle and $activeModule ('home' or 'customers') to be set by the page.
$recent    = array_slice(customers(), 0, 5);
$favorites = array_filter([find_customer(1), find_customer(5)]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | CRMPro</title>
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
        <p class="menu__heading">Recently viewed</p>
        <?php foreach (array_slice($recent, 0, 3) as $r): ?>
          <a href="detail.php?id=<?= (int)$r['id'] ?>" class="menu__item"><?= e($r['name']) ?></a>
        <?php endforeach; ?>
      </div>
    </li>
    <li><a href="#" class="topnav__link">Contacts</a></li>
    <li><a href="#" class="topnav__link">Leads</a></li>
    <li><a href="#" class="topnav__link">Opportunities</a></li>
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
      <input type="search" placeholder="Search customers, companies, or deals..." aria-label="Global search">
    </label>

    <button class="topnav__btn" type="button" aria-label="Alerts, 3 unread">
      <svg class="icon icon--sm"><use href="#i-bell"/></svg>
      <span class="topnav__count">3</span>
    </button>

    <div class="menu menu--right">
      <button class="topnav__user" type="button" aria-haspopup="true">
        <span class="avatar avatar--sm">AC</span>
        <span class="topnav__user-info">
          <span class="topnav__user-name">Alex Carter</span>
          <span class="topnav__user-role">Sales Manager</span>
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
        <li><a href="#" class="sidebar__link">Import customers</a></li>
      </ul>
    </section>
    <section>
      <h2 class="sidebar__title">Recently viewed</h2>
      <ul class="sidebar__list">
        <?php foreach ($recent as $r): ?>
          <li><a href="detail.php?id=<?= (int)$r['id'] ?>" class="sidebar__link"><?= e($r['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <section>
      <h2 class="sidebar__title">Favorites</h2>
      <ul class="sidebar__list">
        <?php foreach ($favorites as $f): ?>
          <li><a href="detail.php?id=<?= (int)$f['id'] ?>" class="sidebar__link"><?= e($f['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </section>
  </aside>

  <main class="main">
