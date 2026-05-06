<?php
$pageTitle = 'Registrations';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

$FORMAT_LABELS = ['onsite'=>'Onsite / Poster','virtual'=>'Virtual','non'=>'Non Presenter'];
$STATUS_LABELS = ['student'=>'Student','academic'=>'Academic / Faculty'];

// ── AJAX: toggle payment status ───────────────────────────────────────────────
if (!empty($_POST['ajax_mark_paid'])) {
    $id  = (int)$_POST['ajax_mark_paid'];
    $val = $_POST['val'] ?? 'paid';
    $val = in_array($val, ['paid','pending']) ? $val : 'pending';
    $db->prepare("UPDATE registrations SET payment_status=? WHERE id=?")->execute([$val, $id]);
    logActivity('Update payment status', "Registration #{$id} → {$val}");
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// ── Filters ───────────────────────────────────────────────────────────────────
$payFilter    = $_GET['pay']    ?? '';
$discFilter   = $_GET['disc']   ?? '';
$from         = $_GET['from']   ?? '';
$to           = $_GET['to']     ?? '';

$sql    = "SELECT * FROM registrations WHERE 1=1";
$params = [];
if ($payFilter  && in_array($payFilter, ['pending','paid'])) { $sql .= " AND payment_status=?"; $params[] = $payFilter; }
if ($discFilter && in_array($discFilter, ['not_required','pending','approved','rejected'])) { $sql .= " AND discount_approval_status=?"; $params[] = $discFilter; }
if ($from) { $sql .= " AND DATE(created_at) >= ?"; $params[] = $from; }
if ($to)   { $sql .= " AND DATE(created_at) <= ?"; $params[] = $to; }
$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql); $stmt->execute($params);
$rows = $stmt->fetchAll();

$total = count($rows);
$paid  = count(array_filter($rows, fn($r) => $r['payment_status'] === 'paid'));
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-4"><div class="card p-3 text-center"><div class="fs-3 fw-bold"><?= $total ?></div><div class="text-muted small">Total Registrations</div></div></div>
    <div class="col-sm-4"><div class="card p-3 text-center border-success"><div class="fs-3 fw-bold text-success"><?= $paid ?></div><div class="text-muted small">Paid</div></div></div>
    <div class="col-sm-4"><div class="card p-3 text-center border-warning"><div class="fs-3 fw-bold text-warning"><?= $total - $paid ?></div><div class="text-muted small">Pending Payment</div></div></div>
</div>

<!-- Filters + Export -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto"><label class="form-label small">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= e($from) ?>">
            </div>
            <div class="col-auto"><label class="form-label small">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= e($to) ?>">
            </div>
            <div class="col-auto"><label class="form-label small">Payment</label>
                <select name="pay" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending" <?= $payFilter==='pending'?'selected':'' ?>>Pending</option>
                    <option value="paid"    <?= $payFilter==='paid'   ?'selected':'' ?>>Paid</option>
                </select>
            </div>
            <div class="col-auto"><label class="form-label small">Discount Status</label>
                <select name="disc" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="not_required" <?= $discFilter==='not_required'?'selected':'' ?>>No Discount</option>
                    <option value="pending"      <?= $discFilter==='pending'     ?'selected':'' ?>>Disc Pending</option>
                    <option value="approved"     <?= $discFilter==='approved'    ?'selected':'' ?>>Disc Approved</option>
                    <option value="rejected"     <?= $discFilter==='rejected'    ?'selected':'' ?>>Disc Rejected</option>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-sm btn-primary">Filter</button></div>
            <div class="col-auto ms-auto">
                <a href="registrations-export.php?pay=<?= urlencode($payFilter) ?>&disc=<?= urlencode($discFilter) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-download"></i> Export Excel
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
                    <th>Rate</th><th>Price</th>
                    <th>Payment</th><th>Disc Approval</th><th>Actions</th>
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
                    <td><?php
                        echo match($r['discount_tier']) {
                            '20pct'  => '<span class="badge bg-warning text-dark">20% OFF</span>',
                            '100pct' => '<span class="badge bg-success">100% Free</span>',
                            default  => '<span class="text-muted small">—</span>',
                        };
                    ?></td>
                    <td><?= e($r['selected_rate'] === 'early' ? 'Early Bird' : ($r['selected_rate'] === 'standard' ? 'Standard' : '—')) ?></td>
                    <td><?= $r['price'] ? number_format($r['price']) . ' THB' : '—' ?></td>
                    <td class="pay-cell-<?= $r['id'] ?>">
                        <?php if ($r['payment_status'] === 'paid'): ?>
                            <span class="badge bg-success">✓ Paid</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?php
                        echo match($r['discount_approval_status']) {
                            'pending'      => '<span class="badge bg-warning text-dark">⏳ Pending</span>',
                            'approved'     => '<span class="badge bg-success">✓ Approved</span>',
                            'rejected'     => '<span class="badge bg-danger">✕ Rejected</span>',
                            default        => '<span class="text-muted small">—</span>',
                        };
                    ?></td>
                    <td>
                        <?php if ($r['payment_status'] !== 'paid'): ?>
                        <button class="btn btn-sm btn-success py-0 px-2"
                                onclick="togglePay(<?= $r['id'] ?>, 'paid')">✓ Mark Paid</button>
                        <?php else: ?>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                                onclick="togglePay(<?= $r['id'] ?>, 'pending')">↩ Unpaid</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-5">No registrations found.</div>
        <?php endif; ?>
    </div>
</div>

<script>
async function togglePay(id, val) {
    const fd = new FormData();
    fd.append('ajax_mark_paid', id);
    fd.append('val', val);
    const r = await fetch('registrations.php', { method:'POST', body: fd });
    const d = await r.json();
    if (d.ok) {
        const cell = document.querySelector(`.pay-cell-${id}`);
        if (val === 'paid') {
            cell.innerHTML = '<span class="badge bg-success">✓ Paid</span>';
            event.target.outerHTML = `<button class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="togglePay(${id},'pending')">↩ Unpaid</button>`;
        } else {
            cell.innerHTML = '<span class="badge bg-warning text-dark">Pending</span>';
            event.target.outerHTML = `<button class="btn btn-sm btn-success py-0 px-2" onclick="togglePay(${id},'paid')">✓ Mark Paid</button>`;
        }
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
