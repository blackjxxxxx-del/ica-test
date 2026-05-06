<?php
/**
 * api/discount-request.php
 * Public endpoint — receives discount request with supporting document upload.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

require_once __DIR__ . '/../includes/db.php';

$fullName    = trim($_POST['fullName']       ?? '');
$email       = trim($_POST['email']          ?? '');
$format      = trim($_POST['format']         ?? '');
$attendeeSts = trim($_POST['attendeeStatus'] ?? '');
$discTier    = trim($_POST['discountTier']   ?? '');

// Validate required fields
if (!$fullName || !$email || !$format || !$discTier) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}
if (!in_array($discTier, ['20pct', '100pct'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid discount tier']);
    exit;
}

// Validate file upload
if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Supporting document is required']);
    exit;
}

$file     = $_FILES['document'];
$maxSize  = 10 * 1024 * 1024; // 10 MB
$allowExt = ['pdf', 'jpg', 'jpeg', 'png'];
$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 10 MB']);
    exit;
}
if (!in_array($ext, $allowExt)) {
    http_response_code(400);
    echo json_encode(['error' => 'Only PDF, JPG, PNG files are allowed']);
    exit;
}

// Save file
$uploadDir = __DIR__ . '/../uploads/registrations/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$savedFilename = uniqid('doc_') . '_' . time() . '.' . $ext;
$destPath      = $uploadDir . $savedFilename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save document']);
    exit;
}

// Insert to database
try {
    $db = getDB();
    $db->prepare("
        INSERT INTO registrations
            (full_name, email, format, attendee_status, discount_tier,
             payment_status, discount_approval_status,
             document_filename, document_original_name)
        VALUES (?, ?, ?, ?, ?, 'pending', 'pending', ?, ?)
    ")->execute([
        $fullName,
        $email,
        $format,
        $attendeeSts ?: null,
        $discTier,
        $savedFilename,
        $file['name'],
    ]);

    echo json_encode(['id' => $db->lastInsertId(), 'message' => 'Discount request submitted successfully']);
} catch (Exception $e) {
    // Clean up uploaded file if DB fails
    @unlink($destPath);
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
