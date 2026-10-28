<?php
// Prices, deals and payment choices for the shop pages.
require __DIR__ . '/_boot.php';

$products = [];
foreach (catalog() as $id => $p) {
    $products[$id] = ['name' => $p['name'], 'price_cents' => $p['price'], 'image' => $p['image'], 'available' => $p['available'], 'options' => $p['options'] ?? new stdClass()];
}
json_out([
    'ok' => true,
    'products' => $products,
    'shipping_cents' => (int)cfg('shipping_cents', 700),
    'promotions' => public_promotions(),
    'payment_methods' => enabled_payment_methods(),
]);
