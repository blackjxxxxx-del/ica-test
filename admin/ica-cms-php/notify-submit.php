<?php
/**
 * Public endpoint — รับ email จาก Coming-soon pages
 * ไม่ต้อง login, เรียกจาก JavaScript fetch
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://icahubthailand.org');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['ok'=>false,'message'=>'Method not allowed']); exit; }

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Rate limiting
if (!checkRateLimit('notify', 3, 600)) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$mode  = in_array($_POST['mode'] ?? '', ['submit','register']) ? $_POST['mode'] : 'submit';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok'=>false,'message'=>'Invalid email address']);
    exit;
}

try {
    $db = getDB();

    // สร้างตารางถ้ายังไม่มี
    $db->exec("CREATE TABLE IF NOT EXISTS `notify_list` (
        `id`         varchar(36)  NOT NULL,
        `email`      varchar(255) NOT NULL,
        `mode`       varchar(50)  NOT NULL DEFAULT 'submit',
        `unsubscribe_token` VARCHAR(64) NOT NULL DEFAULT '',
        `created_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `notify_list_email_mode` (`email`,`mode`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // INSERT หรือ ignore ถ้าซ้ำ
    $token = bin2hex(random_bytes(32));
    $db->prepare("INSERT IGNORE INTO notify_list (id, email, mode, unsubscribe_token) VALUES (?, ?, ?, ?)")
       ->execute([cuid(), $email, $mode, $token]);

    echo json_encode(['ok'=>true,'message'=>'บันทึกสำเร็จ']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'Database error']);
}
