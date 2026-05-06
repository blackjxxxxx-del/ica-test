<?php
/**
 * api/page-single.php — Return single published page by slug
 * GET /api/page-single.php?slug=about-us
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=60');

require_once dirname(__DIR__) . '/includes/db.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'missing slug']);
    exit;
}

try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS pages (
        id VARCHAR(32) PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        content LONGTEXT,
        meta_title VARCHAR(255),
        meta_desc TEXT,
        is_visible TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    )");
    $stmt = $db->prepare("SELECT title, slug, content, meta_title, meta_desc FROM pages WHERE slug = ? AND is_visible = 1");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$page) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not found']);
        exit;
    }
    echo json_encode(['ok' => true, 'data' => $page]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server error']);
}
