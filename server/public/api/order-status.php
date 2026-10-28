<?php
// One order + how to pay for it. Needs the order's secret link (or the owner logged in).
require __DIR__ . '/_boot.php';

$order = get_order_for_viewer((string)($_GET['code'] ?? ''), (string)($_GET['t'] ?? ''));
if (!$order) {
    json_error('We could not find that order. Check the link in your email.', 404);
}
json_out([
    'ok' => true,
    'order' => order_public($order),
    'pay' => [
        'venmo_username' => (string)cfg('pay.venmo_username'),
        'zelle_contact' => (string)cfg('pay.zelle_contact'),
        'zelle_name' => (string)cfg('pay.zelle_name'),
        'paypal_client_id' => paypal_enabled() ? (string)cfg('paypal.client_id') : '',
    ],
]);
