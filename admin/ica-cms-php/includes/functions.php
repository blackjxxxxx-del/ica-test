<?php
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-') ?: 'untitled';
}

function flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function uploadImage($file, int $maxW = 1600, int $maxH = 1600): string|false {
    require_once __DIR__ . '/../config.php';
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) return false;
    $filename = uniqid('img_') . '.' . $ext;
    $dest = UPLOAD_DIR . $filename;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
    resizeImage($dest, $maxW, $maxH); // auto-resize large images
    return UPLOAD_URL . $filename;
}

function resizeImage(string $path, int $maxW = 1600, int $maxH = 1600): void {
    if (!function_exists('imagecreatefromjpeg')) return;
    $info = @getimagesize($path);
    if (!$info) return;
    [$w, $h, $type] = $info;
    if ($w <= $maxW && $h <= $maxH) return; // already small enough

    $ratio = min($maxW / $w, $maxH / $h);
    $nw = (int)round($w * $ratio);
    $nh = (int)round($h * $ratio);

    $src = match($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
        IMAGETYPE_PNG  => @imagecreatefrompng($path),
        IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        IMAGETYPE_GIF  => @imagecreatefromgif($path),
        default        => null,
    };
    if (!$src) return;

    $dst = imagecreatetruecolor($nw, $nh);
    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF])) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    match($type) {
        IMAGETYPE_JPEG => imagejpeg($dst, $path, 85),
        IMAGETYPE_PNG  => imagepng($dst, $path, 8),
        IMAGETYPE_WEBP => imagewebp($dst, $path, 85),
        IMAGETYPE_GIF  => imagegif($dst, $path),
        default        => null,
    };
    imagedestroy($src);
    imagedestroy($dst);
}

function cuid() {
    return 'c' . bin2hex(random_bytes(12));
}

// ── CSRF ───────────────────────────────────────────────────────
function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ── Rate Limiting (DB-based) ───────────────────────────────────
function checkRateLimit(string $action, int $maxRequests = 5, int $windowSeconds = 300): bool {
    try {
        $db  = getDB();
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $now = date('Y-m-d H:i:s');

        // Ensure table exists
        $db->exec("CREATE TABLE IF NOT EXISTS `rate_limits` (
            `id`           VARCHAR(200) NOT NULL,
            `ip`           VARCHAR(45)  NOT NULL,
            `action`       VARCHAR(100) NOT NULL,
            `requests`     INT          NOT NULL DEFAULT 1,
            `window_start` DATETIME     NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `rl_ip_action` (`ip`,`action`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Clean old windows
        $db->prepare("DELETE FROM rate_limits WHERE TIMESTAMPDIFF(SECOND, window_start, NOW()) > ?")
           ->execute([$windowSeconds]);

        // Upsert
        $db->prepare("INSERT INTO rate_limits (id, ip, action, requests, window_start)
                      VALUES (?, ?, ?, 1, ?)
                      ON DUPLICATE KEY UPDATE
                        requests = IF(TIMESTAMPDIFF(SECOND, window_start, NOW()) > ?, 1, requests + 1),
                        window_start = IF(TIMESTAMPDIFF(SECOND, window_start, NOW()) > ?, ?, window_start)")
           ->execute([md5($ip . $action), $ip, $action, $now, $windowSeconds, $windowSeconds, $now]);

        // Re-query properly
        $stmt = $db->prepare("SELECT requests FROM rate_limits WHERE ip=? AND action=?");
        $stmt->execute([$ip, $action]);
        $count = (int) $stmt->fetchColumn();

        return $count <= $maxRequests;
    } catch (Exception $e) {
        return true; // fail open
    }
}

// ── Honeypot check (bot trap) ──────────────────────────────────
function checkHoneypot(string $field = 'website'): bool {
    return empty($_POST[$field]); // true = human, false = bot
}

// ── Activity Logger ────────────────────────────────────────────
function logActivity(string $action, string $details = '', ?string $userId = null, ?string $userName = null): void {
    try {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $uid   = $userId   ?? ($_SESSION['admin_id']   ?? 'system');
        $uname = $userName ?? ($_SESSION['admin_name'] ?? 'System');
        $ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS `activity_log` (
            `id`         VARCHAR(36)   NOT NULL,
            `user_id`    VARCHAR(36)   NOT NULL DEFAULT 'system',
            `user_name`  VARCHAR(255)  NOT NULL DEFAULT '',
            `action`     VARCHAR(255)  NOT NULL,
            `details`    TEXT,
            `ip`         VARCHAR(45)   NOT NULL,
            `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `al_user` (`user_id`),
            KEY `al_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->prepare("INSERT INTO activity_log (id, user_id, user_name, action, details, ip, created_at) VALUES (?,?,?,?,?,?,NOW())")
           ->execute([cuid(), $uid, $uname, $action, $details, $ip]);
    } catch (Exception $e) {
        // fail silently
    }
}
