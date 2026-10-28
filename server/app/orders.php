<?php
// Saving and looking up orders and customers.

declare(strict_types=1);

const ORDER_STATUSES = [
    'awaiting_payment'   => 'Waiting for payment',
    'customer_says_paid' => 'Customer says they paid - check Venmo/Zelle',
    'paid'               => 'Paid - ready to make',
    'shipped'            => 'Shipped',
    'picked_up'          => 'Picked up',
    'cancelled'          => 'Cancelled',
];

// What the customer sees (simpler words)
const CUSTOMER_STATUSES = [
    'awaiting_payment'   => 'Waiting for your payment',
    'customer_says_paid' => 'Checking your payment',
    'paid'               => 'Paid! We are making your gloss',
    'shipped'            => 'Shipped',
    'picked_up'          => 'Picked up',
    'cancelled'          => 'Cancelled',
];

const PAYMENT_METHODS = [
    'venmo'  => 'Venmo',
    'zelle'  => 'Zelle',
    'paypal' => 'PayPal or card',
];

function enabled_payment_methods(): array
{
    $out = [];
    if (cfg('pay.venmo_username')) {
        $out[] = 'venmo';
    }
    if (cfg('pay.zelle_contact')) {
        $out[] = 'zelle';
    }
    if (paypal_enabled()) {
        $out[] = 'paypal';
    }
    return $out;
}

// Codes skip letters that look alike (0/O, 1/I/L) so they're easy to type in Venmo.
function new_order_code(): string
{
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    for ($try = 0; $try < 20; $try++) {
        $code = 'GB-';
        for ($i = 0; $i < 5; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $st = db()->prepare('SELECT 1 FROM orders WHERE code = ?');
        $st->execute([$code]);
        if (!$st->fetchColumn()) {
            return $code;
        }
    }
    throw new RuntimeException('Could not make an order code');
}

function find_or_create_customer(string $email, string $name): int
{
    $st = db()->prepare('SELECT id FROM customers WHERE email = ?');
    $st->execute([$email]);
    $id = $st->fetchColumn();
    if ($id) {
        db()->prepare('UPDATE customers SET name = ? WHERE id = ?')->execute([$name, $id]);
        return (int)$id;
    }
    db()->prepare('INSERT INTO customers (email, name, created_at) VALUES (?, ?, ?)')
        ->execute([$email, $name, now()]);
    return (int)db()->lastInsertId();
}

function create_order(array $c, array $lines, array $price, string $method): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $customerId = find_or_create_customer($c['email'], $c['name']);
        $code = new_order_code();
        $token = bin2hex(random_bytes(20));
        $pdo->prepare(
            'INSERT INTO orders (code, view_token, customer_id, email, name, phone, delivery,
                address1, address2, city, state, zip, items_json,
                subtotal_cents, discount_cents, shipping_cents, tax_cents, tax_label, total_cents,
                promo_id, promo_label, promo_savings_cents, payment_method, status, customer_note, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $code, $token, $customerId, $c['email'], $c['name'], $c['phone'], $c['delivery'],
            $c['address1'], $c['address2'], $c['city'], $c['state'], $c['zip'],
            json_encode($lines, JSON_UNESCAPED_UNICODE),
            $price['subtotal_cents'], $price['discount_cents'], $price['shipping_cents'],
            $price['tax_cents'], $price['tax_cents'] ? $price['tax_label'] : '', $price['total_cents'],
            $price['promo_id'], $price['promo_label'], $price['promo_savings_cents'],
            $method, 'awaiting_payment', $c['note'], now(),
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return get_order($code);
}

function get_order(string $code): ?array
{
    $st = db()->prepare('SELECT * FROM orders WHERE code = ?');
    $st->execute([strtoupper(trim($code))]);
    $row = $st->fetch();
    return $row ?: null;
}

// An order can be seen with its secret link, or by the logged-in customer who owns it.
function get_order_for_viewer(string $code, string $token): ?array
{
    $order = get_order($code);
    if (!$order) {
        return null;
    }
    if ($token !== '' && hash_equals($order['view_token'], $token)) {
        return $order;
    }
    start_session();
    if (!empty($_SESSION['customer_email']) && hash_equals($order['email'], $_SESSION['customer_email'])) {
        return $order;
    }
    return null;
}

function order_link(array $order): string
{
    return rtrim((string)cfg('site_url'), '/') . '/order.html?code=' . rawurlencode($order['code']) . '&t=' . $order['view_token'];
}

// The safe-to-show version of an order for the customer's browser.
function order_public(array $o): array
{
    return [
        'code' => $o['code'],
        'token' => $o['view_token'],
        'status' => $o['status'],
        'status_text' => CUSTOMER_STATUSES[$o['status']] ?? $o['status'],
        'created_at' => $o['created_at'],
        'name' => $o['name'],
        'email' => $o['email'],
        'delivery' => $o['delivery'],
        'address' => $o['delivery'] === 'ship' ? array_filter([$o['address1'], $o['address2'], trim($o['city'] . ', ' . $o['state'] . ' ' . $o['zip'])]) : [],
        'items' => array_map(fn($l) => ['label' => line_label($l), 'qty' => $l['qty'], 'unit_cents' => $l['unit'], 'total_cents' => $l['total']], json_decode($o['items_json'], true)),
        'subtotal_cents' => (int)$o['subtotal_cents'],
        'discount_cents' => (int)$o['discount_cents'],
        'shipping_cents' => (int)$o['shipping_cents'],
        'tax_cents' => (int)$o['tax_cents'],
        'tax_label' => $o['tax_label'],
        'total_cents' => (int)$o['total_cents'],
        'promo_label' => $o['promo_label'],
        'promo_savings_cents' => (int)$o['promo_savings_cents'],
        'payment_method' => $o['payment_method'],
        'payment_method_text' => PAYMENT_METHODS[$o['payment_method']] ?? $o['payment_method'],
        'tracking' => $o['tracking'],
    ];
}

function set_order_status(array $order, string $status, array $extra = []): array
{
    $fields = ['status' => $status];
    $stamp = ['customer_says_paid' => 'customer_paid_at', 'paid' => 'paid_at', 'shipped' => 'shipped_at', 'picked_up' => 'shipped_at', 'cancelled' => 'cancelled_at'];
    if (isset($stamp[$status])) {
        $fields[$stamp[$status]] = now();
    }
    $fields = array_merge($fields, $extra);
    $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($fields)));
    $values = array_values($fields);
    $values[] = $order['id'];
    db()->prepare("UPDATE orders SET $sets WHERE id = ?")->execute($values);
    $order = get_order($order['code']);
    stock_sync_order($order);
    return $order;
}

function customer_orders(string $email): array
{
    $st = db()->prepare('SELECT * FROM orders WHERE email = ? ORDER BY id DESC LIMIT 100');
    $st->execute([$email]);
    return $st->fetchAll();
}
