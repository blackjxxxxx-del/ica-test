<?php
/**
 * api/upload-image.php — Admin-only image upload for article body
 * Called by Quill editor image handler via AJAX.
 * Returns: {"ok":true,"url":"https://..."}
 */
session_start();
header('Content-Type: application/json');

// Auth check
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['ok' => false, 'error' => 'No file']);
    exit;
}

$rel = uploadImage($_FILES['file']);
if (!$rel) {
    echo json_encode(['ok' => false, 'error' => 'Upload failed — check file type or size (max 5MB)']);
    exit;
}

// Build absolute URL so images work on the public website
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
// Script is at /admin/ica-cms-php/api/upload-image.php → base = /admin/ica-cms-php/
$base   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$url    = $scheme . '://' . $host . $base . '/' . ltrim($rel, '/');

echo json_encode(['ok' => true, 'url' => $url]);
