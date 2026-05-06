<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=120');
require_once dirname(__DIR__) . '/includes/db.php';

$page = preg_replace('/[^a-z0-9_]/', '', $_GET['page'] ?? '');
if (!$page) { echo json_encode([]); exit; }

try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS `page_sections` (
        `id` VARCHAR(36) NOT NULL,
        `page` VARCHAR(50) NOT NULL,
        `section_key` VARCHAR(100) NOT NULL,
        `label` VARCHAR(255) NOT NULL DEFAULT '',
        `content` TEXT,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_page_section` (`page`, `section_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $db->prepare("SELECT section_key, content FROM page_sections WHERE page = ?");
    $stmt->execute([$page]);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    echo json_encode($rows ?: new stdClass());
} catch (Exception $e) {
    echo json_encode([]);
}
