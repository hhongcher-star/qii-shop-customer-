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

function qii_sanitize_rich_text(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }

    $html = strip_tags($html, '<span><br><b><strong><i><em>');
    $html = preg_replace_callback('/<span\b([^>]*)>/i', function ($matches) {
        $attributes = $matches[1] ?? '';
        $styles = [];
        if (preg_match('/style\s*=\s*["\']([^"\']*)["\']/i', $attributes, $styleMatch)) {
            foreach (explode(';', $styleMatch[1]) as $rule) {
                [$property, $value] = array_pad(explode(':', $rule, 2), 2, '');
                $property = strtolower(trim($property));
                $value = trim($value);
                if (
                    in_array($property, ['color', 'background-color'], true) &&
                    preg_match('/^(#[0-9a-f]{3,8}|rgba?\([\d\s.,%]+\)|[a-z]{3,20})$/i', $value)
                ) {
                    $styles[] = $property . ':' . $value;
                }
            }
        }
        return $styles ? '<span style="' . htmlspecialchars(implode(';', $styles), ENT_QUOTES, 'UTF-8') . '">' : '<span>';
    }, $html);

    return $html;
}
