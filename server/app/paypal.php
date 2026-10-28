<?php
// PayPal Checkout (Orders v2 API). PayPal tells the server directly when a
// payment went through, so these orders are marked Paid automatically.
// PayPal's buttons also let shoppers pay by debit/credit card without a
// PayPal account - PayPal handles the card, not this website.

declare(strict_types=1);

function paypal_enabled(): bool
{
    return cfg('paypal.client_id') && cfg('paypal.secret');
}

function paypal_base(): string
{
    return cfg('paypal.mode') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
}

function paypal_request(string $method, string $path, ?array $body, string $auth): array
{
    $ch = curl_init(paypal_base() . $path);
    $headers = ['Content-Type: application/json', 'Authorization: ' . $auth];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string)$raw, true);
    return [$status, is_array($data) ? $data : []];
}

function paypal_access_token(): string
{
    $ch = curl_init(paypal_base() . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERPWD => cfg('paypal.client_id') . ':' . cfg('paypal.secret'),
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
    ]);
    $data = json_decode((string)curl_exec($ch), true);
    curl_close($ch);
    if (empty($data['access_token'])) {
        throw new RuntimeException('PayPal login failed - check PAYPAL_CLIENT_ID / PAYPAL_SECRET / PAYPAL_MODE');
    }
    return $data['access_token'];
}

function paypal_create_order(array $o): string
{
    [$status, $data] = paypal_request('POST', '/v2/checkout/orders', [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $o['code'],
            'invoice_id' => $o['code'],
            'description' => 'Glimmer Bliss order ' . $o['code'],
            'amount' => ['currency_code' => 'USD', 'value' => number_format((int)$o['total_cents'] / 100, 2, '.', '')],
        ]],
    ], 'Bearer ' . paypal_access_token());
    if ($status >= 300 || empty($data['id'])) {
        throw new RuntimeException('PayPal create failed: HTTP ' . $status);
    }
    return $data['id'];
}

// Returns the capture id if the money really arrived for the right amount, else null.
function paypal_capture_order(array $o): ?string
{
    [$status, $data] = paypal_request('POST', '/v2/checkout/orders/' . rawurlencode($o['paypal_order_id']) . '/capture', null, 'Bearer ' . paypal_access_token());
    if ($status >= 300 || ($data['status'] ?? '') !== 'COMPLETED') {
        error_log('PayPal capture not completed for ' . $o['code'] . ': HTTP ' . $status);
        return null;
    }
    $capture = $data['purchase_units'][0]['payments']['captures'][0] ?? [];
    $value = $capture['amount']['value'] ?? '';
    $currency = $capture['amount']['currency_code'] ?? '';
    $expected = number_format((int)$o['total_cents'] / 100, 2, '.', '');
    if ($currency !== 'USD' || $value !== $expected || ($capture['status'] ?? '') !== 'COMPLETED') {
        error_log("PayPal amount mismatch for {$o['code']}: got $value $currency, expected $expected USD");
        return null;
    }
    return (string)($capture['id'] ?? '');
}
