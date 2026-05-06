<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=120');

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS `speakers` (
        `id`          VARCHAR(36)   NOT NULL,
        `name`        VARCHAR(255)  NOT NULL,
        `title`       VARCHAR(255)  NOT NULL DEFAULT '',
        `affiliation` VARCHAR(255)  NOT NULL DEFAULT '',
        `bio`         TEXT,
        `photo`       VARCHAR(500)  NOT NULL DEFAULT '',
        `talk_title`  VARCHAR(500)  NOT NULL DEFAULT '',
        `sort_order`  INT           NOT NULL DEFAULT 0,
        `is_visible`  TINYINT(1)    NOT NULL DEFAULT 1,
        `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `sp_order` (`sort_order`,`is_visible`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $rows = $db->query("SELECT id, name, title, affiliation, bio, photo, talk_title, sort_order FROM speakers WHERE is_visible=1 ORDER BY sort_order ASC, created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (Exception $e) {
    echo json_encode([]);
}
