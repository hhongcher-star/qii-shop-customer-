<?php
require_once __DIR__ . '/../includes/seo.php';

header('Content-Type: application/xml; charset=UTF-8');

$urls = [
    ['loc' => qii_site_url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
    ['loc' => qii_site_url('/shop.php'), 'changefreq' => 'daily', 'priority' => '0.9'],
    ['loc' => qii_site_url('/contact.php'), 'changefreq' => 'monthly', 'priority' => '0.5'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
  <url>
    <loc><?= htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') ?></loc>
    <changefreq><?= htmlspecialchars($url['changefreq'], ENT_XML1, 'UTF-8') ?></changefreq>
    <priority><?= htmlspecialchars($url['priority'], ENT_XML1, 'UTF-8') ?></priority>
  </url>
<?php endforeach; ?>
</urlset>
