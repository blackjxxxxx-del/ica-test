<?php
/**
 * contacts-export.php — Export contacts as CSV (admin only)
 */
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$filter = $_GET['filter'] ?? 'all';

$sql = match($filter) {
    'unread'  => "SELECT * FROM contacts WHERE is_read=0 ORDER BY created_at DESC",
    'replied' => "SELECT * FROM contacts WHERE is_replied=1 ORDER BY created_at DESC",
    default   => "SELECT * FROM contacts ORDER BY created_at DESC"
};

$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$filename = 'contacts_' . $filter . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel

fputcsv($out, ['Name', 'Email', 'Organization', 'Subject', 'Message', 'Read', 'Replied', 'Date']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['name'], $r['email'], $r['organization'] ?? '',
        $r['subject'], $r['message'],
        $r['is_read'] ? 'Yes' : 'No',
        $r['is_replied'] ? 'Yes' : 'No',
        $r['created_at'],
    ]);
}
fclose($out);
exit;
