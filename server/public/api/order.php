<?php
// Places an order. Prices are worked out HERE, never trusted from the browser.
require __DIR__ . '/_boot.php';

$in = json_in();
$ip = client_ip();
if (too_many('order', $ip, 10, 3600)) {
    json_error('Too many orders from here in the last hour. Please email us.', 429);
}

// Bots fill in every box, including this hidden one. People never see it.
if (clean_text($in['website'] ?? '', 200) !== '') {
    json_error('Bad request.');
}

[$lines, $err] = validate_items($in['items'] ?? null);
if ($err) {
    json_error($err);
}

$c = [
    'name' => clean_text($in['name'] ?? '', 120),
    'email' => clean_email($in['email'] ?? ''),
    'phone' => clean_text($in['phone'] ?? '', 40),
    'delivery' => ($in['delivery'] ?? '') === 'pickup' ? 'pickup' : 'ship',
    'address1' => clean_text($in['address1'] ?? '', 160),
    'address2' => clean_text($in['address2'] ?? '', 160),
    'city' => clean_text($in['city'] ?? '', 80),
    'state' => clean_text($in['state'] ?? '', 40),
    'zip' => clean_text($in['zip'] ?? '', 20),
    'note' => clean_text($in['note'] ?? '', 500),
];
if ($c['name'] === '') {
    json_error('Please type your name.');
}
if ($c['email'] === null) {
    json_error('Please type a real email address. Your receipt goes there.');
}
if ($c['delivery'] === 'ship') {
    if ($c['address1'] === '' || $c['city'] === '' || $c['state'] === '' || !preg_match('/^\d{5}(-\d{4})?$/', $c['zip'])) {
        json_error('Please fill in your full shipping address (street, city, state, 5-digit ZIP).');
    }
} else {
    $c['address1'] = $c['address2'] = $c['city'] = $c['state'] = $c['zip'] = '';
}

$method = (string)($in['payment_method'] ?? '');
if (!in_array($method, enabled_payment_methods(), true)) {
    json_error('Please pick how you will pay.');
}

$price = price_order($lines, $c['delivery'], $c['state'], clean_text($in['promo_code'] ?? '', 40));
note_attempt('order', $ip);
$order = create_order($c, $lines, $price, $method);

email_order_received($order);
email_owner('🛍️ New order ' . $order['code'] . ' - ' . money((int)$order['total_cents']), $order, 'NEW ORDER! It is waiting for payment.');

json_out(['ok' => true, 'code' => $order['code'], 'token' => $order['view_token']]);
