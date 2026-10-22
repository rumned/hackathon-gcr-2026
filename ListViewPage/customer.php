<?php
session_start();

if (!isset($_SESSION['customers'])) {
    $_SESSION['customers'] = [
        ['id'=>1,'name'=>'Sarah Johnson','company'=>'Acme Corp','email'=>'sarah@acme.com','phone'=>'+1 234 567 890','status'=>'New'],
        ['id'=>2,'name'=>'Michael Chen','company'=>'TechFlow','email'=>'michael@techflow.com','phone'=>'+1 234 567 891','status'=>'Contacted'],
        ['id'=>3,'name'=>'Emily Davis','company'=>'BrightPath','email'=>'emily@brightpath.com','phone'=>'+1 234 567 892','status'=>'Qualified'],
        ['id'=>4,'name'=>'James Wilson','company'=>'NextGen','email'=>'james@nextgen.com','phone'=>'+1 234 567 893','status'=>'Proposal'],
        ['id'=>5,'name'=>'Olivia Martinez','company'=>'CloudSync','email'=>'olivia@cloudsync.com','phone'=>'+1 234 567 894','status'=>'Won'],
        ['id'=>6,'name'=>'Daniel Kim','company'=>'DataHub','email'=>'daniel@datahub.com','phone'=>'+1 234 567 895','status'=>'Contacted'],
        ['id'=>7,'name'=>'Sophia Lee','company'=>'InnoTech','email'=>'sophia@innotech.com','phone'=>'+1 234 567 896','status'=>'New'],
        ['id'=>8,'name'=>'Liam Brown','company'=>'FutureSoft','email'=>'liam@futuresoft.com','phone'=>'+1 234 567 897','status'=>'Qualified']
    ];
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$statuses = ['All','New','Contacted','Qualified','Proposal','Won'];

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
            in_array($status, array_slice($statuses, 1), true)
        ) {
            $ids = array_column($_SESSION['customers'], 'id');
            $nextId = empty($ids) ? 1 : max($ids) + 1;

            $_SESSION['customers'][] = [
                'id' => $nextId,
                'name' => $name,
                'company' => $company,
                'email' => $email,
                'phone' => $phone,
                'status' => $status
            ];
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $_SESSION['customers'] = array_values(array_filter(
            $_SESSION['customers'],
            fn($c) => $c['id'] !== $id
        ));
    }

    header('Location: customers.php');
    exit;
}

$customers = $_SESSION['customers'];

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'All';
$companyFilter = $_GET['company'] ?? 'All';

if (!in_array($statusFilter, $statuses, true)) {
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

function pageUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customers | CRMPro</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="layout">

    <aside class="sidebar">
        <div class="logo">CRM<span>Pro</span></div>

        <nav>
            <a href="index.php">▦ &nbsp; Dashboard</a>
            <a href="customers.php" class="active">♟ &nbsp; Customers</a>
            <a href="#">♧ &nbsp; Leads</a>
            <a href="#">▣ &nbsp; Companies</a>
            <a href="#">☑ &nbsp; Tasks</a>
            <a href="#">▤ &nbsp; Calendar</a>
            <a href="#">▥ &nbsp; Reports</a>
            <a href="#">⚙ &nbsp; Settings</a>
        </nav>

        <div class="sidebar-footer">CRMPro © 2026</div>
    </aside>

    <main class="main">

        <header class="topbar">
            <div class="top-search">
                Search customers, companies, or deals...
            </div>

            <div class="profile">
                <span class="bell">♧</span>
                <div class="avatar">AC</div>
                <div>
                    <strong>Alex Carter</strong>
                    <small>Sales Manager</small>
                </div>
            </div>
        </header>

        <section class="content">

            <div class="page-heading">
                <div>
                    <h1>Customers</h1>
                    <p>Manage your customer relationships.</p>
                </div>

                <button class="btn-gold" onclick="openModal()">
                    + Add Customer
                </button>
            </div>

            <div class="tabs">
                <?php foreach ($statuses as $s): ?>
                    <?php
                    $count = $s === 'All'
                        ? count($customers)
                        : count(array_filter(
                            $customers,
                            fn($c) => $c['status'] === $s
                        ));

                    $params = $_GET;
                    $params['status'] = $s;
                    $params['page'] = 1;
                    ?>
                    <a
                        href="?<?= e(http_build_query($params)) ?>"
                        class="tab <?= $statusFilter === $s ? 'selected' : '' ?>"
                    >
                        <?= e($s) ?> (<?= $count ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" class="filters">
                <input
                    type="text"
                    name="search"
                    placeholder="Search customers..."
                    value="<?= e($search) ?>"
                >

                <select name="company">
                    <option value="All">All Companies</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= e($company) ?>"
                            <?= $companyFilter === $company ? 'selected' : '' ?>>
                            <?= e($company) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s) ?>"
                            <?= $statusFilter === $s ? 'selected' : '' ?>>
                            <?= $s === 'All' ? 'All Status' : e($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-blue">Filter</button>
                <a href="customers.php" class="btn-outline">Reset</a>
            </form>

            <div class="table-card">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Company</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($visible as $c): ?>
                                <?php
                                $initials = '';
                                foreach (explode(' ', $c['name']) as $part) {
                                    if ($part !== '') {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                    }
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="customer-name">
                                            <span class="customer-avatar">
                                                <?= e(substr($initials, 0, 2)) ?>
                                            </span>
                                            <strong><?= e($c['name']) ?></strong>
                                        </div>
                                    </td>

                                    <td><?= e($c['company']) ?></td>
                                    <td class="email"><?= e($c['email']) ?></td>
                                    <td><?= e($c['phone']) ?></td>

                                    <td>
                                        <span class="status status-<?= e(strtolower($c['status'])) ?>">
                                            <?= e($c['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="actions">
                                            <a href="customer-detail.php?id=<?= (int)$c['id'] ?>">
                                                View
                                            </a>

                                            <form method="POST"
                                                onsubmit="return confirm('Delete this customer?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                                <button type="submit" title="Delete">×</button>
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

                <div class="pagination">
                    <span>
                        Showing <?= count($visible) ?> of
                        <?= count($filtered) ?> results
                    </span>

                    <div class="pages">
                        <?php if ($page > 1): ?>
                            <a href="<?= e(pageUrl($page - 1)) ?>">‹</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= e(pageUrl($i)) ?>"
                                class="<?= $page === $i ? 'current' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="<?= e(pageUrl($page + 1)) ?>">›</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<div class="modal-backdrop" id="customerModal">
    <div class="modal">
        <h2>Add Customer</h2>
        <p>Enter the new customer's information.</p>

        <form method="POST">
            <input type="hidden" name="action" value="add">

            <label>Full Name</label>
            <input type="text" name="name" required>

            <label>Company</label>
            <input type="text" name="company" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Phone</label>
            <input type="text" name="phone" required>

            <label>Status</label>
            <select name="status">
                <?php foreach (array_slice($statuses, 1) as $s): ?>
                    <option value="<?= e($s) ?>"><?= e($s) ?></option>
                <?php endforeach; ?>
            </select>

            <div class="modal-actions">
                <button type="button" class="btn-outline"
                    onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-gold">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById("customerModal").classList.add("show");
}

function closeModal() {
    document.getElementById("customerModal").classList.remove("show");
}

document.getElementById("customerModal").addEventListener("click", function(e) {
    if (e.target === this) closeModal();
});
</script>

</body>
</html>