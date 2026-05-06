<?php
$pageTitle = 'Programme / Sessions';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$db->exec("CREATE TABLE IF NOT EXISTS `sessions` (
    `id`          VARCHAR(36)   NOT NULL,
    `day`         TINYINT       NOT NULL DEFAULT 1,
    `start_time`  VARCHAR(10)   NOT NULL DEFAULT '',
    `end_time`    VARCHAR(10)   NOT NULL DEFAULT '',
    `title`       VARCHAR(500)  NOT NULL,
    `type`        ENUM('keynote','panel','workshop','break','networking','ceremony','other') NOT NULL DEFAULT 'other',
    `room`        VARCHAR(255)  NOT NULL DEFAULT '',
    `speaker`     VARCHAR(500)  NOT NULL DEFAULT '',
    `description` TEXT,
    `sort_order`  INT           NOT NULL DEFAULT 0,
    `is_visible`  TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `sess_day` (`day`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// AJAX delete
if (!empty($_POST['ajax_delete'])) {
    header('Content-Type: application/json');
    $db->prepare("DELETE FROM sessions WHERE id=?")->execute([$_POST['ajax_delete']]);
    echo json_encode(['ok'=>true]); exit;
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $fields = [
        'day'         => (int)($_POST['day'] ?? 1),
        'start_time'  => trim($_POST['start_time'] ?? ''),
        'end_time'    => trim($_POST['end_time'] ?? ''),
        'title'       => trim($_POST['title'] ?? ''),
        'type'        => in_array($_POST['type']??'',['keynote','panel','workshop','break','networking','ceremony','other']) ? $_POST['type'] : 'other',
        'room'        => trim($_POST['room'] ?? ''),
        'speaker'     => trim($_POST['speaker'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        'is_visible'  => isset($_POST['is_visible']) ? 1 : 0,
    ];
    if (!$fields['title']) { flash('error','กรุณากรอกชื่อ session'); }
    else {
        if ($_POST['action'] === 'add') {
            $db->prepare("INSERT INTO sessions (id,day,start_time,end_time,title,type,room,speaker,description,sort_order,is_visible) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([cuid(),...array_values($fields)]);
            flash('success','เพิ่มสำเร็จ');
        } elseif ($_POST['action'] === 'edit' && !empty($_POST['id'])) {
            $db->prepare("UPDATE sessions SET day=?,start_time=?,end_time=?,title=?,type=?,room=?,speaker=?,description=?,sort_order=?,is_visible=? WHERE id=?")
               ->execute([...array_values($fields), $_POST['id']]);
            flash('success','บันทึกสำเร็จ');
        }
    }
    header('Location: sessions.php'); exit;
}

$activeDay = (int)($_GET['day'] ?? 1);
$allSessions = [];
for ($d = 1; $d <= 3; $d++) {
    $stmt = $db->prepare("SELECT * FROM sessions WHERE day=? ORDER BY sort_order ASC, start_time ASC");
    $stmt->execute([$d]);
    $allSessions[$d] = $stmt->fetchAll();
}

$typeColors = ['keynote'=>'bg-danger','panel'=>'bg-primary','workshop'=>'bg-success','break'=>'bg-secondary','networking'=>'bg-warning text-dark','ceremony'=>'bg-info','other'=>'bg-light border text-dark'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Programme / Sessions</h5>
        <p class="text-muted small mb-0">จัดการตาราง conference 3 วัน</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#add-session-modal">
        <i class="bi bi-plus-lg me-1"></i>เพิ่ม Session
    </button>
</div>

<!-- Day tabs -->
<ul class="nav nav-tabs mb-3">
    <?php for ($d = 1; $d <= 3; $d++): ?>
    <li class="nav-item">
        <a class="nav-link <?= $d === $activeDay ? 'active' : '' ?>" href="sessions.php?day=<?= $d ?>">
            Day <?= $d ?> <span class="badge bg-secondary ms-1"><?= count($allSessions[$d]) ?></span>
        </a>
    </li>
    <?php endfor; ?>
</ul>

<div class="card">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr><th>เวลา</th><th>หัวข้อ</th><th>ประเภท</th><th>ห้อง</th><th>Speaker</th><th style="width:80px"></th></tr>
        </thead>
        <tbody>
        <?php $sessions = $allSessions[$activeDay]; ?>
        <?php if ($sessions): ?>
        <?php foreach ($sessions as $sess): ?>
        <tr id="sess-row-<?= e($sess['id']) ?>">
            <td class="text-nowrap small text-muted"><?= e($sess['start_time']) ?><?= $sess['end_time'] ? ' – '.e($sess['end_time']) : '' ?></td>
            <td class="fw-semibold"><?= e($sess['title']) ?></td>
            <td><span class="badge <?= $typeColors[$sess['type']] ?>"><?= $sess['type'] ?></span></td>
            <td class="text-muted small"><?= e($sess['room']) ?></td>
            <td class="text-muted small"><?= e($sess['speaker']) ?></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary me-1" onclick="editSession(<?= htmlspecialchars(json_encode($sess)) ?>)">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteSess('<?= e($sess['id']) ?>')">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="6" class="text-center text-muted py-5">ยังไม่มี session สำหรับ Day <?= $activeDay ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="add-session-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold" id="session-modal-title">เพิ่ม Session</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" id="session-form">
                <input type="hidden" name="action" id="session-action" value="add">
                <input type="hidden" name="id" id="session-id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Day</label>
                            <select name="day" id="f-day" class="form-select">
                                <option value="1">Day 1</option>
                                <option value="2">Day 2</option>
                                <option value="3">Day 3</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">เวลาเริ่ม</label>
                            <input type="text" name="start_time" id="f-start" class="form-control" placeholder="09:00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">เวลาสิ้นสุด</label>
                            <input type="text" name="end_time" id="f-end" class="form-control" placeholder="10:30">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">ชื่อ Session *</label>
                            <input type="text" name="title" id="f-title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ประเภท</label>
                            <select name="type" id="f-type" class="form-select">
                                <?php foreach (['keynote','panel','workshop','break','networking','ceremony','other'] as $t): ?>
                                <option value="<?= $t ?>"><?= ucfirst($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ห้อง / Room</label>
                            <input type="text" name="room" id="f-room" class="form-control" placeholder="เช่น Main Hall, Room A">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Speaker / ผู้บรรยาย</label>
                            <input type="text" name="speaker" id="f-speaker" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">รายละเอียดเพิ่มเติม</label>
                            <textarea name="description" id="f-desc" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ลำดับ</label>
                            <input type="number" name="sort_order" id="f-order" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_visible" id="f-visible" checked>
                                <label class="form-check-label" for="f-visible">แสดงบนเว็บ</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary btn-sm">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSession(s) {
    document.getElementById('session-action').value = 'edit';
    document.getElementById('session-id').value     = s.id;
    document.getElementById('session-modal-title').textContent = 'แก้ไข Session';
    document.getElementById('f-day').value     = s.day;
    document.getElementById('f-start').value   = s.start_time;
    document.getElementById('f-end').value     = s.end_time;
    document.getElementById('f-title').value   = s.title;
    document.getElementById('f-type').value    = s.type;
    document.getElementById('f-room').value    = s.room;
    document.getElementById('f-speaker').value = s.speaker;
    document.getElementById('f-desc').value    = s.description || '';
    document.getElementById('f-order').value   = s.sort_order;
    document.getElementById('f-visible').checked = s.is_visible == 1;
    new bootstrap.Modal(document.getElementById('add-session-modal')).show();
}

async function deleteSess(id) {
    if (!confirm('ลบ session นี้?')) return;
    const fd = new FormData(); fd.append('ajax_delete', id);
    await fetch('sessions.php', { method:'POST', body:fd });
    const row = document.getElementById('sess-row-' + id);
    if (row) { row.style.opacity='0'; setTimeout(()=>row.remove(),300); }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
