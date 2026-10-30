<?php
// The words in every email the shop sends.

declare(strict_types=1);

// Plain-text payment steps for one order (also used on the receipt).
function payment_steps_text(array $o): string
{
    $amount = money((int)$o['total_cents']);
    $code = $o['code'];
    switch ($o['payment_method']) {
        case 'venmo':
            return "Send $amount on Venmo to @" . cfg('pay.venmo_username') . ".\n"
                . "In the note, type your order code: $code\n"
                . "(Tap the Pay button on your order page, or scan our Venmo QR code.)";
        case 'zelle':
            return "Send $amount with Zelle (in your bank's app) to " . cfg('pay.zelle_contact')
                . (cfg('pay.zelle_name') ? ' (' . cfg('pay.zelle_name') . ')' : '') . ".\n"
                . "In the memo, type your order code: $code";
        case 'paypal':
            return "Pay $amount with PayPal or a card using the button on your order page.";
    }
    return '';
}

function invoice_rows_text(array $o): string
{
    $out = '';
    foreach (json_decode($o['items_json'], true) as $l) {
        $out .= sprintf("  %d x %s  %s\n", $l['qty'], line_label($l), money($l['total']));
    }
    $out .= "\n  Glosses:  " . money((int)$o['subtotal_cents']) . "\n";
    if ((int)$o['discount_cents'] > 0) {
        $out .= "  Discount: -" . money((int)$o['discount_cents']) . "\n";
    }
    if ($o['delivery'] === 'pickup') {
        $out .= "  Delivery: Local pickup - FREE\n";
    } else {
        $out .= "  Shipping: " . ((int)$o['shipping_cents'] === 0 ? 'FREE' : money((int)$o['shipping_cents'])) . "\n";
    }
    if ((int)$o['tax_cents'] > 0) {
        $out .= "  " . ($o['tax_label'] ?: 'Sales tax') . ": " . money((int)$o['tax_cents']) . "\n";
    }
    if ($o['promo_label'] !== '') {
        $out .= "  Promotion used: " . $o['promo_label'] . " (you saved " . money((int)$o['promo_savings_cents']) . ")\n";
    }
    $out .= "  TOTAL:    " . money((int)$o['total_cents']) . "\n";
    return $out;
}

function invoice_rows_html(array $o): string
{
    $rows = '';
    foreach (json_decode($o['items_json'], true) as $l) {
        $rows .= '<tr><td>' . (int)$l['qty'] . ' &times; ' . e(line_label($l)) . '</td><td align="right">' . money($l['total']) . '</td></tr>';
    }
    $rows .= '<tr><td style="border-top:1px solid #eee">Glosses</td><td align="right" style="border-top:1px solid #eee">' . money((int)$o['subtotal_cents']) . '</td></tr>';
    if ((int)$o['discount_cents'] > 0) {
        $rows .= '<tr><td>Discount</td><td align="right">-' . money((int)$o['discount_cents']) . '</td></tr>';
    }
    $ship = $o['delivery'] === 'pickup' ? 'Local pickup - FREE' : ((int)$o['shipping_cents'] === 0 ? 'FREE' : money((int)$o['shipping_cents']));
    $rows .= '<tr><td>' . ($o['delivery'] === 'pickup' ? 'Delivery' : 'Shipping') . '</td><td align="right">' . e($ship) . '</td></tr>';
    if ((int)$o['tax_cents'] > 0) {
        $rows .= '<tr><td>' . e($o['tax_label'] ?: 'Sales tax') . '</td><td align="right">' . money((int)$o['tax_cents']) . '</td></tr>';
    }
    if ($o['promo_label'] !== '') {
        $rows .= '<tr><td colspan="2" style="color:#c2185b">🎉 ' . e($o['promo_label']) . ' - you saved ' . money((int)$o['promo_savings_cents']) . '</td></tr>';
    }
    $rows .= '<tr><td><strong>Total</strong></td><td align="right"><strong>' . money((int)$o['total_cents']) . '</strong></td></tr>';
    return '<table cellpadding="6" cellspacing="0" width="100%" style="max-width:480px;font-size:15px">' . $rows . '</table>';
}

function email_wrap(string $inner): string
{
    return '<div style="font-family:Arial,sans-serif;color:#333;max-width:560px;margin:auto">'
        . '<h2 style="color:#e91e63;margin-bottom:4px">Glimmer Bliss ✨</h2>'
        . $inner
        . '<p style="color:#999;font-size:12px;margin-top:30px">Handmade lip gloss by Charlie Mae · ' . e((string)cfg('site_url')) . '</p></div>';
}

