<?php
// Text ledger door. Only tools/ledger.py talks to this, with a secret token.
// With no token set in the shop's settings, this door stays shut.
require __DIR__ . '/_boot.php';

$in = json_in();
$secret = (string)cfg('ledger_token', '');
$ip = client_ip();
if ($secret === '' || strlen($secret) < 24) {
    json_error('The text ledger is not switched on.', 403);
}
if (too_many('ledger', $ip, 10, 900)) {
    json_error('Too many tries. Wait 15 minutes.', 429);
}
if (!hash_equals($secret, (string)($in['token'] ?? ''))) {
    note_attempt('ledger', $ip);
    json_error('Not allowed.', 403);
}

$action = (string)($in['action'] ?? '');

// The backup reads only, and runs before the ledger table is ever made.
if ($action === 'export') {
    json_out(['ok' => true] + ledger_export());
}
if ($action === 'ping') {
    json_out(['ok' => true, 'ledger_table' => ledger_exists(), 'glosses' => array_values(array_map(
        fn($it) => ['key' => $it['key'], 'name' => $it['name'], 'price_cents' => $it['price']], stock_items()))]);
}

$actions = ['made' => 'ledger_made', 'sale' => 'ledger_sale', 'expense' => 'ledger_expense', 'count' => 'ledger_count', 'undo' => 'ledger_undo'];
try {
    if (isset($actions[$action])) {
        ledger_migrate();
        json_out(['ok' => true, 'entry' => $actions[$action]($in)]);
    }
    if ($action === 'summary') {
        ledger_migrate();
        json_out(['ok' => true, 'summary' => ledger_summary()]);
    }
    if ($action === 'recent') {
        ledger_migrate();
        json_out(['ok' => true, 'entries' => ledger_recent((int)($in['n'] ?? 20))]);
    }
} catch (LedgerRefused $e) {
    json_out(['ok' => false, 'refused' => true, 'error' => $e->getMessage()], 200);
}
json_error('Unknown action.');
