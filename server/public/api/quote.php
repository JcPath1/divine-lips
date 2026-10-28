<?php
// Live price check while the customer fills in checkout.
require __DIR__ . '/_boot.php';

$in = json_in();
[$lines, $err] = validate_items($in['items'] ?? null);
if ($err) {
    json_error($err);
}
$delivery = ($in['delivery'] ?? 'ship') === 'pickup' ? 'pickup' : 'ship';
$price = price_order($lines, $delivery, clean_text($in['state'] ?? '', 40), clean_text($in['promo_code'] ?? '', 40));
$price['lines'] = array_map(fn($l) => ['label' => line_label($l), 'qty' => $l['qty'], 'total_cents' => $l['total']], $lines);
json_out(['ok' => true] + $price);
