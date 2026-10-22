<?php
require __DIR__ . '/includes/bootstrap.php';

// Emails the backend has sent (quotations). With the default 'outbox' mail transport
// they are saved here only; see crm-backend/src/Mailer.php.

$emailId = (string) ($_GET['id'] ?? '');
$email = $emailId !== '' ? api_get('/api/outbox/' . rawurlencode($emailId)) : null;
if ($emailId !== '' && !$email) {
    http_response_code(404);
}

$pageSize = 10;
$list = $email ? null : api_get('/api/outbox', ['per_page' => $pageSize, 'page' => max(1, (int) ($_GET['page'] ?? 1))]);
$totalPages = $list ? max(1, (int) ceil($list['total'] / $pageSize)) : 1;

function delivery_label(string $status): string
{
    return $status === 'sent' ? 'Sent' : 'Saved to outbox';
}

$pageTitle = $email ? $email['name'] : 'Outbox';
$activeModule = 'outbox';
require __DIR__ . '/includes/layout-top.php';
?>

<?php if ($emailId !== '' && !$email): ?>
  <div class="empty-state"><h3>Email not found</h3><p><a href="outbox.php">Back to Outbox</a></p></div>

<?php elseif ($email): ?>
  <div class="page-head">
    <p class="breadcrumb"><a href="outbox.php">Outbox</a> / <?= e($email['name']) ?></p>
    <h1 class="page-title"><?= e($email['name']) ?></h1>
  </div>

  <section class="card email-view">
    <dl class="email-view__meta">
      <div><dt>From</dt><dd><?= e($email['from_addr']) ?></dd></div>
      <div><dt>To</dt><dd><?= e($email['to_addrs']) ?></dd></div>
      <div><dt>Date</dt><dd><?= e(fmt_datetime($email['date_sent'])) ?> UTC</dd></div>
      <div><dt>Status</dt><dd><?= e(delivery_label($email['status'])) ?></dd></div>
      <?php if ($email['parent_name'] !== null): ?>
        <div><dt>Customer</dt><dd><a href="detail.php?id=<?= e($email['parent_id']) ?>"><?= e($email['parent_name']) ?></a></dd></div>
      <?php endif; ?>
    </dl>
    <pre class="email-view__body"><?= e($email['description']) ?></pre>
  </section>

<?php else: ?>
  <div class="page-head">
    <div>
      <h1 class="page-title">Outbox</h1>
      <p class="page-sub">Emails sent by CRMPro, such as quotations. No mail server is set up yet, so they are saved here instead of being delivered.</p>
    </div>
  </div>

  <div class="table-card">
    <div class="table-scroll">
      <table class="table table--list">
        <thead><tr><th>Date (UTC)</th><th>Subject</th><th>To</th><th>Customer</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($list['records'] as $row): ?>
            <tr>
              <td><?= e(fmt_datetime($row['date_sent'])) ?></td>
              <td><a href="outbox.php?id=<?= e($row['id']) ?>"><?= e($row['name']) ?></a></td>
              <td><?= e($row['to_addrs']) ?></td>
              <td><?php if ($row['parent_name'] !== null): ?><a href="detail.php?id=<?= e($row['parent_id']) ?>"><?= e($row['parent_name']) ?></a><?php endif; ?></td>
              <td><?= e(delivery_label($row['status'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!$list['records']): ?>
        <div class="empty-state"><h3>No emails yet</h3><p>Quotations appear here when they are sent.</p></div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="pager pager--below" aria-label="Pages">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a class="mini-btn<?= $list['page'] === $i ? ' is-current' : '' ?>" href="?page=<?= $i ?>"<?= $list['page'] === $i ? ' aria-current="page"' : '' ?>><?= $i ?></a>
      <?php endfor; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
