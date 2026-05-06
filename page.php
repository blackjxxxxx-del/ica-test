<?php
/**
 * page.php — Public renderer สำหรับหน้าที่สร้างจาก CMS
 * URL: /page.php?slug=about-us  หรือ /.htaccess → /about-us
 */
require_once __DIR__ . '/admin/ica-cms-php/includes/db.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: index.html'); exit; }

$stmt = getDB()->prepare("SELECT * FROM pages WHERE slug = ? AND is_visible = 1");
$stmt->execute([$slug]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    http_response_code(404);
    $title   = 'Page Not Found';
    $content = '<p style="text-align:center;padding:60px 0;color:#64748b;">The page you are looking for does not exist.</p>';
} else {
    $title   = htmlspecialchars($page['title']);
    $content = $page['content']; // HTML from editor — already sanitized on save
    $metaTitle = $page['meta_title'] ? htmlspecialchars($page['meta_title']) : $title . ' | ICA-TH 2026';
    $metaDesc  = htmlspecialchars($page['meta_desc'] ?? '');
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $metaTitle ?? $title . ' | ICA-TH 2026' ?></title>
<?php if (!empty($metaDesc)): ?>
<meta name="description" content="<?= $metaDesc ?>">
<?php endif; ?>
<link rel="icon" href="/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="announcement.css">

<style>
:root{--primary:#1a3a5f;--secondary:#274c77;--accent:#ffb400;--light-bg:#f8fafc;--white:#ffffff;--text-main:#1e293b;--text-muted:#64748b}
*{box-sizing:border-box}html,body{overflow-x:hidden;max-width:100%}
body{margin:0;font-family:'Inter',-apple-system,sans-serif;color:var(--text-main);line-height:1.7;background:var(--white);-webkit-font-smoothing:antialiased}
.container{max-width:1200px;margin:auto;padding:0 24px}
a{text-decoration:none}

/* ── Navbar ── */
.navbar{background:rgba(255,255,255,.98);backdrop-filter:blur(10px);padding:0;position:fixed;top:0;left:0;width:100%;z-index:1000;box-shadow:0 2px 20px rgba(0,0,0,.08);height:20vh;min-height:100px}
.nav-container{max-width:1400px;margin:auto;display:flex;justify-content:space-between;align-items:center;padding:0 40px;height:100%}
.nav-left{display:flex;align-items:center;gap:14px}
.logo-group{display:flex;gap:10px}
.logo{height:65px;width:auto;max-width:130px;object-fit:contain}
.site-title{color:var(--primary);display:flex;flex-direction:column;line-height:1.25}
.site-title span:first-child{font-size:18px;font-weight:700;letter-spacing:.8px;color:var(--primary)}
.site-title span:last-child{font-size:13px;font-weight:500;letter-spacing:1px;color:var(--text-muted);text-transform:uppercase}
.nav-menu{display:flex;align-items:center;gap:18px;list-style:none;margin:0;padding:0}
.nav-menu a{color:var(--secondary);font-size:14px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;transition:color .2s}
.nav-menu a:hover{color:var(--accent)}
.dropdown{position:relative}
.dropdown-menu{position:absolute;top:100%;left:0;background:#fff;list-style:none;padding:12px 0;margin:0;min-width:280px;display:none;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.12);border:1px solid #e2e8f0;white-space:nowrap}
.dropdown::after{content:'';position:absolute;top:100%;left:0;width:100%;height:15px}
.dropdown-menu.active{display:block}
.dropdown-menu li a{color:var(--text-main)!important;padding:12px 24px;font-weight:500;display:block}
.dropdown-menu li:hover{background:#f8fafc}
.hamburger{display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:4px}
.hamburger span{display:block;width:24px;height:2px;background:var(--primary);border-radius:2px;transition:all .3s}

/* ── Page Hero ── */
.page-hero{background:linear-gradient(135deg,rgba(15,47,87,.88) 0%,rgba(39,76,119,.75) 100%),url("im/people-taking-part-high-protocol-event.webp") center/cover no-repeat;background-attachment:fixed;margin-top:max(20vh,100px);padding:80px 20px;text-align:center;color:white}
.page-hero h1{font-size:48px;font-weight:800;margin:0 0 16px;line-height:1.1}
.page-hero p{font-size:18px;max-width:700px;margin:0 auto;opacity:.9;font-weight:300}

/* ── Content ── */
.page-content{padding:72px 0 96px}
.page-content img{max-width:100%;height:auto;border-radius:12px}
.page-content h1,.page-content h2,.page-content h3{color:var(--primary)}
.page-content h2{font-size:28px;font-weight:700;margin:40px 0 16px;position:relative;padding-bottom:14px}
.page-content h2::after{content:'';position:absolute;bottom:0;left:0;width:40px;height:3px;background:var(--accent);border-radius:2px}
.page-content p{font-size:17px;margin-bottom:18px;line-height:1.8}
.page-content ul,.page-content ol{padding-left:24px;margin-bottom:18px}
.page-content li{font-size:16px;margin-bottom:8px;line-height:1.7}
.page-content table{width:100%;border-collapse:collapse;margin-bottom:24px}
.page-content th,.page-content td{padding:12px 16px;border:1px solid #e2e8f0;text-align:left}
.page-content th{background:var(--light-bg);font-weight:600}
.page-content blockquote{border-left:4px solid var(--accent);margin:24px 0;padding:16px 24px;background:var(--light-bg);border-radius:0 8px 8px 0;font-style:italic}

/* ── Footer ── */
.site-footer{background:var(--primary);color:rgba(255,255,255,.65);padding:20px 0 10px}
.site-footer .container{max-width:1200px;padding:0 48px}
.footer-top{display:grid;grid-template-columns:repeat(3,1fr);gap:48px;padding-bottom:18px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:15px}
.footer-col h4{font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.35);margin-bottom:10px}
.footer-col ul{list-style:none;padding:0;margin:0}
.footer-col ul li{margin-bottom:10px}
.footer-col ul li a{font-size:14px;color:rgba(255,255,255,.65);transition:color .2s}
.footer-col ul li a:hover{color:#fff}
.footer-contact-item{display:flex;align-items:flex-start;gap:10px;margin-bottom:14px;font-size:14px;line-height:1.5}
.footer-bottom{display:flex;justify-content:space-between;align-items:center;font-size:13px;flex-wrap:wrap;gap:12px}
.footer-bottom a{color:rgba(255,255,255,.5)}

/* ── Responsive ── */
@media(max-width:768px){
  .navbar{height:72px!important;min-height:72px!important}
  .nav-container{height:72px;padding:0 16px}
  .logo{width:48px;height:48px}
  .nav-menu{display:none}
  .hamburger{display:flex}
  .page-hero{margin-top:72px;padding:60px 20px;background-attachment:scroll}
  .page-hero h1{font-size:30px}
  .page-content{padding:48px 0 64px}
  .footer-top{grid-template-columns:1fr;gap:24px}
  .site-footer .container{padding:0 20px}
}
@media(min-width:769px){.dropdown:hover .dropdown-menu{display:block;margin-top:15px}}
</style>
</head>
<body>

<header class="navbar">
  <div class="nav-container">
    <div class="nav-left">
      <div class="logo-group">
        <img src="/im/LogoICA.webp" class="logo" alt="ICA Logo">
        <img src="/im/Chula_Logo_Update.png" class="logo" alt="Chulalongkorn University Logo">
      </div>
      <div class="site-title">
        <span>76<sup style="font-size:10px">TH</sup> ANNUAL ICA</span>
        <span>Regional Hub Thailand</span>
      </div>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>
    <nav id="mainNav">
      <ul class="nav-menu">
        <li><a href="/index.html">Home</a></li>
        <li><a href="/about.html">About</a></li>
        <li><a href="/programme.html">Programme</a></li>
        <li><a href="/submission.html">Submission</a></li>
        <li><a href="/registration.html">Registration</a></li>
        <li class="dropdown">
          <a href="javascript:void(0)">Conference Information ▾</a>
          <ul class="dropdown-menu">
            <li><a href="/venue.html">Venue</a></li>
            <li><a href="/Publication.html">Publication</a></li>
            <li><a href="/Award.html">Award</a></li>
            <li><a href="/visa.html">Visa Information</a></li>
          </ul>
        </li>
        <li><a href="/contact.html">Contact</a></li>
      </ul>
    </nav>
  </div>
</header>

<section class="page-hero">
  <div class="container">
    <h1><?= $title ?></h1>
  </div>
</section>

<div class="page-content">
  <div class="container">
    <?= $content ?>
  </div>
</div>

<footer class="site-footer">
  <div class="container">
    <div class="footer-top">
      <div class="footer-col">
        <h4>ICA-TH 2026</h4>
        <p style="font-size:14px;line-height:1.75">ICA Regional Hub Thailand 2026<br>6–7 June 2026 · Bangkok, Thailand</p>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="/about.html">About</a></li>
          <li><a href="/submission.html">Submission</a></li>
          <li><a href="/registration.html">Registration</a></li>
          <li><a href="/contact.html">Contact</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <div class="footer-contact-item">Faculty of Communication Arts, Chulalongkorn University, Bangkok 10330</div>
        <div class="footer-contact-item"><a href="mailto:info@icahubthailand.org" style="color:rgba(255,255,255,.65)">info@icahubthailand.org</a></div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 ICA Regional Hub Thailand. All rights reserved.</span>
      <a href="/contact.html">Contact Us</a>
    </div>
  </div>
</footer>

<script>
const hbg = document.getElementById('hamburger');
const nav = document.getElementById('mainNav');
hbg?.addEventListener('click', () => { hbg.classList.toggle('open'); nav.classList.toggle('open'); });
</script>
</body>
</html>
