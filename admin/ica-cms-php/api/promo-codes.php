<?php
/**
 * api/promo-codes.php — ICA-TH 2026
 * Promo code management (MySQL-backed, single-use codes).
 *
 * Public endpoints:
 *   POST  action=validate  → check if code is valid & unused
 *
 * Admin endpoints (no extra auth — Dashboard already requires Firebase login):
 *   GET                    → list all codes
 *   POST  action=add       → add a new code
 *   POST  action=delete    → delete unused code  { id }
 *   POST  action=reset     → reset used → unused { id }
 *
 * Internal endpoint (called by discount-request.php):
 *   POST  action=use       → atomically mark code as used { code, name, email, tier }
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/db.php';

$db = getDB();

/* ── Ensure table exists (safe to call on every request) ── */
$db->exec("CREATE TABLE IF NOT EXISTS `promo_codes` (
    `id`            int(11)      NOT NULL AUTO_INCREMENT,
    `code`          varchar(100) NOT NULL,
    `discount_tier` varchar(20)  NOT NULL COMMENT '20pct | 100pct',
    `category`      varchar(100) NOT NULL,
    `is_used`       tinyint(1)   NOT NULL DEFAULT 0,
    `used_by_name`  varchar(255) DEFAULT NULL,
    `used_by_email` varchar(255) DEFAULT NULL,
    `used_at`       datetime     DEFAULT NULL,
    `created_at`    datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `promo_codes_code_uq` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

/* ── Ensure registrations.promo_code column exists ── */
try {
    $db->exec("ALTER TABLE registrations ADD COLUMN `promo_code` varchar(100) DEFAULT NULL");
} catch (Exception $e) { /* column already exists — ignore */ }

/* ── Seed initial codes if table is empty ── */
function seedCodes($db) {
    $count = (int)$db->query("SELECT COUNT(*) FROM promo_codes")->fetchColumn();
    if ($count > 0) return;

    $ins = $db->prepare("INSERT IGNORE INTO promo_codes (code, discount_tier, category) VALUES (?, ?, ?)");

    /* Presenter — 100% discount (ICA-TH2026-0001 … 0009) */
    for ($i = 1; $i <= 9; $i++)
        $ins->execute(['ICA-TH2026-' . str_pad($i, 4, '0', STR_PAD_LEFT), '100pct', 'Presenter (100%)']);

    /* Non-presenter — 20% discount */
    foreach (['N001', 'N002', 'N003'] as $n)
        $ins->execute(['ICA-TH2026-' . $n, '20pct', 'Non-presenter (20%)']);

    /* Non-presenter Nitade — 100% discount (ICA-TH2026-Nitade001 … 016) */
    for ($i = 1; $i <= 16; $i++)
        $ins->execute(['ICA-TH2026-Nitade' . str_pad($i, 3, '0', STR_PAD_LEFT), '100pct', 'Non-presenter Nitade (100%)']);
}
seedCodes($db);

/* ════════════════════════════════════════════════════════
   GET — list all codes (admin Dashboard)
════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $db->query("SELECT * FROM promo_codes ORDER BY category, code")->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

/* ════════════════════════════════════════════════════════
   POST — various actions
════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? '';

    /* ── validate (public — called from registration form) ── */
    if ($action === 'validate') {
        $code = strtoupper(trim($data['code'] ?? ''));
        if (!$code) { echo json_encode(['valid' => false, 'reason' => 'No code provided']); exit; }

        $stmt = $db->prepare("SELECT * FROM promo_codes WHERE UPPER(code) = ?");
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row)          { echo json_encode(['valid' => false, 'reason' => 'Invalid code']); exit; }
        if ($row['is_used']) { echo json_encode(['valid' => false, 'reason' => 'This code has already been used']); exit; }

        echo json_encode([
            'valid'        => true,
            'discountTier' => $row['discount_tier'],
            'category'     => $row['category'],
            'displayCode'  => $row['code'],
        ]);
        exit;
    }

    /* ── use (internal — called by discount-request.php atomically) ── */
    if ($action === 'use') {
        $code  = strtoupper(trim($data['code']  ?? ''));
        $name  = trim($data['name']             ?? '');
        $email = trim($data['email']            ?? '');
        $tier  = trim($data['tier']             ?? '');

        $stmt = $db->prepare("SELECT * FROM promo_codes WHERE UPPER(code) = ?");
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row)          { http_response_code(400); echo json_encode(['error' => 'Invalid promo code']); exit; }
        if ($row['is_used']) { http_response_code(400); echo json_encode(['error' => 'Code already used']); exit; }
        if ($tier && $row['discount_tier'] !== $tier) {
            http_response_code(400); echo json_encode(['error' => 'Code does not match the selected discount tier']); exit;
        }

        $db->prepare("UPDATE promo_codes SET is_used=1, used_by_name=?, used_by_email=?, used_at=NOW() WHERE id=?")
           ->execute([$name, $email, $row['id']]);

        echo json_encode(['ok' => true, 'code' => $row['code']]);
        exit;
    }

    /* ── add (admin) ── */
    if ($action === 'add') {
        $code = strtoupper(trim($data['code']        ?? ''));
        $tier = trim($data['discountTier']           ?? '');
        $cat  = trim($data['category']               ?? '');

        if (!$code || !$tier || !$cat) { http_response_code(400); echo json_encode(['error' => 'Missing fields']); exit; }
        if (!in_array($tier, ['20pct', '100pct']))   { http_response_code(400); echo json_encode(['error' => 'Invalid discount tier']); exit; }

        try {
            $db->prepare("INSERT INTO promo_codes (code, discount_tier, category) VALUES (?, ?, ?)")
               ->execute([$code, $tier, $cat]);
            echo json_encode(['ok' => true]);
        } catch (Exception $e) {
            http_response_code(400); echo json_encode(['error' => 'Code already exists']);
        }
        exit;
    }

    /* ── delete (admin — only if unused) ── */
    if ($action === 'delete') {
        $id = (int)($data['id'] ?? 0);
        $stmt = $db->prepare("SELECT is_used FROM promo_codes WHERE id=?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row)          { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        if ($row['is_used']) { http_response_code(400); echo json_encode(['error' => 'Cannot delete a used code']); exit; }

        $db->prepare("DELETE FROM promo_codes WHERE id=?")->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    /* ── reset (admin — mark used → unused) ── */
    if ($action === 'reset') {
        $id = (int)($data['id'] ?? 0);
        $db->prepare("UPDATE promo_codes SET is_used=0, used_by_name=NULL, used_by_email=NULL, used_at=NULL WHERE id=?")
           ->execute([$id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
