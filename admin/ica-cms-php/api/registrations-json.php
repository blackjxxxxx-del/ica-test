<?php
/**
 * api/registrations-json.php
 * คืนข้อมูล registrations จาก MySQL เป็น JSON
 * สำหรับ Dashboard.html อ่านแทน Firestore
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

$PRICE_MAP = [
    'onsite'  => ['student' => ['early' => ['20pct' => ['price'=>2400,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=VMTejit_AIMZyEV-WIsB4w']], 'standard' => ['20pct' => ['price'=>3600,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=Ohsi8u1aEML0teGkThWVwQ']]],
                  'academic' => ['early' => ['20pct' => ['price'=>4000,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=Kx-hxOTLzC6iS19WxA7g4w']], 'standard' => ['20pct' => ['price'=>6400,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=Vxp7PXyRrXhY2VNClLwxeA']]]],
    'virtual' => ['student' => ['early' => ['20pct' => ['price'=>1600,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=_v8Zu7FWvQGUNfxOTQToqw']], 'standard' => ['20pct' => ['price'=>2800,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=Lh__Pmmigs2IgchDGa83jA']]],
                  'academic' => ['early' => ['20pct' => ['price'=>2800,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=hqI8xQcw8M5pkQv-TAEwig']], 'standard' => ['20pct' => ['price'=>4400,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=YJgIi1xltB27YpRvZYxMmA']]]],
    'non'     => ['all' => ['early' => ['20pct' => ['price'=>2000,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=NyDbLMLs1F9z_123qBtHiw']], 'standard' => ['20pct' => ['price'=>3600,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=fjJIj8Ww439WWy10dOdh4Q']]]],
];
$FMT_LABELS = ['onsite'=>'Onsite / Poster Presenter','virtual'=>'Virtual Presenter','non'=>'Non Presenter'];
$STS_LABELS = ['student'=>'Student','academic'=>'Academic / Faculty / Professional'];

// ── POST: update status (approve/reject/mark paid) ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? '';
    $id     = (int)($data['id'] ?? 0);

    if (!$id) { http_response_code(400); echo json_encode(['error'=>'Missing id']); exit; }

    $db = getDB();
    if ($action === 'approve') {
        $stmt = $db->prepare("SELECT * FROM registrations WHERE id=?");
        $stmt->execute([$id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reg) {
            $newPayStatus = ($reg['discount_tier'] === '100pct') ? 'paid' : 'pending';
            $db->prepare("UPDATE registrations SET discount_approval_status='approved', payment_status=? WHERE id=?")->execute([$newPayStatus, $id]);
            // Send approval email
            $fmt = $FMT_LABELS[$reg['format']] ?? $reg['format'];
            $sts = $reg['attendee_status'] ? ($STS_LABELS[$reg['attendee_status']] ?? $reg['attendee_status']) : 'All Participants';
            if ($reg['discount_tier'] === '100pct') {
                $subject = 'ICA-TH 2026 — Free Registration Confirmed';
                $body = buildEmailHtml('Free Registration Confirmed ✓',
                    "<p>Dear <strong>".htmlspecialchars($reg['full_name'])."</strong>,</p>
                    <p>Your <strong>100% discount</strong> has been approved. You are registered for <em>ICA Regional Hub Thailand 2026</em> at <strong>no cost</strong>.</p>
                    <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin-top:16px;'>
                        ✅ <strong>No payment required.</strong> You will receive event details closer to the conference.
                    </div>"
                );
            } else {
                $statusKey  = ($reg['format'] === 'non') ? 'all' : $reg['attendee_status'];
                $earlyBirdEnd = new DateTime('2026-05-10 23:59:59', new DateTimeZone('Asia/Bangkok'));
                $rateKey    = (new DateTime('now', new DateTimeZone('Asia/Bangkok')) <= $earlyBirdEnd) ? 'early' : 'standard';
                $rateData   = $PRICE_MAP[$reg['format']][$statusKey][$rateKey]['20pct'] ?? null;
                $btnStyle   = $rateKey === 'early'
                    ? 'background:#f59e0b;color:#1c1917;'
                    : 'background:#6366f1;color:#fff;';
                $btnLabel   = $rateKey === 'early'
                    ? '🐦 Early Bird Rate'
                    : '📅 Standard Rate';
                $payLinks   = $rateData
                    ? "<a href='{$rateData['url']}' style='display:inline-block;{$btnStyle}text-decoration:none;padding:14px 28px;border-radius:10px;font-weight:700;'>{$btnLabel} — ".number_format($rateData['price'])." THB</a>"
                    : '';
                $subject = 'ICA-TH 2026 — Discount Approved — Complete Your Payment';
                $body = buildEmailHtml('Discount Approved! 🎉',
                    "<p>Dear <strong>".htmlspecialchars($reg['full_name'])."</strong>,</p>
                    <p>Your <strong>20% discount</strong> has been approved. Please complete your registration:</p>
                    <div style='margin:16px 0;'>$payLinks</div>"
                );
            }
            sendMail($reg['email'], $subject, $body, '', true);
        }
        echo json_encode(['ok'=>true]);
    } elseif ($action === 'reject') {
        $stmt = $db->prepare("SELECT * FROM registrations WHERE id=?");
        $stmt->execute([$id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reg) {
            $db->prepare("UPDATE registrations SET discount_approval_status='rejected' WHERE id=?")->execute([$id]);
            $body = buildEmailHtml('Discount Request Update',
                "<p>Dear <strong>".htmlspecialchars($reg['full_name'])."</strong>,</p>
                <p>We regret to inform you that your discount request could not be approved at this time.</p>
                <p>You are welcome to register at the standard rate on our registration page.</p>"
            );
            sendMail($reg['email'], 'ICA-TH 2026 — Discount Request Update', $body, '', true);
        }
        echo json_encode(['ok'=>true]);
    } elseif ($action === 'pay') {
        $val = $data['val'] ?? 'paid';
        $val = in_array($val,['paid','pending']) ? $val : 'pending';
        $db->prepare("UPDATE registrations SET payment_status=? WHERE id=?")->execute([$val,$id]);
        echo json_encode(['ok'=>true]);
    } else {
        http_response_code(400); echo json_encode(['error'=>'Unknown action']);
    }
    exit;
}

// ── GET: ดึงข้อมูล ───────────────────────────────────────────────────────────
$db     = getDB();
$type   = $_GET['type']   ?? 'all';       // 'discount' | 'all'
$status = $_GET['status'] ?? '';

$sql    = "SELECT * FROM registrations WHERE 1=1";
$params = [];

if ($type === 'discount') {
    $sql .= " AND discount_approval_status IN ('pending','approved','rejected')";
    if ($status && in_array($status, ['pending','approved','rejected'])) {
        $sql .= " AND discount_approval_status=?"; $params[] = $status;
    }
} else {
    if ($status && in_array($status, ['pending','paid'])) {
        $sql .= " AND payment_status=?"; $params[] = $status;
    }
}

$sql .= " ORDER BY created_at DESC";
$stmt  = $db->prepare($sql);
$stmt->execute($params);
$rows  = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Base URL for uploaded documents (admin-accessible)
$docBase = (
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
        ? 'https://' : 'http://'
) . ($_SERVER['HTTP_HOST'] ?? 'icahubthailand.org') . '/admin/ica-cms-php/uploads/registrations/';

// แปลง field names ให้ตรงกับที่ Dashboard ใช้
$result = array_map(function($r) use ($docBase) {
    return [
        'id'                     => $r['id'],
        'fullName'               => $r['full_name'],
        'email'                  => $r['email'],
        'format'                 => $r['format'],
        'attendeeStatus'         => $r['attendee_status'],
        'discountTier'           => $r['discount_tier'],
        'selectedRate'           => $r['selected_rate'],
        'price'                  => $r['price'],
        'paymentUrl'             => $r['payment_url'],
        'paymentStatus'          => $r['payment_status'],
        'discountApprovalStatus' => $r['discount_approval_status'],
        'documentFilename'       => $r['document_filename'],
        'documentOriginalName'   => $r['document_original_name'],
        'documentUrl'            => $r['document_filename'] ? $docBase . rawurlencode($r['document_filename']) : null,
        'promoCode'              => $r['promo_code'] ?? null,
        'createdAt'              => $r['created_at'],
    ];
}, $rows);

echo json_encode(['data' => $result, 'total' => count($result)]);
