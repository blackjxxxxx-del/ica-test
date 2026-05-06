<?php
$pageTitle = 'ข่าวสาร';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id'])) {
    $db->prepare("DELETE FROM news WHERE id = ?")->execute([$_POST['delete_id']]);
    flash('success', 'ลบข่าวสำเร็จ');
    header('Location: news.php');
    exit;
}

$news = $db->query("SELECT * FROM news ORDER BY created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-end mb-4">
    <a href="news-edit.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>เพิ่มข่าว
    </a>
</div>

<div class="card">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>หัวข้อ</th>
                <th>สถานะ</th>
                <th>ปักหมุด</th>
                <th>วันที่เผยแพร่</th>
                <th style="width:110px"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($news as $n): ?>
        <tr>
            <td class="fw-medium"><?= e($n['title']) ?></td>
            <td>
                <span class="badge badge-<?= $n['status'] ?>">
                    <?= $n['status'] === 'published' ? 'เผยแพร่' : 'ร่าง' ?>
                </span>
            </td>
            <td>
                <?= $n['is_featured'] ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>' ?>
            </td>
            <td class="text-muted small">
                <?= $n['published_at'] ? date('d/m/Y', strtotime($n['published_at'])) : '—' ?>
            </td>
            <td>
                <a href="news-edit.php?id=<?= urlencode($n['id']) ?>" class="btn btn-sm btn-outline-secondary me-1">
                    <i class="bi bi-pencil"></i>
                </a>
                <form method="POST" class="d-inline" onsubmit="return confirm('ยืนยันลบข่าวนี้?')">
                    <input type="hidden" name="delete_id" value="<?= e($n['id']) ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$news): ?>
        <tr><td colspan="5" class="text-center text-muted py-5">ยังไม่มีข่าวสาร</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
