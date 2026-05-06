<?php
/**
 * notify-send.php — ส่งเมลแจ้งเตือนหา Notify List
 *
 * เรียกได้ 2 วิธี:
 *   1. Cron Job:  php /path/to/notify-send.php submit
 *   2. แอดมิน:   GET notify-send.php?mode=submit&key=SECRET  (ต้อง login)
 *
 * Cron ตัวอย่าง (cPanel):
 *   0 8 31 3 * php /home/USERNAME/public_html/admin/ica-cms-php/notify-send.php submit
 */

// ── รับค่า mode (submit / register) ──────────────────────────
$isCLI  = (php_sapi_name() === 'cli');
$mode   = $isCLI ? ($argv[1] ?? 'submit') : ($_GET['mode'] ?? 'submit');
$mode   = in_array($mode, ['submit','register']) ? $mode : 'submit';

// ── ถ้าเรียกจาก browser ต้อง login ──────────────────────────
if (!$isCLI) {
    require_once __DIR__ . '/includes/auth.php';
    requireLogin();
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

// ── เนื้อหาเมล ────────────────────────────────────────────────
$subjects = [
    'submit'   => 'ICA Thailand Hub 2026 — Submission Portal is Now Open!',
    'register' => 'ICA Thailand Hub 2026 — Registration is Now Open!',
];

$bodies = [
    'submit' => "Dear Colleague,\n\n"
        . "We are pleased to announce that the Abstract Submission Portal for\n"
        . "ICA Regional Hub Thailand 2026 is now open!\n\n"
        . "🗓  Conference: 6–7 June 2026\n"
        . "📍 Venue: Faculty of Communication Arts, Chulalongkorn University, Bangkok\n"
        . "🔗 Submit now: https://icahubthailand.org/submission.html\n\n"
        . "Submission deadline: 30 April 2026\n\n"
        . "We look forward to receiving your abstract.\n\n"
        . "Best regards,\n"
        . "ICA Thailand Hub 2026 Organizing Committee\n"
        . "https://icahubthailand.org\n\n"
        . "---\n"
        . "You received this email because you signed up for submission notifications.\n"
        . "To unsubscribe, reply to this email.",

    'register' => "Dear Colleague,\n\n"
        . "We are pleased to announce that Registration for\n"
        . "ICA Regional Hub Thailand 2026 is now open!\n\n"
        . "🗓  Conference: 6–7 June 2026\n"
        . "📍 Venue: Faculty of Communication Arts, Chulalongkorn University, Bangkok\n"
        . "🔗 Register now: https://icahubthailand.org/registration.html\n\n"
        . "Early-Bird Registration: 15 March – 10 May 2026\n\n"
        . "We look forward to welcoming you to Bangkok!\n\n"
        . "Best regards,\n"
        . "ICA Thailand Hub 2026 Organizing Committee\n"
        . "https://icahubthailand.org\n\n"
        . "---\n"
        . "You received this email because you signed up for registration notifications.\n"
        . "To unsubscribe, reply to this email.",
];

// ── ดึง email ที่ยังไม่ได้ส่ง ──────────────────────────────────
$db = getDB();

// เพิ่ม column sent_at ถ้ายังไม่มี
try {
    $db->exec("ALTER TABLE notify_list ADD COLUMN IF NOT EXISTS sent_at datetime DEFAULT NULL");
} catch (Exception $e) {
    // MySQL เก่าไม่รองรับ IF NOT EXISTS — ลองแบบ fallback
    try { $db->exec("ALTER TABLE notify_list ADD COLUMN sent_at datetime DEFAULT NULL"); } catch(Exception $e2) {}
}

$stmt = $db->prepare("SELECT id, email FROM notify_list WHERE mode = ? AND sent_at IS NULL");
$stmt->execute([$mode]);
$rows = $stmt->fetchAll();

if (!$rows) {
    echo "No unsent emails for mode: $mode\n";
    exit;
}

$subject = $subjects[$mode];
$body    = $bodies[$mode];

$sent = 0; $failed = 0;

foreach ($rows as $row) {
    $ok = sendMail($row['email'], $subject, $body);
    if ($ok) {
        $db->prepare("UPDATE notify_list SET sent_at = NOW() WHERE id = ?")
           ->execute([$row['id']]);
        $sent++;
        echo "✓ Sent to: {$row['email']}\n";
    } else {
        $failed++;
        echo "✗ Failed:  {$row['email']}\n";
    }
    // หน่วงนิดหน่อยป้องกัน spam filter
    if (!$isCLI) usleep(300000); // 0.3s
    else sleep(1);
}

echo "\nDone — Sent: $sent | Failed: $failed | Mode: $mode\n";
