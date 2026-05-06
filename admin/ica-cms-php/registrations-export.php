<?php
/**
 * registrations-export.php — Export registrations as CSV (admin only)
 */
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$payFilter  = $_GET['pay']  ?? '';
$discFilter = $_GET['disc'] ?? '';
$from       = $_GET['from'] ?? '';
$to         = $_GET['to']   ?? '';

$sql    = "SELECT * FROM registrations WHERE 1=1";
$params = [];
if ($payFilter  && in_array($payFilter,  ['pending','paid']))                               { $sql .= " AND payment_status=?";          $params[] = $payFilter; }
if ($discFilter && in_array($discFilter, ['not_required','pending','approved','rejected'])) { $sql .= " AND discount_approval_status=?"; $params[] = $discFilter; }
if ($from) { $sql .= " AND DATE(created_at) >= ?"; $params[] = $from; }
if ($to)   { $sql .= " AND DATE(created_at) <= ?"; $params[] = $to; }
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql); $stmt->execute($params);
$rows = $stmt->fetchAll();

$FORMAT_LABELS = ['onsite'=>'Onsite / Poster Presenter','virtual'=>'Virtual Presenter','non'=>'Non Presenter'];
$STATUS_LABELS = ['student'=>'Student','academic'=>'Academic / Faculty / Professional'];
$DISC_LABELS   = ['none'=>'—','20pct'=>'20% OFF','100pct'=>'100% Free'];
$APPR_LABELS   = ['not_required'=>'—','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'];

$filename = 'registrations_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

fputcsv($out, ['#','Date','Full Name','Email','Format','Status','Discount','Rate','Price (THB)','Payment Status','Discount Approval']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'],
        $r['created_at'],
        $r['full_name'],
        $r['email'],
        $FORMAT_LABELS[$r['format']] ?? $r['format'],
        $r['attendee_status'] ? ($STATUS_LABELS[$r['attendee_status']] ?? $r['attendee_status']) : '—',
        $DISC_LABELS[$r['discount_tier']] ?? $r['discount_tier'],
        $r['selected_rate'] === 'early' ? 'Early Bird' : ($r['selected_rate'] === 'standard' ? 'Standard' : '—'),
        $r['price'] ?? 0,
        ucfirst($r['payment_status']),
        $APPR_LABELS[$r['discount_approval_status']] ?? $r['discount_approval_status'],
    ]);
}
fclose($out);
exit;
