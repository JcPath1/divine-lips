<?php
// Glimmer Bliss - private shop admin. glimmerbliss.com/admin/
// Sell at the booth, count glosses, write down what was bought, see the money.
// See orders, mark them paid / shipped, run promotions, see customers.
declare(strict_types=1);
require dirname(__DIR__) . '/api/_boot.php';

header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
start_session();

$self = strtok($_SERVER['REQUEST_URI'] ?? '/admin/', '?');
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

function go(string $query = '', string $flash = ''): never
{
    global $self;
    if ($flash !== '') {
        $_SESSION['flash'] = $flash;
    }
    header('Location: ' . $self . ($query !== '' ? '?' . $query : ''));
    exit;
}

function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf() . '">';
}

function dollars_to_cents(string $s): int
{
    return (int)round(((float)preg_replace('/[^0-9.]/', '', $s)) * 100);
}

function local_time(?string $utc): string
{
    if (!$utc) {
        return '';
    }
    $d = new DateTime($utc, new DateTimeZone('UTC'));
    $d->setTimezone(new DateTimeZone('America/Chicago'));
    return $d->format('M j, g:i a');
}

// ---------- log in / out ----------

$isAdmin = !empty($_SESSION['admin']) && ($_SESSION['admin_until'] ?? 0) > time();
$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $ip = client_ip();
    if (too_many('admin', $ip, 5, 900)) {
        go('', 'Too many tries. Wait 15 minutes.');
    }
    if (password_verify((string)($_POST['password'] ?? ''), (string)cfg('admin_password_hash'))) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['admin_until'] = time() + 12 * 3600;
        db_migrate();
        go();
    }
    note_attempt('admin', $ip);
    go('', 'Wrong password.');
}

if ($isAdmin && $action !== '' && !hash_equals(csrf(), (string)($_POST['csrf'] ?? ''))) {
    go('', 'That form was too old. Please try again.');
}

if ($isAdmin && $action === 'logout') {
    unset($_SESSION['admin'], $_SESSION['admin_until']);
    go();
}

// ---------- order buttons ----------

if ($isAdmin && $action !== '' && isset($_POST['code'])) {
    $order = get_order((string)$_POST['code']);
    if (!$order) {
        go('', 'Order not found.');
    }
    $back = 'order=' . rawurlencode($order['code']);
    switch ($action) {
        case 'mark_paid':
            $order = set_order_status($order, 'paid', ['payment_ref' => clean_text($_POST['payment_ref'] ?? $order['payment_ref'], 120)]);
            email_payment_received($order);
            go($back, 'Marked PAID. The customer was emailed a receipt.');
        case 'mark_shipped':
            $order = set_order_status($order, 'shipped', ['tracking' => clean_text($_POST['tracking'] ?? '', 120)]);
            email_shipped($order);
            go($back, 'Marked SHIPPED. The customer was emailed.');
        case 'mark_picked_up':
            $order = set_order_status($order, 'picked_up');
            email_shipped($order);
            go($back, 'Marked PICKED UP. The customer was emailed a thank-you.');
        case 'cancel':
            set_order_status($order, 'cancelled');
            go($back, 'Order cancelled. (No email was sent - tell the customer yourself if needed.)');
        case 'reopen':
            set_order_status($order, 'awaiting_payment');
            go($back, 'Order set back to Waiting for payment.');
        case 'note':
            db()->prepare('UPDATE orders SET admin_note = ? WHERE id = ?')->execute([mb_substr(trim((string)($_POST['admin_note'] ?? '')), 0, 1000), $order['id']]);
            go($back, 'Note saved.');
        case 'resend':
            email_order_received($order);
            go($back, 'Order email sent again.');
    }
}

// ---------- promotion buttons ----------

if ($isAdmin && $action === 'promo_add') {
    $kind = array_key_exists($_POST['kind'] ?? '', PROMO_KINDS) ? $_POST['kind'] : 'free_shipping';
    $label = clean_text($_POST['label'] ?? '', 160);
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($_POST['promo_code'] ?? '')));
    $starts = trim((string)($_POST['starts_at'] ?? ''));
    $ends = trim((string)($_POST['ends_at'] ?? ''));
    if ($label === '') {
        go('view=promos', 'Give the promotion a name customers will see, like "Free shipping over $25".');
    }
    db()->prepare('INSERT INTO promotions (label, kind, min_subtotal_cents, percent, code, active, starts_at, ends_at, created_at) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)')
        ->execute([
            $label, $kind, dollars_to_cents((string)($_POST['min'] ?? '0')),
            $kind === 'percent_off' ? max(1, min(100, (int)($_POST['percent'] ?? 0))) : 0,
            $code !== '' ? $code : null,
            $starts !== '' ? gmdate('Y-m-d H:i:s', strtotime($starts . ' 00:00:00 America/Chicago')) : null,
            $ends !== '' ? gmdate('Y-m-d H:i:s', strtotime($ends . ' 23:59:59 America/Chicago')) : null,
            now(),
        ]);
    go('view=promos', 'Promotion added and switched ON.');
}
if ($isAdmin && in_array($action, ['promo_toggle', 'promo_delete'], true)) {
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'promo_toggle') {
        db()->prepare('UPDATE promotions SET active = 1 - active WHERE id = ?')->execute([$id]);
        go('view=promos', 'Promotion switched.');
    }
    db()->prepare('DELETE FROM promotions WHERE id = ?')->execute([$id]);
    go('view=promos', 'Promotion deleted. (Old orders still show the promo they used.)');
}

