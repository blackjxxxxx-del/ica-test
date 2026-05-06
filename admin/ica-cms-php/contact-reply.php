<?php
/**
 * contact-reply.php — Send reply email to contact sender (admin only)
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']); exit;
}

$id      = trim($_POST['id']      ?? '');
$email   = trim($_POST['email']   ?? '');
$name    = trim($_POST['name']    ?? '');
$subject = trim($_POST['subject'] ?? '');
$body    = trim($_POST['body']    ?? '');

if (!$id || !$email || !$body) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']); exit;
}

$htmlBody = buildEmailHtml(
    'Re: ' . htmlspecialchars($subject),
    '<p>Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>
     <p>Thank you for contacting ICA Thailand Hub 2026. Here is our reply:</p>
     <div style="background:#f8fafc;border-left:4px solid #ffb400;border-radius:4px;padding:16px 20px;margin:16px 0;white-space:pre-wrap;color:#1e293b;">'
     . nl2br(htmlspecialchars($body)) . '</div>',
    '<br><br>This email was sent in response to your enquiry at icahubthailand.org'
);

$sent = sendMail($email, 'Re: ' . $subject, $htmlBody, 'icahubthailand@gmail.com', true);

if ($sent) {
    getDB()->prepare("UPDATE contacts SET is_replied=1, is_read=1, updated_at=NOW() WHERE id=?")
           ->execute([$id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'ส่งอีเมลไม่สำเร็จ']);
}
