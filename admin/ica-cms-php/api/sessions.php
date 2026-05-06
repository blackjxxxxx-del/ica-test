<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=60');

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS `sessions` (
        `id`          VARCHAR(36)   NOT NULL,
        `day`         TINYINT       NOT NULL DEFAULT 1 COMMENT '1=Day1, 2=Day2, 3=Day3',
        `start_time`  VARCHAR(10)   NOT NULL DEFAULT '',
        `end_time`    VARCHAR(10)   NOT NULL DEFAULT '',
        `title`       VARCHAR(500)  NOT NULL,
        `type`        ENUM('keynote','panel','workshop','break','networking','ceremony','other') NOT NULL DEFAULT 'other',
        `room`        VARCHAR(255)  NOT NULL DEFAULT '',
        `speaker`     VARCHAR(500)  NOT NULL DEFAULT '',
        `description` TEXT,
        `sort_order`  INT           NOT NULL DEFAULT 0,
        `is_visible`  TINYINT(1)    NOT NULL DEFAULT 1,
        `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `sess_day_order` (`day`,`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $day = isset($_GET['day']) ? (int)$_GET['day'] : null;
    if ($day) {
        $stmt = $db->prepare("SELECT * FROM sessions WHERE is_visible=1 AND day=? ORDER BY sort_order ASC, start_time ASC");
        $stmt->execute([$day]);
    } else {
        $stmt = $db->query("SELECT * FROM sessions WHERE is_visible=1 ORDER BY day ASC, sort_order ASC, start_time ASC");
    }
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo json_encode([]);
}
