<?php
$useQuill = true;
require_once __DIR__ . '/includes/header.php';

$db   = getDB();
$slug = $_GET['slug'] ?? '';
$page = null;

if ($slug) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ?");
    $stmt->execute([$slug]);
    $page = $stmt->fetch();
    if (!$page) {
        flash('error', 'ไม่พบหน้าเว็บนี้');
        header('Location: pages.php');
        exit;
    }
    $pageTitle = 'แก้ไข: ' . $page['title'];
} else {
    $pageTitle = 'เพิ่มหน้าเว็บใหม่';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title']      ?? '');
    $newSlug   = slugify(trim($_POST['slug'] ?? ''));
    $content   = $_POST['content']   ?? '';
    $metaTitle = trim($_POST['meta_title'] ?? '');
    $metaDesc  = trim($_POST['meta_desc']  ?? '');
    $isVisible = isset($_POST['is_visible']) ? 1 : 0;

    if (!$title || !$newSlug) {
        flash('error', 'กรุณากรอกชื่อหน้าและ Slug');
    } else {
        try {
            if ($page) {
                $db->prepare("UPDATE pages SET title=?, slug=?, content=?, meta_title=?, meta_desc=?, is_visible=?, updated_at=NOW() WHERE id=?")
                   ->execute([$title, $newSlug, $content, $metaTitle, $metaDesc, $isVisible, $page['id']]);
            } else {
                $db->prepare("INSERT INTO pages (id, title, slug, content, meta_title, meta_desc, is_visible, created_at, updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())")
                   ->execute([cuid(), $title, $newSlug, $content, $metaTitle, $metaDesc, $isVisible]);
            }
            flash('success', 'บันทึกหน้าเว็บสำเร็จ');
            header('Location: pages.php');
            exit;
        } catch (Exception $e) {
            flash('error', 'เกิดข้อผิดพลาด: Slug อาจซ้ำกับหน้าอื่น');
        }
    }
}
?>

<div class="mb-3">
    <a href="pages.php" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left me-1"></i>กลับรายการหน้าเว็บ
    </a>
</div>

<form method="POST">
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4">
            <div class="mb-3">
                <label class="form-label fw-semibold">ชื่อหน้า <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= e($page['title'] ?? '') ?>"
                       oninput="autoSlug(this.value)" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text text-muted">/</span>
                    <input type="text" name="slug" id="slug-input" class="form-control"
                           value="<?= e($page['slug'] ?? '') ?>" required>
                </div>
                <div class="form-text">URL ของหน้า เช่น about-us, contact</div>
            </div>
            <div class="mb-1">
                <label class="form-label fw-semibold">เนื้อหา</label>
                <input type="hidden" name="content" id="content-input"
                       value="<?= e($page['content'] ?? '') ?>">
                <div id="quill-editor"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card p-4 mb-3">
            <h6 class="fw-semibold mb-3">การแสดงผล</h6>
            <?php $isVis = (int)($page['is_visible'] ?? 0); ?>
            <div class="alert <?= $isVis ? 'alert-success' : 'alert-warning' ?> py-2 small mb-3">
                <i class="bi <?= $isVis ? 'bi-eye' : 'bi-eye-slash' ?> me-1"></i>
                <?= $isVis ? 'กำลังแสดงบนเว็บ — อยู่ใน <strong>Conference Information ▾</strong>' : 'ซ่อนอยู่ — ยังไม่แสดงบนเว็บ' ?>
            </div>
            <input type="hidden" name="is_visible" id="is_visible_val" value="<?= $isVis ?>">
            <div class="d-grid gap-2">
                <?php if (!$isVis): ?>
                <button type="submit" class="btn btn-success" onclick="document.getElementById('is_visible_val').value='1'">
                    <i class="bi bi-send me-1"></i>บันทึก & เผยแพร่
                </button>
                <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('is_visible_val').value='0'">
                    <i class="bi bi-floppy me-1"></i>บันทึกเป็นร่าง
                </button>
                <?php else: ?>
                <button type="submit" class="btn btn-primary" onclick="document.getElementById('is_visible_val').value='1'">
                    <i class="bi bi-check-lg me-1"></i>บันทึก
                </button>
                <button type="submit" class="btn btn-outline-warning btn-sm" onclick="document.getElementById('is_visible_val').value='0'">
                    <i class="bi bi-eye-slash me-1"></i>ซ่อนหน้านี้
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card p-4">
            <h6 class="fw-semibold mb-3">SEO (ไม่บังคับ)</h6>
            <div class="mb-3">
                <label class="form-label small">Meta Title</label>
                <input type="text" name="meta_title" class="form-control form-control-sm"
                       value="<?= e($page['meta_title'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label small">Meta Description</label>
                <textarea name="meta_desc" class="form-control form-control-sm" rows="3"><?= e($page['meta_desc'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
</div>
</form>

<script>
const slugInput  = document.getElementById('slug-input');
let   slugEdited = <?= $page ? 'true' : 'false' ?>;
slugInput.addEventListener('input', () => { slugEdited = true; });
function autoSlug(val) {
    if (slugEdited) return;
    slugInput.value = val.toLowerCase().trim()
        .replace(/[^a-z0-9\-]/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
