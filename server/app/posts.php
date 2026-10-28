<?php
// The Post Maker: every week it makes 3 ready-to-post social media posts
// (Monday, Wednesday, Friday) - pictures in the right sizes plus captions.
// Pictures are saved in the website's posts/ folder; the admin "📣 Posts" tab
// shows them, and weekly-posts.php emails them every Monday.
//
// To add a post idea: copy one in post_ideas(). Only write things that are TRUE
// about the shop - the captions go out to the public.

declare(strict_types=1);

const POST_DAYS = ['Monday', 'Wednesday', 'Friday'];

// Picture sizes: what each one is for.
const POST_SIZES = [
    'square' => ['w' => 1080, 'h' => 1080, 'photo_h' => 740,  'for' => 'Instagram & Facebook posts'],
    'tall'   => ['w' => 1080, 'h' => 1920, 'photo_h' => 1330, 'for' => 'TikTok, Reels & Stories'],
    'pin'    => ['w' => 1000, 'h' => 1500, 'photo_h' => 990,  'for' => 'Pinterest'],
];

const POST_HASHTAGS = '#lipgloss #handmadelipgloss #glimmerbliss #smallbusiness #shopsmall #texassmallbusiness';

// What each gloss looks like, in the words used on its own page.
const POST_GLOSS_LOOKS = [
    'pink-paradise' => 'a dreamy tropical pink',
    'love'          => 'a pretty red',
    'true-love'     => 'a deep, dark red',
    'gloss-angeles' => 'a clear gloss with a sunny city shine',
    'sugar-cookie'  => 'a warm cookie-brown',
];

function posts_web_root(): string
{
    return dirname(__DIR__) . '/' . cfg('web_dir', 'public_html');
}

function site_link(string $path = ''): string
{
    return rtrim((string)cfg('site_url'), '/') . '/' . ltrim($path, '/');
}

// One post about one gloss.
function post_spotlight(string $id, array $product): array
{
    $link = site_link('products/' . $id . '.html');
    $price = money((int)$product['price']);
    if ($id === 'glimmer-kiss') {
        return [
            'photo' => $product['image'], 'headline' => 'Glimmer Kiss', 'subline' => 'Goes on clear, turns YOUR pink · ' . $price,
            'caption' => "Meet Glimmer Kiss ✨ It goes on clear, then turns a soft pink on your lips. The exact shade comes from your own natural pH - so your pink is yours alone. 💖\n\nLimited Edition, with sparkle. $price. When they're gone, they're gone!\n\n🛒 $link",
            'short' => "Glimmer Kiss goes on clear and turns YOUR own pink ✨ Limited Edition, $price. $link",
        ];
    }
    $look = POST_GLOSS_LOOKS[$id] ?? 'a pretty';
    return [
        'photo' => $product['image'], 'headline' => $product['name'], 'subline' => 'Handmade lip gloss · ' . $price,
        'caption' => "Meet {$product['name']} 💋 " . ucfirst($look) . " lip gloss, handmade in small batches by Charlie Mae.\n\nVanilla flavored · lip-safe ingredients · $price\n\n🛒 $link\n🚚 Free shipping on \$35+ · free local pickup",
        'short' => "Meet {$product['name']} 💋 " . ucfirst($look) . " lip gloss, handmade by Charlie Mae. $price. $link",
    ];
}

