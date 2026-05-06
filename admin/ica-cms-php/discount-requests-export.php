<?php
/**
 * discount-requests-export.php
 * Downloads a ZIP containing:
 *   - discount-requests.xls  (HTML-format spreadsheet — Excel opens it, links are clickable)
 *   - documents/             (all uploaded supporting files)
 *
 * The document column in the XLS uses relative hyperlinks, e.g. documents/doc_xxx.pdf
 * When the ZIP is extracted and the XLS opened, clicking a link opens the file directly.
 */
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$statusFilter = $_GET['status'] ?? '';
$from         = $_GET['from']   ?? '';
$to           = $_GET['to']     ?? '';

$sql    = "SELECT * FROM registrations WHERE discount_approval_status IN ('pending','approved','rejected')";
$params = [];
if ($statusFilter && in_array($statusFilter, ['pending','approved','rejected'])) { $sql .= " AND discount_approval_status=?"; $params[] = $statusFilter; }
if ($from) { $sql .= " AND DATE(created_at) >= ?"; $params[] = $from; }
if ($to)   { $sql .= " AND DATE(created_at) <= ?"; $params[] = $to; }
$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($params);
$rows = $stmt->fetchAll();

$FORMAT_LABELS = ['onsite'=>'Onsite / Poster Presenter','virtual'=>'Virtual Presenter','non'=>'Non Presenter'];
$STATUS_LABELS = ['student'=>'Student','academic'=>'Academic / Faculty / Professional'];
$DISC_LABELS   = ['20pct'=>'20% OFF','100pct'=>'100% Free','none'=>'—'];
$APPR_LABELS   = ['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'];

$uploadDir = __DIR__ . '/uploads/registrations/';

// ── Build XLS content (HTML table — Excel opens natively, supports hyperlinks) ─
$xlsRows = '';
foreach ($rows as $i => $r) {
    $bg     = ($i % 2 === 0) ? '#f8f8f8' : '#ffffff';
    $appr   = $APPR_LABELS[$r['discount_approval_status']] ?? $r['discount_approval_status'];
    $apprColor = match($r['discount_approval_status']) {
        'approved' => '#16a34a', 'rejected' => '#dc2626', default => '#d97706'
    };

    if ($r['document_filename']) {
        $docCell = "<a href=\"documents/{$r['document_filename']}\">" . htmlspecialchars($r['document_original_name'] ?? $r['document_filename']) . "</a>";
    } else {
        $docCell = '—';
    }

    $xlsRows .= "<tr style=\"background:{$bg};\">
        <td>{$r['id']}</td>
        <td>" . htmlspecialchars($r['created_at']) . "</td>
        <td><b>" . htmlspecialchars($r['full_name']) . "</b></td>
        <td>" . htmlspecialchars($r['email']) . "</td>
        <td>" . htmlspecialchars($FORMAT_LABELS[$r['format']] ?? $r['format']) . "</td>
        <td>" . htmlspecialchars($r['attendee_status'] ? ($STATUS_LABELS[$r['attendee_status']] ?? $r['attendee_status']) : '—') . "</td>
        <td>" . htmlspecialchars($DISC_LABELS[$r['discount_tier']] ?? $r['discount_tier']) . "</td>
        <td>{$docCell}</td>
        <td style=\"color:{$apprColor};font-weight:bold;\">{$appr}</td>
    </tr>\n";
}

$xlsContent = <<<XLS
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
<meta charset="UTF-8">
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
<x:Name>Discount Requests</x:Name>
<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
<style>
body { font-family: Arial, sans-serif; font-size: 12px; }
table { border-collapse: collapse; width: 100%; }
th { background: #1a1a2e; color: #ffffff; padding: 8px 12px; text-align: left; }
td { padding: 7px 12px; border-bottom: 1px solid #e0e0e0; }
a { color: #0563C1; }
</style>
</head>
<body>
<h2 style="font-family:Arial;color:#1a1a2e;">ICA-TH 2026 — Discount Requests</h2>
<p style="font-family:Arial;color:#666;font-size:11px;">Generated: <?= date('d M Y H:i') ?> · <?= count($rows) ?> records</p>
<table>
<thead><tr>
    <th>#</th><th>Date</th><th>Full Name</th><th>Email</th>
    <th>Format</th><th>Attendee Status</th><th>Discount</th>
    <th>Document</th><th>Approval Status</th>
</tr></thead>
<tbody>
{$xlsRows}
</tbody>
</table>
</body></html>
XLS;

// ── Build ZIP ──────────────────────────────────────────────────────────────────
if (!class_exists('ZipArchive')) {
    die('ZipArchive not available on this server.');
}

$tmpZip = tempnam(sys_get_temp_dir(), 'ica_disc_') . '.zip';
$zip = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die('Could not create ZIP file.');
}

// Add the XLS spreadsheet
$zip->addFromString('discount-requests.xls', $xlsContent);

// Add document files
foreach ($rows as $r) {
    if ($r['document_filename']) {
        $filePath = $uploadDir . $r['document_filename'];
        if (file_exists($filePath)) {
            $zip->addFile($filePath, 'documents/' . $r['document_filename']);
        }
    }
}

$zip->close();

// ── Stream ZIP to browser ──────────────────────────────────────────────────────
$zipFilename = 'ica-discount-requests-' . date('Ymd_His') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
header('Content-Length: ' . filesize($tmpZip));
header('Pragma: no-cache');
readfile($tmpZip);
@unlink($tmpZip);
exit;
