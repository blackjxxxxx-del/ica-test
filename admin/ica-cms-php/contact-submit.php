<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://icahubthailand.org');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mailer.php';

// Honeypot check
if (!checkHoneypot('website_url')) {
    echo json_encode(['success' => false, 'message' => 'Bot detected']);
    exit;
}

// Rate limiting: max 5 contacts per 10 minutes per IP
if (!checkRateLimit('contact', 5, 600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait before submitting again.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

$name         = trim($data['name']         ?? '');
$email        = trim($data['email']        ?? '');
$subject      = trim($data['subject']      ?? 'General Enquiry');
$message      = trim($data['message']      ?? '');
$organization = trim($data['organization'] ?? '');

if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลให้ครบ']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'อีเมลไม่ถูกต้อง']);
    exit;
}

// 1. Save to database
try {
    $db = getDB();
    $db->prepare("INSERT INTO contacts (id, name, email, subject, message, organization, is_read, is_replied, created_at, updated_at) VALUES (?,?,?,?,?,?,0,0,NOW(),NOW())")
       ->execute([cuid(), $name, $email, $subject, $message, $organization]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// 2. Notify admin (background — ไม่บล็อก response)
echo json_encode(['success' => true, 'message' => 'ส่งข้อความสำเร็จ']);
flush();
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();

$orgLine = $organization ? "<tr><td style='color:#64748b;width:120px;padding:6px 0'>Organization</td><td style='padding:6px 0;font-weight:600'>".htmlspecialchars($organization)."</td></tr>" : '';
$adminBodyHtml = buildEmailHtml(
    '📬 New Contact Message',
    "<table style='width:100%;border-collapse:collapse;margin-bottom:20px;'>
      <tr><td style='color:#64748b;width:120px;padding:6px 0'>From</td><td style='padding:6px 0;font-weight:600'>".htmlspecialchars($name)."</td></tr>
      <tr><td style='color:#64748b;padding:6px 0'>Email</td><td style='padding:6px 0'><a href='mailto:".htmlspecialchars($email)."' style='color:#274c77'>".htmlspecialchars($email)."</a></td></tr>
      {$orgLine}
      <tr><td style='color:#64748b;padding:6px 0'>Subject</td><td style='padding:6px 0;font-weight:600'>".htmlspecialchars($subject)."</td></tr>
    </table>
    <div style='background:#f8fafc;border-left:4px solid #ffb400;border-radius:4px;padding:16px 20px;margin-top:8px;'>
      <div style='font-size:13px;color:#64748b;margin-bottom:8px;text-transform:uppercase;letter-spacing:1px;font-weight:700;'>Message</div>
      <div style='white-space:pre-wrap;color:#1e293b;'>".htmlspecialchars($message)."</div>
    </div>
    <div style='margin-top:24px;text-align:center;'>
      <a href='https://icahubthailand.org/admin/ica-cms-php/contacts.php' style='display:inline-block;background:#1a3a5f;color:#ffffff;padding:12px 28px;border-radius:8px;font-weight:600;text-decoration:none;'>View in Admin Panel →</a>
    </div>"
);

sendMail('icahubthailand@gmail.com', '[ICA Contact] ' . $subject, $adminBodyHtml, $email, true);
