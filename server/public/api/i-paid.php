<?php
// Customer taps "I sent the money". This does NOT mark it paid -
// it tells the owner to go check Venmo/Zelle.
require __DIR__ . '/_boot.php';

$in = json_in();
$order = get_order_for_viewer((string)($in['code'] ?? ''), (string)($in['token'] ?? ''));
if (!$order) {
    json_error('We could not find that order.', 404);
}
if ($order['status'] === 'awaiting_payment' && in_array($order['payment_method'], ['venmo', 'zelle'], true)) {
    $order = set_order_status($order, 'customer_says_paid', ['payment_ref' => clean_text($in['payment_ref'] ?? '', 120)]);
    $app = PAYMENT_METHODS[$order['payment_method']];
    email_owner('💸 ' . $order['code'] . ' says they paid ' . money((int)$order['total_cents']) . ' by ' . $app, $order,
        "The customer says they sent the money. Check your $app for " . money((int)$order['total_cents']) . ' with "' . $order['code'] . '" in the note, then tap Mark Paid.');
}
json_out(['ok' => true, 'order' => order_public($order)]);
