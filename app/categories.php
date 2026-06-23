<?php

function qii_default_categories(): array
{
    return [
        'phone' => ['手机配件', '📱'],
        'hair' => ['发夹发饰', '🎀'],
        'snack' => ['零食', '🍬'],
        'creative' => ['文创', '💗'],
        'case' => ['手机壳', '📱'],
        'nail' => ['穿戴甲', '💅'],
        'scent' => ['香片', '🌸'],
        'doll' => ['娃娃', '🧸'],
        'stationery' => ['文具', '✏️'],
    ];
}

function qii_ensure_categories(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS product_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_key VARCHAR(80) NOT NULL UNIQUE,
            name TEXT NOT NULL,
            emoji VARCHAR(20) NOT NULL DEFAULT '',
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $columns = $pdo->query('SHOW COLUMNS FROM product_categories')->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('name', $columns, true)) {
        $pdo->exec("ALTER TABLE product_categories MODIFY name TEXT NOT NULL");
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO product_categories (category_key, name, emoji, sort_order) VALUES (?, ?, ?, ?)");
    $order = 1;
    foreach (qii_default_categories() as $key => [$name, $emoji]) {
        $stmt->execute([$key, $name, $emoji, $order++]);
    }

    $restoreIcon = $pdo->prepare("
        UPDATE product_categories
        SET emoji = ?
        WHERE category_key = ? AND emoji = ''
    ");
    foreach (qii_default_categories() as $key => [$name, $emoji]) {
        $restoreIcon->execute([$emoji, $key]);
    }
}

function qii_categories(PDO $pdo, bool $activeOnly = true): array
{
    qii_ensure_categories($pdo);
    $sql = "SELECT * FROM product_categories" . ($activeOnly ? " WHERE status = 'active'" : "") . " ORDER BY sort_order, id";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    foreach ($rows as $row) {
        $result[$row['category_key']] = $row;
    }
    return $result;
}
