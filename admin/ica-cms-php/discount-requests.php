<?php
$pageTitle = 'Discount Requests';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/mailer.php';

$db = getDB();

// ── Price map for approval email ──────────────────────────────────────────────
$PRICE_MAP = [
    'onsite'  => ['student' => ['early' => ['20pct' => ['price'=>2400,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=VMTejit_AIMZyEV-WIsB4w']], 'standard' => ['20pct' => ['price'=>3600,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=Ohsi8u1aEML0teGkThWVwQ']]],
                  'academic' => ['early' => ['20pct' => ['price'=>4000,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=Kx-hxOTLzC6iS19WxA7g4w']], 'standard' => ['20pct' => ['price'=>6400,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=Vxp7PXyRrXhY2VNClLwxeA']]]],
    'virtual' => ['student' => ['early' => ['20pct' => ['price'=>1600,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=_v8Zu7FWvQGUNfxOTQToqw']], 'standard' => ['20pct' => ['price'=>2800,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=Lh__Pmmigs2IgchDGa83jA']]],
                  'academic' => ['early' => ['20pct' => ['price'=>2800,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=hqI8xQcw8M5pkQv-TAEwig']], 'standard' => ['20pct' => ['price'=>4400,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=YJgIi1xltB27YpRvZYxMmA']]]],
    'non'     => ['all' => ['early' => ['20pct' => ['price'=>2000,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=NyDbLMLs1F9z_123qBtHiw']], 'standard' => ['20pct' => ['price'=>3600,'url'=>'https://ofas.chula.ac.th/Service/DetailTraining?data=fjJIj8Ww439WWy10dOdh4Q']]]],
];
$FORMAT_LABELS = ['onsite'=>'Onsite / Poster Presenter','virtual'=>'Virtual Presenter','non'=>'Non Presenter'];
$STATUS_LABELS = ['student'=>'Student','academic'=>'Academic / Faculty / Professional'];

// ── AJAX: Approve ─────────────────────────────────────────────────────────────
if (!empty($_POST['ajax_approve'])) {
    $id  = (int)$_POST['ajax_approve'];
    $reg = $db->prepare("SELECT * FROM registrations WHERE id=?")->execute([$id]) ? null : null;
    $stmt = $db->prepare("SELECT * FROM registrations WHERE id=?");
    $stmt->execute([$id]);
    $reg = $stmt->fetch();

    if ($reg && $reg['discount_approval_status'] === 'pending') {
        $newPayStatus = ($reg['discount_tier'] === '100pct') ? 'paid' : 'pending';
        $db->prepare("UPDATE registrations SET discount_approval_status='approved', payment_status=? WHERE id=?")->execute([$newPayStatus, $id]);

        // Send approval email
        $fmt = $FORMAT_LABELS[$reg['format']] ?? $reg['format'];
        $sts = $reg['attendee_status'] ? ($STATUS_LABELS[$reg['attendee_status']] ?? $reg['attendee_status']) : 'All Participants';

        if ($reg['discount_tier'] === '100pct') {
            $subject = 'ICA-TH 2026 — Free Registration Confirmed';
            $body = buildEmailHtml('Free Registration Confirmed ✓',
                "<p>Dear <strong>" . htmlspecialchars($reg['full_name']) . "</strong>,</p>
                <p>We are pleased to confirm that your <strong>100% discount</strong> has been approved. You are registered for <em>ICA Regional Hub Thailand 2026</em> at <strong>no cost</strong>.</p>
                <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
                    <tr><td style='color:#64748b;padding:6px 0;width:130px;'>Name</td><td style='padding:6px 0;font-weight:600;'>" . htmlspecialchars($reg['full_name']) . "</td></tr>
                    <tr><td style='color:#64748b;padding:6px 0;'>Email</td><td style='padding:6px 0;'>" . htmlspecialchars($reg['email']) . "</td></tr>
                    <tr><td style='color:#64748b;padding:6px 0;'>Format</td><td style='padding:6px 0;'>" . htmlspecialchars($fmt) . "</td></tr>
                    <tr><td style='color:#64748b;padding:6px 0;'>Status</td><td style='padding:6px 0;'>" . htmlspecialchars($sts) . "</td></tr>
                    <tr><td style='color:#64748b;padding:6px 0;'>Discount</td><td style='padding:6px 0;color:#16a34a;font-weight:700;'>100% FREE</td></tr>
                </table>
                <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;'>
                    ✅ <strong>No payment is required.</strong> You will receive further event details closer to the conference.
                </div>"
            );
        } else {
            $statusKey    = ($reg['format'] === 'non') ? 'all' : $reg['attendee_status'];
            $earlyBirdEnd = new DateTime('2026-05-10 23:59:59', new DateTimeZone('Asia/Bangkok'));
            $rateKey      = (new DateTime('now', new DateTimeZone('Asia/Bangkok')) <= $earlyBirdEnd) ? 'early' : 'standard';
            $rateData     = $PRICE_MAP[$reg['format']][$statusKey][$rateKey]['20pct'] ?? null;
            $btnStyle     = $rateKey === 'early' ? 'background:#f59e0b;color:#1c1917;' : 'background:#6366f1;color:#fff;';
            $btnLabel     = $rateKey === 'early' ? '🐦 Early Bird Rate' : '📅 Standard Rate';
            $payLinks     = $rateData
                ? "<a href='{$rateData['url']}' style='display:inline-block;{$btnStyle}text-decoration:none;padding:14px 28px;border-radius:10px;font-weight:700;'>{$btnLabel} — " . number_format($rateData['price']) . " THB</a>"
                : '';
            $subject = 'ICA-TH 2026 — Discount Approved — Complete Your Payment';
            $body = buildEmailHtml('Discount Request Approved! 🎉',
                "<p>Dear <strong>" . htmlspecialchars($reg['full_name']) . "</strong>,</p>
                <p>Your <strong>20% discount</strong> has been approved. Please click below to complete your registration.</p>
                <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
                    <tr><td style='color:#64748b;padding:6px 0;width:130px;'>Name</td><td style='padding:6px 0;font-weight:600;'>" . htmlspecialchars($reg['full_name']) . "</td></tr>
                    <tr><td style='color:#64748b;padding:6px 0;'>Email</td><td style='padding:6px 0;'>" . htmlspecialchars($reg['email']) . "</td></tr>
                    <tr><td style='color:#64748b;padding:6px 0;'>Format</td><td style='padding:6px 0;'>" . htmlspecialchars($fmt) . "</td></tr>
                    <tr><td style='color:#64748b;padding:6px 0;'>Status</td><td style='padding:6px 0;'>" . htmlspecialchars($sts) . "</td></tr>
                    <tr><td style='color:#64748b;padding:6px 0;'>Discount</td><td style='padding:6px 0;color:#d97706;font-weight:700;'>20% OFF</td></tr>
                </table>
                <div style='margin:12px 0;'>$payLinks</div>"
            );
        }

        sendMail($reg['email'], $subject, $body, '', true);
        logActivity('Approve discount request', "ID #{$id} — {$reg['full_name']} ({$reg['email']})");
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// ── AJAX: Reject ──────────────────────────────────────────────────────────────
if (!empty($_POST['ajax_reject'])) {
    $id = (int)$_POST['ajax_reject'];
    $stmt = $db->prepare("SELECT * FROM registrations WHERE id=?");
    $stmt->execute([$id]);
    $reg = $stmt->fetch();

    if ($reg) {
        $db->prepare("UPDATE registrations SET discount_approval_status='rejected' WHERE id=?")->execute([$id]);
        $body = buildEmailHtml('Discount Request Update',
            "<p>Dear <strong>" . htmlspecialchars($reg['full_name']) . "</strong>,</p>
            <p>We regret to inform you that your discount request could not be approved at this time.</p>
            <p>You are welcome to register at the standard rate. Please visit our registration page to complete your registration.</p>
            <p style='color:#64748b;font-size:13px;'>If you have any questions, please reply to this email.</p>"
        );
        sendMail($reg['email'], 'ICA-TH 2026 — Discount Request Update', $body, '', true);
        logActivity('Reject discount request', "ID #{$id} — {$reg['full_name']} ({$reg['email']})");
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// ── Load data ─────────────────────────────────────────────────────────────────
$statusFilter = $_GET['status'] ?? '';
$from         = $_GET['from']   ?? '';
$to           = $_GET['to']     ?? '';

$sql    = "SELECT * FROM registrations WHERE discount_approval_status IN ('pending','approved','rejected')";
$params = [];
if ($statusFilter && in_array($statusFilter, ['pending','approved','rejected'])) {
    $sql .= " AND discount_approval_status = ?"; $params[] = $statusFilter;
}
if ($from) { $sql .= " AND DATE(created_at) >= ?"; $params[] = $from; }
if ($to)   { $sql .= " AND DATE(created_at) <= ?"; $params[] = $to; }
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$total    = count($rows);
$pending  = count(array_filter($rows, fn($r) => $r['discount_approval_status'] === 'pending'));
$approved = count(array_filter($rows, fn($r) => $r['discount_approval_status'] === 'approved'));
$rejected = count(array_filter($rows, fn($r) => $r['discount_approval_status'] === 'rejected'));

$DISC_LABELS = ['20pct' => '20% OFF', '100pct' => '100% Free', 'none' => '—'];
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-3"><div class="card p-3 text-center"><div class="fs-3 fw-bold"><?= $total ?></div><div class="text-muted small">Total Requests</div></div></div>
    <div class="col-sm-3"><div class="card p-3 text-center border-warning"><div class="fs-3 fw-bold text-warning"><?= $pending ?></div><div class="text-muted small">Pending</div></div></div>
    <div class="col-sm-3"><div class="card p-3 text-center border-success"><div class="fs-3 fw-bold text-success"><?= $approved ?></div><div class="text-muted small">Approved</div></div></div>
    <div class="col-sm-3"><div class="card p-3 text-center border-danger"><div class="fs-3 fw-bold text-danger"><?= $rejected ?></div><div class="text-muted small">Rejected</div></div></div>
</div>

<!-- Filters + Export -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label small">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending"  <?= $statusFilter==='pending'  ?'selected':'' ?>>Pending</option>
                    <option value="approved" <?= $statusFilter==='approved' ?'selected':'' ?>>Approved</option>
                    <option value="rejected" <?= $statusFilter==='rejected' ?'selected':'' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-sm btn-primary">Filter</button></div>
            <div class="col-auto ms-auto">
                <a href="discount-requests-export.php?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&status=<?= urlencode($statusFilter) ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-download"></i> Export ZIP (Excel + Docs)
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if ($rows): ?>
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th><th>Date</th><th>Full Name</th><th>Email</th>
                    <th>Format</th><th>Status</th><th>Discount</th>
                    <th>Document</th><th>Approval</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr id="row-<?= $r['id'] ?>">
                    <td class="text-muted small"><?= $r['id'] ?></td>
                    <td class="text-muted small text-nowrap"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
                    <td><strong><?= e($r['full_name']) ?></strong></td>
                    <td class="text-muted small"><?= e($r['email']) ?></td>
                    <td><?= e($FORMAT_LABELS[$r['format']] ?? $r['format']) ?></td>
                    <td><?= e($r['attendee_status'] ? ($STATUS_LABELS[$r['attendee_status']] ?? $r['attendee_status']) : '—') ?></td>
                    <td>
                        <?php if ($r['discount_tier'] === '20pct'): ?>
                            <span class="badge bg-warning text-dark">20% OFF</span>
                        <?php elseif ($r['discount_tier'] === '100pct'): ?>
                            <span class="badge bg-success">100% Free</span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['document_filename']): ?>
                            <a href="uploads/registrations/<?= urlencode($r['document_filename']) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2">
                                <i class="bi bi-file-earmark"></i> <?= e(mb_strimwidth($r['document_original_name'] ?? '', 0, 20, '…')) ?>
                            </a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="status-cell">
                        <?php if ($r['discount_approval_status'] === 'pending'): ?>
                            <span class="badge bg-warning text-dark">⏳ Pending</span>
                        <?php elseif ($r['discount_approval_status'] === 'approved'): ?>
                            <span class="badge bg-success">✓ Approved</span>
                        <?php else: ?>
                            <span class="badge bg-danger">✕ Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td class="action-cell">
                        <?php if ($r['discount_approval_status'] === 'pending'): ?>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-success py-0 px-2" onclick="doApprove(<?= $r['id'] ?>)">✓ Approve</button>
                            <button class="btn btn-sm btn-danger  py-0 px-2" onclick="doReject(<?= $r['id'] ?>)">✕ Reject</button>
                        </div>
                        <?php else: ?>
                            <span class="text-muted small">Done</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-5">No discount requests found.</div>
        <?php endif; ?>
    </div>
</div>

<script>
async function doApprove(id) {
    if (!confirm('Approve this discount request and send approval email?')) return;
    const btn = document.querySelector(`#row-${id} button`);
    if (btn) { btn.disabled = true; btn.textContent = '…'; }
    const fd = new FormData();
    fd.append('ajax_approve', id);
    const r = await fetch('discount-requests.php', { method:'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
        document.querySelector(`#row-${id} .status-cell`).innerHTML = '<span class="badge bg-success">✓ Approved</span>';
        document.querySelector(`#row-${id} .action-cell`).innerHTML = '<span class="text-muted small">Done</span>';
    }
}
async function doReject(id) {
    if (!confirm('Reject this request and notify the applicant?')) return;
    const fd = new FormData();
    fd.append('ajax_reject', id);
    const r = await fetch('discount-requests.php', { method:'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
        document.querySelector(`#row-${id} .status-cell`).innerHTML = '<span class="badge bg-danger">✕ Rejected</span>';
        document.querySelector(`#row-${id} .action-cell`).innerHTML = '<span class="text-muted small">Done</span>';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
