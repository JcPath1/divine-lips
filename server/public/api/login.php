<?php
// "Email me a login link". No passwords to forget or get stolen:
// a one-time link goes to the customer's email.
require __DIR__ . '/_boot.php';

$in = json_in();
$email = clean_email($in['email'] ?? '');
if ($email === null) {
    json_error('Please type a real email address.');
}
$ip = client_ip();
// Same answer either way, so nobody can use this to find out who our customers are.
$reply = ['ok' => true, 'message' => 'If that email has ordered from us, a login link is on its way. Check your inbox (and spam folder)!'];

if (too_many('login_ip', $ip, 10, 3600) || too_many('login_email', $email, 3, 900)) {
    json_out($reply);
}
note_attempt('login_ip', $ip);
note_attempt('login_email', $email);

$st = db()->prepare('SELECT 1 FROM customers WHERE email = ?');
$st->execute([$email]);
if ($st->fetchColumn()) {
    $token = bin2hex(random_bytes(32));
    db()->prepare('INSERT INTO login_tokens (email, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$email, hash('sha256', $token), gmdate('Y-m-d H:i:s', time() + 20 * 60), now()]);
    email_login_link($email, rtrim((string)cfg('site_url'), '/') . '/api/verify.php?token=' . $token);
}
json_out($reply);
