<?php
$pageTitle = 'ข้อความติดต่อ';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// AJAX: mark read
if (!empty($_POST['ajax_mark_read'])) {
    $id  = (int)$_POST['ajax_mark_read'];
    $val = isset($_POST['val']) ? (int)$_POST['val'] : 1;
    $db->prepare("UPDATE contacts SET is_read = ? WHERE id = ?")->execute([$val, $id]);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// AJAX: realtime poll — คืน count + id ล่าสุด
if (isset($_GET['action']) && $_GET['action'] === 'count') {
    $unread = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();
    $total  = $db->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
    $latest = $db->query("SELECT id FROM contacts ORDER BY created_at DESC LIMIT 1")->fetchColumn();
    header('Content-Type: application/json');
    echo json_encode(['unread' => (int)$unread, 'total' => (int)$total, 'latest' => $latest]);
    exit;
}

// AJAX: delete
if (!empty($_POST['ajax_delete'])) {
    $id = $_POST['ajax_delete'];
    $db->prepare("DELETE FROM contacts WHERE id = ?")->execute([$id]);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

$contacts = $db->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll();
?>

<?php
$unread = count(array_filter($contacts, function($c) { return !$c['is_read']; }));
?>

<!-- Banner แจ้งเตือนข้อความใหม่ (ซ่อนอยู่ จนกว่า polling จะตรวจเจอ) -->
<div id="new-msg-banner" style="display:none;" class="alert alert-info alert-dismissible d-flex align-items-center gap-2 mb-3 py-2" role="alert">
    <i class="bi bi-bell-fill"></i>
    <span></span>
    <a href="contacts.php" class="btn btn-sm btn-info ms-auto">โหลดใหม่</a>
    <button type="button" class="btn-close ms-1" onclick="this.closest('#new-msg-banner').style.display='none'"></button>
</div>

<?php if ($contacts): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted small mb-0">
        ทั้งหมด <?= count($contacts) ?> ข้อความ
        <?php if ($unread): ?>
        — <span class="text-warning fw-semibold" id="unread-count" data-count="<?= $unread ?>"><?= $unread ?> ยังไม่ได้อ่าน</span>
        <?php endif; ?>
    </p>
    <div class="d-flex gap-2">
        <a href="contacts-export.php?filter=all" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
        <a href="contacts-export.php?filter=unread" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-download me-1"></i>ยังไม่อ่าน
        </a>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>ชื่อ</th>
                <th>อีเมล</th>
                <th>หัวข้อ</th>
                <th>วันที่</th>
                <th>สถานะ</th>
                <th style="width:120px"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($contacts as $c): ?>
        <tr id="row-<?= $c['id'] ?>" class="<?= !$c['is_read'] ? 'fw-semibold' : '' ?>">
            <td><?= e($c['name']) ?></td>
            <td class="text-muted small"><?= e($c['email']) ?></td>
            <td><?= e($c['subject']) ?></td>
            <td class="text-muted small text-nowrap"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
            <td>
                <?php if ($c['is_replied']): ?>
                <span class="badge bg-success badge-new-<?= $c['id'] ?>">ตอบแล้ว</span>
                <?php elseif (!$c['is_read']): ?>
                <span class="badge bg-warning text-dark badge-new-<?= $c['id'] ?>">ใหม่</span>
                <?php else: ?>
                <span class="badge bg-light text-muted border badge-new-<?= $c['id'] ?>">อ่านแล้ว</span>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-primary me-1"
                        data-bs-toggle="modal" data-bs-target="#msg-<?= $c['id'] ?>">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteMsg('<?= e($c['id']) ?>', this)">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>

        <!-- Modal -->
        <div class="modal fade" id="msg-<?= $c['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fs-6"><?= e($c['subject']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- ข้อมูลผู้ส่ง -->
                        <dl class="row mb-3 small">
                            <dt class="col-sm-3 text-muted">จาก</dt>
                            <dd class="col-sm-9"><?= e($c['name']) ?></dd>
                            <dt class="col-sm-3 text-muted">อีเมล</dt>
                            <dd class="col-sm-9"><?= e($c['email']) ?></dd>
                            <?php if ($c['organization']): ?>
                            <dt class="col-sm-3 text-muted">องค์กร</dt>
                            <dd class="col-sm-9"><?= e($c['organization']) ?></dd>
                            <?php endif; ?>
                            <dt class="col-sm-3 text-muted">วันที่</dt>
                            <dd class="col-sm-9"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></dd>
                        </dl>
                        <div class="bg-light rounded p-3 mb-4" style="white-space:pre-wrap;font-size:.9rem"><?= e($c['message']) ?></div>

                        <!-- ฟอร์มตอบกลับ -->
                        <div id="reply-area-<?= $c['id'] ?>">
                            <h6 class="fw-semibold mb-2"><i class="bi bi-reply me-1"></i>ตอบกลับถึง <?= e($c['name']) ?></h6>
                            <textarea class="form-control mb-2" id="reply-body-<?= $c['id'] ?>" rows="5"
                                      placeholder="พิมพ์ข้อความตอบกลับที่นี่..."></textarea>
                            <div id="reply-alert-<?= $c['id'] ?>"></div>
                            <button class="btn btn-primary btn-sm"
                                    onclick="sendReply('<?= $c['id'] ?>', '<?= e($c['email']) ?>', '<?= e(addslashes($c['name'])) ?>', '<?= e(addslashes($c['subject'])) ?>')">
                                <i class="bi bi-send me-1"></i>ส่งอีเมลตอบกลับ
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="me-auto" id="read-btn-wrap-<?= $c['id'] ?>">
                        <?php if (!$c['is_read']): ?>
                            <button class="btn btn-sm btn-outline-success"
                                    id="read-btn-<?= $c['id'] ?>"
                                    onclick="markRead(<?= $c['id'] ?>, this)">
                                <i class="bi bi-check2 me-1"></i>อ่านแล้ว
                            </button>
                        <?php endif; ?>
                        </div>
                        <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (!$contacts): ?>
        <tr><td colspan="6" class="text-center text-muted py-5">ยังไม่มีข้อความ</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
/* ── Mark read via AJAX (modal ไม่ปิด) ── */
async function markRead(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass me-1"></i>...';
    const fd = new FormData();
    fd.append('ajax_mark_read', id);
    fd.append('val', 1);
    try {
        await fetch('contacts.php', { method: 'POST', body: fd });
        // อัปเดต UI: ซ่อนปุ่ม, เปลี่ยน badge ในตาราง
        document.getElementById('read-btn-wrap-' + id).innerHTML = '';
        const row = document.getElementById('row-' + id);
        if (row) {
            row.classList.remove('fw-semibold');
            const badge = row.querySelector('.badge-new-' + id);
            if (badge) {
                badge.className = 'badge bg-light text-muted border';
                badge.textContent = 'อ่านแล้ว';
            }
        }
        // อัปเดตตัวเลขยังไม่อ่าน
        updateUnreadCounter(-1);
    } catch(e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>อ่านแล้ว';
    }
}

async function deleteMsg(id, btn) {
    if (!confirm('ยืนยันลบข้อความนี้?')) return;
    btn.disabled = true;
    const fd = new FormData();
    fd.append('ajax_delete', id);
    try {
        await fetch('contacts.php', { method: 'POST', body: fd });
        const row = document.getElementById('row-' + id);
        if (row) {
            // ถ้า unread ให้ลดตัวนับก่อน
            const badge = row.querySelector('.badge-new-' + id);
            if (badge && badge.textContent.trim() === 'ใหม่') updateUnreadCounter(-1);
            row.style.transition = 'opacity .3s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        }
        // ปิด modal ถ้าเปิดอยู่
        const modal = document.getElementById('msg-' + id);
        if (modal) bootstrap.Modal.getInstance(modal)?.hide();
    } catch(e) {
        btn.disabled = false;
        alert('เกิดข้อผิดพลาด');
    }
}

function updateUnreadCounter(delta) {
    const el = document.getElementById('unread-count');
    if (!el) return;
    const cur = parseInt(el.dataset.count || '0') + delta;
    el.dataset.count = Math.max(0, cur);
    el.textContent = Math.max(0, cur) + ' ยังไม่ได้อ่าน';
    if (cur <= 0) el.style.display = 'none';
}

/* ── Realtime polling ทุก 10 วินาที ── */
(function startPolling() {
    const initialTotal  = <?= count($contacts) ?>;
    const initialLatest = '<?= $db->query("SELECT id FROM contacts ORDER BY created_at DESC LIMIT 1")->fetchColumn() ?>';
    let lastTotal  = initialTotal;
    let lastLatest = initialLatest;

    async function poll() {
        try {
            const res  = await fetch('contacts.php?action=count&_=' + Date.now());
            const data = await res.json();
            if (data.total > lastTotal || data.latest !== lastLatest) {
                // มีข้อความใหม่ → reload หน้าอัตโนมัติ (modal ไม่ได้เปิดอยู่)
                const anyModalOpen = document.querySelector('.modal.show');
                if (!anyModalOpen) {
                    window.location.reload();
                } else {
                    // ถ้า modal เปิดอยู่ ให้แสดง banner แทน
                    const banner = document.getElementById('new-msg-banner');
                    if (banner) {
                        banner.style.display = '';
                        banner.querySelector('span').textContent =
                            '🔔 มีข้อความใหม่ ' + (data.total - lastTotal) + ' รายการ';
                    }
                    lastTotal  = data.total;
                    lastLatest = data.latest;
                }
            }
        } catch(e) { /* network error — ลองใหม่รอบหน้า */ }
    }

    setInterval(poll, 10000); // ทุก 10 วินาที
})();

async function sendReply(id, email, name, subject) {
    const body = document.getElementById('reply-body-' + id).value.trim();
    const alertEl = document.getElementById('reply-alert-' + id);
    if (!body) { alertEl.innerHTML = '<div class="alert alert-warning py-2 small">กรุณาพิมพ์ข้อความก่อน</div>'; return; }

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass me-1"></i>กำลังส่ง...';
    alertEl.innerHTML = '';

    const fd = new FormData();
    fd.append('id', id);
    fd.append('email', email);
    fd.append('name', name);
    fd.append('subject', subject);
    fd.append('body', body);

    try {
        const res  = await fetch('contact-reply.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alertEl.innerHTML = '<div class="alert alert-success py-2 small"><i class="bi bi-check-circle me-1"></i>ส่งอีเมลสำเร็จ!</div>';
            document.getElementById('reply-body-' + id).value = '';
            btn.innerHTML = '<i class="bi bi-check me-1"></i>ส่งแล้ว';
        } else {
            throw new Error(data.message || 'เกิดข้อผิดพลาด');
        }
    } catch(e) {
        alertEl.innerHTML = '<div class="alert alert-danger py-2 small">' + e.message + '</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send me-1"></i>ส่งอีเมลตอบกลับ';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
