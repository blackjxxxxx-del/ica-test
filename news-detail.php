<?php
/**
 * news-detail.php — Public news article page
 * URL: /news-detail.php?slug=article-slug
 */
require_once __DIR__ . '/admin/ica-cms-php/includes/db.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_GET['slug'] ?? '')));
if (!$slug) { http_response_code(404); exit('Not found'); }

$stmt = getDB()->prepare("SELECT * FROM news WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    http_response_code(404);
    header('Location: index.html');
    exit;
}

$title   = htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8');
$pubDate = $article['published_at'] ? date('d F Y', strtotime($article['published_at'])) : '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> | ICA-TH 2026</title>
    <link rel="icon" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="announcement.css">

    <style>
        :root{--primary:#1a3a5f;--secondary:#274c77;--accent:#ffb400;--text-main:#1e293b;--text-muted:#64748b;--border:#e2e8f0}
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;font-family:'Inter',-apple-system,sans-serif;background:#f8fafc;color:var(--text-main);overflow-x:hidden}
        a{text-decoration:none;color:inherit}
        .navbar{background:rgba(255,255,255,0.98);backdrop-filter:blur(10px);position:fixed;top:0;left:0;width:100%;z-index:1000;box-shadow:0 2px 20px rgba(0,0,0,0.08);border-bottom:1px solid var(--border);height:100px}
        .nav-container{max-width:1400px;margin:auto;display:flex;justify-content:space-between;align-items:center;padding:0 40px;height:100%}
        .nav-left{display:flex;align-items:center;gap:14px}
        .logo{height:65px;width:auto;max-width:130px;object-fit:contain}
        .logo-group{display:flex;gap:10px}
        .site-title{display:flex;flex-direction:column;line-height:1.25}
        .site-title span:first-child{font-size:18px;font-weight:700;color:var(--primary)}
        .site-title span:last-child{font-size:13px;font-weight:500;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px}
        .hero{background:linear-gradient(135deg,rgba(15,47,87,0.92) 0%,rgba(39,76,119,0.8) 100%),url("im/senior-woman-company-leader-brainstorming-conference-room-corporate-staff-discussing-new-business-application-with-colleagues-looking-screen.webp") center/cover no-repeat;margin-top:100px;padding:70px 20px;text-align:center;color:#fff}
        .hero h1{font-size:clamp(24px,4vw,40px);font-weight:800;margin:0 0 16px;line-height:1.2;max-width:800px;margin-inline:auto}
        .hero-meta{display:flex;align-items:center;justify-content:center;gap:16px;font-size:14px;opacity:.8;flex-wrap:wrap}
        .article-wrap{max-width:820px;margin:60px auto;padding:0 24px}
        .article-card{background:#fff;border-radius:20px;box-shadow:0 4px 24px rgba(0,0,0,0.07);overflow:hidden}
        .featured-img{width:100%;max-height:420px;object-fit:cover}
        .article-body{padding:40px 48px;line-height:1.85;font-size:16px;color:var(--text-main)}
        .article-body h1,.article-body h2,.article-body h3{color:var(--primary);margin-top:1.5em}
        .article-body img{max-width:100%;border-radius:10px;margin:12px 0}
        .article-body a{color:var(--secondary);text-decoration:underline}
        .article-body blockquote{border-left:4px solid var(--accent);margin:20px 0;padding:10px 20px;background:#f8fafc;border-radius:0 8px 8px 0;color:var(--text-muted);font-style:italic}
        .back-link{display:inline-flex;align-items:center;gap:8px;color:var(--text-muted);font-size:14px;margin-bottom:20px;transition:.2s}
        .back-link:hover{color:var(--primary)}
        .footer{background:var(--primary);color:rgba(255,255,255,.65);padding:24px 0;text-align:center;font-size:13px;margin-top:80px}
        @media(max-width:768px){.nav-container{padding:0 16px}.article-body{padding:24px 20px}.navbar{height:72px}.hero{margin-top:72px;padding:50px 20px}}
    </style>
</head>
<body>
<header class="navbar">
    <div class="nav-container">
        <div class="nav-left">
            <div class="logo-group">
                <img src="im/LogoICA.webp" class="logo" alt="ICA Logo">
                <img src="im/Chula_Logo_Update.png" class="logo" alt="Chulalongkorn University Logo">
            </div>
            <div class="site-title">
                <span>76<sup style="font-size:10px">TH</sup> ANNUAL ICA</span>
                <span>Regional Hub Thailand</span>
            </div>
        </div>
        <a href="index.html" style="font-size:14px;font-weight:600;color:var(--secondary)">← Back to Home</a>
    </div>
</header>

<section class="hero">
    <h1><?= $title ?></h1>
    <div class="hero-meta">
        <?php if ($pubDate): ?><span>📅 <?= $pubDate ?></span><?php endif; ?>
        <?php if ($article['is_featured']): ?><span>⭐ Featured</span><?php endif; ?>
    </div>
</section>

<div class="article-wrap">
    <a href="index.html" class="back-link">← Back to Home</a>
    <div class="article-card">
        <?php if ($article['featured_img']): ?>
        <img src="<?= htmlspecialchars($article['featured_img']) ?>" class="featured-img" alt="<?= $title ?>">
        <?php endif; ?>
        <div class="article-body">
            <?= $article['content'] ?>
        </div>
    </div>
</div>

<footer class="footer">
    <p style="margin:0">© 2026 ICA Thailand Hub · Faculty of Communication Arts, Chulalongkorn University</p>
</footer>
</body>
</html>
