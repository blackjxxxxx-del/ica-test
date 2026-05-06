<?php
$pageTitle = 'Notify List';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// สร้างตารางถ้ายังไม่มี
$db->exec("CREATE TABLE IF NOT EXISTS `notify_list` (
    `id`         varchar(36)  NOT NULL,
    `email`      varchar(255) NOT NULL,
    `mode`       varchar(50)  NOT NULL DEFAULT 'submit',
    `created_at` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `notify_list_email_mode` (`email`,`mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Export CSV
if (isset($_GET['export'])) {
    $mode = $_GET['export'];
    if ($mode === 'all') {
        $rows = $db->query("SELECT * FROM notify_list ORDER BY created_at DESC")->fetchAll();
    } else {
        $stmt = $db->prepare("SELECT * FROM notify_list WHERE mode=? ORDER BY created_at DESC");
        $stmt->execute([$mode]);
        $rows = $stmt->fetchAll();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="notify-' . $mode . '-' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo "Email,Mode,Date\n";
    foreach ($rows as $r) {
        echo '"' . $r['email'] . '","' . $r['mode'] . '","' . $r['created_at'] . '"' . "\n";
    }
    exit;
}

$entries = $db->query("SELECT * FROM notify_list ORDER BY created_at DESC")->fetchAll();
$submit   = array_filter($entries, fn($e) => $e['mode'] === 'submit');
$register = array_filter($entries, fn($e) => $e['mode'] === 'register');
?>

<div class="d-flex align-items-center justify-content-between mb-4 gap-3 flex-wrap">
    <div>
        <h5 class="mb-0 fw-bold">Notify List</h5>
        <p class="text-muted small mb-0">อีเมลที่ลงทะเบียนรอรับการแจ้งเตือนจากหน้า Coming Soon</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="?export=submit"   class="btn btn-sm btn-outline-secondary">⬇ Export Submission</a>
        <a href="?export=register" class="btn btn-sm btn-outline-secondary">⬇ Export Registration</a>
        <a href="?export=all"      class="btn btn-sm btn-outline-dark">⬇ Export All</a>
        <button class="btn btn-sm btn-primary" onclick="sendNotify('submit')">📨 ส่งเมล Submission</button>
        <button class="btn btn-sm btn-success" onclick="sendNotify('register')">📨 ส่งเมล Registration</button>
    </div>
</div>

<div id="send-result" class="alert d-none mb-3"></div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div style="font-size:2rem;font-weight:800;"><?= count($entries) ?></div>
            <div class="text-muted small">รวมทั้งหมด</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div style="font-size:2rem;font-weight:800;color:#2563eb;"><?= count($submit) ?></div>
            <div class="text-muted small">Submission (Coming-soon.html)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center p-3">
            <div style="font-size:2rem;font-weight:800;color:#16a34a;"><?= count($register) ?></div>
            <div class="text-muted small">Registration (Coming-soon2.html)</div>
        </div>
    </div>
</div>

<div class="card">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>อีเมล</th>
                <th>ประเภท</th>
                <th>วันที่</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['email']) ?></td>
            <td>
                <?php if ($e['mode'] === 'submit'): ?>
                <span class="badge" style="background:#dbeafe;color:#1d4ed8;">Submission</span>
                <?php else: ?>
                <span class="badge" style="background:#dcfce7;color:#166534;">Registration</span>
                <?php endif; ?>
            </td>
            <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($e['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$entries): ?>
        <tr><td colspan="3" class="text-center text-muted py-5">ยังไม่มีข้อมูล</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
async function sendNotify(mode) {
    const label = mode === 'submit' ? 'Submission' : 'Registration';
    if (!confirm(`ส่งเมลแจ้งเตือน ${label} ให้ทุกคนในรายการที่ยังไม่ได้รับ?\n\n(ไม่ส่งซ้ำให้คนที่ได้รับแล้ว)`)) return;

    const resultEl = document.getElementById('send-result');
    resultEl.className = 'alert alert-info';
    resultEl.textContent = '⏳ กำลังส่งเมล อาจใช้เวลาสักครู่...';

    try {
        const res  = await fetch(`notify-send.php?mode=${mode}`);
        const text = await res.text();
        const sent   = (text.match(/✓/g) || []).length;
        const failed = (text.match(/✗/g) || []).length;
        resultEl.className = 'alert alert-success';
        resultEl.innerHTML = `✅ ส่งสำเร็จ <strong>${sent}</strong> อีเมล`
            + (failed ? ` | ส่งไม่ได้ <strong>${failed}</strong>` : '')
            + `<br><pre class="mb-0 mt-2 small">${text}</pre>`;
    } catch(e) {
        resultEl.className = 'alert alert-danger';
        resultEl.textContent = 'เกิดข้อผิดพลาด: ' + e.message;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
