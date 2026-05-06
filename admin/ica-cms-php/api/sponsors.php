<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=120');

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS `sponsors` (
        `id`          VARCHAR(36)   NOT NULL,
        `name`        VARCHAR(255)  NOT NULL,
        `logo_url`    VARCHAR(500)  NOT NULL DEFAULT '',
        `website_url` VARCHAR(500)  NOT NULL DEFAULT '',
        `tier`        ENUM('platinum','gold','silver','bronze','supporter') NOT NULL DEFAULT 'supporter',
        `sort_order`  INT           NOT NULL DEFAULT 0,
        `is_visible`  TINYINT(1)    NOT NULL DEFAULT 1,
        `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $rows = $db->query("SELECT id, name, logo_url, website_url, tier, sort_order FROM sponsors WHERE is_visible=1 ORDER BY FIELD(tier,'platinum','gold','silver','bronze','supporter'), sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (Exception $e) {
    echo json_encode([]);
}
