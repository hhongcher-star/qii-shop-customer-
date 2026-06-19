<?php

function qii_ensure_content_settings(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_settings (
        setting_key VARCHAR(120) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_drafts (
        setting_key VARCHAR(120) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_versions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        page_key VARCHAR(40) NOT NULL,
        snapshot LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_content_versions_page (page_key, id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function qii_content(PDO $pdo, string $key, string $default = ''): string
{
    qii_ensure_content_settings($pdo);
    $table = !empty($_GET['visual_edit']) ? 'content_drafts' : 'content_settings';
    $stmt = $pdo->prepare("SELECT setting_value FROM {$table} WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    if ($value === false && $table === 'content_drafts') {
        $stmt = $pdo->prepare("SELECT setting_value FROM content_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
    }
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

function qii_save_draft(PDO $pdo, array $settings): void
{
    qii_ensure_content_settings($pdo);
    $stmt = $pdo->prepare("INSERT INTO content_drafts (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }
}

function qii_publish_content(PDO $pdo, string $page, array $keys, array $settings): void
{
    qii_ensure_content_settings($pdo);
    $pdo->beginTransaction();
    try {
        $snapshot = [];
        $read = $pdo->prepare("SELECT setting_value FROM content_settings WHERE setting_key = ?");
        foreach ($keys as $key) {
            $read->execute([$key]);
            $value = $read->fetchColumn();
            if ($value !== false) $snapshot[$key] = (string)$value;
        }
        $version = $pdo->prepare("INSERT INTO content_versions (page_key, snapshot) VALUES (?, ?)");
        $version->execute([$page, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        qii_save_content($pdo, $settings);
        $delete = $pdo->prepare("DELETE FROM content_drafts WHERE setting_key = ?");
        foreach (array_keys($settings) as $key) $delete->execute([$key]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function qii_content_versions(PDO $pdo, string $page, int $limit = 20): array
{
    qii_ensure_content_settings($pdo);
    $limit = max(1, min(50, $limit));
    $stmt = $pdo->prepare("SELECT id, created_at FROM content_versions WHERE page_key = ? ORDER BY id DESC LIMIT {$limit}");
    $stmt->execute([$page]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function qii_restore_content_version(PDO $pdo, string $page, int $versionId): bool
{
    qii_ensure_content_settings($pdo);
    $stmt = $pdo->prepare("SELECT snapshot FROM content_versions WHERE id = ? AND page_key = ? LIMIT 1");
    $stmt->execute([$versionId, $page]);
    $snapshot = $stmt->fetchColumn();
    if ($snapshot === false) return false;
    $settings = json_decode((string)$snapshot, true);
    if (!is_array($settings)) return false;
    qii_save_draft($pdo, $settings);
    return true;
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
                $validColor = in_array($property, ['color', 'background-color'], true)
                    && preg_match('/^(#[0-9a-f]{3,8}|rgba?\([\d\s.,%]+\)|[a-z]{3,20})$/i', $value);
                $validSize = $property === 'font-size'
                    && preg_match('/^(1[0-9]|2[0-9]|3[0-9]|4[0-8])px$/', $value);
                $validWeight = $property === 'font-weight'
                    && preg_match('/^(300|400|500|600|700|800|900|normal|bold)$/', $value);
                $validAlign = $property === 'text-align' && in_array($value, ['left', 'center', 'right'], true);
                $validDisplay = $property === 'display' && $value === 'block';
                if ($validColor || $validSize || $validWeight || $validAlign || $validDisplay) {
                    $styles[] = $property . ':' . $value;
                }
            }
        }
        return $styles ? '<span style="' . htmlspecialchars(implode(';', $styles), ENT_QUOTES, 'UTF-8') . '">' : '<span>';
    }, $html);

    return $html;
}
