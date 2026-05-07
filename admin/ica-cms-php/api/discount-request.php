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
$promoCode   = strtoupper(trim($_POST['promoCode'] ?? ''));

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
if (!in_array($discTier, ['20pct', '100pct', 'none'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid discount tier']);
    exit;
}

// Document required for student verification (discountTier=none) and optional for promo code tiers
$hasDocument = !empty($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK;
if (!$hasDocument && $discTier === 'none') {
    http_response_code(400);
    echo json_encode(['error' => 'Supporting document is required for student verification']);
    exit;
}
if (!$hasDocument && in_array($discTier, ['20pct', '100pct'])) {
    // Promo code tiers: document upload is optional — skip file handling
    $hasDocument = false;
}

$savedFilename = null;
$originalName  = null;
$destPath      = null;

if ($hasDocument) {
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
    $originalName  = $file['name'];

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save document']);
        exit;
    }
}

// Validate & lock promo code (if provided)
$resolvedCode = null;
if ($promoCode) {
    // Include promo-codes logic inline to avoid circular dependency
    $db = getDB();

    // Ensure promo_codes table exists
    $db->exec("CREATE TABLE IF NOT EXISTS `promo_codes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `code` varchar(100) NOT NULL,
        `discount_tier` varchar(20) NOT NULL,
        `category` varchar(100) NOT NULL,
        `is_used` tinyint(1) NOT NULL DEFAULT 0,
        `used_by_name` varchar(255) DEFAULT NULL,
        `used_by_email` varchar(255) DEFAULT NULL,
        `used_at` datetime DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `promo_codes_code_uq` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $db->prepare("SELECT * FROM promo_codes WHERE UPPER(code) = ?");
    $stmt->execute([$promoCode]);
    $codeRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$codeRow) {
        @unlink($destPath);
        http_response_code(400);
        echo json_encode(['error' => 'Invalid promo code']);
        exit;
    }
    if ($codeRow['is_used']) {
        @unlink($destPath);
        http_response_code(400);
        echo json_encode(['error' => 'This promo code has already been used']);
        exit;
    }
    if ($codeRow['discount_tier'] !== $discTier) {
        @unlink($destPath);
        http_response_code(400);
        echo json_encode(['error' => 'Code does not match the selected discount tier']);
        exit;
    }

    // Mark code as used atomically
    $db->prepare("UPDATE promo_codes SET is_used=1, used_by_name=?, used_by_email=?, used_at=NOW() WHERE id=?")
       ->execute([$fullName, $email, $codeRow['id']]);

    $resolvedCode = $codeRow['code'];
}

// Insert to database
try {
    $db = $db ?? getDB();

    // Ensure promo_code column exists on registrations
    try { $db->exec("ALTER TABLE registrations ADD COLUMN `promo_code` varchar(100) DEFAULT NULL"); }
    catch (Exception $e) { /* already exists */ }

    $db->prepare("
        INSERT INTO registrations
            (full_name, email, format, attendee_status, discount_tier,
             payment_status, discount_approval_status,
             document_filename, document_original_name, promo_code)
        VALUES (?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?)
    ")->execute([
        $fullName,
        $email,
        $format,
        $attendeeSts ?: null,
        $discTier,
        $savedFilename,
        $originalName,
        $resolvedCode,
    ]);

    echo json_encode(['id' => $db->lastInsertId(), 'message' => 'Discount request submitted successfully']);
} catch (Exception $e) {
    // Clean up uploaded file if DB fails
    if ($destPath) @unlink($destPath);
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