// Posts about the shop (not one gloss).
function post_ideas(): array
{
    $shop = site_link();
    return [
        [
            'photo' => 'images/group-photo-2.jpg', 'headline' => 'Pick your shade', 'subline' => 'Handmade lip gloss · from $12',
            'caption' => "Which one is YOUR shade? 💄 Pink Paradise, Love, True Love, Gloss Angeles, Sugar Cookie - or the Limited Edition Glimmer Kiss.\n\nAll handmade in small batches by Charlie Mae, all vanilla flavored.\n\n🛒 $shop",
            'short' => "Which shade is yours? 💄 6 handmade, vanilla-flavored lip glosses from \$12. $shop",
        ],
        [
            'photo' => 'images/group-photo.jpg', 'headline' => 'Free shipping on $35+', 'subline' => "That's just 3 glosses!",
            'caption' => "🚚 FREE shipping when you spend \$35 or more - that's just 3 glosses!\n\nMix and match any colors. Perfect for sharing with a friend. 💕\n\n🛒 $shop",
            'short' => "🚚 Free shipping on \$35+ - that's just 3 glosses! $shop",
        ],
        [
            'photo' => 'images/group-photo-2.jpg', 'headline' => 'Made by hand', 'subline' => 'Every batch mixed by Charlie Mae',
            'caption' => "Every Glimmer Bliss gloss is mixed by hand in small batches by Charlie Mae. ✨ She picks every color, mixes every batch and packs every order herself.\n\nLip-safe, cosmetic-grade ingredients - never craft glitter.\n\n🛒 $shop",
            'short' => "Every gloss is mixed by hand in small batches by Charlie Mae ✨ $shop",
        ],
        [
            'photo' => 'images/group-photo.jpg', 'headline' => 'Come see us!', 'subline' => 'First Monday Trade Days · Canton, TX',
            'caption' => "Come say hi at our booth at First Monday Trade Days in Canton, TX! 👋 Try the colors in person.\n\nWe take cash, Venmo and cards. 💳\n\nCan't make it? Shop anytime: $shop",
            'short' => "Find us at First Monday Trade Days in Canton, TX! 👋 Or shop anytime: $shop",
        ],
        [
            'photo' => 'images/group-photo-2.jpg', 'headline' => 'Free local pickup', 'subline' => 'Choose pickup at checkout',
            'caption' => "Live nearby? Pick up your gloss for FREE! 🛍️ Just choose Local pickup at checkout and we'll email you to set up a time.\n\n🛒 $shop",
            'short' => "Live nearby? Local pickup is FREE 🛍️ $shop",
        ],
        [
            'photo' => 'images/bottle-measurements.jpg', 'headline' => 'Pocket-size gloss', 'subline' => '5ml · 3.31 inches tall',
            'caption' => "Small enough for your pocket, backpack or purse 👛 Each bottle holds 5ml and stands 3.31 inches tall, with a soft wide wand.\n\n🛒 $shop",
            'short' => "Pocket-size gloss 👛 5ml, 3.31 inches tall, soft wide wand. $shop",
        ],
        [
            'photo' => 'images/group-photo.jpg', 'headline' => 'Every one is vanilla', 'subline' => 'Glossy, smooth, not sticky',
            'caption' => "Fun fact: every Glimmer Bliss gloss is vanilla flavored! 🍦 The finish is glossy and lightweight - smooth, not sticky.\n\n🛒 $shop",
            'short' => "Every Glimmer Bliss gloss is vanilla flavored 🍦 Glossy, not sticky. $shop",
        ],
        [
            'photo' => 'images/group-photo-2.jpg', 'headline' => 'A sweet little gift', 'subline' => 'Birthdays · friends · just because',
            'caption' => "Looking for a little gift? 🎁 A handmade gloss is perfect for birthdays, best friends or just because.\n\nPlanning party favors or gift bags? Email orders@glimmerbliss.com and we'll work something out.\n\n🛒 $shop",
            'short' => "A handmade lip gloss makes a sweet little gift 🎁 $shop",
        ],
    ];
}

// Monday of the week (shop time) for any date, as Y-m-d.
function post_week_of(?string $date = null): string
{
    $d = new DateTime($date ?? 'now', new DateTimeZone('America/Chicago'));
    $d->modify('monday this week');
    return $d->format('Y-m-d');
}

// Picks the 3 posts for a week. $variant > 0 gives a different set ("make different ones").
function plan_week(string $week, int $variant = 0): array
{
    $weekNo = intdiv((int)((strtotime($week) - strtotime('2026-01-05')) / 86400), 7) + $variant * 5;
    $products = array_filter(catalog(), fn($p) => $p['available']);
    $ids = array_keys($products);
    $ideas = post_ideas();
    $n = count($ideas);
    $id = $ids[(($weekNo % count($ids)) + count($ids)) % count($ids)];
    $a = ((2 * $weekNo) % $n + $n) % $n;
    $b = ($a + 1) % $n;
    return [post_spotlight($id, $products[$id]), $ideas[$a], $ideas[$b]];
}

function post_dir(string $week): string
{
    return posts_web_root() . '/posts/' . $week;
}