// ---------- booth, inventory and expense buttons ----------

if ($isAdmin && $action === 'sell') {
    $qtys = is_array($_POST['qty'] ?? null) ? $_POST['qty'] : [];
    $id = record_show_sale($qtys, (string)($_POST['pay'] ?? ''));
    if ($id === null) {
        go('view=sell', 'Tap a gloss first, then tap how they paid.');
    }
    $st = db()->prepare('SELECT total_cents FROM show_sales WHERE id = ?');
    $st->execute([$id]);
    go('view=sell', '✅ Sold! ' . money((int)$st->fetchColumn()));
}
if ($isAdmin && $action === 'sale_undo') {
    go('view=sell', undo_show_sale((int)($_POST['id'] ?? 0)) ? '↩️ Sale taken back. The glosses are back on the shelf.' : 'That sale was already taken back.');
}
if ($isAdmin && in_array($action, ['stock_add', 'stock_remove', 'stock_made'], true)) {
    $key = (string)($_POST['key'] ?? '');
    $n = $action === 'stock_made' ? max(0, min(500, (int)($_POST['n'] ?? 0))) : 1;
    if ($action === 'stock_remove' && (stock_counts()[$key] ?? 0) <= 0) {
        $n = 0; // − never goes below zero
    }
    stock_change($key, $action === 'stock_remove' ? -$n : $n, $action === 'stock_made' ? 'made' : 'count');
    $name = stock_items()[$key]['name'] ?? 'gloss';
    go('view=stock#' . rawurlencode($key), $action === 'stock_made' ? ($n ? "🎉 Added $n $name!" : 'Type how many you made.') : '');
}
if ($isAdmin && $action === 'expense_add') {
    $what = clean_text($_POST['what'] ?? '', 160);
    $amount = dollars_to_cents((string)($_POST['amount'] ?? ''));
    $date = (string)($_POST['spent_on'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = local_date(now());
    }
    if ($what === '' || $amount <= 0) {
        go('view=spent', 'Type what you bought and how much it cost.');
    }
    db()->prepare('INSERT INTO expenses (spent_on, what, amount_cents, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$date, $what, $amount, now()]);
    go('view=spent', '🧾 Saved: ' . $what . ' ' . money($amount));
}
if ($isAdmin && $action === 'expense_delete') {
    db()->prepare('DELETE FROM expenses WHERE id = ?')->execute([(int)($_POST['id'] ?? 0)]);
    go('view=spent', 'Deleted.');
}

// ---------- social media posts ----------

if ($isAdmin && in_array($action, ['posts_make', 'posts_email'], true)) {
    $week = post_week_of();
    $data = load_week_posts($week);
    if ($action === 'posts_make' || $data === null) {
        $data = make_week_posts($week, $data === null ? 0 : (int)$data['variant'] + 1);
        if ($action === 'posts_make') {
            go('view=posts', '✨ New posts made!');
        }
    }
    go('view=posts', email_week_posts($data) ? '📧 Emailed to ' . cfg('mail.owner_alert') : 'The email did not send. Try again later.');
}

// ---------- reviews ----------

if ($isAdmin && in_array($action, ['review_approve', 'review_hide', 'review_delete'], true)) {
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'review_delete') {
        db()->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
        go('view=reviews', '🗑️ Review deleted.');
    }
    set_review_status($id, $action === 'review_approve' ? 'approved' : 'hidden');
    go('view=reviews', $action === 'review_approve' ? '✅ Approved! It shows on the website now.' : 'Hidden. It is off the website.');
}

// ---------- page ----------

$view = $_GET['view'] ?? 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Glimmer Bliss Admin</title>
<style>
  :root { --pink:#e91e63; --soft:#fde7ef; --ink:#333; --line:#eee; }
  * { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; color: var(--ink); background: #fafafa; margin: 0; }
  header { background: var(--pink); color: #fff; padding: 12px 16px; display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
  header strong { font-size: 18px; margin-right: auto; }
  header a { color: #fff; text-decoration: none; font-weight: bold; }
  header a.on { text-decoration: underline; }
  main { max-width: 980px; margin: 0 auto; padding: 16px; }
  .card { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 16px; margin-bottom: 16px; }
  .flash { background: #e8f5e9; border: 1px solid #a5d6a7; padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th, td { text-align: left; padding: 8px 6px; border-bottom: 1px solid var(--line); vertical-align: top; }
  .badge { display: inline-block; padding: 3px 9px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #eee; }
  .s-awaiting_payment { background: #fff3cd; } .s-customer_says_paid { background: #ffd6a5; }
  .s-paid { background: #c8e6c9; } .s-shipped, .s-picked_up { background: #bbdefb; } .s-cancelled { background: #e0e0e0; color: #777; }
  button, .btn { background: var(--pink); color: #fff; border: 0; border-radius: 20px; padding: 9px 16px; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-block; }
  button.plain { background: #fff; color: var(--ink); border: 1px solid #ccc; }
  input, select, textarea { font: inherit; padding: 8px; border: 1px solid #ccc; border-radius: 8px; width: 100%; }
  label { display: block; font-size: 13px; font-weight: bold; margin: 10px 0 4px; }
  .row { display: flex; gap: 10px; flex-wrap: wrap; } .row > * { flex: 1 1 160px; }
  .tabs a { margin-right: 12px; } .tabs a.on { font-weight: bold; color: var(--pink); }
  .actions form { display: inline-block; margin: 4px 6px 4px 0; }
  .muted { color: #888; font-size: 13px; }
  .big { font-size: 22px; font-weight: bold; }
  @media (max-width: 640px) { .hide-sm { display: none; } }

  /* Charlie's screens: big buttons, big numbers */
  .tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
  .tile { position: relative; background: #fff; border: 3px solid var(--line); border-radius: 16px; padding: 10px; text-align: center; color: var(--ink); font-size: 16px; }
  .tile img { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 10px; }
  .tile b { display: block; margin-top: 6px; }
  button.tile { cursor: pointer; }
  .tile.picked { border-color: var(--pink); background: var(--soft); }
  .tile .count { position: absolute; top: -10px; right: -10px; background: var(--pink); color: #fff; border-radius: 50%; width: 38px; height: 38px; line-height: 38px; font-size: 20px; font-weight: bold; }
  .tile .count[hidden] { display: none; }
  .total-bar { font-size: 30px; font-weight: bold; text-align: center; margin: 14px 0; }
  .pay-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; }
  .pay-buttons button { font-size: 20px; padding: 18px 6px; border-radius: 16px; }
  .stock-num { font-size: 40px; font-weight: bold; margin: 4px 0; }
  .stock-num.low { color: #d32f2f; }
  .plusminus { display: flex; gap: 8px; justify-content: center; }
  .plusminus button { font-size: 26px; width: 56px; height: 56px; padding: 0; border-radius: 50%; }
  .made { display: flex; gap: 6px; margin-top: 10px; }
  .made input { font-size: 18px; text-align: center; }
  .made button { white-space: nowrap; }
  .money-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
  .money-row { display: flex; justify-content: space-between; font-size: 18px; padding: 10px 0; border-bottom: 1px solid var(--line); }
  .money-row.kept { font-size: 24px; font-weight: bold; color: #2e7d32; border: 0; }
  .money-row small { display: block; color: #888; font-size: 13px; }
  .big-form input, .big-form button { font-size: 20px; padding: 12px; }
  .post-pics { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
  .post-pics a { text-align: center; color: var(--ink); text-decoration: none; font-size: 13px; }
  .review-row { border-bottom: 1px solid var(--line); padding: 12px 0; }
  .review-row:last-child { border-bottom: 0; }
  .review-stars { color: #f5a623; font-size: 22px; letter-spacing: 2px; }
  .review-body { font-size: 16px; margin: 8px 0; }
  .post-pics img { display: block; height: 180px; border-radius: 8px; border: 1px solid var(--line); margin-bottom: 4px; }
</style>
</head>
<body>
<?php if (!$isAdmin): ?>
  <main style="max-width:380px;margin-top:60px">
    <div class="card">
      <h2 style="color:var(--pink);margin-top:0">Glimmer Bliss Admin</h2>
      <?php if ($flash): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="action" value="login">
        <label for="pw">Password</label>
        <input id="pw" type="password" name="password" autocomplete="current-password" autofocus required>
        <p><button>Log in</button></p>
      </form>
    </div>
  </main>
<?php exit; endif; ?>

<header>
  <strong>✨ Glimmer Bliss Admin</strong>
  <a href="?view=sell" class="<?= $view === 'sell' ? 'on' : '' ?>">🛍️ Sell</a>
  <a href="?view=stock" class="<?= $view === 'stock' ? 'on' : '' ?>">📦 My Gloss</a>
  <a href="?view=spent" class="<?= $view === 'spent' ? 'on' : '' ?>">🧾 Spent</a>
  <a href="?view=money" class="<?= $view === 'money' ? 'on' : '' ?>">💰 Money</a>
  <a href="?view=posts" class="<?= $view === 'posts' ? 'on' : '' ?>">📣 Posts</a>
  <?php $waiting = pending_review_count(); ?>
  <a href="?view=reviews" class="<?= $view === 'reviews' ? 'on' : '' ?>">⭐ Reviews<?= $waiting ? " ($waiting)" : '' ?></a>
  <a href="?view=orders" class="<?= $view === 'orders' ? 'on' : '' ?>">Orders</a>
  <a href="?view=promos" class="<?= $view === 'promos' ? 'on' : '' ?>">Promotions</a>
  <a href="?view=customers" class="<?= $view === 'customers' ? 'on' : '' ?>">Customers</a>
  <form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="plain" style="padding:5px 12px">Log out</button></form>
</header>
<main>
<?php if ($flash): ?><div class="flash"><?= e($flash) ?></div><?php endif; ?>

<?php if (isset($_GET['order'])):
    $o = get_order((string)$_GET['order']);
    if (!$o): ?>
    <div class="card">Order not found. <a href="?">Back</a></div>
<?php else: ?>
  <p><a href="?view=orders">← All orders</a></p>
  <div class="card">
    <div class="big"><?= e($o['code']) ?> · <?= money((int)$o['total_cents']) ?></div>
    <p><span class="badge s-<?= e($o['status']) ?>"><?= e(ORDER_STATUSES[$o['status']] ?? $o['status']) ?></span>
       &nbsp; paying by <strong><?= e(PAYMENT_METHODS[$o['payment_method']] ?? $o['payment_method']) ?></strong>
       <span class="muted"> · ordered <?= e(local_time($o['created_at'])) ?></span></p>

    <?php if (in_array($o['status'], ['awaiting_payment', 'customer_says_paid'], true) && $o['payment_method'] !== 'paypal'): ?>
      <p style="background:var(--soft);padding:10px;border-radius:8px">
        Look in your <strong><?= e(PAYMENT_METHODS[$o['payment_method']]) ?></strong> for
        <strong><?= money((int)$o['total_cents']) ?></strong> from <strong><?= e($o['name']) ?></strong>
        with <strong><?= e($o['code']) ?></strong> in the note. Found it? Tap Mark Paid.
      </p>
    <?php endif; ?>

    <div class="actions">
      <?php if (in_array($o['status'], ['awaiting_payment', 'customer_says_paid'], true)): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="code" value="<?= e($o['code']) ?>"><input type="hidden" name="action" value="mark_paid">
          <input name="payment_ref" placeholder="Payment note (optional)" value="<?= e($o['payment_ref']) ?>" style="width:200px">
          <button>✅ Mark Paid</button></form>
      <?php endif; ?>
      <?php if ($o['status'] === 'paid' && $o['delivery'] === 'ship'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="code" value="<?= e($o['code']) ?>"><input type="hidden" name="action" value="mark_shipped">
          <input name="tracking" placeholder="Tracking number (optional)" style="width:220px">
          <button>📦 Mark Shipped</button></form>
      <?php endif; ?>
      <?php if ($o['status'] === 'paid' && $o['delivery'] === 'pickup'): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="code" value="<?= e($o['code']) ?>"><input type="hidden" name="action" value="mark_picked_up"><button>🤝 Mark Picked Up</button></form>
      <?php endif; ?>
      <?php if ($o['status'] !== 'cancelled'): ?>
        <form method="post" onsubmit="return confirm('Cancel order <?= e($o['code']) ?>?')"><?= csrf_field() ?><input type="hidden" name="code" value="<?= e($o['code']) ?>"><input type="hidden" name="action" value="cancel"><button class="plain">Cancel order</button></form>
      <?php else: ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="code" value="<?= e($o['code']) ?>"><input type="hidden" name="action" value="reopen"><button class="plain">Re-open order</button></form>
      <?php endif; ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="code" value="<?= e($o['code']) ?>"><input type="hidden" name="action" value="resend"><button class="plain">Re-send order email</button></form>
    </div>
  </div>

  <div class="row">
    <div class="card">
      <h3 style="margin-top:0">Customer</h3>
      <p><strong><?= e($o['name']) ?></strong><br><a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a><?= $o['phone'] !== '' ? '<br>' . e($o['phone']) : '' ?></p>
      <?php if ($o['delivery'] === 'ship'): ?>
        <h3>Ship to</h3>
        <p><?= e($o['name']) ?><br><?= e($o['address1']) ?><?= $o['address2'] !== '' ? '<br>' . e($o['address2']) : '' ?><br><?= e($o['city']) ?>, <?= e($o['state']) ?> <?= e($o['zip']) ?></p>
      <?php else: ?>
        <h3>Local pickup</h3><p>Arrange a pickup time with the customer.</p>
      <?php endif; ?>
      <?php if ($o['customer_note'] !== ''): ?><h3>Their note</h3><p><?= e($o['customer_note']) ?></p><?php endif; ?>
    </div>
    <div class="card">
      <h3 style="margin-top:0">Items</h3>
      <?= invoice_rows_html($o) ?>
      <p class="muted">
        <?= $o['customer_paid_at'] ? 'Customer said paid: ' . e(local_time($o['customer_paid_at'])) . '<br>' : '' ?>
        <?= $o['paid_at'] ? 'Paid: ' . e(local_time($o['paid_at'])) . '<br>' : '' ?>
        <?= $o['payment_ref'] !== '' ? 'Payment note: ' . e($o['payment_ref']) . '<br>' : '' ?>
        <?= $o['shipped_at'] ? 'Shipped / picked up: ' . e(local_time($o['shipped_at'])) . '<br>' : '' ?>
        <?= $o['tracking'] !== '' ? 'Tracking: ' . e($o['tracking']) : '' ?>
      </p>
    </div>
  </div>
  <div class="card">
    <form method="post"><?= csrf_field() ?><input type="hidden" name="code" value="<?= e($o['code']) ?>"><input type="hidden" name="action" value="note">
      <label for="an">Private note (only you see this)</label>
      <textarea id="an" name="admin_note" rows="3"><?= e($o['admin_note']) ?></textarea>
      <p><button class="plain">Save note</button></p></form>
  </div>
<?php endif; ?>

<?php elseif ($view === 'sell'):
    $items = stock_items();
    $counts = stock_counts();
    $todayStart = (new DateTime('today', new DateTimeZone('America/Chicago')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $st = db()->prepare('SELECT * FROM show_sales WHERE created_at >= ? ORDER BY id DESC');
    $st->execute([$todayStart]);
    $today = $st->fetchAll();
    $todayTotal = array_sum(array_map(fn($s) => $s['undone'] ? 0 : (int)$s['total_cents'], $today)); ?>
  <form method="post" id="sellForm" class="card"><?= csrf_field() ?><input type="hidden" name="action" value="sell">
    <h2 style="margin-top:0">🛍️ Sell at the booth</h2>
    <p class="muted">Tap a gloss once for each one they buy. Tap the pink number to take one off. Prices include tax.</p>
    <div class="tiles">
      <?php foreach ($items as $key => $it): ?>
        <button type="button" class="tile" data-key="<?= e($key) ?>" data-price="<?= (int)$it['price'] ?>">
          <span class="count" hidden>0</span>
          <img src="../<?= e($it['image']) ?>" alt="">
          <b><?= e($it['name']) ?></b>
          <?= money((int)$it['price']) ?> <span class="muted">· <?= (int)$counts[$key] ?> left</span>
          <input type="hidden" name="qty[<?= e($key) ?>]" value="0">
        </button>
      <?php endforeach; ?>
    </div>
    <div class="total-bar">Total: <span id="sellTotal">$0.00</span></div>
    <p style="text-align:center;margin-top:-6px" class="muted">How did they pay?</p>
    <div class="pay-buttons">
      <?php foreach (SHOW_PAY_METHODS as $m => $label): ?>
        <button name="pay" value="<?= $m ?>"><?= $label ?></button>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center"><button type="button" class="plain" id="sellClear">Start over</button></p>
  </form>
  <script>
  (function () {
    const form = document.getElementById('sellForm');
    const total = document.getElementById('sellTotal');
    function update() {
      let cents = 0;
      form.querySelectorAll('.tile').forEach(t => {
        const n = +t.querySelector('input').value;
        const badge = t.querySelector('.count');
        badge.textContent = n;
        badge.hidden = n === 0;
        t.classList.toggle('picked', n > 0);
        cents += n * +t.dataset.price;
      });
      total.textContent = '$' + (cents / 100).toFixed(2);
    }
    form.querySelectorAll('.tile').forEach(t => t.addEventListener('click', e => {
      const input = t.querySelector('input');
      const minus = e.target.classList.contains('count');
      input.value = Math.max(0, +input.value + (minus ? -1 : 1));
      update();
    }));
    document.getElementById('sellClear').addEventListener('click', () => {
      form.querySelectorAll('.tile input').forEach(i => { i.value = 0; });
      update();
    });
    form.addEventListener('submit', e => {
      if (total.textContent === '$0.00') { e.preventDefault(); alert('Tap a gloss first!'); }
    });
  })();
  </script>
  <div class="card">
    <h3 style="margin-top:0">Today: <?= money($todayTotal) ?></h3>
    <?php if (!$today): ?><p class="muted">No booth sales yet today.</p><?php endif; ?>
    <table>
      <?php foreach ($today as $s): $lines = json_decode($s['items_json'], true); ?>
      <tr style="<?= $s['undone'] ? 'opacity:.4;text-decoration:line-through' : '' ?>">
        <td><?= e(local_time($s['created_at'])) ?></td>
        <td><?= e(implode(', ', array_map(fn($l) => $l['qty'] . ' ' . $l['name'], $lines))) ?></td>
        <td><?= money((int)$s['total_cents']) ?><br><span class="muted"><?= e(SHOW_PAY_METHODS[$s['pay_method']] ?? $s['pay_method']) ?></span></td>
        <td><?php if (!$s['undone']): ?>
          <form method="post" onsubmit="return confirm('Take this sale back?')"><?= csrf_field() ?><input type="hidden" name="action" value="sale_undo"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="plain">Oops</button></form>
        <?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php elseif ($view === 'stock'):
    $items = stock_items();
    $counts = stock_counts(); ?>
  <div class="card">
    <h2 style="margin-top:0">📦 My Gloss</h2>
    <p class="muted">How many of each you have. Made a batch? Type how many and tap Add. Use − and + to fix the count.
      Sales at the booth and paid website orders come off by themselves.</p>
  </div>
  <div class="tiles">
    <?php foreach ($items as $key => $it): $n = (int)$counts[$key]; ?>
      <div class="tile" id="<?= e($key) ?>">
        <img src="../<?= e($it['image']) ?>" alt="">
        <b><?= e($it['name']) ?></b>
        <div class="stock-num <?= $n <= LOW_STOCK ? 'low' : '' ?>"><?= $n ?></div>
        <?php if ($n <= LOW_STOCK): ?><div class="muted" style="color:#d32f2f">⚠️ Running low</div><?php endif; ?>
        <div class="plusminus">
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="stock_remove"><input type="hidden" name="key" value="<?= e($key) ?>"><button class="plain" aria-label="One less">−</button></form>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="stock_add"><input type="hidden" name="key" value="<?= e($key) ?>"><button aria-label="One more">+</button></form>
        </div>
        <form method="post" class="made"><?= csrf_field() ?><input type="hidden" name="action" value="stock_made"><input type="hidden" name="key" value="<?= e($key) ?>">
          <input name="n" type="number" min="1" max="500" inputmode="numeric" placeholder="How many?" aria-label="How many you made">
          <button>Add</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif ($view === 'spent'):
    $expenses = db()->query('SELECT * FROM expenses ORDER BY spent_on DESC, id DESC LIMIT 300')->fetchAll(); ?>
  <div class="card">
    <h2 style="margin-top:0">🧾 What did you buy?</h2>
    <form method="post" class="big-form"><?= csrf_field() ?><input type="hidden" name="action" value="expense_add">
      <label for="xw">What was it?</label>
      <input id="xw" name="what" maxlength="160" placeholder="Lip gloss bottles" required>
      <div class="row">
        <div><label for="xa">How much? ($)</label><input id="xa" name="amount" inputmode="decimal" placeholder="24.99" required></div>
        <div><label for="xd">When?</label><input id="xd" type="date" name="spent_on" value="<?= e(local_date(now())) ?>"></div>
      </div>
      <p><button>Save</button></p>
    </form>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Everything you bought (<?= money(array_sum(array_column($expenses, 'amount_cents'))) ?>)</h3>
    <?php if (!$expenses): ?><p class="muted">Nothing yet.</p><?php endif; ?>
    <table>
      <?php foreach ($expenses as $x): ?>
      <tr><td class="muted"><?= e(date('M j, Y', strtotime($x['spent_on']))) ?></td><td><?= e($x['what']) ?></td><td><?= money((int)$x['amount_cents']) ?></td>
        <td><form method="post" onsubmit="return confirm('Delete this?')"><?= csrf_field() ?><input type="hidden" name="action" value="expense_delete"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="plain">Delete</button></form></td></tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php elseif ($view === 'money'):
    $periods = ['This month' => money_summary(month_start_utc()), 'All time' => money_summary(null)]; ?>
  <div class="money-grid">
    <?php foreach ($periods as $title => $m): ?>
    <div class="card">
      <h2 style="margin-top:0"><?= e($title) ?></h2>
      <div class="money-row"><span>💰 Money in<small><?= $m['online_n'] ?> website order<?= $m['online_n'] === 1 ? '' : 's' ?> + <?= $m['show_n'] ?> booth sale<?= $m['show_n'] === 1 ? '' : 's' ?></small></span><span><?= money($m['in']) ?></span></div>
      <div class="money-row"><span>🏛️ Tax to save<small>This belongs to Texas</small></span><span>−<?= money($m['tax']) ?></span></div>
      <div class="money-row"><span>🧾 Spent<small>Supplies and costs</small></span><span>−<?= money($m['spent']) ?></span></div>
      <div class="money-row kept"><span><?= $m['kept'] >= 0 ? '😊 You kept' : '😬 Still to earn back' ?></span><span><?= money(abs($m['kept'])) ?></span></div>
    </div>
    <?php endforeach; ?>
  </div>

<?php elseif ($view === 'posts'):
    $week = post_week_of();
    $data = load_week_posts($week); ?>
  <div class="card">
    <h2 style="margin-top:0">📣 This week's posts</h2>
    <p class="muted">3 posts a week: Monday, Wednesday and Friday. They're emailed to you every Monday morning.
      Hold a picture to save it, tap 📋 to copy the words, then post it in the app.</p>
    <p class="muted"><b>Square</b> = <?= e(POST_SIZES['square']['for']) ?> · <b>Tall</b> = <?= e(POST_SIZES['tall']['for']) ?> · <b>Pin</b> = <?= e(POST_SIZES['pin']['for']) ?></p>
    <div class="actions">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="posts_make"><button><?= $data ? '🔄 Make different ones' : '✨ Make this week\'s posts' ?></button></form>
      <?php if ($data): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="posts_email"><button class="plain">📧 Email them to me</button></form><?php endif; ?>
    </div>
  </div>
  <?php foreach ($data['posts'] ?? [] as $i => $post): ?>
  <div class="card">
    <h3 style="margin-top:0"><?= e($post['day']) ?>: <?= e($post['headline']) ?></h3>
    <div class="post-pics">
      <?php foreach ($post['images'] as $size => $name): $src = '../posts/' . rawurlencode($week) . '/' . rawurlencode($name); ?>
        <a href="<?= e($src) ?>" download><img src="<?= e($src) ?>" alt="<?= e($post['headline']) ?> (<?= e($size) ?>)"><span><?= e(ucfirst($size)) ?> ⬇️</span></a>
      <?php endforeach; ?>
    </div>
    <label for="cap<?= $i ?>">Words (Instagram, TikTok, Facebook, Pinterest)</label>
    <textarea id="cap<?= $i ?>" rows="8" readonly><?= e($post['caption']) ?></textarea>
    <p><button type="button" class="copy" data-for="cap<?= $i ?>">📋 Copy words</button></p>
    <label for="short<?= $i ?>">Short version (for X)</label>
    <textarea id="short<?= $i ?>" rows="2" readonly><?= e($post['short']) ?></textarea>
    <p><button type="button" class="copy plain" data-for="short<?= $i ?>">📋 Copy short</button></p>
  </div>
  <?php endforeach; ?>
  <script>
  document.querySelectorAll('button.copy').forEach(b => b.addEventListener('click', async () => {
    const box = document.getElementById(b.dataset.for);
    try { await navigator.clipboard.writeText(box.value); } catch (e) { box.select(); document.execCommand('copy'); }
    const was = b.textContent; b.textContent = '✅ Copied!'; setTimeout(() => { b.textContent = was; }, 1500);
  }));
  </script>

<?php elseif ($view === 'reviews'):
    $byStatus = ['pending' => [], 'approved' => [], 'hidden' => []];
    foreach (db()->query('SELECT * FROM reviews ORDER BY id DESC LIMIT 500') as $r) {
        $byStatus[$r['status']][] = $r;
    }
    $titles = ['pending' => '⏳ Waiting for you', 'approved' => '✅ Showing on the website', 'hidden' => '🙈 Hidden'];
    $site = rtrim((string)cfg('site_url'), '/'); ?>
  <div class="card">
    <h2 style="margin-top:0">⭐ Reviews</h2>
    <p class="muted">New reviews wait here until you tap <b>Approve</b>. Only approved ones show on the website,
      with just a first name and one letter (like "Emma S."). Emails never show.</p>
    <p class="muted">Review page for your QR code: <b><?= e($site) ?>/review</b></p>
  </div>
  <?php foreach ($titles as $status => $title): if ($status !== 'pending' && !$byStatus[$status]) continue; ?>
  <div class="card">
    <h3 style="margin-top:0"><?= $title ?> (<?= count($byStatus[$status]) ?>)</h3>
    <?php if (!$byStatus[$status]): ?><p class="muted">No new reviews. 🎉</p><?php endif; ?>
    <?php foreach ($byStatus[$status] as $r): ?>
    <div class="review-row">
      <div class="review-stars"><?= str_repeat('★', (int)$r['stars']) . str_repeat('☆', 5 - (int)$r['stars']) ?></div>
      <b><?= e(review_product_name($r['product_id'])) ?></b>
      · <?= e($r['name']) ?> <span class="muted">(shows as "<?= e(review_display_name($r['name'])) ?>")</span>
      <?php if ($r['order_code'] !== ''): ?><span class="badge s-paid">✓ Verified buyer · <?= e($r['order_code']) ?></span><?php endif; ?>
      <p class="review-body"><?= nl2br(e($r['body'])) ?></p>
      <p class="muted"><?= e(local_time($r['created_at'])) ?><?= $r['email'] !== '' ? ' · ' . e($r['email']) : '' ?></p>
      <div class="actions">
        <?php if ($status !== 'approved'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="review_approve"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button>✅ Approve</button></form><?php endif; ?>
        <?php if ($status === 'approved'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="review_hide"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="plain">🙈 Hide</button></form><?php endif; ?>
        <form method="post" onsubmit="return confirm('Delete this review for good?')"><?= csrf_field() ?><input type="hidden" name="action" value="review_delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="plain">🗑️ Delete</button></form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>

<?php elseif ($view === 'promos'):
    $promos =db()->query('SELECT * FROM promotions ORDER BY active DESC, id DESC')->fetchAll(); ?>
  <div class="card">
    <h2 style="margin-top:0">Promotions</h2>
    <p class="muted">Each order gets the ONE promotion that saves the customer the most. Promotions without a code apply by themselves and show in the pink bar at the top of the shop.</p>
    <table>
      <tr><th>Name customers see</th><th>Deal</th><th class="hide-sm">Code</th><th class="hide-sm">Dates</th><th>Status</th><th></th></tr>
      <?php foreach ($promos as $p): ?>
      <tr>
        <td><?= e($p['label']) ?></td>
        <td><?= $p['kind'] === 'free_shipping' ? 'Free shipping' : (int)$p['percent'] . '% off' ?><?= (int)$p['min_subtotal_cents'] > 0 ? ' over ' . money((int)$p['min_subtotal_cents']) : '' ?></td>
        <td class="hide-sm"><?= $p['code'] ? e($p['code']) : '<span class="muted">automatic</span>' ?></td>
        <td class="hide-sm muted"><?= $p['starts_at'] ? e(local_time($p['starts_at'])) : 'now' ?> → <?= $p['ends_at'] ? e(local_time($p['ends_at'])) : 'forever' ?></td>
        <td><span class="badge <?= $p['active'] ? 's-paid' : 's-cancelled' ?>"><?= $p['active'] ? 'ON' : 'OFF' ?></span></td>
        <td style="white-space:nowrap">
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="promo_toggle"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="plain"><?= $p['active'] ? 'Turn off' : 'Turn on' ?></button></form>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete this promotion?')"><?= csrf_field() ?><input type="hidden" name="action" value="promo_delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="plain">Delete</button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <div class="card">
    <h3 style="margin-top:0">Add a promotion</h3>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="promo_add">
      <label for="pl">Name customers will see</label>
      <input id="pl" name="label" placeholder="Free shipping on orders over $25!" required>
      <div class="row">
        <div><label for="pk">Type of deal</label>
          <select id="pk" name="kind" onchange="document.getElementById('pctWrap').style.display = this.value === 'percent_off' ? '' : 'none'">
            <option value="free_shipping">Free shipping</option>
            <option value="percent_off">Percent off the glosses</option>
          </select></div>
        <div id="pctWrap" style="display:none"><label for="pp">Percent off</label><input id="pp" name="percent" type="number" min="1" max="100" placeholder="10"></div>
        <div><label for="pm">Order must be at least ($)</label><input id="pm" name="min" inputmode="decimal" placeholder="25.00"></div>
        <div><label for="pc">Code (leave empty = automatic)</label><input id="pc" name="promo_code" placeholder="GLOSSY"></div>
      </div>
      <div class="row">
        <div><label for="ps">Starts (optional)</label><input id="ps" type="date" name="starts_at"></div>
        <div><label for="pe">Ends (optional)</label><input id="pe" type="date" name="ends_at"></div>
      </div>
      <p><button>Add promotion</button></p>
    </form>
  </div>

<?php elseif ($view === 'customers'):
    $customers = db()->query(
        "SELECT c.email, c.name, c.created_at, COUNT(o.id) AS orders,
                SUM(CASE WHEN o.status IN ('paid','shipped','picked_up') THEN o.total_cents ELSE 0 END) AS spent
           FROM customers c LEFT JOIN orders o ON o.customer_id = c.id
          GROUP BY c.id, c.email, c.name, c.created_at ORDER BY c.id DESC LIMIT 500"
    )->fetchAll(); ?>
  <div class="card">
    <h2 style="margin-top:0">Customers (<?= count($customers) ?>)</h2>
    <table>
      <tr><th>Name</th><th>Email</th><th>Orders</th><th>Paid total</th><th class="hide-sm">First order</th></tr>
      <?php foreach ($customers as $c): ?>
      <tr><td><?= e($c['name']) ?></td><td><a href="?view=orders&q=<?= rawurlencode($c['email']) ?>"><?= e($c['email']) ?></a></td><td><?= (int)$c['orders'] ?></td><td><?= money((int)$c['spent']) ?></td><td class="hide-sm muted"><?= e(local_time($c['created_at'])) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php else:
    $tab = $_GET['tab'] ?? 'todo';
    $q = trim((string)($_GET['q'] ?? ''));
    $groups = [
        'todo' => ['Check payment', ['awaiting_payment', 'customer_says_paid']],
        'make' => ['Paid - to make & send', ['paid']],
        'done' => ['Done', ['shipped', 'picked_up']],
        'cancelled' => ['Cancelled', ['cancelled']],
        'all' => ['All', array_keys(ORDER_STATUSES)],
    ];
    if (!isset($groups[$tab])) {
        $tab = 'todo';
    }
    $statuses = $groups[$tab][1];
    $marks = implode(',', array_fill(0, count($statuses), '?'));
    $sql = "SELECT * FROM orders WHERE status IN ($marks)";
    $args = $statuses;
    if ($q !== '') {
        $sql .= ' AND (code LIKE ? OR email LIKE ? OR name LIKE ?)';
        array_push($args, "%$q%", "%$q%", "%$q%");
    }
    $sql .= ' ORDER BY id DESC LIMIT 300';
    $st = db()->prepare($sql);
    $st->execute($args);
    $orders = $st->fetchAll();
    $counts = [];
    foreach ($groups as $key => [$label, $sts]) {
        $m = implode(',', array_fill(0, count($sts), '?'));
        $c = db()->prepare("SELECT COUNT(*) FROM orders WHERE status IN ($m)");
        $c->execute($sts);
        $counts[$key] = (int)$c->fetchColumn();
    }
?>
  <div class="card">
    <div class="tabs">
      <?php foreach ($groups as $key => [$label]): ?>
        <a href="?view=orders&tab=<?= $key ?>" class="<?= $tab === $key ? 'on' : '' ?>"><?= e($label) ?> (<?= $counts[$key] ?>)</a>
      <?php endforeach; ?>
    </div>
    <form method="get" style="margin-top:12px" class="row">
      <input type="hidden" name="view" value="orders"><input type="hidden" name="tab" value="all">
      <input name="q" value="<?= e($q) ?>" placeholder="Search order code, name or email">
      <button style="flex:0 0 auto">Search</button>
    </form>
  </div>
  <div class="card">
    <?php if (!$orders): ?><p class="muted">No orders here. 🎉</p><?php else: ?>
    <table>
      <tr><th>Order</th><th>Customer</th><th>Total</th><th class="hide-sm">Pay by</th><th>Status</th><th class="hide-sm">When</th></tr>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td><a href="?order=<?= rawurlencode($o['code']) ?>"><strong><?= e($o['code']) ?></strong></a></td>
        <td><?= e($o['name']) ?><br><span class="muted"><?= e($o['delivery'] === 'pickup' ? 'Pickup' : 'Ship') ?></span></td>
        <td><?= money((int)$o['total_cents']) ?></td>
        <td class="hide-sm"><?= e(PAYMENT_METHODS[$o['payment_method']] ?? $o['payment_method']) ?></td>
        <td><span class="badge s-<?= e($o['status']) ?>"><?= e(ORDER_STATUSES[$o['status']] ?? $o['status']) ?></span></td>
        <td class="hide-sm muted"><?= e(local_time($o['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>
<?php endif; ?>
</main>
</body>
</html>
