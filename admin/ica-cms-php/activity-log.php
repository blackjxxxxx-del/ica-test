<?php
$pageTitle = 'Activity Log';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Ensure table exists
$db->exec("CREATE TABLE IF NOT EXISTS `activity_log` (
    `id`         VARCHAR(36)   NOT NULL,
    `user_id`    VARCHAR(36)   NOT NULL DEFAULT 'system',
    `user_name`  VARCHAR(255)  NOT NULL DEFAULT '',
    `action`     VARCHAR(255)  NOT NULL,
    `details`    TEXT,
    `ip`         VARCHAR(45)   NOT NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `al_user` (`user_id`),
    KEY `al_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Filter
$filter = $_GET['user'] ?? '';
$logs = [];
try {
    if ($filter) {
        $stmt = $db->prepare("SELECT * FROM activity_log WHERE user_id=? ORDER BY created_at DESC LIMIT 200");
        $stmt->execute([$filter]);
    } else {
        $stmt = $db->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 200");
    }
    $logs = $stmt->fetchAll();
} catch (Exception $e) {}

$users = [];
try {
    $users = $db->query("SELECT id, name FROM users ORDER BY name")->fetchAll();
} catch (Exception $e) {}

$actionIcons = [
    'login'    => ['bi-box-arrow-in-right', 'text-success'],
    'logout'   => ['bi-box-arrow-right',    'text-secondary'],
    'settings' => ['bi-gear-fill',          'text-primary'],
    'news'     => ['bi-newspaper',          'text-info'],
    'page'     => ['bi-file-earmark-text',  'text-primary'],
    'gallery'  => ['bi-images',             'text-warning'],
    'user'     => ['bi-person-fill',        'text-danger'],
    'contact'  => ['bi-envelope-fill',      'text-warning'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Activity Log</h5>
        <p class="text-muted small mb-0">บันทึกการกระทำ 200 รายการล่าสุด</p>
    </div>
    <form method="GET" class="d-flex gap-2">
        <select name="user" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            <option value="">ผู้ใช้ทั้งหมด</option>
            <?php foreach ($users as $u): ?>
            <option value="<?= e($u['id']) ?>" <?= $filter === $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($filter): ?>
        <a href="activity-log.php" class="btn btn-sm btn-outline-secondary">ล้าง</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th style="width:160px">เวลา</th>
                <th style="width:140px">ผู้ใช้</th>
                <th>การกระทำ</th>
                <th class="d-none d-md-table-cell">รายละเอียด</th>
                <th class="d-none d-lg-table-cell" style="width:130px">IP Address</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($logs): ?>
        <?php foreach ($logs as $log):
            $iconKey = 'settings';
            foreach (array_keys($actionIcons) as $k) {
                if (stripos($log['action'], $k) !== false) { $iconKey = $k; break; }
            }
            [$icon, $iconClass] = $actionIcons[$iconKey];
        ?>
        <tr>
            <td class="text-muted small text-nowrap">
                <?= date('d/m/y H:i:s', strtotime($log['created_at'])) ?>
            </td>
            <td>
                <span class="fw-semibold small"><?= e($log['user_name']) ?></span>
            </td>
            <td>
                <i class="bi <?= $icon ?> <?= $iconClass ?> me-1"></i>
                <?= e($log['action']) ?>
            </td>
            <td class="text-muted small d-none d-md-table-cell"><?= e($log['details']) ?></td>
            <td class="text-muted small d-none d-lg-table-cell font-monospace"><?= e($log['ip']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="5" class="text-center text-muted py-5">ยังไม่มีบันทึก</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
