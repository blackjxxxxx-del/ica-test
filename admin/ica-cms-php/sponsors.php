<?php
$pageTitle = 'ผู้สนับสนุน (Sponsors)';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$db->exec("CREATE TABLE IF NOT EXISTS `sponsors` (
    `id`          VARCHAR(36)   NOT NULL,
    `name`        VARCHAR(255)  NOT NULL,
    `logo_url`    VARCHAR(500)  NOT NULL DEFAULT '',
    `website_url` VARCHAR(500)  NOT NULL DEFAULT '',
    `tier`        ENUM('platinum','gold','silver','bronze','supporter') NOT NULL DEFAULT 'supporter',
    `sort_order`  INT           NOT NULL DEFAULT 0,
    `is_visible`  TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// AJAX: delete
if (!empty($_POST['ajax_delete'])) {
    header('Content-Type: application/json');
    $db->prepare("DELETE FROM sponsors WHERE id=?")->execute([$_POST['ajax_delete']]);
    echo json_encode(['ok' => true]); exit;
}

// Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    $name        = trim($_POST['name'] ?? '');
    $logo        = trim($_POST['logo_url'] ?? '');
    $website     = trim($_POST['website_url'] ?? '');
    $tier        = in_array($_POST['tier'] ?? '', ['platinum','gold','silver','bronze','supporter']) ? $_POST['tier'] : 'supporter';
    $sort_order  = (int)($_POST['sort_order'] ?? 0);

    if (!empty($_FILES['logo_file']['name'])) {
        $up = uploadImage($_FILES['logo_file']);
        if ($up) $logo = $up;
    }

    if ($_POST['action'] === 'add' && $name) {
        $db->prepare("INSERT INTO sponsors (id,name,logo_url,website_url,tier,sort_order) VALUES (?,?,?,?,?,?)")
           ->execute([cuid(),$name,$logo,$website,$tier,$sort_order]);
        flash('success',"เพิ่ม {$name} สำเร็จ");
    } elseif ($_POST['action'] === 'edit' && $name && !empty($_POST['id'])) {
        $db->prepare("UPDATE sponsors SET name=?,logo_url=?,website_url=?,tier=?,sort_order=? WHERE id=?")
           ->execute([$name,$logo,$website,$tier,$sort_order,$_POST['id']]);
        flash('success',"บันทึกสำเร็จ");
    }
    header('Location: sponsors.php'); exit;
}

$sponsors = $db->query("SELECT * FROM sponsors ORDER BY FIELD(tier,'platinum','gold','silver','bronze','supporter'), sort_order ASC")->fetchAll();
$tierColors = ['platinum'=>'bg-secondary','gold'=>'bg-warning text-dark','silver'=>'bg-light border text-dark','bronze'=>'bg-danger bg-opacity-75','supporter'=>'bg-primary bg-opacity-50'];
$tierLabels = ['platinum'=>'Platinum','gold'=>'Gold','silver'=>'Silver','bronze'=>'Bronze','supporter'=>'Supporter'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">ผู้สนับสนุน (Sponsors)</h5>
        <p class="text-muted small mb-0"><?= count($sponsors) ?> รายการ</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#add-sponsor-modal">
        <i class="bi bi-plus-lg me-1"></i>เพิ่ม Sponsor
    </button>
</div>

<div class="card">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr><th>Logo</th><th>ชื่อ</th><th>Tier</th><th>เว็บไซต์</th><th style="width:80px">ลำดับ</th><th style="width:80px"></th></tr>
        </thead>
        <tbody>
        <?php if ($sponsors): ?>
        <?php foreach ($sponsors as $sp): ?>
        <tr id="sp-row-<?= e($sp['id']) ?>">
            <td>
                <?php if ($sp['logo_url']): ?>
                <img src="<?= e($sp['logo_url']) ?>" height="40" style="object-fit:contain;max-width:100px" alt="">
                <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
            </td>
            <td class="fw-semibold"><?= e($sp['name']) ?></td>
            <td><span class="badge <?= $tierColors[$sp['tier']] ?>"><?= $tierLabels[$sp['tier']] ?></span></td>
            <td class="text-muted small"><?= $sp['website_url'] ? '<a href="'.e($sp['website_url']).'" target="_blank">'.e($sp['website_url']).'</a>' : '—' ?></td>
            <td class="text-center text-muted small"><?= (int)$sp['sort_order'] ?></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger" onclick="deleteSponsor('<?= e($sp['id']) ?>')">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="6" class="text-center text-muted py-5">ยังไม่มีข้อมูล</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="add-sponsor-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">เพิ่ม Sponsor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small fw-semibold">ชื่อ *</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Logo URL</label><input type="text" name="logo_url" class="form-control" placeholder="https://..."></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">อัพโหลด Logo</label><input type="file" name="logo_file" class="form-control" accept="image/*"></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">เว็บไซต์</label><input type="text" name="website_url" class="form-control" placeholder="https://..."></div>
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label small fw-semibold">Tier</label>
                            <select name="tier" class="form-select">
                                <option value="platinum">Platinum</option>
                                <option value="gold">Gold</option>
                                <option value="silver">Silver</option>
                                <option value="bronze">Bronze</option>
                                <option value="supporter" selected>Supporter</option>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label small fw-semibold">ลำดับ</label><input type="number" name="sort_order" class="form-control" value="0" min="0"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary btn-sm">เพิ่ม</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function deleteSponsor(id) {
    if (!confirm('ลบรายการนี้?')) return;
    const fd = new FormData(); fd.append('ajax_delete', id);
    await fetch('sponsors.php', { method:'POST', body:fd });
    const row = document.getElementById('sp-row-' + id);
    if (row) { row.style.opacity='0'; setTimeout(()=>row.remove(),300); }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
