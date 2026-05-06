<?php
// ── POST handler (AJAX save) ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_section') {
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/auth.php';
    require_once __DIR__ . '/includes/functions.php';
    requireLogin();
    header('Content-Type: application/json');
    $page    = preg_replace('/[^a-z0-9_]/', '', $_POST['page']        ?? '');
    $key     = preg_replace('/[^a-z0-9_]/', '', $_POST['section_key'] ?? '');
    $content = $_POST['content'] ?? '';
    $label   = $_POST['label']   ?? '';
    if (!$page || !$key) { echo json_encode(['ok' => false, 'error' => 'Missing params']); exit; }
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS `page_sections` (
            `id` VARCHAR(36) NOT NULL,
            `page` VARCHAR(50) NOT NULL,
            `section_key` VARCHAR(100) NOT NULL,
            `label` VARCHAR(255) NOT NULL DEFAULT '',
            `content` TEXT,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_page_section` (`page`, `section_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->prepare("INSERT INTO page_sections (id, page, section_key, label, content)
                      VALUES (?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE label = VALUES(label), content = VALUES(content)")
           ->execute([cuid(), $page, $key, $label, $content]);
        logActivity('page_content_save', "page={$page} key={$key}");
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── Admin page ──────────────────────────────────────────────────
$pageTitle = 'แก้ไขเนื้อหาหน้าเว็บ';
// Do NOT set $useQuill — we load Quill manually to avoid footer's single-instance init.
require_once __DIR__ . '/includes/header.php';
// Load Quill CSS/JS here so it's available before our inline scripts
echo '<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">';
echo '<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>';
// Dummy #quill-editor to prevent footer crash if $useQuill is accidentally true
// (not needed here since we're not setting $useQuill, just defensive)

// Section definitions
$pageSections = [
    'about' => [
        'hero_text'  => 'ข้อความใต้หัวข้อ About (Hero)',
        'what_it_is' => 'What It Is — ข้อความอธิบาย',
        'why_now'    => 'Why Now — ข้อความ',
        'who_for'    => 'Who It Is For — ข้อความ',
    ],
    'venue' => [
        'hero_text'         => 'ข้อความใต้หัวข้อ Venue (Hero)',
        'venue_name'        => 'ชื่อสถานที่',
        'venue_address'     => 'ที่อยู่',
        'venue_description' => 'รายละเอียดสถานที่',
    ],
    'contact' => [
        'contact_info' => 'ข้อมูลติดต่อ (อีเมล, โทร, ที่อยู่)',
    ],
    'submission' => [
        'intro'      => 'ข้อความแนะนำการส่งบทความ',
        'guidelines' => 'Submission Guidelines',
        'topics'     => 'Conference Topics / Call for Papers',
    ],
    'registration' => [
        'intro'        => 'ข้อความแนะนำการลงทะเบียน',
        'fees'         => 'ค่าลงทะเบียน (Registration Fees)',
        'instructions' => 'ขั้นตอนการลงทะเบียน',
    ],
];

$pageLabels = [
    'about'        => 'About',
    'venue'        => 'Venue',
    'contact'      => 'Contact',
    'submission'   => 'Submission',
    'registration' => 'Registration',
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">แก้ไขเนื้อหาหน้าเว็บ</h5>
        <p class="text-muted small mb-0">จัดการข้อความและเนื้อหาที่แสดงบนหน้าต่าง ๆ ของเว็บไซต์</p>
    </div>
</div>

<div class="card">
    <!-- Tab nav -->
    <ul class="nav nav-tabs px-3 pt-3" id="page-tab-nav">
        <?php $first = true; foreach ($pageLabels as $pid => $plabel): ?>
        <li class="nav-item">
            <a class="nav-link<?= $first ? ' active' : '' ?>"
               href="#"
               data-page="<?= e($pid) ?>"
               id="tab-link-<?= e($pid) ?>">
                <?= e($plabel) ?>
            </a>
        </li>
        <?php $first = false; endforeach; ?>
    </ul>

    <!-- Tab content -->
    <div class="tab-content p-4" id="page-tab-content">
        <?php $first = true; foreach ($pageSections as $pid => $sections): ?>
        <div id="tab-<?= e($pid) ?>" class="tab-pane<?= $first ? ' active' : '' ?>">
            <?php foreach ($sections as $skey => $slabel): ?>
            <div class="mb-4">
                <label class="fw-semibold mb-2 d-block"><?= e($slabel) ?></label>
                <div data-editor="<?= e($pid) ?>_<?= e($skey) ?>"
                     id="editor-<?= e($pid) ?>-<?= e($skey) ?>"
                     style="min-height:150px;background:#fff;border:1px solid #dee2e6;border-radius:0 0 4px 4px;"></div>
                <div style="margin-top:8px;">
                    <button class="btn btn-primary btn-sm"
                            onclick="saveSection('<?= e($pid) ?>','<?= e($skey) ?>','<?= e(addslashes($slabel)) ?>')">
                        <i class="bi bi-check2 me-1"></i>บันทึกส่วนนี้
                    </button>
                    <span class="save-status ms-2 text-success small"
                          id="status-<?= e($pid) ?>-<?= e($skey) ?>"></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php $first = false; endforeach; ?>
    </div>
</div>

<script>
/* ── Page Content Editor ───────────────────────────────────────── */
const quills = new Map();

function initQuills(pageId) {
    const tab = document.getElementById('tab-' + pageId);
    if (!tab) return;
    const editors = tab.querySelectorAll('[data-editor]');
    editors.forEach(function(el) {
        const compositeKey = el.dataset.editor;
        if (quills.has(compositeKey)) return;
        const q = new Quill(el, {
            theme: 'snow',
            modules: {
                toolbar: [['bold','italic','underline'], ['link'], ['clean']]
            }
        });
        quills.set(compositeKey, q);
    });
}

function loadPageData(pageId) {
    fetch('api/page-content.php?page=' + encodeURIComponent(pageId))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            Object.entries(data).forEach(function([key, content]) {
                if (!content) return;
                const compositeKey = pageId + '_' + key;
                const q = quills.get(compositeKey);
                if (q && content) {
                    q.clipboard.dangerouslyPasteHTML(content);
                }
            });
        })
        .catch(function() { /* fail silently */ });
}

function saveSection(page, key, label) {
    const compositeKey = page + '_' + key;
    const q = quills.get(compositeKey);
    if (!q) return;
    const content = q.root.innerHTML;
    const statusEl = document.getElementById('status-' + page + '-' + key);

    const fd = new FormData();
    fd.append('action',      'save_section');
    fd.append('page',        page);
    fd.append('section_key', key);
    fd.append('label',       label);
    fd.append('content',     content);

    fetch('page-content.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (statusEl) {
                statusEl.textContent = data.ok ? '\u2713 บันทึกแล้ว' : '\u2717 เกิดข้อผิดพลาด';
                statusEl.className   = 'save-status ms-2 small ' + (data.ok ? 'text-success' : 'text-danger');
                setTimeout(function() { if (statusEl) statusEl.textContent = ''; }, 3000);
            }
        })
        .catch(function() {
            if (statusEl) { statusEl.textContent = '\u2717 เกิดข้อผิดพลาด'; statusEl.className = 'save-status ms-2 small text-danger'; }
        });
}

// Tab switching
document.getElementById('page-tab-nav').querySelectorAll('a[data-page]').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const pageId = this.dataset.page;
        // Update nav links
        document.querySelectorAll('#page-tab-nav .nav-link').forEach(function(l) { l.classList.remove('active'); });
        this.classList.add('active');
        // Show tab pane
        document.querySelectorAll('#page-tab-content .tab-pane').forEach(function(p) { p.classList.remove('active'); });
        const pane = document.getElementById('tab-' + pageId);
        if (pane) pane.classList.add('active');
        // Init quills + load data if needed
        initQuills(pageId);
        loadPageData(pageId);
    });
});

// Init the first active tab on load
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Quill library to load (it's loaded by footer.php before this script runs)
    const firstPageId = document.querySelector('#page-tab-nav .nav-link.active')?.dataset.page;
    if (firstPageId) {
        initQuills(firstPageId);
        loadPageData(firstPageId);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
