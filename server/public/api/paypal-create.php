<?php
// Starts a PayPal payment for an order (called by the PayPal button).
require __DIR__ . '/_boot.php';

$in = json_in();
$order = get_order_for_viewer((string)($in['code'] ?? ''), (string)($in['token'] ?? ''));
if (!$order || $order['payment_method'] !== 'paypal' || $order['status'] !== 'awaiting_payment') {
    json_error('This order cannot be paid with PayPal right now.');
}
try {
    $id = paypal_create_order($order);
} catch (Throwable $e) {
    error_log($e->getMessage());
    json_error('PayPal is not working right now. Please try again in a few minutes.', 502);
}
db()->prepare('UPDATE orders SET paypal_order_id = ? WHERE id = ?')->execute([$id, $order['id']]);
json_out(['ok' => true, 'id' => $id]);
