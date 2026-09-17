<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function qii_ensure_customer_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customers (
          id INT AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(160) NOT NULL,
          email VARCHAR(190) NOT NULL UNIQUE,
          phone VARCHAR(80) NULL,
          password_hash VARCHAR(255) NOT NULL,
          status VARCHAR(30) NOT NULL DEFAULT 'active',
          last_login_at DATETIME NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          KEY idx_customers_status (status),
          KEY idx_customers_phone (phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = $pdo->query('SHOW COLUMNS FROM orders')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('customer_id', $columns, true)) {
        $pdo->exec('ALTER TABLE orders ADD COLUMN customer_id INT NULL AFTER id');
        $pdo->exec('ALTER TABLE orders ADD KEY idx_orders_customer (customer_id)');
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customer_addresses (
          id INT AUTO_INCREMENT PRIMARY KEY,
          customer_id INT NOT NULL,
          recipient_name VARCHAR(160) NOT NULL,
          phone VARCHAR(80) NULL,
          address TEXT NULL,
          postcode VARCHAR(20) NULL,
          state VARCHAR(80) NULL,
          is_default TINYINT(1) NOT NULL DEFAULT 0,
          last_used_at DATETIME NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          KEY idx_addresses_customer (customer_id),
          KEY idx_addresses_default (customer_id, is_default),
          KEY idx_addresses_used (customer_id, last_used_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customer_remember_tokens (
          id BIGINT AUTO_INCREMENT PRIMARY KEY,
          customer_id INT NOT NULL,
          selector VARCHAR(64) NOT NULL UNIQUE,
          token_hash CHAR(64) NOT NULL,
          expires_at DATETIME NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          KEY idx_remember_customer (customer_id),
          KEY idx_remember_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $customerColumns = $pdo->query('SHOW COLUMNS FROM customers')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('admin_notes', $customerColumns, true)) {
        $pdo->exec('ALTER TABLE customers ADD COLUMN admin_notes TEXT NULL AFTER last_login_at');
    }
    if (!in_array('admin_tags', $customerColumns, true)) {
        $pdo->exec('ALTER TABLE customers ADD COLUMN admin_tags VARCHAR(500) NULL AFTER admin_notes');
    }
    if (!in_array('email_verified_at', $customerColumns, true)) {
        $pdo->exec('ALTER TABLE customers ADD COLUMN email_verified_at DATETIME NULL AFTER email');
    }
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customer_action_tokens (
          id BIGINT AUTO_INCREMENT PRIMARY KEY,
          customer_id INT NOT NULL,
          token_hash CHAR(64) NOT NULL UNIQUE,
          purpose VARCHAR(30) NOT NULL,
          expires_at DATETIME NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          KEY idx_action_customer (customer_id),
          KEY idx_action_purpose (purpose)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function qii_create_customer_action_token(PDO $pdo, int $customerId, string $purpose, int $ttlSeconds): string
{
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('DELETE FROM customer_action_tokens WHERE customer_id=? AND purpose=?')->execute([$customerId, $purpose]);
    $stmt = $pdo->prepare('INSERT INTO customer_action_tokens (customer_id, token_hash, purpose, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$customerId, hash('sha256', $token), $purpose, date('Y-m-d H:i:s', time() + $ttlSeconds)]);
    return $token;
}

function qii_send_customer_mail(string $email, string $subject, string $message): bool
{
    $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: qii.shop <no-reply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">\r\n";
    return @mail($email, $subject, $message, $headers);
}

function qii_safe_next_path(string $next, string $fallback = 'account.php'): string
{
    $next = trim($next);
    if ($next === '') {
        return $fallback;
    }
    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $next) || str_starts_with($next, '//') || str_contains($next, "\r") || str_contains($next, "\n")) {
        return $fallback;
    }
    if (str_starts_with($next, '/')) {
        $scriptDir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        return $scriptDir !== '' && str_starts_with($next, $scriptDir . '/')
            ? ltrim(substr($next, strlen($scriptDir)), '/')
            : $fallback;
    }
    if (str_contains($next, '..')) {
        return $fallback;
    }
    return $next;
}

function qii_customer_addresses(PDO $pdo, int $customerId, int $limit = 6): array
{
    $limit = max(1, min(20, $limit));
    $stmt = $pdo->prepare("
        SELECT id, recipient_name, phone, address, postcode, state, is_default, last_used_at
        FROM customer_addresses
        WHERE customer_id=?
        ORDER BY is_default DESC, last_used_at DESC, updated_at DESC, id DESC
        LIMIT $limit
    ");
    $stmt->execute([$customerId]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($addresses) {
        return $addresses;
    }

    $stmt = $pdo->prepare("
        SELECT
          0 AS id,
          addr_name AS recipient_name,
          addr_phone AS phone,
          addr_address AS address,
          addr_postcode AS postcode,
          addr_state AS state,
          0 AS is_default,
          MAX(updated_at) AS last_used_at
        FROM orders
        WHERE customer_id=? AND addr_name IS NOT NULL AND addr_name <> ''
        GROUP BY addr_name, addr_phone, addr_address, addr_postcode, addr_state
        ORDER BY MAX(updated_at) DESC, MAX(created_at) DESC
        LIMIT $limit
    ");
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function qii_save_customer_address(PDO $pdo, int $customerId, string $name, string $phone, string $address, string $postcode, string $state): void
{
    $name = trim($name);
    $phone = trim($phone);
    $address = trim($address);
    $postcode = trim($postcode);
    $state = trim($state);

    if ($customerId <= 0 || $name === '') {
        return;
    }

    $stmt = $pdo->prepare("
        SELECT id
        FROM customer_addresses
        WHERE customer_id=? AND recipient_name=? AND COALESCE(phone, '')=? AND COALESCE(address, '')=?
        LIMIT 1
    ");
    $stmt->execute([$customerId, $name, $phone, $address]);
    $existingId = $stmt->fetchColumn();

    if ($existingId) {
        $stmt = $pdo->prepare("
            UPDATE customer_addresses
            SET postcode=?, state=?, last_used_at=NOW(), updated_at=NOW()
            WHERE id=? AND customer_id=?
        ");
        $stmt->execute([$postcode ?: null, $state ?: null, (int)$existingId, $customerId]);
        return;
    }

    $hasDefaultStmt = $pdo->prepare('SELECT COUNT(*) FROM customer_addresses WHERE customer_id=?');
    $hasDefaultStmt->execute([$customerId]);
    $isDefault = (int)$hasDefaultStmt->fetchColumn() === 0 ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO customer_addresses
            (customer_id, recipient_name, phone, address, postcode, state, is_default, last_used_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $customerId,
        $name,
        $phone ?: null,
        $address ?: null,
        $postcode ?: null,
        $state ?: null,
        $isDefault,
    ]);
}

function qii_customer(): ?array
{
    qii_start_session();
    if (empty($_SESSION['customer_id'])) {
        global $pdo;
        if ($pdo instanceof PDO) {
            qii_restore_customer($pdo);
        }
    }
    if (empty($_SESSION['customer_id'])) {
        return null;
    }

    global $pdo;
    if ($pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT id, name, email FROM customers WHERE id=? AND status='active' LIMIT 1");
        $stmt->execute([(int)$_SESSION['customer_id']]);
        $fresh = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fresh) {
            qii_logout_customer($pdo);
            return null;
        }
        $_SESSION['customer_name'] = (string)$fresh['name'];
        $_SESSION['customer_email'] = (string)$fresh['email'];
    }

    return [
        'id' => (int)$_SESSION['customer_id'],
        'name' => (string)($_SESSION['customer_name'] ?? ''),
        'email' => (string)($_SESSION['customer_email'] ?? ''),
    ];
}

function qii_customer_id(): ?int
{
    $customer = qii_customer();
    return $customer ? (int)$customer['id'] : null;
}

function qii_require_customer(): void
{
    if (!qii_customer()) {
        header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? 'account.php'));
        exit;
    }
}

function qii_remember_cookie_options(int $expires): array
{
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function qii_issue_remember_token(PDO $pdo, int $customerId): void
{
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $expires = time() + (10 * 365 * 24 * 60 * 60);
    $expiresAt = date('Y-m-d H:i:s', $expires);

    $pdo->prepare('DELETE FROM customer_remember_tokens WHERE expires_at < NOW()')->execute();
    $stmt = $pdo->prepare('INSERT INTO customer_remember_tokens (customer_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$customerId, $selector, hash('sha256', $validator), $expiresAt]);
    setcookie('qii_customer_remember', $selector . ':' . $validator, qii_remember_cookie_options($expires));
}

function qii_restore_customer(PDO $pdo): bool
{
    $cookie = (string)($_COOKIE['qii_customer_remember'] ?? '');
    if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $parts)) {
        return false;
    }

    $stmt = $pdo->prepare("
        SELECT t.id AS token_id, t.token_hash, c.id, c.name, c.email, c.status
        FROM customer_remember_tokens t
        INNER JOIN customers c ON c.id=t.customer_id
        WHERE t.selector=? AND t.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$parts[1]]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || ($row['status'] ?? '') !== 'active' || !hash_equals((string)$row['token_hash'], hash('sha256', $parts[2]))) {
        setcookie('qii_customer_remember', '', qii_remember_cookie_options(time() - 3600));
        return false;
    }

    qii_start_session();
    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int)$row['id'];
    $_SESSION['customer_name'] = (string)$row['name'];
    $_SESSION['customer_email'] = (string)$row['email'];
    return true;
}

function qii_login_customer(array $customer, ?PDO $pdo = null): void
{
    qii_start_session();
    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int)$customer['id'];
    $_SESSION['customer_name'] = (string)$customer['name'];
    $_SESSION['customer_email'] = (string)$customer['email'];

    if (!$pdo) {
        global $pdo;
    }
    if ($pdo instanceof PDO) {
        qii_issue_remember_token($pdo, (int)$customer['id']);
    }
}

function qii_logout_customer(?PDO $pdo = null): void
{
    qii_start_session();
    if (!$pdo) {
        global $pdo;
    }
    $cookie = (string)($_COOKIE['qii_customer_remember'] ?? '');
    if ($pdo instanceof PDO && preg_match('/^([a-f0-9]{24}):/', $cookie, $parts)) {
        $pdo->prepare('DELETE FROM customer_remember_tokens WHERE selector=?')->execute([$parts[1]]);
    }
    setcookie('qii_customer_remember', '', qii_remember_cookie_options(time() - 3600));
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
    session_regenerate_id(true);
}
?>
