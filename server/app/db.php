<?php
// Database connection + table setup.
// On the server this is MySQL/MariaDB. On Claude's test computer it is a
// small SQLite file, so the same code can be tested before going live.

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(cfg('db.dsn'), cfg('db.user'), cfg('db.pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        if (db_is_sqlite($pdo)) {
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $pdo->exec("SET NAMES utf8mb4");
        }
        // Make the tables the very first time the shop runs
        $marker = __DIR__ . '/.schema-v4';
        if (!is_file($marker)) {
            db_migrate();
            @touch($marker);
        }
    }
    return $pdo;
}

function db_is_sqlite(?PDO $pdo = null): bool
{
    return ($pdo ?? db())->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
}

// Creates any missing tables. Safe to run again and again.
function db_migrate(): void
{
    $sqlite = db_is_sqlite();
    $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
    $tail = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    $tables = [
        "CREATE TABLE IF NOT EXISTS customers (
            id $id,
            email VARCHAR(190) NOT NULL UNIQUE,
            name VARCHAR(120) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS orders (
            id $id,
            code VARCHAR(16) NOT NULL UNIQUE,
            view_token VARCHAR(64) NOT NULL,
            customer_id INT NOT NULL,
            email VARCHAR(190) NOT NULL,
            name VARCHAR(120) NOT NULL,
            phone VARCHAR(40) NOT NULL DEFAULT '',
            delivery VARCHAR(10) NOT NULL,
            address1 VARCHAR(160) NOT NULL DEFAULT '',
            address2 VARCHAR(160) NOT NULL DEFAULT '',
            city VARCHAR(80) NOT NULL DEFAULT '',
            state VARCHAR(40) NOT NULL DEFAULT '',
            zip VARCHAR(20) NOT NULL DEFAULT '',
            items_json TEXT NOT NULL,
            subtotal_cents INT NOT NULL,
            discount_cents INT NOT NULL DEFAULT 0,
            shipping_cents INT NOT NULL DEFAULT 0,
            tax_cents INT NOT NULL DEFAULT 0,
            tax_label VARCHAR(60) NOT NULL DEFAULT '',
            total_cents INT NOT NULL,
            promo_id INT NULL,
            promo_label VARCHAR(160) NOT NULL DEFAULT '',
            promo_savings_cents INT NOT NULL DEFAULT 0,
            payment_method VARCHAR(20) NOT NULL,
            status VARCHAR(30) NOT NULL,
            paypal_order_id VARCHAR(64) NOT NULL DEFAULT '',
            payment_ref VARCHAR(120) NOT NULL DEFAULT '',
            tracking VARCHAR(120) NOT NULL DEFAULT '',
            customer_note VARCHAR(500) NOT NULL DEFAULT '',
            admin_note VARCHAR(1000) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            customer_paid_at DATETIME NULL,
            paid_at DATETIME NULL,
            shipped_at DATETIME NULL,
            cancelled_at DATETIME NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS promotions (
            id $id,
            label VARCHAR(160) NOT NULL,
            kind VARCHAR(20) NOT NULL,
            min_subtotal_cents INT NOT NULL DEFAULT 0,
            percent INT NOT NULL DEFAULT 0,
            code VARCHAR(40) NULL,
            active INT NOT NULL DEFAULT 1,
            starts_at DATETIME NULL,
            ends_at DATETIME NULL,
            created_at DATETIME NOT NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS login_tokens (
            id $id,
            email VARCHAR(190) NOT NULL,
            token_hash VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS attempts (
            id $id,
            kind VARCHAR(20) NOT NULL,
            akey VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL
        )$tail",

        // Every change to how many glosses are on the shelf
        "CREATE TABLE IF NOT EXISTS stock_moves (
            id $id,
            item_key VARCHAR(80) NOT NULL,
            change_qty INT NOT NULL,
            reason VARCHAR(20) NOT NULL,
            ref VARCHAR(40) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL
        )$tail",

        // Sales at the booth (shows, markets)
        "CREATE TABLE IF NOT EXISTS show_sales (
            id $id,
            items_json TEXT NOT NULL,
            total_cents INT NOT NULL,
            tax_cents INT NOT NULL DEFAULT 0,
            pay_method VARCHAR(10) NOT NULL,
            undone INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL
        )$tail",

        "CREATE TABLE IF NOT EXISTS expenses (
            id $id,
            spent_on DATE NOT NULL,
            what VARCHAR(160) NOT NULL,
            amount_cents INT NOT NULL,
            created_at DATETIME NOT NULL
        )$tail",

        // Customer reviews - only 'approved' ones show on the site
        "CREATE TABLE IF NOT EXISTS reviews (
            id $id,
            product_id VARCHAR(40) NOT NULL DEFAULT '',
            stars INT NOT NULL,
            name VARCHAR(60) NOT NULL,
            body TEXT NOT NULL,
            email VARCHAR(190) NOT NULL DEFAULT '',
            order_code VARCHAR(16) NOT NULL DEFAULT '',
            status VARCHAR(10) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            approved_at DATETIME NULL
        )$tail",
    ];
    foreach ($tables as $sql) {
        db()->exec($sql);
    }

    // Shops made before sales tax was added: give old tables the tax columns
    foreach (['tax_cents' => 'INT NOT NULL DEFAULT 0', 'tax_label' => "VARCHAR(60) NOT NULL DEFAULT ''"] as $col => $type) {
        try {
            db()->query("SELECT $col FROM orders LIMIT 1");
        } catch (PDOException $e) {
            db()->exec("ALTER TABLE orders ADD COLUMN $col $type");
        }
    }

    // First run only: start with the shop's normal "free shipping on $35 or more" deal.
    if ((int)db()->query('SELECT COUNT(*) FROM promotions')->fetchColumn() === 0) {
        db()->prepare('INSERT INTO promotions (label, kind, min_subtotal_cents, percent, code, active, created_at) VALUES (?, ?, ?, 0, NULL, 1, ?)')
            ->execute(['Free shipping on orders of $35 or more', 'free_shipping', 3500, now()]);
    }
    // The original $30 deal became $35 (27 Oct 2026). Only touches the untouched original.
    db()->prepare("UPDATE promotions SET label = ?, min_subtotal_cents = 3500
                   WHERE kind = 'free_shipping' AND min_subtotal_cents = 3000 AND label = ?")
        ->execute(['Free shipping on orders of $35 or more', 'Free shipping on orders over $30']);

    // Old login links and old tries are not needed after a day
    $old = gmdate('Y-m-d H:i:s', time() - 86400);
    db()->prepare('DELETE FROM login_tokens WHERE expires_at < ?')->execute([$old]);
    db()->prepare('DELETE FROM attempts WHERE created_at < ?')->execute([$old]);
}
