<?php
// Customer reviews. Anyone can write one (website buyers AND people who
// bought at a show), but NOTHING shows on the site until the owner taps
// Approve in the admin.

declare(strict_types=1);

const REVIEW_STATUSES = ['pending' => 'Waiting', 'approved' => 'Showing', 'hidden' => 'Hidden'];

// "emma smith" -> "Emma S." Only a first name and one letter ever show.
function review_display_name(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $first = mb_convert_case(mb_substr($parts[0] ?? '', 0, 20), MB_CASE_TITLE);
    $initial = isset($parts[1]) ? ' ' . mb_strtoupper(mb_substr($parts[1], 0, 1)) . '.' : '';
    return $first !== '' ? $first . $initial : 'A happy customer';
}

function review_product_name(string $id): string
{
    return catalog()[$id]['name'] ?? 'Glimmer Bliss';
}

// The link in order emails. It marks the review "Verified buyer".
function review_link(array $order): string
{
    return rtrim((string)cfg('site_url'), '/') . '/review/?o=' . rawurlencode($order['code']) . '&t=' . rawurlencode($order['view_token']);
}

// Saves a new review as "pending". Returns [id, error].
function add_review(array $in): array
{
    $stars = (int)($in['stars'] ?? 0);
    $name = clean_text($in['name'] ?? '', 40);
    $body = trim(mb_substr(preg_replace('/[ \t]+/u', ' ', str_replace("\r", '', (string)($in['body'] ?? ''))) ?? '', 0, 1000));
    $body = preg_replace("/\n{3,}/", "\n\n", $body);
    $product = (string)($in['product'] ?? '');
    $emailRaw = trim((string)($in['email'] ?? ''));
    $email = $emailRaw === '' ? '' : clean_email($emailRaw);

    if ($stars < 1 || $stars > 5) {
        return [null, 'Please tap the stars to pick 1 to 5.'];
    }
    if ($name === '') {
        return [null, 'Please type your first name.'];
    }
    if (mb_strlen($body) < 3) {
        return [null, 'Please write a few words about your gloss.'];
    }
    if ($email === null) {
        return [null, 'That email does not look right. You can leave it empty.'];
    }
    if ($product !== '' && !isset(catalog()[$product])) {
        $product = '';
    }

    // A link from an order email makes it a "Verified buyer" review.
    $orderCode = '';
    $order = get_order_for_viewer((string)($in['order'] ?? ''), (string)($in['token'] ?? ''));
    if ($order && in_array($order['status'], ['paid', 'shipped', 'picked_up'], true)) {
        $orderCode = $order['code'];
    }

    db()->prepare('INSERT INTO reviews (product_id, stars, name, body, email, order_code, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$product, $stars, $name, $body, $email, $orderCode, 'pending', now()]);
    return [(int)db()->lastInsertId(), null];
}

function get_review(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM reviews WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function set_review_status(int $id, string $status): void
{
    if (!isset(REVIEW_STATUSES[$status])) {
        return;
    }
    db()->prepare('UPDATE reviews SET status = ?, approved_at = ? WHERE id = ?')
        ->execute([$status, $status === 'approved' ? now() : null, $id]);
}

// What the website is allowed to see: approved reviews only, no emails.
function public_reviews(string $product = ''): array
{
    $sql = "SELECT * FROM reviews WHERE status = 'approved'";
    $args = [];
    if ($product !== '') {
        $sql .= ' AND product_id = ?';
        $args[] = $product;
    }
    $st = db()->prepare($sql . ' ORDER BY approved_at DESC, id DESC LIMIT 100');
    $st->execute($args);
    $out = [];
    foreach ($st as $r) {
        $out[] = [
            'name' => review_display_name($r['name']),
            'stars' => (int)$r['stars'],
            'body' => $r['body'],
            'product' => $r['product_id'],
            'product_name' => review_product_name($r['product_id']),
            'date' => substr($r['created_at'], 0, 10),
            'verified' => $r['order_code'] !== '',
        ];
    }
    return $out;
}

function pending_review_count(): int
{
    return (int)db()->query("SELECT COUNT(*) FROM reviews WHERE status = 'pending'")->fetchColumn();
}

function email_owner_review(array $r): void
{
    $to = (string)cfg('mail.owner_alert');
    if ($to === '') {
        return;
    }
    $admin = rtrim((string)cfg('site_url'), '/') . '/admin/?view=reviews';
    $text = str_repeat('★', (int)$r['stars']) . str_repeat('☆', 5 - (int)$r['stars']) . ' from ' . $r['name']
        . ' about ' . review_product_name($r['product_id']) . ($r['order_code'] !== '' ? ' (verified buyer, order ' . $r['order_code'] . ')' : '')
        . "\n\n" . $r['body'] . "\n\nIt is NOT on the website yet. Approve or delete it here:\n$admin";
    send_mail($to, '⭐ New review waiting for you', '<pre style="font-family:Arial,sans-serif;font-size:14px;white-space:pre-wrap">' . e($text) . '</pre>', $text);
}
