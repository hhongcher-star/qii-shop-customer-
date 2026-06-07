<?php

function qii_ensure_content_settings(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_settings (
        setting_key VARCHAR(120) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function qii_content(PDO $pdo, string $key, string $default = ''): string
{
    qii_ensure_content_settings($pdo);
    $stmt = $pdo->prepare("SELECT setting_value FROM content_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string)$value;
}

function qii_save_content(PDO $pdo, array $settings): void
{
    qii_ensure_content_settings($pdo);
    $stmt = $pdo->prepare("INSERT INTO content_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }
}