function email_order_received(array $o): void
{
    $link = order_link($o);
    $steps = payment_steps_text($o);
    $text = "Hi {$o['name']}!\n\nThank you for your order! Your order code is {$o['code']}.\n\n"
        . invoice_rows_text($o)
        . "\nHOW TO PAY\n$steps\n\nSee your order and pay here:\n$link\n\n"
        . "We start making your gloss as soon as your payment arrives.\n\nGlimmer Bliss";
    $html = email_wrap(
        '<p>Hi ' . e($o['name']) . '!</p><p>Thank you for your order! Your order code is <strong style="font-size:18px">' . e($o['code']) . '</strong>.</p>'
        . invoice_rows_html($o)
        . '<h3>How to pay</h3><p>' . nl2br(e($steps)) . '</p>'
        . '<p><a href="' . e($link) . '" style="background:#e91e63;color:#fff;padding:12px 22px;border-radius:24px;text-decoration:none;display:inline-block">See my order &amp; pay</a></p>'
        . '<p>We start making your gloss as soon as your payment arrives.</p>'
    );
    send_mail($o['email'], "Your Glimmer Bliss order {$o['code']}", $html, $text, (string)cfg('mail.from'));
}

function email_payment_received(array $o): void
{
    $link = order_link($o);
    $next = $o['delivery'] === 'pickup' ? "We'll email you when it's ready to pick up." : "We'll email you when it ships.";
    $review = review_link($o);
    $text = "Hi {$o['name']}!\n\nWe got your payment for order {$o['code']} - thank you!\n\n" . invoice_rows_text($o) . "\n$next\n\n$link\n\n"
        . "Once you try your gloss, we'd love a review:\n$review\n\nGlimmer Bliss";
    $html = email_wrap('<p>Hi ' . e($o['name']) . '!</p><p>We got your payment for order <strong>' . e($o['code']) . '</strong> - thank you! 💖</p>'
        . invoice_rows_html($o) . '<p>' . e($next) . '</p><p><a href="' . e($link) . '">View your order</a></p>'
        . '<p>Once you try your gloss, we\'d love a review! <a href="' . e($review) . '">Leave a review ⭐</a></p>');
    send_mail($o['email'], "Payment received - order {$o['code']}", $html, $text, (string)cfg('mail.from'));
}

function email_shipped(array $o): void
{
    $link = order_link($o);
    if ($o['status'] === 'picked_up') {
        $line = 'Your order was picked up. Enjoy your gloss!';
    } else {
        $line = 'Your order is on its way!' . ($o['tracking'] !== '' ? ' Tracking number: ' . $o['tracking'] : '');
    }
    $review = review_link($o);
    $text = "Hi {$o['name']}!\n\n$line\n\nOrder {$o['code']}\n$link\n\nLove your gloss? Leave a review:\n$review\n\nThank you for shopping with Glimmer Bliss!";
    $html = email_wrap('<p>Hi ' . e($o['name']) . '!</p><p>' . e($line) . '</p><p>Order <strong>' . e($o['code']) . '</strong> · <a href="' . e($link) . '">view it here</a></p>'
        . '<p>Love your gloss? <a href="' . e($review) . '">Leave a review ⭐</a></p><p>Thank you for shopping with Glimmer Bliss!</p>');
    $subject = $o['status'] === 'picked_up' ? "Thanks for picking up order {$o['code']}" : "Your order {$o['code']} has shipped!";
    send_mail($o['email'], $subject, $html, $text, (string)cfg('mail.from'));
}

function email_login_link(string $email, string $link): void
{
    $text = "Here is your link to see your Glimmer Bliss orders:\n\n$link\n\nIt works once and stops working in 20 minutes.\nIf you didn't ask for this, you can ignore this email.";
    $html = email_wrap('<p>Here is your link to see your Glimmer Bliss orders:</p>'
        . '<p><a href="' . e($link) . '" style="background:#e91e63;color:#fff;padding:12px 22px;border-radius:24px;text-decoration:none;display:inline-block">Open my orders</a></p>'
        . '<p style="color:#777">It works once and stops working in 20 minutes. If you didn\'t ask for this, you can ignore this email.</p>');
    send_mail($email, 'Your Glimmer Bliss login link', $html, $text);
}

// Alerts to the shop owner
function email_owner(string $subject, array $o, string $headline): void
{
    $to = (string)cfg('mail.owner_alert');
    if ($to === '') {
        return;
    }
    $admin = rtrim((string)cfg('site_url'), '/') . '/admin/?order=' . rawurlencode($o['code']);
    $who = "{$o['name']} <{$o['email']}>" . ($o['phone'] !== '' ? " · {$o['phone']}" : '');
    $where = $o['delivery'] === 'pickup' ? 'LOCAL PICKUP' : "SHIP TO: {$o['address1']} {$o['address2']}, {$o['city']}, {$o['state']} {$o['zip']}";
    $text = "$headline\n\nOrder {$o['code']} - " . money((int)$o['total_cents']) . ' by ' . (PAYMENT_METHODS[$o['payment_method']] ?? $o['payment_method'])
        . "\n$who\n$where\n\n" . invoice_rows_text($o)
        . ($o['customer_note'] !== '' ? "\nCustomer note: {$o['customer_note']}\n" : '')
        . "\nOpen in admin: $admin";
    send_mail($to, $subject, '<pre style="font-family:Arial,sans-serif;font-size:14px">' . e($text) . '</pre>', $text, $o['email']);
}
