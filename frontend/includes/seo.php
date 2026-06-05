<?php
require_once __DIR__ . '/../../app/bootstrap.php';

function qii_site_url($path = '') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = $scheme . '://' . $host;
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function qii_seo_meta($opts = []) {
    $title = $opts['title'] ?? 'qii.shoppp | Kawaii Gifts & Cute Accessories';
    $description = $opts['description'] ?? 'qii.shoppp offers cute phone accessories, hair clips, stationery, snacks, dolls, charms and kawaii gifts in Malaysia.';
    $path = $opts['path'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
    $canonical = $opts['canonical'] ?? qii_site_url(strtok($path, '?') ?: '/');
    $image = $opts['image'] ?? qii_site_url('images/logo.png');
    $type = $opts['type'] ?? 'website';
    $robots = $opts['robots'] ?? 'index, follow';
    $keywords = $opts['keywords'] ?? 'qii.shoppp, Qii Shop, kawaii shop Malaysia, cute accessories, phone charms, hair clips, stationery, cute gifts';

    echo "\n";
    echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</title>\n";
    echo '<meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<meta name="keywords" content="' . htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<meta name="robots" content="' . htmlspecialchars($robots, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<link rel="canonical" href="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<meta property="og:site_name" content="qii.shoppp">' . "\n";
    echo '<meta property="og:type" content="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<meta property="og:url" content="' . htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<meta property="og:image" content="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<meta name="twitter:image" content="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . "\">\n";
    echo '<link rel="icon" href="' . htmlspecialchars(qii_site_url('images/favicon.png'), ENT_QUOTES, 'UTF-8') . "\">\n";
    qii_frontend_csrf_meta();
}

function qii_store_json_ld() {
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'Store',
        'name' => 'qii.shoppp',
        'description' => 'Kawaii gifts, cute accessories, phone charms, stationery and small lifestyle goods in Malaysia.',
        'url' => qii_site_url('/'),
        'logo' => qii_site_url('images/logo.png'),
        'image' => qii_site_url('images/logo.png'),
        'address' => [
            '@type' => 'PostalAddress',
            'addressCountry' => 'MY',
        ],
    ];

    echo '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
?>
