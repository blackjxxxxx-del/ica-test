<?php
/**
 * unsubscribe.php — Public endpoint for email unsubscribe
 * URL: /admin/ica-cms-php/unsubscribe.php?token=xxx
 */
require_once __DIR__ . '/includes/db.php';

$token = trim($_GET['token'] ?? '');
$done  = false;
$error = false;

if ($token) {
    try {
        $db   = getDB();
        $stmt = $db->prepare("DELETE FROM notify_list WHERE unsubscribe_token = ?");
        $stmt->execute([$token]);
        $done = $stmt->rowCount() > 0;
        if (!$done) $error = true; // token not found
    } catch (Exception $e) {
        $error = true;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unsubscribe — ICA Thailand Hub</title>
<link rel="icon" href="/favicon.png">
<style>
  body{font-family:'Inter',-apple-system,sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
  .box{background:#fff;border-radius:16px;padding:48px 40px;text-align:center;max-width:460px;box-shadow:0 4px 24px rgba(0,0,0,.07)}
  .icon{font-size:48px;margin-bottom:16px}
  h1{font-size:22px;font-weight:700;color:#1a3a5f;margin:0 0 12px}
  p{color:#64748b;font-size:15px;line-height:1.6;margin:0 0 24px}
  a{display:inline-block;padding:12px 28px;background:#1a3a5f;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px}
</style>
</head>
<body>
<div class="box">
  <?php if ($done): ?>
    <div class="icon">✅</div>
    <h1>Unsubscribed Successfully</h1>
    <p>You have been removed from our notification list. You will no longer receive emails from ICA Thailand Hub.</p>
    <a href="https://icahubthailand.org">Back to Homepage</a>
  <?php elseif ($error): ?>
    <div class="icon">❌</div>
    <h1>Link Not Found</h1>
    <p>This unsubscribe link is invalid or has already been used. If you continue to receive emails, please contact us.</p>
    <a href="https://icahubthailand.org/contact.html">Contact Us</a>
  <?php else: ?>
    <div class="icon">❓</div>
    <h1>Invalid Request</h1>
    <p>No unsubscribe token was provided.</p>
    <a href="https://icahubthailand.org">Back to Homepage</a>
  <?php endif; ?>
</div>
</body>
</html>
