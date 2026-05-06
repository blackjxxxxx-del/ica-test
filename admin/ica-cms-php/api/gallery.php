<?php
/**
 * api/gallery.php — Public JSON endpoint for gallery images
 * Called by content-loader.js on the homepage.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=120');

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $limit = min((int)($_GET['limit'] ?? 9), 24);
    $stmt  = getDB()->prepare(
        "SELECT url, title, alt FROM gallery_images
         ORDER BY created_at DESC LIMIT ?"
    );
    $stmt->execute([$limit]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['ok' => true, 'data' => $images]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'data' => []]);
}
