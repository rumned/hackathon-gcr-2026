<?php
// Stages: every customer in a column for its sales status (accounts.status).
// Cards show customer details and the date of the last action; buttons move
// the customer to the previous or next stage.
require __DIR__ . '/includes/bootstrap.php';

$meta = api_get('/api/meta/accounts');
$statusOptions = $meta['fields']['status']['options'];
$stageOptions = $meta['fields']['customer_stage']['options'];
$statuses = array_column($statusOptions, 'value');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'move') {
    $status = (string) ($_POST['status'] ?? '');
    $id = (string) ($_POST['id'] ?? '');
    if (!in_array($status, $statuses, true)) {
        flash('error', 'Not moved: unknown stage.');
    } else {
        $result = move_customer($id, $status);
        flash($result['ok'] ? 'success' : 'error', $result['ok']
            ? "{$result['data']['name']} moved to $status."
            : 'Not moved: ' . (implode(' ', $result['errors']) ?: $result['message']));
    }
    redirect('stages.php');
}

// All customers, 100 per request
$customers = [];
for ($page = 1; ; $page++) {
    $list = api_get('/api/accounts', ['per_page' => 100, 'page' => $page, 'sort' => 'date_modified', 'dir' => 'desc']);
    array_push($customers, ...$list['records']);
    if (count($customers) >= $list['total'] || !$list['records']) {
        break;
    }
}

// Group by sales status. Customers with no status (or one not in the list) go in the first column.
$columns = array_fill_keys($statuses, []);
foreach ($customers as $customer) {
    $key = in_array($customer['status'], $statuses, true) ? $customer['status'] : $statuses[0];
    $columns[$key][] = $customer;
}

$pageTitle = 'Stages';
$activeModule = 'stages';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="page-head page-head--compact">
  <div>
    <h1 class="page-title">Stages</h1>
    <p class="page-sub">Every customer in the column for its sales stage. Moving a customer to Won also sets their Stage to Existing.</p>
  </div>
</div>

<div class="board" data-board>
  <?php foreach ($columns as $status => $cards): ?>
    <?php
      $next = next_status($statusOptions, $status);
      $prev = prev_status($statusOptions, $status);
    ?>
    <section class="board__column" data-status="<?= e($status) ?>" aria-labelledby="col-<?= e(status_class($status)) ?>">
      <header class="board__head">
        <h2 id="col-<?= e(status_class($status)) ?>"><?= e($status) ?></h2>
        <span class="board__count" data-count><?= count($cards) ?></span>
      </header>
      <ul class="board__cards" data-dropzone>
        <?php foreach ($cards as $card): ?>
          <?php
            $city = trim((string) ($card['billing_address_city'] ?? ''));
            $country = trim((string) ($card['billing_address_country'] ?? ''));
            $place = trim($city . ($city !== '' && $country !== '' ? ', ' : '') . $country);
            $phones = array_values(array_filter([
                trim((string) ($card['phone_office'] ?? '')),
                trim((string) ($card['phone_mobile'] ?? '')),
            ]));
            $lastAction = $card['last_contacted'] ?? $card['date_modified'] ?? null;
          ?>
          <li class="board-card" draggable="true" data-id="<?= e($card['id']) ?>">
            <a class="board-card__name" href="detail.php?id=<?= e($card['id']) ?>" draggable="false"><?= e($card['name']) ?></a>
            <p class="board-card__line">
              <?php if ($card['customer_stage'] !== null && $card['customer_stage'] !== ''): ?><span class="status <?= e(stage_badge_class($card['customer_stage'])) ?>"><?= e(option_label($stageOptions, $card['customer_stage'])) ?></span><?php endif; ?>
              <?php if ($card['status'] !== null && $card['status'] !== ''): ?><span class="status <?= e(status_class($card['status'])) ?>"><?= e($card['status']) ?></span><?php endif; ?>
            </p>
            <?php if (($card['contact_name'] ?? null) !== null && $card['contact_name'] !== ''): ?>
              <p class="board-card__line board-card__contact"><?= e($card['contact_name']) ?><?= ($card['contact_title'] ?? '') !== '' ? ' · ' . e($card['contact_title']) : '' ?></p>
            <?php endif; ?>
            <?php if ($card['email1'] !== null && $card['email1'] !== ''): ?>
              <p class="board-card__line"><a href="mailto:<?= e($card['email1']) ?>"><?= e($card['email1']) ?></a></p>
            <?php endif; ?>
            <?php if ($phones): ?>
              <p class="board-card__line"><?= e(implode(' · ', $phones)) ?></p>
            <?php endif; ?>
            <?php if ($place !== ''): ?>
              <p class="board-card__line"><?= e($place) ?></p>
            <?php endif; ?>
            <p class="board-card__line board-card__date-row">
              <span class="board-card__date-label">Last action:</span>
              <span class="board-card__date<?= $lastAction === null ? ' board-card__date--none' : '' ?>" title="Date of the last action"
                ><?= $lastAction !== null ? e(fmt_date($lastAction)) : 'Not contacted' ?></span>
            </p>
            <div class="board-card__foot">
              <span class="board-card__user">
                <?php if ($card['assigned_user_name'] !== null && $card['assigned_user_name'] !== ''): ?>
                  <span class="avatar avatar--xs"><?= e(initials($card['assigned_user_name'])) ?></span><?= e($card['assigned_user_name']) ?>
                <?php endif; ?>
              </span>
            </div>
            <div class="board-card__moves">
              <?php if ($prev !== null): ?>
                <form method="POST">
                  <input type="hidden" name="action" value="move">
                  <input type="hidden" name="id" value="<?= e($card['id']) ?>">
                  <input type="hidden" name="status" value="<?= e($prev) ?>">
                  <button class="btn btn--sm board-card__move" type="submit" title="Move to <?= e($prev) ?>">&larr; <?= e(move_short($prev)) ?></button>
                </form>
              <?php endif; ?>
              <?php if ($next !== null): ?>
                <form method="POST">
                  <input type="hidden" name="action" value="move">
                  <input type="hidden" name="id" value="<?= e($card['id']) ?>">
                  <input type="hidden" name="status" value="<?= e($next) ?>">
                  <button class="btn btn--sm btn--blue board-card__move" type="submit" title="Move to <?= e($next) ?>"><?= e(move_short($next)) ?> &rarr;</button>
                </form>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
        <?php if (!$cards): ?>
          <li class="empty">No customers in this stage.</li>
        <?php endif; ?>
      </ul>
    </section>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
