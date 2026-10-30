<?php
// A customer sends a review. It is saved as "waiting" and the owner is
// emailed - it only shows on the site after the owner taps Approve.
require __DIR__ . '/_boot.php';

$in = json_in();
$reply = ['ok' => true, 'message' => "Thank you! 💖 Your review will show on the site after we read it."];
// Bots fill in the hidden "website" box; people never see it.
if (clean_text($in['website'] ?? '', 200) !== '') {
    json_out($reply);
}
$ip = client_ip();
if (too_many('review', $ip, 5, 3600)) {
    json_error('Thanks! You sent a lot of reviews. Please try again later.', 429);
}
[$id, $error] = add_review($in);
if ($error !== null) {
    json_error($error);
}
note_attempt('review', $ip);
email_owner_review(get_review($id));
json_out($reply);
