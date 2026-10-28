<?php
// Works out the total for an order: glosses + shipping - best promotion + Texas tax.
// Only ONE promotion is used per order: whichever saves the customer most.

declare(strict_types=1);

const PROMO_KINDS = [
    'free_shipping' => 'Free shipping',
    'percent_off'   => 'Percent off',
];

// Promotions that are switched on and inside their start/end dates.
function live_promotions(): array
{
    $now = now();
    $st = db()->prepare(
        'SELECT * FROM promotions WHERE active = 1
           AND (starts_at IS NULL OR starts_at <= ?)
           AND (ends_at IS NULL OR ends_at >= ?)
         ORDER BY id'
    );
    $st->execute([$now, $now]);
    return $st->fetchAll();
}

// Promotions anyone gets without typing a code (shown in the site banner).
function public_promotions(): array
{
    $out = [];
    foreach (live_promotions() as $p) {
        if ($p['code'] === null || $p['code'] === '') {
            $out[] = ['label' => $p['label'], 'kind' => $p['kind'], 'min_subtotal_cents' => (int)$p['min_subtotal_cents'], 'percent' => (int)$p['percent']];
        }
    }
    return $out;
}

// Texas sales tax is charged on Texas orders only: local pickup, or shipping to a
// Texas address. Orders shipped to other states pay no Texas tax.
// Shipping is taxed too, because Texas taxes delivery charges on taxable items.
function is_taxable_order(string $delivery, string $state): bool
{
    if ($delivery === 'pickup') {
        return true;
    }
    $state = strtoupper(trim(str_replace('.', '', $state)));
    return $state === 'TX' || $state === 'TEXAS';
}

// The rate is kept in basis points (825 = 8.25%) so the maths stays in whole cents.
function tax_rate_bp(): int
{
    return max(0, (int)cfg('tax_rate_bp', 825));
}

function tax_rate_text(): string
{
    return rtrim(rtrim(number_format(tax_rate_bp() / 100, 2), '0'), '.') . '%';
}

// $lines come from validate_items(). Returns the full price breakdown.
function price_order(array $lines, string $delivery, string $state = '', string $promoCode = ''): array
{
    $subtotal = 0;
    foreach ($lines as $line) {
        $subtotal += $line['total'];
    }
    $shipping = $delivery === 'ship' ? (int)cfg('shipping_cents', 700) : 0;
    $promoCode = strtoupper(trim($promoCode));

    $best = null;
    $bestSavings = 0;
    $codeMatched = false;
    foreach (live_promotions() as $p) {
        $code = strtoupper((string)($p['code'] ?? ''));
        if ($code !== '') {
            if ($promoCode === '' || !hash_equals($code, $promoCode)) {
                continue;
            }
            $codeMatched = true;
        }
        if ($subtotal < (int)$p['min_subtotal_cents']) {
            continue;
        }
        $savings = 0;
        if ($p['kind'] === 'free_shipping') {
            $savings = $shipping;
        } elseif ($p['kind'] === 'percent_off') {
            $savings = intdiv($subtotal * max(0, min(100, (int)$p['percent'])), 100);
        }
        if ($savings > $bestSavings) {
            $best = $p;
            $bestSavings = $savings;
        }
    }

    $discount = 0;
    $finalShipping = $shipping;
    if ($best !== null) {
        if ($best['kind'] === 'free_shipping') {
            $finalShipping = 0;
        } else {
            $discount = $bestSavings;
        }
    }

    $codeMessage = '';
    if ($promoCode !== '') {
        if (!$codeMatched) {
            $codeMessage = "Sorry, \"$promoCode\" isn't a working code right now.";
        } elseif ($best === null || strtoupper((string)$best['code']) !== $promoCode) {
            $codeMessage = "Code \"$promoCode\" doesn't apply to this order (check the minimum), or a better deal was already used.";
        }
    }

    // Tell shoppers how close they are to a deal they don't have yet
    $nudge = '';
    foreach (public_promotions() as $p) {
        $gap = $p['min_subtotal_cents'] - $subtotal;
        if ($gap > 0 && ($best === null || $best['kind'] !== $p['kind'])) {
            if ($p['kind'] !== 'free_shipping' || $delivery === 'ship') {
                $nudge = 'Add ' . money($gap) . ' more to get: ' . $p['label'] . '!';
                break;
            }
        }
    }

    $beforeTax = $subtotal - $discount + $finalShipping;
    $tax = is_taxable_order($delivery, $state) ? intdiv($beforeTax * tax_rate_bp() + 5000, 10000) : 0;

    return [
        'subtotal_cents' => $subtotal,
        'shipping_before_promo_cents' => $shipping,
        'shipping_cents' => $finalShipping,
        'discount_cents' => $discount,
        'tax_cents' => $tax,
        'tax_label' => 'Texas sales tax (' . tax_rate_text() . ')',
        'total_cents' => $beforeTax + $tax,
        'promo_id' => $best ? (int)$best['id'] : null,
        'promo_label' => $best ? $best['label'] : '',
        'promo_savings_cents' => $bestSavings,
        'code_message' => $codeMessage,
        'nudge' => $nudge,
    ];
}
