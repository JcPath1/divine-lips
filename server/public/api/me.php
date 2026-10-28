<?php
// The logged-in customer's details and past orders. POST {"logout":true} logs out.
require __DIR__ . '/_boot.php';

start_session();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $in = json_in();
    if (!empty($in['logout'])) {
        unset($_SESSION['customer_email']);
        session_regenerate_id(true);
    }
    json_out(['ok' => true, 'logged_in' => false]);
}

$email = $_SESSION['customer_email'] ?? '';
if ($email === '') {
    json_out(['ok' => true, 'logged_in' => false]);
}
$orders = customer_orders($email);
$last = $orders[0] ?? null;
json_out([
    'ok' => true,
    'logged_in' => true,
    'email' => $email,
    // Used to fill in checkout for returning customers
    'saved' => $last ? [
        'name' => $last['name'], 'phone' => $last['phone'],
        'address1' => $last['address1'], 'address2' => $last['address2'],
        'city' => $last['city'], 'state' => $last['state'], 'zip' => $last['zip'],
    ] : null,
    'orders' => array_map('order_public', $orders),
]);
