<?php
$pageTitle = 'Keynote Speakers';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$db->exec("CREATE TABLE IF NOT EXISTS `speakers` (
    `id`          VARCHAR(36)   NOT NULL,
    `name`        VARCHAR(255)  NOT NULL,
    `title`       VARCHAR(255)  NOT NULL DEFAULT '',
    `affiliation` VARCHAR(255)  NOT NULL DEFAULT '',
    `bio`         TEXT,
    `photo`       VARCHAR(500)  NOT NULL DEFAULT '',
    `talk_title`  VARCHAR(500)  NOT NULL DEFAULT '',
    `sort_order`  INT           NOT NULL DEFAULT 0,
    `is_visible`  TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Toggle visible
if (!empty($_POST['ajax_toggle'])) {
    header('Content-Type: application/json');
    $id  = $_POST['ajax_toggle'];
    $val = (int)$_POST['val'];
    $db->prepare("UPDATE speakers SET is_visible=? WHERE id=?")->execute([$val, $id]);
    echo json_encode(['ok' => true]);
    exit;
}

// Delete
if (!empty($_POST['ajax_delete'])) {
    header('Content-Type: application/json');
    $id = $_POST['ajax_delete'];
    $db->prepare("DELETE FROM speakers WHERE id=?")->execute([$id]);
    logActivity('speaker', 'ลบ speaker id: ' . $id);
    echo json_encode(['ok' => true]);
    exit;
}

$speakers = $db->query("SELECT * FROM speakers ORDER BY sort_order ASC, created_at ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Keynote Speakers</h5>
        <p class="text-muted small mb-0">จัดการ <?= count($speakers) ?> speaker</p>
    </div>
    <a href="speaker-edit.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>เพิ่ม Speaker
    </a>
</div>

<div class="card">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width:64px">รูป</th>
                <th>ชื่อ</th>
                <th>ตำแหน่ง / สถาบัน</th>
                <th>หัวข้อบรรยาย</th>
                <th style="width:80px">ลำดับ</th>
                <th style="width:80px">แสดง</th>
                <th style="width:100px"></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($speakers): ?>
        <?php foreach ($speakers as $sp): ?>
        <tr id="sp-row-<?= e($sp['id']) ?>">
            <td>
                <?php if ($sp['photo']): ?>
                <img src="<?= e($sp['photo']) ?>" class="rounded-circle object-fit-cover" width="44" height="44" alt="">
                <?php else: ?>
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width:44px;height:44px;font-size:18px">
                    <?= mb_strtoupper(mb_substr($sp['name'], 0, 1)) ?>
                </div>
                <?php endif; ?>
            </td>
            <td class="fw-semibold"><?= e($sp['name']) ?></td>
            <td class="text-muted small"><?= e($sp['title']) ?><?= $sp['affiliation'] ? '<br><span class="text-primary">' . e($sp['affiliation']) . '</span>' : '' ?></td>
            <td class="text-muted small"><?= e(mb_strimwidth($sp['talk_title'], 0, 60, '...')) ?></td>
            <td class="text-center text-muted small"><?= (int)$sp['sort_order'] ?></td>
            <td class="text-center">
                <div class="form-check form-switch d-flex justify-content-center">
                    <input class="form-check-input" type="checkbox" <?= $sp['is_visible'] ? 'checked' : '' ?>
                           onchange="toggleSp('<?= e($sp['id']) ?>', this.checked ? 1 : 0)">
                </div>
            </td>
            <td class="text-end">
                <a href="speaker-edit.php?id=<?= e($sp['id']) ?>" class="btn btn-sm btn-outline-primary me-1">
                    <i class="bi bi-pencil"></i>
                </a>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteSp('<?= e($sp['id']) ?>')">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="7" class="text-center text-muted py-5">ยังไม่มี speaker</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
async function toggleSp(id, val) {
    const fd = new FormData();
    fd.append('ajax_toggle', id);
    fd.append('val', val);
    await fetch('speakers.php', { method: 'POST', body: fd });
}
async function deleteSp(id) {
    if (!confirm('ลบ speaker นี้?')) return;
    const fd = new FormData();
    fd.append('ajax_delete', id);
    await fetch('speakers.php', { method: 'POST', body: fd });
    const row = document.getElementById('sp-row-' + id);
    if (row) { row.style.opacity='0'; setTimeout(()=>row.remove(),300); }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
