<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Main stats
$stats = [
    'pages'    => (int) $db->query("SELECT COUNT(*) FROM pages WHERE is_visible=1")->fetchColumn(),
    'news'     => (int) $db->query("SELECT COUNT(*) FROM news WHERE status='published'")->fetchColumn(),
    'contacts' => (int) $db->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn(),
    'gallery'  => (int) $db->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn(),
];

// Extra stats
$todayContacts = (int) $db->query("SELECT COUNT(*) FROM contacts WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$totalNotify   = (int) $db->query("SELECT COUNT(*) FROM notify_list")->fetchColumn();
$weekContacts  = (int) $db->query("SELECT COUNT(*) FROM contacts WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn();
$totalNews     = (int) $db->query("SELECT COUNT(*) FROM news")->fetchColumn();

$recentContacts = $db->query(
    "SELECT name, subject, created_at, is_read FROM contacts ORDER BY created_at DESC LIMIT 5"
)->fetchAll();
$recentNews = $db->query(
    "SELECT title, status, published_at, created_at FROM news ORDER BY created_at DESC LIMIT 5"
)->fetchAll();

// Chart: contacts per day last 14 days
$chartData = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $stmt = $db->prepare("SELECT COUNT(*) FROM contacts WHERE DATE(created_at)=?");
    $stmt->execute([$date]);
    $chartData[] = ['date' => date('d/m', strtotime($date)), 'count' => (int)$stmt->fetchColumn()];
}

// Notify list growth last 14 days
$notifyData = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $stmt = $db->prepare("SELECT COUNT(*) FROM notify_list WHERE DATE(created_at)<=?");
    $stmt->execute([$date]);
    $notifyData[] = (int)$stmt->fetchColumn();
}
?>

<!-- Main stat cards -->
<div class="row g-3 mb-3">
    <?php foreach ([
        ['หน้าเว็บ (เผยแพร่)',  $stats['pages'],    'bi-file-earmark-text', 'primary', 'pages.php'],
        ['บทความ (เผยแพร่)',    $stats['news'],     'bi-newspaper',         'success', 'news.php'],
        ['ข้อความยังไม่อ่าน',   $stats['contacts'], 'bi-envelope-fill',     'warning', 'contacts.php'],
        ['รูปใน Gallery',       $stats['gallery'],  'bi-images',            'info',    'gallery.php'],
    ] as [$label, $val, $icon, $color, $link]): ?>
    <div class="col-6 col-xl-3">
        <a href="<?= $link ?>" class="text-decoration-none">
            <div class="card p-4 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-<?= $color ?> bg-opacity-10">
                        <i class="bi <?= $icon ?> fs-4 text-<?= $color ?>"></i>
                    </div>
                    <div>
                        <div class="h3 fw-bold mb-0"><?= $val ?></div>
                        <div class="text-muted small"><?= $label ?></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Secondary stat cards -->
<div class="row g-3 mb-4">
    <?php foreach ([
        ['ข้อความวันนี้',    $todayContacts, 'bi-chat-dots',    'primary'],
        ['ข้อความ 7 วัน',   $weekContacts,  'bi-graph-up',     'success'],
        ['Notify Me ทั้งหมด',$totalNotify,   'bi-bell-fill',    'warning', 'notify-list.php'],
        ['บทความทั้งหมด',   $totalNews,     'bi-journals',     'info',    'news.php'],
    ] as $item): ?>
    <div class="col-6 col-xl-3">
        <?php $link = $item[4] ?? '#'; ?>
        <a href="<?= $link ?>" class="text-decoration-none">
            <div class="card px-4 py-3 h-100">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi <?= $item[2] ?> text-<?= $item[3] ?>"></i>
                    <div class="text-muted small"><?= $item[0] ?></div>
                </div>
                <div class="fw-bold fs-4 mt-1"><?= $item[1] ?></div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Analytics Charts -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-bar-chart-line me-2 text-primary"></i>ข้อความที่ได้รับ (14 วันย้อนหลัง)</h6>
            </div>
            <canvas id="contactChart" height="90"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0"><i class="bi bi-bell me-2 text-warning"></i>Notify List Growth</h6>
            </div>
            <canvas id="notifyChart" height="180"></canvas>
        </div>
    </div>
</div>

<!-- Tables -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0">ข้อความล่าสุด</h6>
                <a href="contacts.php" class="small text-primary">ดูทั้งหมด →</a>
            </div>
            <?php if ($recentContacts): ?>
            <table class="table table-sm table-hover mb-0">
                <tbody>
                <?php foreach ($recentContacts as $c): ?>
                <tr>
                    <td>
                        <?php if (!$c['is_read']): ?>
                        <span class="badge bg-warning text-dark me-1" style="font-size:10px">ใหม่</span>
                        <?php endif; ?>
                        <?= e($c['name']) ?>
                    </td>
                    <td class="text-muted small"><?= e($c['subject']) ?></td>
                    <td class="text-muted small text-nowrap"><?= date('d/m/y', strtotime($c['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-muted small mb-0">ยังไม่มีข้อความ</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0">บทความล่าสุด</h6>
                <a href="news.php" class="small text-primary">ดูทั้งหมด →</a>
            </div>
            <?php if ($recentNews): ?>
            <table class="table table-sm table-hover mb-0">
                <tbody>
                <?php foreach ($recentNews as $n): ?>
                <tr>
                    <td><?= e($n['title']) ?></td>
                    <td>
                        <span class="badge <?= $n['status']==='published' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $n['status']==='published' ? 'เผยแพร่' : 'ร่าง' ?>
                        </span>
                    </td>
                    <td class="text-muted small text-nowrap">
                        <?= date('d/m/y', strtotime($n['published_at'] ?: $n['created_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-muted small mb-0">ยังไม่มีบทความ</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
const chartLabels  = <?= json_encode(array_column($chartData, 'date')) ?>;
const chartCounts  = <?= json_encode(array_column($chartData, 'count')) ?>;
const notifyCounts = <?= json_encode($notifyData) ?>;

// Contact bar chart
new Chart(document.getElementById('contactChart'), {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'ข้อความ',
            data: chartCounts,
            backgroundColor: 'rgba(37,99,235,0.75)',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
        }
    }
});

// Notify line chart
new Chart(document.getElementById('notifyChart'), {
    type: 'line',
    data: {
        labels: chartLabels,
        datasets: [{
            label: 'Notify List',
            data: notifyCounts,
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245,158,11,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 3,
            pointBackgroundColor: '#f59e0b',
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
