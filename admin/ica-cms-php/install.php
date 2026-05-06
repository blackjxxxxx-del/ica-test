<?php
/**
 * ICA CMS — Installer
 * รันครั้งเดียวเพื่อสร้างตารางและ admin user
 * ลบไฟล์นี้ออกหลังจากติดตั้งเสร็จแล้ว!
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$done    = [];
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPass  = trim($_POST['admin_pass']  ?? '');
    $adminName  = trim($_POST['admin_name']  ?? 'Administrator');

    if (!$adminEmail || !$adminPass || strlen($adminPass) < 6) {
        $errors[] = 'กรุณากรอกอีเมล และรหัสผ่านอย่างน้อย 6 ตัวอักษร';
    } else {
        try {
            $db = getDB();

            $tables = [
                "CREATE TABLE IF NOT EXISTS `users` (
                    `id`         varchar(36)  NOT NULL,
                    `email`      varchar(255) NOT NULL,
                    `password`   varchar(255) NOT NULL,
                    `name`       varchar(255) NOT NULL,
                    `role`       varchar(50)  NOT NULL DEFAULT 'admin',
                    `created_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_email_key` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS `pages` (
                    `id`         varchar(36)  NOT NULL,
                    `slug`       varchar(255) NOT NULL,
                    `title`      varchar(255) NOT NULL,
                    `content`    longtext,
                    `meta_title` varchar(255) DEFAULT NULL,
                    `meta_desc`  text         DEFAULT NULL,
                    `is_visible` tinyint(1)   NOT NULL DEFAULT 1,
                    `created_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `pages_slug_key` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS `news` (
                    `id`           varchar(36)  NOT NULL,
                    `title`        varchar(255) NOT NULL,
                    `slug`         varchar(255) NOT NULL,
                    `content`      longtext,
                    `excerpt`      text         DEFAULT NULL,
                    `featured_img` varchar(500) DEFAULT NULL,
                    `status`       varchar(50)  NOT NULL DEFAULT 'draft',
                    `is_featured`  tinyint(1)   NOT NULL DEFAULT 0,
                    `published_at` datetime     DEFAULT NULL,
                    `created_at`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `news_slug_key` (`slug`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS `contacts` (
                    `id`           varchar(36)  NOT NULL,
                    `name`         varchar(255) NOT NULL,
                    `email`        varchar(255) NOT NULL,
                    `subject`      varchar(255) NOT NULL,
                    `message`      text         NOT NULL,
                    `organization` varchar(255) DEFAULT NULL,
                    `is_read`      tinyint(1)   NOT NULL DEFAULT 0,
                    `is_replied`   tinyint(1)   NOT NULL DEFAULT 0,
                    `created_at`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS `gallery_images` (
                    `id`         varchar(36)  NOT NULL,
                    `url`        varchar(500) NOT NULL,
                    `title`      varchar(255) DEFAULT NULL,
                    `alt`        varchar(255) DEFAULT NULL,
                    `category`   varchar(100) DEFAULT NULL,
                    `created_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS `settings` (
                    `id`         varchar(36)  NOT NULL,
                    `key`        varchar(255) NOT NULL,
                    `value`      longtext,
                    `created_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `settings_key_key` (`key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

                "CREATE TABLE IF NOT EXISTS `registrations` (
                    `id`                       int(11)      NOT NULL AUTO_INCREMENT,
                    `created_at`               datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `full_name`                varchar(255) NOT NULL,
                    `email`                    varchar(255) NOT NULL,
                    `format`                   varchar(50)  NOT NULL COMMENT 'onsite | virtual | non',
                    `attendee_status`          varchar(50)  DEFAULT NULL COMMENT 'student | academic | null',
                    `discount_tier`            varchar(20)  NOT NULL DEFAULT 'none' COMMENT 'none | 20pct | 100pct',
                    `selected_rate`            varchar(20)  DEFAULT NULL COMMENT 'early | standard',
                    `price`                    int(11)      DEFAULT NULL,
                    `payment_url`              text         DEFAULT NULL,
                    `payment_status`           varchar(20)  NOT NULL DEFAULT 'pending' COMMENT 'pending | paid',
                    `discount_approval_status` varchar(20)  NOT NULL DEFAULT 'not_required' COMMENT 'not_required | pending | approved | rejected',
                    `document_filename`        varchar(255) DEFAULT NULL,
                    `document_original_name`   varchar(255) DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            ];

            foreach ($tables as $sql) {
                $db->exec($sql);
                preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/', $sql, $m);
                $done[] = 'สร้างตาราง `' . ($m[1] ?? '?') . '` สำเร็จ';
            }

            // Create admin user
            $existing = $db->prepare("SELECT id FROM users WHERE email = ?");
            $existing->execute([$adminEmail]);
            if ($existing->fetch()) {
                // Update password
                $db->prepare("UPDATE users SET password=?, name=?, updated_at=NOW() WHERE email=?")
                   ->execute([password_hash($adminPass, PASSWORD_BCRYPT), $adminName, $adminEmail]);
                $done[] = 'อัปเดต admin user แล้ว';
            } else {
                $db->prepare("INSERT INTO users (id, email, password, name, role) VALUES (?,?,?,?,'admin')")
                   ->execute([cuid(), $adminEmail, password_hash($adminPass, PASSWORD_BCRYPT), $adminName]);
                $done[] = 'สร้าง admin user แล้ว';
            }

            // Default settings
            $defaults = [
                'site_name'        => 'ICA Thailand Hub',
                'contact_email'    => $adminEmail,
                'meta_title'       => 'ICA Thailand Hub 2026',
                'meta_description' => 'International Cooperative Alliance Thailand Hub',
            ];
            $ss = $db->prepare("INSERT INTO settings (id,`key`,value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
            foreach ($defaults as $k => $v) $ss->execute([cuid(), $k, $v]);

            $success = true;

        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?><!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install — ICA CMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>body{background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center}</style>
</head>
<body>
<div style="width:100%;max-width:480px" class="p-3">
    <div class="card shadow-sm p-4 p-md-5">
        <div class="text-center mb-4">
            <div style="font-size:2.5rem">🐘</div>
            <h4 class="fw-bold mt-1">ICA CMS Installer</h4>
            <p class="text-muted small">ติดตั้งระบบครั้งเดียว</p>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>ติดตั้งสำเร็จ!</strong>
            <ul class="mb-0 mt-2 small">
                <?php foreach ($done as $d): ?><li><?= e($d) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>ลบไฟล์ install.php ออกทันที</strong> เพื่อความปลอดภัย
        </div>
        <a href="login.php" class="btn btn-primary w-100">ไปหน้า Login &rarr;</a>

        <?php else: ?>

        <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger small"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-semibold">ชื่อ Admin</label>
                <input type="text" name="admin_name" class="form-control"
                       value="<?= e($_POST['admin_name'] ?? 'Administrator') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">อีเมล Admin</label>
                <input type="email" name="admin_email" class="form-control"
                       value="<?= e($_POST['admin_email'] ?? '') ?>"
                       placeholder="admin@example.com" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)</label>
                <input type="password" name="admin_pass" class="form-control"
                       placeholder="••••••••" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">ติดตั้ง</button>
        </form>

        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
