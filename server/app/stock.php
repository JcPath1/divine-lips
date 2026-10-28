<?php
// Inventory, booth (show) sales, expenses, and the money summary.
// Made for Charlie (age 11) to use on a phone - keep everything simple.
//
// Inventory is a list of changes ("made 10", "sold 2", "order GB-ABC23"),
// and the count is the sum. That way nothing is ever lost or overwritten.

declare(strict_types=1);

// Card is off until there's a Square / Tap to Pay account: add 'card' => '💳 Card' back then.
const SHOW_PAY_METHODS = [
    'cash'  => '💵 Cash',
    'venmo' => '📱 Venmo',
];

const LOW_STOCK = 3;

// Every gloss you can count on a shelf. Glimmer Kiss with and without glitter
// are different bottles, so each choice gets its own count.
function stock_items(): array
{
    $out = [];
    foreach (catalog() as $id => $p) {
        $choices = [[]];
        foreach ($p['options'] ?? [] as $optName => $values) {
            $next = [];
            foreach ($choices as $c) {
                foreach ($values as $v) {
                    $next[] = $c + [$optName => $v];
                }
            }
            $choices = $next;
        }
        foreach ($choices as $options) {
            $key = stock_key($id, $options);
            $out[$key] = [
                'key' => $key,
                'id' => $id,
                'name' => line_label(['name' => $p['name'], 'options' => $options]),
                'options' => $options,
                'price' => $p['price'],
                'image' => $p['image'],
            ];
        }
    }
    return $out;
}

function stock_key(string $id, array $options): string
{
    return $options ? $id . '|' . implode('|', array_values($options)) : $id;
}

function stock_counts(): array
{
    $counts = array_fill_keys(array_keys(stock_items()), 0);
    foreach (db()->query('SELECT item_key, SUM(change_qty) AS n FROM stock_moves GROUP BY item_key') as $r) {
        if (isset($counts[$r['item_key']])) {
            $counts[$r['item_key']] = (int)$r['n'];
        }
    }
    return $counts;
}

function stock_change(string $key, int $change, string $reason, string $ref = ''): void
{
    if ($change === 0 || !isset(stock_items()[$key])) {
        return;
    }
    db()->prepare('INSERT INTO stock_moves (item_key, change_qty, reason, ref, created_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$key, $change, $reason, $ref, now()]);
}

// Online orders take glosses off the shelf once they are paid, and put them
// back if a paid order is cancelled. Safe to call again and again.
function stock_sync_order(array $order): void
{
    $taken = in_array($order['status'], ['paid', 'shipped', 'picked_up'], true);
    $want = [];
    if ($taken) {
        foreach (json_decode($order['items_json'], true) as $l) {
            $key = stock_key($l['id'], $l['options'] ?? []);
            $want[$key] = ($want[$key] ?? 0) - (int)$l['qty'];
        }
    }
    $have = [];
    $st = db()->prepare("SELECT item_key, SUM(change_qty) AS n FROM stock_moves WHERE reason = 'online' AND ref = ? GROUP BY item_key");
    $st->execute([$order['code']]);
    foreach ($st as $r) {
        $have[$r['item_key']] = (int)$r['n'];
    }
    foreach (array_unique(array_merge(array_keys($want), array_keys($have))) as $key) {
        stock_change($key, ($want[$key] ?? 0) - ($have[$key] ?? 0), 'online', $order['code']);
    }
}

// Show prices INCLUDE Texas tax ($12 is $12), so the tax is the part inside.
function tax_inside(int $cents): int
{
    $bp = tax_rate_bp();
    return intdiv($cents * $bp + intdiv(10000 + $bp, 2), 10000 + $bp);
}

// $qtys = [stock key => how many]. Returns the new sale id, or null if empty.
function record_show_sale(array $qtys, string $pay): ?int
{
    $items = stock_items();
    $lines = [];
    $total = 0;
    foreach ($qtys as $key => $qty) {
        $qty = (int)$qty;
        if ($qty < 1 || !isset($items[$key])) {
            continue;
        }
        $qty = min($qty, 50);
        $lines[] = ['key' => $key, 'name' => $items[$key]['name'], 'qty' => $qty, 'unit' => $items[$key]['price']];
        $total += $qty * $items[$key]['price'];
    }
    if (!$lines || !isset(SHOW_PAY_METHODS[$pay])) {
        return null;
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO show_sales (items_json, total_cents, tax_cents, pay_method, undone, created_at) VALUES (?, ?, ?, ?, 0, ?)')
            ->execute([json_encode($lines, JSON_UNESCAPED_UNICODE), $total, tax_inside($total), $pay, now()]);
        $id = (int)$pdo->lastInsertId();
        foreach ($lines as $l) {
            stock_change($l['key'], -$l['qty'], 'show', 'sale-' . $id);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return $id;
}

// "Oops" button: takes a booth sale back and puts the glosses back on the shelf.
function undo_show_sale(int $id): bool
{
    $st = db()->prepare('SELECT * FROM show_sales WHERE id = ? AND undone = 0');
    $st->execute([$id]);
    $sale = $st->fetch();
    if (!$sale) {
        return false;
    }
    db()->prepare('UPDATE show_sales SET undone = 1 WHERE id = ?')->execute([$id]);
    foreach (json_decode($sale['items_json'], true) as $l) {
        stock_change($l['key'], (int)$l['qty'], 'show', 'undo-' . $id);
    }
    return true;
}

// Money in, tax to save, money spent, money kept - since $since (UTC, or null for all time).
function money_summary(?string $since): array
{
    $paid = "status IN ('paid','shipped','picked_up')";
    $sql = "SELECT COUNT(*) AS n, COALESCE(SUM(total_cents),0) AS total, COALESCE(SUM(tax_cents),0) AS tax FROM orders WHERE $paid";
    $args = [];
    if ($since) {
        $sql .= ' AND paid_at >= ?';
        $args[] = $since;
    }
    $st = db()->prepare($sql);
    $st->execute($args);
    $online = $st->fetch();

    $sql = 'SELECT COUNT(*) AS n, COALESCE(SUM(total_cents),0) AS total, COALESCE(SUM(tax_cents),0) AS tax FROM show_sales WHERE undone = 0';
    if ($since) {
        $sql .= ' AND created_at >= ?';
    }
    $st = db()->prepare($sql);
    $st->execute($since ? [$since] : []);
    $show = $st->fetch();

    $st = db()->prepare('SELECT COALESCE(SUM(amount_cents),0) FROM expenses' . ($since ? ' WHERE spent_on >= ?' : ''));
    $st->execute($since ? [local_date($since)] : []);
    $spent = (int)$st->fetchColumn();

    $in = (int)$online['total'] + (int)$show['total'];
    $tax = (int)$online['tax'] + (int)$show['tax'];
    return [
        'online_n' => (int)$online['n'], 'online' => (int)$online['total'],
        'show_n' => (int)$show['n'], 'show' => (int)$show['total'],
        'in' => $in, 'tax' => $tax, 'spent' => $spent, 'kept' => $in - $tax - $spent,
    ];
}

// UTC time -> Texas date "2026-10-27"
function local_date(string $utc): string
{
    $d = new DateTime($utc, new DateTimeZone('UTC'));
    $d->setTimezone(new DateTimeZone('America/Chicago'));
    return $d->format('Y-m-d');
}

// Start of this month in Texas, as UTC for the database.
function month_start_utc(): string
{
    $d = new DateTime('first day of this month 00:00:00', new DateTimeZone('America/Chicago'));
    $d->setTimezone(new DateTimeZone('UTC'));
    return $d->format('Y-m-d H:i:s');
}
