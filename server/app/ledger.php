<?php
// Text ledger: entries that arrive as a plain message through Phronesis
// ("made 24 Pink Paradise", "sold 4 Pink Paradise at the show, $48 cash", "spent $56.50 on bottles").
//
// Rules (JC, 14 Nov 2026):
// - One stock count: every entry moves the same stock_moves list the Sell screen and web orders use.
// - Nothing is ever deleted. A wrong entry is reversed by an "undo" entry, and both stay in the history.
// - Stock is never pushed below zero. The entry is refused and the reason is sent back instead.
// - Every entry keeps who sent it, when, and the words they sent.
// This file only adds its own table ("ledger"). It does not change the orders, checkout or payment code.

declare(strict_types=1);

const LEDGER_CHANNELS = ['show' => 'Shows', 'web' => 'Website', 'tiktok' => 'TikTok Shop', 'other' => 'Other'];
const LEDGER_PAY = ['cash' => 'Cash', 'venmo' => 'Venmo', 'card' => 'Card', 'zelle' => 'Zelle', 'paypal' => 'PayPal', 'tiktok' => 'TikTok payout', 'other' => 'Other'];
const LEDGER_EXPENSE_KINDS = ['supplies' => 'Supplies', 'bottles' => 'Bottles', 'labels' => 'Labels', 'hosting' => 'Hosting',
    'show_fees' => 'Show fees', 'ads' => 'Ads', 'shipping' => 'Shipping', 'other' => 'Other'];

class LedgerRefused extends RuntimeException
{
}

function ledger_migrate(): void
{
    $sqlite = db_is_sqlite();
    $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
    $tail = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    db()->exec("CREATE TABLE IF NOT EXISTS ledger (
        id $id,
        kind VARCHAR(10) NOT NULL,
        lines_json TEXT NOT NULL,
        channel VARCHAR(10) NOT NULL DEFAULT '',
        pay_method VARCHAR(10) NOT NULL DEFAULT '',
        total_cents INT NOT NULL DEFAULT 0,
        tax_cents INT NOT NULL DEFAULT 0,
        expense_kind VARCHAR(20) NOT NULL DEFAULT '',
        donated INT NOT NULL DEFAULT 0,
        what VARCHAR(200) NOT NULL DEFAULT '',
        happened_on DATE NOT NULL,
        sender VARCHAR(60) NOT NULL,
        source_text VARCHAR(500) NOT NULL DEFAULT '',
        undoes INT NULL,
        undone_by INT NULL,
        created_at DATETIME NOT NULL
    )$tail");
}

function ledger_exists(): bool
{
    try {
        db()->query('SELECT 1 FROM ledger LIMIT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function ledger_date($value): string
{
    $d = (string)$value;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) || strtotime($d) === false) {
        return local_date(now());
    }
    if ($d > local_date(now())) {
        throw new LedgerRefused("The date $d is in the future.");
    }
    return $d;
}

function ledger_sender($value): string
{
    $s = clean_text($value, 60);
    if ($s === '') {
        throw new LedgerRefused('Who sent this? The sender name is missing.');
    }
    return $s;
}

// [['key' => stock key, 'qty' => n], ...] -> checked lines with names and prices
function ledger_lines($items, bool $allowZero = false): array
{
    $all = stock_items();
    $lines = [];
    foreach (is_array($items) ? $items : [] as $it) {
        $key = (string)($it['key'] ?? '');
        $qty = (int)($it['qty'] ?? 0);
        if (!isset($all[$key])) {
            throw new LedgerRefused("I do not know the gloss \"$key\".");
        }
        if ($qty < ($allowZero ? 0 : 1) || $qty > 1000) {
            throw new LedgerRefused('The number for ' . $all[$key]['name'] . ' must be between ' . ($allowZero ? 0 : 1) . ' and 1000.');
        }
        if (isset($lines[$key])) {
            throw new LedgerRefused($all[$key]['name'] . ' is listed twice.');
        }
        $lines[$key] = ['key' => $key, 'name' => $all[$key]['name'], 'qty' => $qty, 'unit' => $all[$key]['price']];
    }
    if (!$lines) {
        throw new LedgerRefused('No gloss was named.');
    }
    return array_values($lines);
}

function ledger_insert(array $row): int
{
    $row += ['lines_json' => '[]', 'channel' => '', 'pay_method' => '', 'total_cents' => 0, 'tax_cents' => 0, 'expense_kind' => '',
        'donated' => 0, 'what' => '', 'undoes' => null];
    db()->prepare('INSERT INTO ledger (kind, lines_json, channel, pay_method, total_cents, tax_cents, expense_kind, donated, what,
                       happened_on, sender, source_text, undoes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$row['kind'], $row['lines_json'], $row['channel'], $row['pay_method'], $row['total_cents'], $row['tax_cents'],
            $row['expense_kind'], $row['donated'], $row['what'], $row['happened_on'], $row['sender'],
            clean_text($row['source_text'] ?? '', 500), $row['undoes'], now()]);
    return (int)db()->lastInsertId();
}

