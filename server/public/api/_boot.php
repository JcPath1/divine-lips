<?php
// Every API file starts here. On the server, the app folder sits next to
// public_html (one level up), out of reach of web browsers.
declare(strict_types=1);
$app = dirname(__DIR__, 2) . '/glimmerbliss_app/bootstrap.php';
if (!is_file($app)) {
    $app = dirname(__DIR__, 2) . '/app/bootstrap.php'; // Claude's local test copy
}
require $app;
