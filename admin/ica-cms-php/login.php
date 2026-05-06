<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error      = '';
$timeoutMsg = isset($_GET['timeout']) ? 'หมดเวลา กรุณาเข้าสู่ระบบใหม่' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        if (!verifyCsrf()) { $error = 'คำขอไม่ถูกต้อง'; } else
        try {
            $db   = getDB();
            $stmt = $db->prepare("SELECT id, password, name FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']      = $user['id'];
                $_SESSION['admin_name']    = $user['name'];
                $_SESSION['last_activity'] = time();
                logActivity('login', 'เข้าสู่ระบบสำเร็จ', $user['id'], $user['name']);
                header('Location: index.php');
                exit;
            } else {
                $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (Exception $e) {
            $error = 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาตรวจสอบ config.php';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบ';
    }
}
?><!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ — ICA CMS</title>
    <link rel="icon" href="https://icahubthailand.org/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; }
    </style>
</head>
<body>
<div class="login-card p-3">
    <div class="card shadow-sm p-4 p-md-5">
        <div class="text-center mb-4">
            <img src="https://icahubthailand.org/favicon.png" alt="ICA" style="width:56px;height:56px;object-fit:contain;border-radius:10px;">
            <h4 class="fw-bold mt-2 mb-0">ICA Thailand Hub</h4>
            <p class="text-muted small mt-1">Admin Panel</p>
        </div>

        <?php if ($timeoutMsg): ?>
        <div class="alert alert-warning py-2 small"><i class="bi bi-clock me-1"></i><?= e($timeoutMsg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold">อีเมล</label>
                <input type="email" name="email" class="form-control"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="admin@example.com" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">รหัสผ่าน</label>
                <input type="password" name="password" class="form-control"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                เข้าสู่ระบบ
            </button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
