<?php
/**
 * api/news.php — Public JSON endpoint for published news articles
 * Called by content-loader.js on the homepage.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=120');

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $limit = min((int)($_GET['limit'] ?? 3), 9);
    $stmt  = getDB()->prepare(
        "SELECT title, slug, excerpt, featured_img, published_at
         FROM news
         WHERE status = 'published'
         ORDER BY published_at DESC, created_at DESC
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'data' => $news]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'data' => []]);
}
