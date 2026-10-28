<?php
// The link from the login email lands here. Good link = logged in.
require __DIR__ . '/_boot.php';

$token = (string)($_GET['token'] ?? '');
$email = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = db()->prepare('SELECT * FROM login_tokens WHERE token_hash = ?');
    $st->execute([hash('sha256', $token)]);
    $row = $st->fetch();
    if ($row && $row['used_at'] === null && $row['expires_at'] >= now()) {
        // Each link works once
        $used = db()->prepare('UPDATE login_tokens SET used_at = ? WHERE id = ? AND used_at IS NULL');
        $used->execute([now(), $row['id']]);
        if ($used->rowCount() === 1) {
            $email = $row['email'];
        }
    }
}

if ($email === null) {
    header('Location: /account.html?link=expired');
    exit;
}
start_session();
session_regenerate_id(true);
$_SESSION['customer_email'] = $email;
header('Location: /account.html');
