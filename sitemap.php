<?php
/**
 * sitemap.php — Auto-generated XML sitemap
 * URL: /sitemap.php  or reference as /sitemap.xml via .htaccess
 */
header('Content-Type: application/xml; charset=UTF-8');

require_once __DIR__ . '/admin/ica-cms-php/includes/db.php';
$db  = getDB();
$now = date('Y-m-d');

$staticPages = [
    ['loc' => '/',                  'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => '/about.html',        'priority' => '0.8', 'changefreq' => 'monthly'],
    ['loc' => '/programme.html',    'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => '/submission.html',   'priority' => '0.9', 'changefreq' => 'weekly'],
    ['loc' => '/registration.html', 'priority' => '0.9', 'changefreq' => 'weekly'],
    ['loc' => '/venue.html',        'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => '/visa.html',         'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => '/Publication.html',  'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => '/Award.html',        'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => '/contact.html',      'priority' => '0.7', 'changefreq' => 'monthly'],
];

// Dynamic pages from CMS
$cmsPages = $db->query("SELECT slug, updated_at FROM pages WHERE is_visible=1 ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Published news articles
$newsArticles = $db->query("SELECT slug, published_at FROM news WHERE status='published' ORDER BY published_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$baseUrl = 'https://icahubthailand.org';

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
foreach ($staticPages as $p) {
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}{$p['loc']}</loc>\n";
    echo "    <lastmod>{$now}</lastmod>\n";
    echo "    <changefreq>{$p['changefreq']}</changefreq>\n";
    echo "    <priority>{$p['priority']}</priority>\n";
    echo "  </url>\n";
}

// CMS pages
foreach ($cmsPages as $p) {
    $lastmod = $p['updated_at'] ? date('Y-m-d', strtotime($p['updated_at'])) : $now;
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}/page.php?slug=" . htmlspecialchars($p['slug']) . "</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

// News articles
foreach ($newsArticles as $n) {
    $lastmod = $n['published_at'] ? date('Y-m-d', strtotime($n['published_at'])) : $now;
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}/news-detail.php?slug=" . htmlspecialchars($n['slug']) . "</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.6</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
