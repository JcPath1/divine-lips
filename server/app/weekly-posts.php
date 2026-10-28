<?php
// Run by the server's cron every Monday morning: makes this week's posts
// (if they aren't made yet) and emails them to the shop owner.
//   php weekly-posts.php          make + email this week's posts
//   php weekly-posts.php --test   make them but don't email
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require __DIR__ . '/bootstrap.php';

$week = post_week_of();
$data = load_week_posts($week) ?? make_week_posts($week);
echo "Posts for the week of $week: " . count($data['posts']) . " made.\n";
if (in_array('--test', $argv, true)) {
    exit(0);
}
echo email_week_posts($data) ? "Emailed.\n" : "Email FAILED.\n";
