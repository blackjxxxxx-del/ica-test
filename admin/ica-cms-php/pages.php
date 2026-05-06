<?php
$pageTitle = 'หน้าเว็บ';
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$db->exec("CREATE TABLE IF NOT EXISTS pages (
    id VARCHAR(32) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT,
    meta_title VARCHAR(255),
    meta_desc TEXT,
    is_visible TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
)");

// AJAX: toggle visibility
if (!empty($_POST['toggle_id'])) {
    header('Content-Type: application/json');
    $id = $_POST['toggle_id'];
    $db->prepare("UPDATE pages SET is_visible = 1 - is_visible, updated_at = NOW() WHERE id = ?")->execute([$id]);
    $row = $db->prepare("SELECT is_visible FROM pages WHERE id = ?")->execute([$id]) ? null : null;
    $stmt2 = $db->prepare("SELECT is_visible FROM pages WHERE id = ?");
    $stmt2->execute([$id]);
    $vis = (int)$stmt2->fetchColumn();
    echo json_encode(['ok' => true, 'visible' => $vis]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id'])) {
    $db->prepare("DELETE FROM pages WHERE id = ?")->execute([$_POST['delete_id']]);
    flash('success', 'ลบหน้าเว็บสำเร็จ');
    header('Location: pages.php');
    exit;
}

$pages = $db->query("SELECT * FROM pages ORDER BY created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted small mb-0">เพจที่เผยแพร่แล้วจะแสดงใน <strong>Conference Information ▾</strong> บนเว็บหลักอัตโนมัติ</p>
    </div>
    <a href="pages-edit.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>เพิ่มหน้าเว็บ
    </a>
</div>

<div class="card">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>ชื่อหน้า</th>
                <th>Slug</th>
                <th>สถานะ</th>
                <th>อัปเดต</th>
                <th style="width:160px"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($pages as $p): ?>
        <tr id="row-<?= e($p['id']) ?>">
            <td class="fw-medium"><?= e($p['title']) ?></td>
            <td><code class="text-muted small"><?= e($p['slug']) ?></code></td>
            <td id="badge-<?= e($p['id']) ?>">
                <?php if ($p['is_visible']): ?>
                <span class="badge badge-published">เผยแพร่แล้ว</span>
                <?php else: ?>
                <span class="badge badge-draft">ร่าง (ซ่อน)</span>
                <?php endif; ?>
            </td>
            <td class="text-muted small"><?= date('d/m/Y', strtotime($p['updated_at'])) ?></td>
            <td class="text-end">
                <button class="btn btn-sm <?= $p['is_visible'] ? 'btn-outline-warning' : 'btn-success' ?> me-1"
                        id="toggle-btn-<?= e($p['id']) ?>"
                        onclick="togglePage('<?= e($p['id']) ?>', <?= $p['is_visible'] ?>)">
                    <?php if ($p['is_visible']): ?>
                    <i class="bi bi-eye-slash me-1"></i>ซ่อน
                    <?php else: ?>
                    <i class="bi bi-send me-1"></i>เผยแพร่
                    <?php endif; ?>
                </button>
                <a href="pages-edit.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-sm btn-outline-secondary me-1">
                    <i class="bi bi-pencil"></i>
                </a>
                <form method="POST" class="d-inline" onsubmit="return confirm('ยืนยันลบหน้านี้?')">
                    <input type="hidden" name="delete_id" value="<?= e($p['id']) ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$pages): ?>
        <tr><td colspan="5" class="text-center text-muted py-5">ยังไม่มีหน้าเว็บ</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
async function togglePage(id, currentVisible) {
    const btn = document.getElementById('toggle-btn-' + id);
    btn.disabled = true;
    const fd = new FormData();
    fd.append('toggle_id', id);
    const res  = await fetch('pages.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
        const badge = document.getElementById('badge-' + id);
        if (data.visible) {
            badge.innerHTML = '<span class="badge badge-published">เผยแพร่แล้ว</span>';
            btn.className = 'btn btn-sm btn-outline-warning me-1';
            btn.innerHTML = '<i class="bi bi-eye-slash me-1"></i>ซ่อน';
            btn.onclick = () => togglePage(id, 1);
        } else {
            badge.innerHTML = '<span class="badge badge-draft">ร่าง (ซ่อน)</span>';
            btn.className = 'btn btn-sm btn-success me-1';
            btn.innerHTML = '<i class="bi bi-send me-1"></i>เผยแพร่';
            btn.onclick = () => togglePage(id, 0);
        }
    }
    btn.disabled = false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