function ledger_get(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM ledger WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function ledger_tx(callable $fn)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $out = $fn();
        $pdo->commit();
        return $out;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function ledger_stock_after(array $lines): array
{
    $counts = stock_counts();
    $out = [];
    foreach ($lines as $l) {
        $out[] = ['name' => $l['name'], 'in_stock' => $counts[$l['key']] ?? 0];
    }
    return $out;
}

// "made 24 Pink Paradise"
function ledger_made(array $in): array
{
    $lines = ledger_lines($in['items'] ?? null);
    $sender = ledger_sender($in['sender'] ?? '');
    $date = ledger_date($in['date'] ?? '');
    $id = ledger_tx(function () use ($lines, $sender, $date, $in) {
        $id = ledger_insert(['kind' => 'made', 'lines_json' => json_encode($lines, JSON_UNESCAPED_UNICODE), 'happened_on' => $date,
            'sender' => $sender, 'source_text' => $in['text'] ?? '']);
        foreach ($lines as $l) {
            stock_change($l['key'], $l['qty'], 'text-made', 'L' . $id);
        }
        return $id;
    });
    return ['id' => $id, 'kind' => 'made', 'lines' => $lines, 'date' => $date, 'sender' => $sender, 'stock' => ledger_stock_after($lines)];
}

// "sold 4 Pink Paradise at the show, $48 cash"
function ledger_sale(array $in): array
{
    $lines = ledger_lines($in['items'] ?? null);
    $sender = ledger_sender($in['sender'] ?? '');
    $date = ledger_date($in['date'] ?? '');
    $channel = (string)($in['channel'] ?? '');
    $pay = (string)($in['pay'] ?? '');
    $total = (int)($in['total_cents'] ?? -1);
    if (!isset(LEDGER_CHANNELS[$channel])) {
        throw new LedgerRefused('Where was it sold? Say show, web, TikTok or other.');
    }
    if (!isset(LEDGER_PAY[$pay])) {
        throw new LedgerRefused('How did they pay? Say cash, Venmo, card, Zelle, PayPal, TikTok or other.');
    }
    if ($total < 0 || $total > 10000000) {
        throw new LedgerRefused('How much money came in for this sale?');
    }
    $counts = stock_counts();
    $short = [];
    foreach ($lines as $l) {
        if (($counts[$l['key']] ?? 0) < $l['qty']) {
            $short[] = $l['name'] . ': ' . ($counts[$l['key']] ?? 0) . ' in stock, ' . $l['qty'] . ' sold';
        }
    }
    if ($short) {
        throw new LedgerRefused('This would put stock below zero (' . implode('; ', $short)
            . '). Nothing was recorded. Fix the count first ("count: 30 Pink Paradise") or record the batch that was made.');
    }
    // Show and in-person prices include Texas tax, the same as the Sell screen. An explicit tax amount wins.
    $tax = isset($in['tax_cents']) ? max(0, min($total, (int)$in['tax_cents'])) : (in_array($channel, ['show', 'web'], true) ? tax_inside($total) : 0);
    $id = ledger_tx(function () use ($lines, $sender, $date, $channel, $pay, $total, $tax, $in) {
        $id = ledger_insert(['kind' => 'sale', 'lines_json' => json_encode($lines, JSON_UNESCAPED_UNICODE), 'channel' => $channel,
            'pay_method' => $pay, 'total_cents' => $total, 'tax_cents' => $tax, 'happened_on' => $date, 'sender' => $sender,
            'source_text' => $in['text'] ?? '']);
        foreach ($lines as $l) {
            stock_change($l['key'], -$l['qty'], 'text-sale', 'L' . $id);
        }
        return $id;
    });
    $list = 0;
    foreach ($lines as $l) {
        $list += $l['qty'] * $l['unit'];
    }
    return ['id' => $id, 'kind' => 'sale', 'lines' => $lines, 'channel' => $channel, 'pay' => $pay, 'total_cents' => $total,
        'tax_cents' => $tax, 'tax_worked_out' => isset($in['tax_cents']) || in_array($channel, ['show', 'web'], true),
        'list_price_cents' => $list, 'date' => $date, 'sender' => $sender, 'stock' => ledger_stock_after($lines)];
}

// "spent $56.50 on bottles"  /  "donated: 2 oz squalane from Grandma"
function ledger_expense(array $in): array
{
    $sender = ledger_sender($in['sender'] ?? '');
    $date = ledger_date($in['date'] ?? '');
    $what = clean_text($in['what'] ?? '', 200);
    $kind = (string)($in['expense_kind'] ?? '');
    $donated = !empty($in['donated']) ? 1 : 0;
    $amount = (int)($in['amount_cents'] ?? -1);
    if ($what === '') {
        throw new LedgerRefused('What was it?');
    }
    if (!isset(LEDGER_EXPENSE_KINDS[$kind])) {
        throw new LedgerRefused('What kind of cost is it? Say supplies, bottles, labels, hosting, show fees, ads, shipping or other.');
    }
    if ($donated) {
        $amount = 0;                         // donated items cost the business nothing
    } elseif ($amount <= 0 || $amount > 10000000) {
        throw new LedgerRefused('How much did it cost?');
    }
    $id = ledger_insert(['kind' => 'expense', 'total_cents' => $amount, 'expense_kind' => $kind, 'donated' => $donated, 'what' => $what,
        'happened_on' => $date, 'sender' => $sender, 'source_text' => $in['text'] ?? '']);
    return ['id' => $id, 'kind' => 'expense', 'what' => $what, 'expense_kind' => $kind, 'donated' => (bool)$donated,
        'amount_cents' => $amount, 'date' => $date, 'sender' => $sender];
}

// "count: 30 Pink Paradise" - sets the shelf count to what was counted
function ledger_count(array $in): array
{
    $lines = ledger_lines($in['items'] ?? null, true);
    $sender = ledger_sender($in['sender'] ?? '');
    $date = ledger_date($in['date'] ?? '');
    $before = stock_counts();
    foreach ($lines as &$l) {
        $l['was'] = $before[$l['key']] ?? 0;
        $l['change'] = $l['qty'] - $l['was'];
    }
    unset($l);
    $id = ledger_tx(function () use ($lines, $sender, $date, $in) {
        $id = ledger_insert(['kind' => 'count', 'lines_json' => json_encode($lines, JSON_UNESCAPED_UNICODE), 'happened_on' => $date,
            'sender' => $sender, 'source_text' => $in['text'] ?? '']);
        foreach ($lines as $l) {
            stock_change($l['key'], $l['change'], 'text-count', 'L' . $id);
        }
        return $id;
    });
    return ['id' => $id, 'kind' => 'count', 'lines' => $lines, 'date' => $date, 'sender' => $sender, 'stock' => ledger_stock_after($lines)];
}

// "undo 12" - reverses entry 12. Both entries stay in the history.
function ledger_undo(array $in): array
{
    $sender = ledger_sender($in['sender'] ?? '');
    $orig = ledger_get((int)($in['id'] ?? 0));
    if (!$orig) {
        throw new LedgerRefused('There is no entry with that number.');
    }
    if ($orig['kind'] === 'undo') {
        throw new LedgerRefused('Entry ' . $orig['id'] . ' is itself an undo. Send the entry again instead.');
    }
    if ($orig['undone_by']) {
        throw new LedgerRefused('Entry ' . $orig['id'] . ' was already undone (by entry ' . $orig['undone_by'] . ').');
    }
    $st = db()->prepare('SELECT item_key, SUM(change_qty) AS n FROM stock_moves WHERE ref = ? GROUP BY item_key');
    $st->execute(['L' . $orig['id']]);
    $moves = $st->fetchAll();
    $counts = stock_counts();
    $names = stock_items();
    $short = [];
    foreach ($moves as $m) {
        if (($counts[$m['item_key']] ?? 0) - (int)$m['n'] < 0) {
            $short[] = ($names[$m['item_key']]['name'] ?? $m['item_key']) . ': ' . ($counts[$m['item_key']] ?? 0) . ' in stock';
        }
    }
    if ($short) {
        throw new LedgerRefused('Undoing entry ' . $orig['id'] . ' would put stock below zero (' . implode('; ', $short) . '). Nothing was changed.');
    }
    $reason = ['made' => 'text-made', 'sale' => 'text-sale', 'count' => 'text-count'][$orig['kind']] ?? 'text-undo';
    $id = ledger_tx(function () use ($orig, $moves, $sender, $reason, $in) {
        $id = ledger_insert(['kind' => 'undo', 'happened_on' => local_date(now()), 'sender' => $sender, 'undoes' => (int)$orig['id'],
            'what' => 'Undo of entry ' . $orig['id'], 'source_text' => $in['text'] ?? '']);
        foreach ($moves as $m) {
            stock_change($m['item_key'], -(int)$m['n'], $reason, 'L' . $id);
        }
        db()->prepare('UPDATE ledger SET undone_by = ? WHERE id = ? AND undone_by IS NULL')->execute([$id, $orig['id']]);
        return $id;
    });
    $lines = json_decode($orig['lines_json'], true) ?: [];
    return ['id' => $id, 'kind' => 'undo', 'undoes' => ledger_public($orig), 'sender' => $sender, 'stock' => ledger_stock_after($lines)];
}

function ledger_public(array $r): array
{
    return ['id' => (int)$r['id'], 'kind' => $r['kind'], 'lines' => json_decode($r['lines_json'], true) ?: [], 'channel' => $r['channel'],
        'pay' => $r['pay_method'], 'total_cents' => (int)$r['total_cents'], 'tax_cents' => (int)$r['tax_cents'],
        'expense_kind' => $r['expense_kind'], 'donated' => (bool)$r['donated'], 'what' => $r['what'], 'date' => $r['happened_on'],
        'sender' => $r['sender'], 'text' => $r['source_text'], 'undoes' => $r['undoes'] ? (int)$r['undoes'] : null,
        'undone_by' => $r['undone_by'] ? (int)$r['undone_by'] : null, 'created_at' => $r['created_at']];
}

function ledger_recent(int $n = 20): array
{
    $n = max(1, min(200, $n));
    return array_map('ledger_public', db()->query("SELECT * FROM ledger ORDER BY id DESC LIMIT $n")->fetchAll());
}

// Money from text entries, for the admin Money screen. $since is a Texas date or null.
function ledger_money(?string $sinceDate): array
{
    $out = ['sale_n' => 0, 'sale' => 0, 'tax' => 0, 'spent' => 0];
    if (!ledger_exists()) {
        return $out;
    }
    $where = "undone_by IS NULL" . ($sinceDate ? ' AND happened_on >= ?' : '');
    $args = $sinceDate ? [$sinceDate] : [];
    $st = db()->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(total_cents),0) AS total, COALESCE(SUM(tax_cents),0) AS tax FROM ledger WHERE kind = 'sale' AND $where");
    $st->execute($args);
    $s = $st->fetch();
    $st = db()->prepare("SELECT COALESCE(SUM(total_cents),0) FROM ledger WHERE kind = 'expense' AND donated = 0 AND $where");
    $st->execute($args);
    return ['sale_n' => (int)$s['n'], 'sale' => (int)$s['total'], 'tax' => (int)$s['tax'], 'spent' => (int)$st->fetchColumn()];
}

