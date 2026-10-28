<?php
// Finishes a PayPal payment and marks the order Paid only if PayPal says
// the right amount of money arrived.
require __DIR__ . '/_boot.php';

$in = json_in();
$order = get_order_for_viewer((string)($in['code'] ?? ''), (string)($in['token'] ?? ''));
if (!$order || $order['paypal_order_id'] === '') {
    json_error('We could not find that PayPal payment.');
}
if ($order['status'] === 'paid') {
    json_out(['ok' => true, 'order' => order_public($order)]);
}
try {
    $captureId = paypal_capture_order($order);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $captureId = null;
}
if ($captureId === null) {
    json_error('The payment did not go through. Please try again or pick another way to pay.', 402);
}
$order = set_order_status($order, 'paid', ['payment_ref' => 'PayPal ' . $captureId]);
email_payment_received($order);
email_owner('✅ PAID by PayPal: ' . $order['code'] . ' - ' . money((int)$order['total_cents']), $order, 'PayPal confirmed this payment. Time to make the gloss!');
json_out(['ok' => true, 'order' => order_public($order)]);
