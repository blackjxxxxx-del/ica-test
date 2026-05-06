<?php
$pageTitle = 'จัดการผู้ใช้';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/mailer.php';

$db = getDB();

// AJAX: delete user
if (!empty($_POST['ajax_delete_user'])) {
    header('Content-Type: application/json');
    $uid = $_POST['ajax_delete_user'];
    if ($uid === $_SESSION['admin_id']) {
        echo json_encode(['ok' => false, 'msg' => 'ไม่สามารถลบตัวเองได้']);
    } else {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$uid]);
        echo json_encode(['ok' => true]);
    }
    exit;
}

// Add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'add') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['admin', 'editor']) ? $_POST['role'] : 'editor';

    if (!$name || !$email || !$password) {
        flash('error', 'กรุณากรอกข้อมูลให้ครบ');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'อีเมลไม่ถูกต้อง');
    } elseif (strlen($password) < 8) {
        flash('error', 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
    } else {
        try {
            $db->prepare("INSERT INTO users (id, name, email, password, role, created_at, updated_at) VALUES (?,?,?,?,?,NOW(),NOW())")
               ->execute([cuid(), $name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
            flash('success', "เพิ่มผู้ใช้ {$name} สำเร็จ");
        } catch (Exception $e) {
            flash('error', 'อีเมลนี้มีอยู่แล้วในระบบ');
        }
    }
    header('Location: users.php'); exit;
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'change_pw') {
    $uid     = $_POST['uid'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    if ($uid && strlen($newPass) >= 8) {
        $db->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE id=?")
           ->execute([password_hash($newPass, PASSWORD_DEFAULT), $uid]);
        flash('success', 'เปลี่ยนรหัสผ่านสำเร็จ');
    } else {
        flash('error', 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
    }
    header('Location: users.php'); exit;
}

$users = $db->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">ผู้ใช้งานระบบ</h5>
        <p class="text-muted small mb-0">จัดการบัญชีแอดมินทั้งหมด <?= count($users) ?> บัญชี</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#add-user-modal">
        <i class="bi bi-person-plus me-1"></i>เพิ่มผู้ใช้
    </button>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>ชื่อ</th>
                <th>อีเมล</th>
                <th>บทบาท</th>
                <th>วันที่เพิ่ม</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr id="user-row-<?= e($u['id']) ?>">
            <td class="fw-semibold">
                <?= e($u['name']) ?>
                <?php if ($u['id'] === $_SESSION['admin_id']): ?>
                <span class="badge bg-primary ms-1" style="font-size:10px">คุณ</span>
                <?php endif; ?>
            </td>
            <td class="text-muted"><?= e($u['email']) ?></td>
            <td>
                <span class="badge <?= $u['role']==='admin' ? 'bg-danger' : 'bg-secondary' ?>">
                    <?= $u['role'] === 'admin' ? 'Admin' : 'Editor' ?>
                </span>
            </td>
            <td class="text-muted small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-secondary me-1"
                        onclick="showChangePw('<?= e($u['id']) ?>','<?= e($u['name']) ?>')">
                    <i class="bi bi-key"></i>
                </button>
                <?php if ($u['id'] !== $_SESSION['admin_id']): ?>
                <button class="btn btn-sm btn-outline-danger"
                        onclick="deleteUser('<?= e($u['id']) ?>','<?= e($u['name']) ?>')">
                    <i class="bi bi-trash"></i>
                </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="add-user-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">เพิ่มผู้ใช้ใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">ชื่อ</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">อีเมล</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">รหัสผ่าน (อย่างน้อย 8 ตัว)</label>
                        <input type="password" name="password" class="form-control" minlength="8" required>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">บทบาท</label>
                        <select name="role" class="form-select">
                            <option value="editor">Editor — แก้ไขเนื้อหาได้</option>
                            <option value="admin">Admin — สิทธิ์เต็ม</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary btn-sm">เพิ่มผู้ใช้</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="change-pw-modal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">เปลี่ยนรหัสผ่าน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="change_pw">
                <input type="hidden" name="uid" id="pw-uid">
                <div class="modal-body">
                    <p class="text-muted small mb-3">สำหรับ: <strong id="pw-name"></strong></p>
                    <input type="password" name="new_password" class="form-control"
                           placeholder="รหัสผ่านใหม่ (อย่างน้อย 8 ตัว)" minlength="8" required>
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
function showChangePw(uid, name) {
    document.getElementById('pw-uid').value  = uid;
    document.getElementById('pw-name').textContent = name;
    new bootstrap.Modal(document.getElementById('change-pw-modal')).show();
}
async function deleteUser(uid, name) {
    if (!confirm(`ลบผู้ใช้ "${name}" ออกจากระบบ?`)) return;
    const fd = new FormData();
    fd.append('ajax_delete_user', uid);
    const res  = await fetch('users.php', { method:'POST', body:fd });
    const data = await res.json();
    if (data.ok) {
        const row = document.getElementById('user-row-' + uid);
        row.style.transition = 'opacity .3s';
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 300);
    } else {
        alert(data.msg || 'เกิดข้อผิดพลาด');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
