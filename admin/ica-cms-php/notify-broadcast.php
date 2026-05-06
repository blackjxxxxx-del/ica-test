<?php
$pageTitle = 'ส่งอีเมลแจ้งเตือน';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/mailer.php';

$db = getDB();

// Count recipients
$counts = [
    'all'      => (int) $db->query("SELECT COUNT(*) FROM notify_list")->fetchColumn(),
    'submit'   => (int) $db->query("SELECT COUNT(*) FROM notify_list WHERE mode='submit'")->fetchColumn(),
    'register' => (int) $db->query("SELECT COUNT(*) FROM notify_list WHERE mode='register'")->fetchColumn(),
];

// AJAX send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ajax_broadcast'])) {
    header('Content-Type: application/json');
    $mode    = in_array($_POST['mode'] ?? '', ['submit','register','all']) ? $_POST['mode'] : 'all';
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');

    if (!$subject || !$body) {
        echo json_encode(['ok' => false, 'msg' => 'กรุณากรอกหัวข้อและเนื้อหา']);
        exit;
    }

    $sql = $mode === 'all'
        ? "SELECT email, unsubscribe_token FROM notify_list"
        : "SELECT email, unsubscribe_token FROM notify_list WHERE mode=?";

    $stmt = $db->prepare($sql);
    $mode === 'all' ? $stmt->execute() : $stmt->execute([$mode]);
    $recipients = $stmt->fetchAll();

    $sent = 0; $failed = 0;
    foreach ($recipients as $r) {
        $unsub = 'https://icahubthailand.org/admin/ica-cms-php/unsubscribe.php?token=' . urlencode($r['unsubscribe_token']);
        $html  = buildEmailHtml(
            $subject,
            "<p style='font-size:16px;line-height:1.7;color:#1e293b;'>$body</p>",
            "<a href='$unsub' style='color:#94a3b8;font-size:12px;'>Unsubscribe from these emails</a>"
        );
        if (sendMail($r['email'], $subject, $html, '', true)) {
            $sent++;
        } else {
            $failed++;
        }
    }

    logActivity('broadcast_email', "ส่ง {$sent} ฉบับ, ล้มเหลว {$failed} ฉบับ (mode: {$mode}, หัวข้อ: {$subject})");
    echo json_encode(['ok' => true, 'sent' => $sent, 'failed' => $failed]);
    exit;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">ส่งอีเมลแจ้งเตือน (Broadcast)</h5>
        <p class="text-muted small mb-0">ส่งอีเมลถึงผู้ที่กด Notify Me ทั้งหมด</p>
    </div>
    <a href="notify-list.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-list me-1"></i>ดูรายชื่อ
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4">
            <div id="broadcast-alert"></div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">ส่งถึง</label>
                <div class="d-flex gap-3 flex-wrap">
                    <?php foreach ([
                        ['all',      'ทั้งหมด',             $counts['all']],
                        ['submit',   'Submission Notify',   $counts['submit']],
                        ['register', 'Registration Notify', $counts['register']],
                    ] as [$val, $label, $count]): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="bc_mode" id="mode_<?= $val ?>"
                               value="<?= $val ?>" <?= $val === 'all' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="mode_<?= $val ?>">
                            <?= $label ?> <span class="badge bg-secondary"><?= $count ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">หัวข้ออีเมล (Subject)</label>
                <input type="text" id="bc-subject" class="form-control" placeholder="เช่น Submission Portal is Now Open!">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">เนื้อหา</label>
                <textarea id="bc-body" class="form-control" rows="6"
                    placeholder="พิมพ์เนื้อหาอีเมลที่นี่...&#10;&#10;Link ปุ่มและรูปภาพจะถูกจัดรูปแบบสวยงามอัตโนมัติ"></textarea>
            </div>
            <div class="d-flex gap-2">
                <button id="bc-preview-btn" class="btn btn-outline-primary btn-sm" onclick="previewEmail()">
                    <i class="bi bi-eye me-1"></i>Preview
                </button>
                <button id="bc-send-btn" class="btn btn-primary btn-sm" onclick="sendBroadcast()">
                    <i class="bi bi-send me-1"></i>ส่งอีเมล
                </button>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4">
            <h6 class="fw-semibold mb-3">สถิติ</h6>
            <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted small">ทั้งหมด</span>
                <strong><?= $counts['all'] ?></strong>
            </div>
            <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted small">Submission</span>
                <strong><?= $counts['submit'] ?></strong>
            </div>
            <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted small">Registration</span>
                <strong><?= $counts['register'] ?></strong>
            </div>
            <hr>
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                ทุกอีเมลจะมีลิงก์ Unsubscribe ตามกฎหมาย PDPA อัตโนมัติ
            </p>
        </div>
        <div class="card p-4 mt-3" id="preview-box" style="display:none">
            <h6 class="fw-semibold mb-2">Preview</h6>
            <div id="preview-content" class="small border rounded p-3 bg-light"></div>
        </div>
    </div>
</div>

<script>
function getMode() {
    return document.querySelector('input[name="bc_mode"]:checked')?.value || 'all';
}

function previewEmail() {
    const subject = document.getElementById('bc-subject').value.trim();
    const body    = document.getElementById('bc-body').value.trim();
    const box     = document.getElementById('preview-box');
    const content = document.getElementById('preview-content');
    box.style.display = '';
    content.innerHTML = `<strong>${subject || '(ไม่มีหัวข้อ)'}</strong><hr class="my-2">${body.replace(/\n/g,'<br>')}`;
}

async function sendBroadcast() {
    const subject = document.getElementById('bc-subject').value.trim();
    const body    = document.getElementById('bc-body').value.trim();
    const alertEl = document.getElementById('broadcast-alert');

    if (!subject || !body) {
        alertEl.innerHTML = '<div class="alert alert-warning py-2 small">กรุณากรอกหัวข้อและเนื้อหา</div>';
        return;
    }

    const mode = getMode();
    const modeLabels = { all: 'ทั้งหมด', submit: 'Submission', register: 'Registration' };
    const modeCount = { all: <?= $counts['all'] ?>, submit: <?= $counts['submit'] ?>, register: <?= $counts['register'] ?> };

    if (!confirm(`ยืนยันส่งอีเมลถึง "${modeLabels[mode]}" ทั้งหมด ${modeCount[mode]} คน?`)) return;

    const btn = document.getElementById('bc-send-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังส่ง...';
    alertEl.innerHTML = '';

    const fd = new FormData();
    fd.append('ajax_broadcast', '1');
    fd.append('mode', mode);
    fd.append('subject', subject);
    fd.append('body', body);

    try {
        const res  = await fetch('notify-broadcast.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
            alertEl.innerHTML = `<div class="alert alert-success py-2 small">
                <i class="bi bi-check-circle me-1"></i>ส่งสำเร็จ ${data.sent} ฉบับ
                ${data.failed ? ` (ล้มเหลว ${data.failed} ฉบับ)` : ''}
            </div>`;
            document.getElementById('bc-subject').value = '';
            document.getElementById('bc-body').value = '';
        } else {
            alertEl.innerHTML = `<div class="alert alert-danger py-2 small">${data.msg || 'เกิดข้อผิดพลาด'}</div>`;
        }
    } catch(e) {
        alertEl.innerHTML = '<div class="alert alert-danger py-2 small">Connection error</div>';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-1"></i>ส่งอีเมล';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