// Everything JC asked to see in one place.
function ledger_summary(): array
{
    $items = stock_items();
    $per = [];
    foreach ($items as $key => $it) {
        $per[$key] = ['name' => $it['name'], 'made' => 0, 'sold' => 0, 'adjusted' => 0, 'in_stock' => 0];
    }
    foreach (db()->query('SELECT item_key, reason, SUM(change_qty) AS n FROM stock_moves GROUP BY item_key, reason') as $r) {
        if (!isset($per[$r['item_key']])) {
            continue;
        }
        $n = (int)$r['n'];
        $per[$r['item_key']]['in_stock'] += $n;
        if (in_array($r['reason'], ['made', 'text-made'], true)) {
            $per[$r['item_key']]['made'] += $n;
        } elseif (in_array($r['reason'], ['show', 'online', 'text-sale'], true)) {
            $per[$r['item_key']]['sold'] -= $n;
        } else {
            $per[$r['item_key']]['adjusted'] += $n;      // counts and corrections
        }
    }

    $channels = [];
    foreach (LEDGER_CHANNELS as $key => $label) {
        $channels[$key] = ['label' => $label, 'sales' => 0, 'units' => 0, 'total_cents' => 0, 'tax_cents' => 0];
    }
    $add = function (string $ch, array $lines, int $total, int $tax) use (&$channels) {
        $channels[$ch]['sales']++;
        $channels[$ch]['total_cents'] += $total;
        $channels[$ch]['tax_cents'] += $tax;
        foreach ($lines as $l) {
            $channels[$ch]['units'] += (int)$l['qty'];
        }
    };
    foreach (db()->query("SELECT items_json, total_cents, tax_cents FROM orders WHERE status IN ('paid','shipped','picked_up')") as $o) {
        $add('web', json_decode($o['items_json'], true) ?: [], (int)$o['total_cents'], (int)$o['tax_cents']);
    }
    foreach (db()->query('SELECT items_json, total_cents, tax_cents FROM show_sales WHERE undone = 0') as $s) {
        $add('show', json_decode($s['items_json'], true) ?: [], (int)$s['total_cents'], (int)$s['tax_cents']);
    }
    $spend = [];
    foreach (LEDGER_EXPENSE_KINDS as $key => $label) {
        $spend[$key] = ['label' => $label, 'cents' => 0, 'n' => 0];
    }
    $donated = [];
    foreach (db()->query("SELECT * FROM ledger WHERE undone_by IS NULL AND kind IN ('sale','expense') ORDER BY id") as $r) {
        if ($r['kind'] === 'sale') {
            $add($r['channel'], json_decode($r['lines_json'], true) ?: [], (int)$r['total_cents'], (int)$r['tax_cents']);
        } elseif ($r['donated']) {
            $donated[] = ['id' => (int)$r['id'], 'what' => $r['what'], 'kind' => $r['expense_kind'], 'date' => $r['happened_on']];
        } else {
            $spend[$r['expense_kind']]['cents'] += (int)$r['total_cents'];
            $spend[$r['expense_kind']]['n']++;
        }
    }
    // Purchases typed on the admin "Spent" screen have no kind
    $adm = db()->query('SELECT COUNT(*) AS n, COALESCE(SUM(amount_cents),0) AS c FROM expenses')->fetch();
    $spend['admin'] = ['label' => 'Entered on the Spent screen (no kind)', 'cents' => (int)$adm['c'], 'n' => (int)$adm['n']];

    $in = array_sum(array_column($channels, 'total_cents'));
    $tax = array_sum(array_column($channels, 'tax_cents'));
    $spent = array_sum(array_column($spend, 'cents'));
    $open = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status IN ('awaiting_payment','customer_says_paid')")->fetchColumn();
    return ['as_of' => now(), 'glosses' => array_values($per), 'channels' => $channels, 'spending' => $spend, 'donated' => $donated,
        'money' => ['in_cents' => $in, 'tax_cents' => $tax, 'spent_cents' => $spent, 'left_cents' => $in - $tax - $spent],
        'web_orders_waiting_for_payment' => $open, 'last_entries' => ledger_recent(5)];
}

// A full copy of the shop's records, for a backup before anything is changed. Reads only.
function ledger_export(): array
{
    $out = ['taken_at' => now(), 'tables' => []];
    foreach (['customers', 'orders', 'promotions', 'stock_moves', 'show_sales', 'expenses', 'reviews', 'ledger'] as $t) {
        try {
            $out['tables'][$t] = db()->query("SELECT * FROM $t")->fetchAll();
        } catch (PDOException $e) {
            $out['tables'][$t] = null;       // table not made yet
        }
    }
    return $out;
}
