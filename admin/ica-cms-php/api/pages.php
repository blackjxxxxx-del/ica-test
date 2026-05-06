<?php
/**
 * api/pages.php — Public JSON endpoint for published pages
 * Used by content-loader.js to inject links into Conference Information nav dropdown
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=60');

require_once dirname(__DIR__) . '/includes/db.php';

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
    $stmt = $db->prepare(
        "SELECT title, slug FROM pages WHERE is_visible = 1 ORDER BY created_at ASC"
    );
    $stmt->execute();
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'data' => $pages]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'data' => []]);
}
