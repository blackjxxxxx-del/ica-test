<?php
$useQuill = true;
require_once __DIR__ . '/includes/header.php';

$db   = getDB();
$id   = $_GET['id'] ?? '';
$news = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $news = $stmt->fetch();
    if (!$news) {
        flash('error', 'ไม่พบข่าวนี้');
        header('Location: news.php');
        exit;
    }
    $pageTitle = 'แก้ไข: ' . $news['title'];
} else {
    $pageTitle = 'เพิ่มข่าวใหม่';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']   ?? '');
    $slug       = slugify(trim($_POST['slug'] ?? ''));
    $content    = $_POST['content']  ?? '';
    $excerpt    = trim($_POST['excerpt'] ?? '');
    $status     = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $publishedAt = ($status === 'published' && empty($news['published_at']))
                    ? date('Y-m-d H:i:s') : ($news['published_at'] ?? null);

    // Handle image upload
    $featuredImg = $news['featured_img'] ?? null;
    if (!empty($_FILES['featured_img']['name'])) {
        $uploaded = uploadImage($_FILES['featured_img']);
        if ($uploaded) $featuredImg = $uploaded;
    }

    if (!$title || !$slug) {
        flash('error', 'กรุณากรอกหัวข้อและ Slug');
    } else {
        try {
            if ($news) {
                $db->prepare("UPDATE news SET title=?, slug=?, content=?, excerpt=?, featured_img=?, status=?, is_featured=?, published_at=?, updated_at=NOW() WHERE id=?")
                   ->execute([$title, $slug, $content, $excerpt, $featuredImg, $status, $isFeatured, $publishedAt, $news['id']]);
            } else {
                $db->prepare("INSERT INTO news (id, title, slug, content, excerpt, featured_img, status, is_featured, published_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())")
                   ->execute([cuid(), $title, $slug, $content, $excerpt, $featuredImg, $status, $isFeatured, $publishedAt]);
            }
            flash('success', 'บันทึกข่าวสำเร็จ');
            header('Location: news.php');
            exit;
        } catch (Exception $e) {
            flash('error', 'เกิดข้อผิดพลาด: Slug อาจซ้ำกับข่าวอื่น');
        }
    }
}
?>

<div class="mb-3">
    <a href="news.php" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left me-1"></i>กลับรายการข่าว
    </a>
</div>

<form method="POST" enctype="multipart/form-data">
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4">
            <div class="mb-3">
                <label class="form-label fw-semibold">หัวข้อข่าว <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?= e($news['title'] ?? '') ?>"
                       oninput="autoSlug(this.value)" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Slug <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text text-muted">/news/</span>
                    <input type="text" name="slug" id="slug-input" class="form-control"
                           value="<?= e($news['slug'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">เนื้อหา</label>
                <div id="quill-editor"></div>
                <input type="hidden" name="content" id="content-input" value="<?= e($news['content'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label fw-semibold">ย่อหน้า (Excerpt)</label>
                <textarea name="excerpt" class="form-control" rows="3"
                          placeholder="สรุปสั้นๆ สำหรับแสดงในหน้ารายการ"><?= e($news['excerpt'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card p-4 mb-3">
            <h6 class="fw-semibold mb-3">การเผยแพร่</h6>
            <div class="mb-3">
                <label class="form-label small">สถานะ</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="draft"     <?= ($news['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>ร่าง</option>
                    <option value="published" <?= ($news['status'] ?? '') === 'published' ? 'selected' : '' ?>>เผยแพร่</option>
                </select>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured"
                       <?= ($news['is_featured'] ?? 0) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_featured">ปักหมุด (Featured)</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-lg me-1"></i>บันทึก
            </button>
        </div>

        <div class="card p-4">
            <h6 class="fw-semibold mb-3">รูปภาพหลัก</h6>
            <?php if (!empty($news['featured_img'])): ?>
            <img src="<?= e($news['featured_img']) ?>" class="img-fluid rounded mb-2" alt="">
            <?php endif; ?>
            <input type="file" name="featured_img" class="form-control form-control-sm" accept="image/*">
            <div class="form-text">JPG, PNG, WebP — สูงสุด 5MB</div>
        </div>
    </div>
</div>
</form>

<script>
const slugInput  = document.getElementById('slug-input');
let   slugEdited = <?= $news ? 'true' : 'false' ?>;
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
