<?php
$pageTitle = 'แกลเลอรี';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload
    if (!empty($_FILES['images']['name'][0])) {
        $files   = $_FILES['images'];
        $count   = count($files['name']);
        $ok      = 0;
        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            $url = uploadImage($file);
            if ($url) {
                $db->prepare("INSERT INTO gallery_images (id, url, title, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())")
                   ->execute([cuid(), $url, pathinfo($file['name'], PATHINFO_FILENAME)]);
                $ok++;
            }
        }
        flash($ok > 0 ? 'success' : 'error', $ok > 0 ? "อัพโหลด $ok รูปสำเร็จ" : 'อัพโหลดไม่สำเร็จ ตรวจสอบประเภทไฟล์');
        header('Location: gallery.php');
        exit;
    }

    // Delete
    if (!empty($_POST['delete_id'])) {
        $stmt = $db->prepare("SELECT url FROM gallery_images WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $img = $stmt->fetch();
        if ($img) {
            $local = __DIR__ . '/' . $img['url'];
            if (file_exists($local)) @unlink($local);
            $db->prepare("DELETE FROM gallery_images WHERE id = ?")->execute([$_POST['delete_id']]);
        }
        flash('success', 'ลบรูปสำเร็จ');
        header('Location: gallery.php');
        exit;
    }
}

$images = $db->query("SELECT * FROM gallery_images ORDER BY created_at DESC")->fetchAll();
?>

<div class="card p-4 mb-4">
    <h6 class="fw-semibold mb-3">อัพโหลดรูปภาพ</h6>
    <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
        <div class="col-md-9">
            <input type="file" name="images[]" class="form-control" accept="image/*" multiple required>
            <div class="form-text">รองรับ JPG, PNG, WebP, GIF — สูงสุด 5MB ต่อไฟล์ — เลือกได้หลายรูปพร้อมกัน</div>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-cloud-upload me-1"></i>อัพโหลด
            </button>
        </div>
    </form>
</div>

<div class="row g-3">
<?php foreach ($images as $img): ?>
<div class="col-6 col-md-4 col-lg-3 col-xl-2">
    <div class="card overflow-hidden position-relative" style="aspect-ratio:1/1">
        <img src="<?= e($img['url']) ?>" class="w-100 h-100 object-fit-cover" alt="<?= e($img['title'] ?? '') ?>">
        <div class="position-absolute bottom-0 start-0 end-0 p-1 bg-dark bg-opacity-60 d-flex justify-content-between align-items-center">
            <span class="text-white small text-truncate me-1" style="max-width:70%;font-size:.7rem">
                <?= e($img['title'] ?? '') ?>
            </span>
            <form method="POST" onsubmit="return confirm('ลบรูปนี้?')">
                <input type="hidden" name="delete_id" value="<?= e($img['id']) ?>">
                <button class="btn btn-danger p-0" style="width:22px;height:22px;font-size:.7rem;line-height:1">
                    <i class="bi bi-x"></i>
                </button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if (!$images): ?>
<div class="col-12 text-center text-muted py-5">
    <i class="bi bi-images fs-1 d-block mb-2 opacity-25"></i>
    ยังไม่มีรูปภาพ
</div>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