function load_week_posts(string $week): ?array
{
    $file = post_dir($week) . '/posts.json';
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

// Makes (or re-makes) the pictures and captions for a week and saves them.
function make_week_posts(string $week, int $variant = 0): array
{
    $dir = post_dir($week);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Could not make the posts folder.');
    }
    foreach (glob($dir . '/*.jpg') ?: [] as $old) {
        unlink($old);
    }
    $posts = [];
    foreach (plan_week($week, $variant) as $i => $p) {
        $images = [];
        foreach (POST_SIZES as $size => $spec) {
            $name = 'post-' . ($i + 1) . '-' . $size . '-v' . $variant . '.jpg';
            post_picture($p, $spec, $dir . '/' . $name);
            $images[$size] = $name;
        }
        $posts[] = [
            'day' => POST_DAYS[$i], 'headline' => $p['headline'],
            'caption' => $p['caption'] . "\n\n" . POST_HASHTAGS,
            'short' => $p['short'], 'images' => $images,
        ];
    }
    $data = ['week' => $week, 'variant' => $variant, 'made_at' => now(), 'posts' => $posts];
    file_put_contents($dir . '/posts.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    return $data;
}

function post_image_url(string $week, string $name): string
{
    return site_link('posts/' . $week . '/' . $name);
}

// ---------- drawing the pictures ----------

function post_font(bool $bold): string
{
    return __DIR__ . '/fonts/Poppins-' . ($bold ? 'Bold' : 'Regular') . '.ttf';
}

function load_photo(string $path)
{
    $full = posts_web_root() . '/' . $path;
    $img = str_ends_with($path, '.png') ? imagecreatefrompng($full) : imagecreatefromjpeg($full);
    if (!$img) {
        throw new RuntimeException('Could not open ' . $path);
    }
    return trim_dark_bars($img);
}

// Some photos have black bars at the top and bottom - cut them off.
function trim_dark_bars($img)
{
    $w = imagesx($img);
    $h = imagesy($img);
    $isDark = function (int $y) use ($img, $w): bool {
        $sum = 0;
        $n = 0;
        for ($x = 0; $x < $w; $x += 40) {
            $sum += (imagecolorat($img, $x, $y) >> 16) & 0xFF;
            $n++;
        }
        return $sum / $n < 40;
    };
    $top = 0;
    while ($top < $h / 3 && $isDark($top)) {
        $top++;
    }
    $bottom = $h - 1;
    while ($bottom > $h * 2 / 3 && $isDark($bottom)) {
        $bottom--;
    }
    if ($top === 0 && $bottom === $h - 1) {
        return $img;
    }
    $top += 2; // skip the soft edge of the bar
    $bottom -= 2;
    return imagecrop($img, ['x' => 0, 'y' => $top, 'width' => $w, 'height' => $bottom - $top + 1]);
}

// Puts the photo in the box. If the box is wider than the photo, the whole photo
// is shown and the sides are filled by stretching its edge colors outward - so
// bottle tops never get cut off. Otherwise it fills the box and trims the sides.
function paste_photo($dst, $src, int $dx, int $dy, int $dw, int $dh): void
{
    $sw = imagesx($src);
    $sh = imagesy($src);
    if ($dw / $dh <= $sw / $sh) {
        paste_cover($dst, $src, $dx, $dy, $dw, $dh);
        return;
    }
    $w = (int)round($sw * $dh / $sh);
    $left = $dx + (int)(($dw - $w) / 2);
    imagecopyresampled($dst, $src, $left, $dy, 0, 0, $w, $dh, $sw, $sh);
    for ($y = 0; $y < $dh; $y++) {
        $sy = min($sh - 1, (int)($y * $sh / $dh));
        imageline($dst, $dx, $dy + $y, $left, $dy + $y, imagecolorat($src, 3, $sy));
        imageline($dst, $left + $w - 1, $dy + $y, $dx + $dw, $dy + $y, imagecolorat($src, $sw - 4, $sy));
    }
}

function paste_cover($dst, $src, int $dx, int $dy, int $dw, int $dh): void
{
    $sw = imagesx($src);
    $sh = imagesy($src);
    $scale = max($dw / $sw, $dh / $sh);
    $cw = (int)round($dw / $scale);
    $ch = (int)round($dh / $scale);
    imagecopyresampled($dst, $src, $dx, $dy, (int)(($sw - $cw) / 2), (int)(($sh - $ch) / 2), $dw, $dh, $cw, $ch);
}

function text_width(string $text, string $font, float $size): int
{
    $box = imagettfbbox($size, 0, $font, $text);
    return $box[2] - $box[0];
}

// Breaks text into lines that fit $maxW.
function wrap_text(string $text, string $font, float $size, int $maxW): array
{
    $lines = [];
    $line = '';
    foreach (explode(' ', $text) as $word) {
        $try = $line === '' ? $word : $line . ' ' . $word;
        if ($line !== '' && text_width($try, $font, $size) > $maxW) {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $try;
        }
    }
    $lines[] = $line;
    return $lines;
}

function draw_centered($img, string $text, string $font, float $size, int $y, int $color): void
{
    $x = (int)((imagesx($img) - text_width($text, $font, $size)) / 2);
    imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
}

function post_picture(array $p, array $spec, string $out): void
{
    $w = $spec['w'];
    $h = $spec['h'];
    $img = imagecreatetruecolor($w, $h);
    $soft = imagecolorallocate($img, 0xFF, 0xF0, 0xF5);
    $pink = imagecolorallocate($img, 0xE9, 0x1E, 0x63);
    $ink = imagecolorallocate($img, 0x1A, 0x1A, 0x1A);
    $gray = imagecolorallocate($img, 0x6B, 0x72, 0x80);
    $white = imagecolorallocate($img, 0xFF, 0xFF, 0xFF);
    imagefill($img, 0, 0, $soft);

    // Photo on top
    $photo = load_photo($p['photo']);
    paste_photo($img, $photo, 0, 0, $w, $spec['photo_h']);
    imagedestroy($photo);

    // Logo in a white badge, top-left
    $logo = imagecreatefrompng(posts_web_root() . '/images/logo.png');
    $lw = (int)($w * 0.26);
    $lh = (int)(imagesy($logo) * $lw / imagesx($logo));
    $pad = (int)($w * 0.03);
    imagefilledrectangle($img, $pad, $pad, $pad + $lw + 20, $pad + $lh + 20, $white);
    imagecopyresampled($img, $logo, $pad + 10, $pad + 10, 0, 0, $lw, $lh, imagesx($logo), imagesy($logo));
    imagedestroy($logo);

    // Pink stripe between photo and words
    imagefilledrectangle($img, 0, $spec['photo_h'], $w, $spec['photo_h'] + 10, $pink);

    // Words: headline, small line, then the web address in a pink pill.
    // Shrinks the words if they don't fit the space under the photo.
    $bold = post_font(true);
    $regular = post_font(false);
    $panelTop = $spec['photo_h'] + 10;
    $panelH = $h - $panelTop;
    for ($k = 1.0; ; $k -= 0.05) {
        $headSize = $w * 0.062 * $k;
        $subSize = $w * 0.032 * $k;
        $urlSize = $w * 0.03 * $k;
        $headLines = wrap_text($p['headline'], $bold, $headSize, (int)($w * 0.88));
        $lineH = (int)($headSize * 1.35);
        $pillH = (int)($urlSize * 2.3);
        $block = (int)($headSize + (count($headLines) - 1) * $lineH + $subSize * 2.9 + $pillH);
        if ($block <= $panelH * 0.8 || $k <= 0.5) {
            break;
        }
    }
    $y = $panelTop + (int)(($panelH - $block) / 2) + (int)$headSize;
    foreach ($headLines as $i => $line) {
        draw_centered($img, $line, $bold, $headSize, $y + $i * $lineH, $ink);
    }
    $y += (count($headLines) - 1) * $lineH + (int)($subSize * 2.0);
    draw_centered($img, $p['subline'], $regular, $subSize, $y, $gray);

    $url = 'glimmerbliss.com';
    $pillW = text_width($url, $bold, $urlSize) + (int)($urlSize * 2.4);
    $px = (int)(($w - $pillW) / 2);
    $py = $y + (int)($subSize * 0.9);
    $r = (int)($pillH / 2);
    imagefilledrectangle($img, $px + $r, $py, $px + $pillW - $r, $py + $pillH, $pink);
    imagefilledellipse($img, $px + $r, $py + $r, $pillH, $pillH, $pink);
    imagefilledellipse($img, $px + $pillW - $r, $py + $r, $pillH, $pillH, $pink);
    draw_centered($img, $url, $bold, $urlSize, $py + (int)($pillH / 2 + $urlSize * 0.45), $white);

    imagejpeg($img, $out, 86);
    imagedestroy($img);
}

// ---------- the Monday email ----------

function email_week_posts(array $data): bool
{
    $to = (string)cfg('mail.owner_alert');
    if ($to === '') {
        return false;
    }
    $admin = site_link('admin/?view=posts');
    $text = "This week's 3 Glimmer Bliss posts are ready! ✨\n\nOpen them on your phone to save the pictures and copy the words:\n$admin\n\n";
    $html = '<p>This week\'s 3 Glimmer Bliss posts are ready! ✨</p>'
        . '<p><a href="' . e($admin) . '" style="background:#e91e63;color:#fff;padding:12px 22px;border-radius:24px;text-decoration:none;display:inline-block">Open this week\'s posts</a></p>';
    $files = [];
    foreach ($data['posts'] as $post) {
        $text .= "---- {$post['day']}: {$post['headline']} ----\n{$post['caption']}\n\nShort version (for X):\n{$post['short']}\n\n";
        $html .= '<h3 style="margin-bottom:4px">' . e($post['day']) . ': ' . e($post['headline']) . '</h3>'
            . '<img src="' . e(post_image_url($data['week'], $post['images']['square'])) . '" width="260" style="border-radius:10px" alt="">'
            . '<p style="white-space:pre-wrap;background:#fff0f5;padding:10px;border-radius:8px">' . e($post['caption']) . '</p>';
        $files[] = post_dir($data['week']) . '/' . $post['images']['square'];
    }
    $html .= '<p style="color:#777">The square pictures are attached. Tall (TikTok/Reels) and Pinterest sizes are on the posts page.</p>';
    return send_mail($to, 'Your Glimmer Bliss posts for the week of ' . date('M j', strtotime($data['week'])), email_wrap($html), $text, '', $files);
}
