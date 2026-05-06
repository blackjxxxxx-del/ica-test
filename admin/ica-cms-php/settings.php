<?php
// Handle AJAX save BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/functions.php';
    header('Content-Type: application/json');
    try {
        $db  = getDB();
        $sql = "INSERT INTO settings (id, `key`, value, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())
                ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()";
        $stmt = $db->prepare($sql);
        foreach ($_POST as $k => $v) {
            if (strpos($k, 'setting_') === 0) {
                $stmt->execute([cuid(), substr($k, 8), (string)$v]);
            }
        }
        // Return the saved key/value so admin JS can confirm what was stored
        $saved = [];
        foreach ($_POST as $k => $v) {
            if (strpos($k, 'setting_') === 0) $saved[substr($k, 8)] = (string)$v;
        }
        echo json_encode(['ok' => true, 'saved' => $saved]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

$pageTitle = 'ตั้งค่าเว็บไซต์';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

$raw = $db->query("SELECT `key`, value FROM settings")->fetchAll();
$s   = [];
foreach ($raw as $row) $s[$row['key']] = $row['value'];

function sv(array $s, string $k, string $d = ''): string {
    return htmlspecialchars($s[$k] ?? $d, ENT_QUOTES, 'UTF-8');
}
?>

<!-- Save toast -->
<div id="save-toast" style="display:none;position:fixed;top:20px;right:24px;z-index:9999;
     background:#16a34a;color:#fff;padding:12px 22px;border-radius:10px;
     font-weight:600;font-size:15px;box-shadow:0 4px 20px rgba(0,0,0,0.15);
     transition:opacity .3s">✓ บันทึกสำเร็จ</div>

<form id="settings-form" method="POST">
<div class="row g-4">
    <div class="col-lg-8">

        <div class="card p-4 mb-4">
            <h6 class="fw-semibold mb-3"><i class="bi bi-shield-check me-2"></i>Security & Analytics</h6>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-semibold">Google Analytics 4 — Measurement ID</label>
                    <input type="text" name="setting_ga4_id" class="form-control"
                           value="<?= sv($s, 'ga4_id') ?>" placeholder="G-XXXXXXXXXX">
                    <div class="form-text">วางไว้ในหน้าเว็บทุกหน้าโดยอัตโนมัติผ่าน content-loader.js</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">reCAPTCHA v3 — Site Key <span class="text-muted">(public)</span></label>
                    <input type="text" name="setting_recaptcha_site_key" class="form-control"
                           value="<?= sv($s, 'recaptcha_site_key') ?>" placeholder="6Le...">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">reCAPTCHA v3 — Secret Key <span class="text-muted">(private)</span></label>
                    <input type="password" name="setting_recaptcha_secret_key" class="form-control"
                           value="<?= sv($s, 'recaptcha_secret_key') ?>" placeholder="6Le...">
                    <div class="form-text text-danger small"><i class="bi bi-lock me-1"></i>ไม่เผยแพร่สู่ public</div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Open Graph Image URL <span class="text-muted">(สำหรับ social sharing)</span></label>
                    <input type="text" name="setting_og_image" class="form-control"
                           value="<?= sv($s, 'og_image', 'https://icahubthailand.org/im/og-cover.jpg') ?>"
                           placeholder="https://icahubthailand.org/im/og-cover.jpg">
                </div>
            </div>
        </div>

    </div>
    <div class="col-12">
        <hr class="my-2">
        <h5 class="fw-bold mb-4 mt-2"><i class="bi bi-calendar-event me-2"></i>วันสำคัญ (Important Dates)</h5>
        <div class="card p-4 mb-4">
            <p class="text-muted small mb-3">ข้อมูลนี้จะแสดงบนหน้าเว็บโดยอัตโนมัติ เช่น Submission Deadline, Conference Day ฯลฯ</p>
            <div class="row g-3">
                <?php foreach ([
                    'date_callOpen'     => 'Call for Abstracts Opens',
                    'date_subDeadline'  => 'Submission Deadline',
                    'date_notification' => 'Notification of Acceptance',
                    'date_earlyBird'    => 'Early-Bird Registration Deadline',
                    'date_stdReg'       => 'Standard Registration Deadline',
                    'date_techCheck'    => 'Technical Check',
                    'date_reception'    => 'Welcome Reception',
                    'date_day1'         => 'Conference Day 1',
                    'date_day2'         => 'Conference Day 2',
                    'date_proceedings'  => 'Proceedings Published',
                ] as $key => $label): ?>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold"><?= $label ?></label>
                    <input type="text" name="setting_<?= $key ?>" class="form-control"
                           value="<?= sv($s, $key) ?>" placeholder="เช่น 31 March 2026">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <h5 class="fw-bold mb-4"><i class="bi bi-hourglass-split me-2"></i>นับถอยหลัง (Countdown Pages)</h5>
        <div class="card p-4 mb-4">
            <p class="text-muted small mb-3">วันที่แสดงบนหน้า Coming Soon — เมื่อแก้แล้ว Countdown จะอัปเดตอัตโนมัติทันที</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Submission Portal Opens <span class="text-muted">(Coming-soon.html)</span></label>
                    <input type="text" name="setting_countdown_submit_date" class="form-control"
                           value="<?= sv($s, 'countdown_submit_date', '2026-03-31') ?>" placeholder="YYYY-MM-DD เช่น 2026-03-31">
                    <div class="form-text">รูปแบบ ISO: 2026-03-31 (เที่ยงคืน UTC+7)</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Registration Portal Opens <span class="text-muted">(Coming-soon2.html)</span></label>
                    <input type="text" name="setting_countdown_register_date" class="form-control"
                           value="<?= sv($s, 'countdown_register_date', '2026-03-31') ?>" placeholder="YYYY-MM-DD เช่น 2026-06-01">
                    <div class="form-text">รูปแบบ ISO: 2026-06-01 (เที่ยงคืน UTC+7)</div>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-4"><i class="bi bi-toggles me-2"></i>เปิด/ปิดส่วนต่างๆ บนเว็บไซต์</h5>
        <div class="card p-4 mb-4">
            <p class="text-muted small mb-3">กดสวิตช์เพื่อเปิด/ปิดทันที — ไม่ต้องกดบันทึก</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check form-switch d-flex align-items-center gap-2">
                        <input class="form-check-input auto-toggle" type="checkbox" name="setting_show_news"
                               id="show_news" value="1" data-key="show_news"
                               <?= ($s['show_news'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="show_news">
                            <i class="bi bi-newspaper me-1"></i>แสดงหน้า <em>News & Articles</em> + เพิ่มลิ้งค์ใน Nav
                        </label>
                        <span class="toggle-status ms-1"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch d-flex align-items-center gap-2">
                        <input class="form-check-input auto-toggle" type="checkbox" name="setting_show_gallery"
                               id="show_gallery" value="1" data-key="show_gallery"
                               <?= ($s['show_gallery'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="show_gallery">
                            <i class="bi bi-images me-1"></i>แสดง <em>Gallery</em> + เพิ่มลิ้งค์ใน Nav
                        </label>
                        <span class="toggle-status ms-1"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch d-flex align-items-center gap-2">
                        <input class="form-check-input auto-toggle" type="checkbox" name="setting_show_speakers"
                               id="show_speakers" value="1" data-key="show_speakers"
                               <?= ($s['show_speakers'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="show_speakers">
                            <i class="bi bi-person-video3 me-1"></i>แสดงหน้า <em>Speakers</em> + เพิ่มลิ้งค์ใน Nav
                        </label>
                        <span class="toggle-status ms-1"></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch d-flex align-items-center gap-2">
                        <input class="form-check-input auto-toggle" type="checkbox" name="setting_show_sponsors"
                               id="show_sponsors" value="1" data-key="show_sponsors"
                               <?= ($s['show_sponsors'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="show_sponsors">
                            <i class="bi bi-award me-1"></i>แสดงหน้า <em>Sponsors</em> + เพิ่มลิ้งค์ใน Nav
                        </label>
                        <span class="toggle-status ms-1"></span>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-4"><i class="bi bi-megaphone me-2"></i>ป้ายประกาศ &amp; ปุ่มบนเว็บไซต์</h5>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card p-4 h-100">
                    <h6 class="fw-semibold mb-3">ป้ายประกาศ</h6>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="setting_notice_submission"
                               id="notice_sub" value="1"
                               <?= ($s['notice_submission'] ?? '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notice_sub">
                            แสดงประกาศ <em>"Submission coming soon"</em>
                        </label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="setting_notice_registration"
                               id="notice_reg" value="1"
                               <?= ($s['notice_registration'] ?? '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notice_reg">
                            แสดงประกาศ <em>"Registration coming soon"</em>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4 h-100">
                    <h6 class="fw-semibold mb-3">ปุ่ม Submit Your Abstract</h6>
                    <div class="mb-2">
                        <label class="form-label small">สถานะ</label>
                        <select name="setting_btn_submit_state" class="form-select form-select-sm">
                            <option value="disabled" <?= ($s['btn_submit_state'] ?? 'disabled') === 'disabled' ? 'selected' : '' ?>>ปิด (disabled)</option>
                            <option value="enabled"  <?= ($s['btn_submit_state'] ?? '') === 'enabled'  ? 'selected' : '' ?>>เปิด (enabled)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small">URL ปลายทาง</label>
                        <input type="text" name="setting_btn_submit_url" class="form-control form-control-sm"
                               value="<?= sv($s, 'btn_submit_url', '/Coming-soon.html') ?>" placeholder="/Coming-soon.html หรือ https://...">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4 h-100">
                    <h6 class="fw-semibold mb-3">ปุ่ม Register to Attend</h6>
                    <div class="mb-2">
                        <label class="form-label small">สถานะ</label>
                        <select name="setting_btn_register_state" class="form-select form-select-sm">
                            <option value="disabled" <?= ($s['btn_register_state'] ?? 'disabled') === 'disabled' ? 'selected' : '' ?>>ปิด (disabled)</option>
                            <option value="enabled"  <?= ($s['btn_register_state'] ?? '') === 'enabled'  ? 'selected' : '' ?>>เปิด (enabled)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small">URL ปลายทาง</label>
                        <input type="text" name="setting_btn_register_url" class="form-control form-control-sm"
                               value="<?= sv($s, 'btn_register_url', '/Coming-soon2.html') ?>" placeholder="/Coming-soon2.html หรือ https://...">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4 position-sticky" style="top:1rem">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-check-lg me-1"></i>บันทึกการตั้งค่า
            </button>
        </div>
    </div>
</div>
</form>

<script>
/* ── Auto-save toggles — saves immediately, no Save button needed ── */
document.querySelectorAll('.auto-toggle').forEach(function(cb) {
    cb.addEventListener('change', async function() {
        const key  = this.dataset.key;
        const val  = this.checked ? '1' : '0';
        const spin = this.closest('.form-check').querySelector('.toggle-status');

        if (spin) spin.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary" style="width:14px;height:14px;"></span>';

        const fd = new FormData();
        fd.append('setting_' + key, val);

        try {
            const res  = await fetch('settings.php', { method: 'POST', body: fd });
            const data = await res.json();

            console.log('[ICA Admin] Toggle saved:', data.saved);  // debug

            if (spin) {
                spin.innerHTML = data.ok
                    ? '<i class="bi bi-check-circle-fill text-success" style="font-size:14px;" title="บันทึกแล้ว"></i>'
                    : '<i class="bi bi-x-circle-fill text-danger" style="font-size:14px;" title="บันทึกไม่สำเร็จ"></i>';
                setTimeout(() => { if (spin) spin.innerHTML = ''; }, 2500);
            }

            if (!data.ok) console.error('[ICA Admin] Save failed:', data.msg);
        } catch(e) {
            console.error('[ICA Admin] Network error:', e);
            if (spin) spin.innerHTML = '<i class="bi bi-x-circle-fill text-danger" style="font-size:14px;"></i>';
        }
    });
});

document.getElementById('settings-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>กำลังบันทึก...';

    try {
        const fd = new FormData(this);
        // checkboxes: add unchecked ones as '0'
        ['setting_notice_submission','setting_notice_registration',
         'setting_show_news','setting_show_gallery',
         'setting_show_speakers','setting_show_sponsors'].forEach(name => {
            if (!fd.has(name)) fd.append(name, '0');
        });
        const res  = await fetch('settings.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
            const toast = document.getElementById('save-toast');
            toast.style.display = 'block';
            toast.style.opacity = '1';
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.style.display = 'none', 300);
            }, 2500);
        } else {
            alert('เกิดข้อผิดพลาด: ' + (data.msg || 'ไม่ทราบสาเหตุ'));
        }
    } catch(err) {
        alert('Connection error');
    }

    btn.disabled = false;
    btn.innerHTML = origText;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
