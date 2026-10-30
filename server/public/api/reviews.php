<?php
// Approved reviews for the website (?product=sugar-cookie for one gloss).
require __DIR__ . '/_boot.php';

$product = (string)($_GET['product'] ?? '');
$reviews = public_reviews(isset(catalog()[$product]) ? $product : '');
$count = count($reviews);
$avg = $count ? round(array_sum(array_column($reviews, 'stars')) / $count, 1) : 0;
json_out(['ok' => true, 'count' => $count, 'average' => $avg, 'reviews' => $reviews]);
