<?php
// The ONE true price list. The website asks the server for prices, and
// every order is priced here - so nobody can change a price in their browser.
//
// To add a gloss: copy a line, change the id (must match the page name in
// docs/products/), name, price (in cents: 1200 = $12.00) and image.
// To mark one sold out: set 'available' => false.

declare(strict_types=1);

function catalog(): array
{
    return [
        'pink-paradise' => ['name' => 'Pink Paradise', 'price' => 1200, 'image' => 'images/pink-paradise.jpg', 'available' => true],
        'true-love'     => ['name' => 'True Love',     'price' => 1200, 'image' => 'images/true-love.jpg',     'available' => true],
        'gloss-angeles' => ['name' => 'Gloss Angeles', 'price' => 1200, 'image' => 'images/gloss-angeles.jpg', 'available' => true],
        'sugar-cookie'  => ['name' => 'Sugar Cookie',  'price' => 1200, 'image' => 'images/sugar-cookie.jpg',  'available' => true],
        'glimmer-kiss'  => [
            'name' => 'Glimmer Kiss', 'price' => 1500, 'image' => 'images/glimmer-kiss.jpg', 'available' => true,
            'options' => ['glitter' => ['With glitter', 'No glitter']],
        ],
    ];
}

const MAX_QTY_PER_LINE = 20;
const MAX_LINES = 30;

// Turns what the browser sent into clean order lines, or returns an error.
// Returns [lines, error]. Each line: id, name, options, qty, unit, total.
function validate_items($items): array
{
    if (!is_array($items) || count($items) === 0) {
        return [[], 'Your cart is empty.'];
    }
    if (count($items) > MAX_LINES) {
        return [[], 'That cart is too big for one order. Please contact us.'];
    }
    $catalog = catalog();
    $lines = [];
    foreach ($items as $item) {
        $id = is_array($item) ? (string)($item['id'] ?? '') : '';
        $qty = is_array($item) ? (int)($item['qty'] ?? 0) : 0;
        if (!isset($catalog[$id])) {
            return [[], 'One of the glosses in your cart is no longer sold. Please remove it.'];
        }
        $product = $catalog[$id];
        if (!$product['available']) {
            return [[], $product['name'] . ' is sold out right now. Please remove it from your cart.'];
        }
        if ($qty < 1 || $qty > MAX_QTY_PER_LINE) {
            return [[], 'Please pick between 1 and ' . MAX_QTY_PER_LINE . ' of each gloss.'];
        }
        $options = [];
        foreach ($product['options'] ?? [] as $optName => $choices) {
            $picked = (string)($item['options'][$optName] ?? $choices[0]);
            if (!in_array($picked, $choices, true)) {
                $picked = $choices[0];
            }
            $options[$optName] = $picked;
        }
        // Same gloss + same choices = one line with a bigger number
        $key = $id . '|' . json_encode($options);
        if (isset($lines[$key])) {
            $lines[$key]['qty'] = min(MAX_QTY_PER_LINE, $lines[$key]['qty'] + $qty);
        } else {
            $lines[$key] = ['id' => $id, 'name' => $product['name'], 'options' => $options, 'qty' => $qty, 'unit' => $product['price']];
        }
    }
    $out = [];
    foreach ($lines as $line) {
        $line['total'] = $line['unit'] * $line['qty'];
        $out[] = $line;
    }
    return [$out, null];
}

function line_label(array $line): string
{
    $label = $line['name'];
    if (!empty($line['options'])) {
        $label .= ' (' . implode(', ', $line['options']) . ')';
    }
    return $label;
}
