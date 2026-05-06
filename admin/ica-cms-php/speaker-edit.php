<?php
$pageTitle = 'แก้ไข Speaker';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$id = $_GET['id'] ?? null;
$sp = $id ? $db->prepare("SELECT * FROM speakers WHERE id=?") : null;
if ($sp) { $sp->execute([$id]); $sp = $sp->fetch(); }
if ($id && !$sp) { flash('error','ไม่พบข้อมูล'); header('Location: speakers.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $title       = trim($_POST['title'] ?? '');
    $affiliation = trim($_POST['affiliation'] ?? '');
    $bio         = trim($_POST['bio'] ?? '');
    $talk_title  = trim($_POST['talk_title'] ?? '');
    $sort_order  = (int)($_POST['sort_order'] ?? 0);
    $is_visible  = isset($_POST['is_visible']) ? 1 : 0;
    $photo       = $sp['photo'] ?? '';

    if (!empty($_FILES['photo']['name'])) {
        $up = uploadImage($_FILES['photo']);
        if ($up) $photo = $up;
    }

    if (!$name) { flash('error','กรุณากรอกชื่อ'); }
    else {
        if ($id) {
            $db->prepare("UPDATE speakers SET name=?,title=?,affiliation=?,bio=?,photo=?,talk_title=?,sort_order=?,is_visible=?,updated_at=NOW() WHERE id=?")
               ->execute([$name,$title,$affiliation,$bio,$photo,$talk_title,$sort_order,$is_visible,$id]);
            logActivity('speaker', "แก้ไข: $name");
        } else {
            $id = cuid();
            $db->prepare("INSERT INTO speakers (id,name,title,affiliation,bio,photo,talk_title,sort_order,is_visible,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())")
               ->execute([$id,$name,$title,$affiliation,$bio,$photo,$talk_title,$sort_order,$is_visible]);
            logActivity('speaker', "เพิ่ม: $name");
        }
        flash('success','บันทึกสำเร็จ');
        header('Location: speakers.php'); exit;
    }
}
?>

<div class="mb-4">
    <a href="speakers.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>กลับ</a>
    <h5 class="fw-bold mt-2 mb-0"><?= $sp ? 'แก้ไข Speaker' : 'เพิ่ม Speaker ใหม่' ?></h5>
</div>

<form method="POST" enctype="multipart/form-data">
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card p-4 mb-4">
            <div class="mb-3">
                <label class="form-label small fw-semibold">ชื่อ-นามสกุล *</label>
                <input type="text" name="name" class="form-control" value="<?= e($sp['name'] ?? '') ?>" required>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">ตำแหน่ง (Title)</label>
                    <input type="text" name="title" class="form-control" value="<?= e($sp['title'] ?? '') ?>" placeholder="เช่น Professor, Dr.">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">สถาบัน (Affiliation)</label>
                    <input type="text" name="affiliation" class="form-control" value="<?= e($sp['affiliation'] ?? '') ?>" placeholder="เช่น Chulalongkorn University">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold">หัวข้อบรรยาย (Talk Title)</label>
                <input type="text" name="talk_title" class="form-control" value="<?= e($sp['talk_title'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label small fw-semibold">Bio / ประวัติย่อ</label>
                <textarea name="bio" class="form-control" rows="5"><?= e($sp['bio'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4 mb-4">
            <h6 class="fw-semibold mb-3">รูปภาพ</h6>
            <?php if (!empty($sp['photo'])): ?>
            <img src="<?= e($sp['photo']) ?>" class="img-fluid rounded-circle mb-3 mx-auto d-block" style="width:120px;height:120px;object-fit:cover" alt="">
            <?php endif; ?>
            <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
            <div class="form-text">แนะนำ: ภาพสี่เหลี่ยมจัตุรัส อย่างน้อย 400×400px</div>
        </div>
        <div class="card p-4 mb-4">
            <h6 class="fw-semibold mb-3">การตั้งค่า</h6>
            <div class="mb-3">
                <label class="form-label small fw-semibold">ลำดับที่แสดง</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)($sp['sort_order'] ?? 0) ?>" min="0">
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_visible" id="vis" <?= ($sp['is_visible'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="vis">แสดงบนเว็บ</label>
            </div>
        </div>
        <div class="card p-4">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-lg me-1"></i>บันทึก
            </button>
        </div>
    </div>
</div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
