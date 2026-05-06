<?php
$cur = basename($_SERVER['PHP_SELF'], '.php');

function sideLink($href, $icon, $label, $cur) {
    $file = basename($href, '.php');
    $active = ($file === $cur
        || ($file === 'pages'    && $cur === 'pages-edit')
        || ($file === 'news'     && $cur === 'news-edit')
        || ($file === 'speakers' && $cur === 'speaker-edit')
        || ($file === 'notify-list' && $cur === 'notify-broadcast'))
        ? ' active' : '';
    return "<a href=\"{$href}\" class=\"nav-link{$active}\"><i class=\"bi {$icon}\"></i>{$label}</a>";
}
?>
<nav class="sidebar d-flex flex-column p-3">
    <div class="brand mb-2">ICA CMS</div>
    <div class="nav flex-column gap-1 flex-grow-1">
        <?= sideLink('index.php',    'bi-speedometer2',      'Dashboard',  $cur) ?>
        <?= sideLink('pages.php',    'bi-file-earmark-text', 'หน้าเว็บ',   $cur) ?>
        <?= sideLink('news.php',     'bi-newspaper',         'ข่าวสาร',    $cur) ?>
        <?= sideLink('contacts.php',   'bi-envelope',          'ข้อความ',    $cur) ?>
        <?= sideLink('notify-list.php','bi-bell',            'Notify List', $cur) ?>
        <?= sideLink('gallery.php',  'bi-images',            'แกลเลอรี',   $cur) ?>
        <?= sideLink('speakers.php',  'bi-person-video3', 'Speakers',   $cur) ?>
        <?= sideLink('sponsors.php',  'bi-award',         'Sponsors',   $cur) ?>
        <?= sideLink('sessions.php',    'bi-calendar3',       'Programme',     $cur) ?>
        <?= sideLink('page-content.php','bi-pencil-square', 'แก้ไขหน้าเว็บ', $cur) ?>
        <?= sideLink('registrations.php',     'bi-person-check',   'Registrations',   $cur) ?>
        <?= sideLink('discount-requests.php', 'bi-tag',            'Discount Requests', $cur) ?>
        <?= sideLink('users.php',    'bi-people',            'ผู้ใช้งาน',  $cur) ?>
        <?= sideLink('activity-log.php',     'bi-journal-text',   'Activity Log', $cur) ?>
        <?= sideLink('notify-broadcast.php', 'bi-megaphone',       'Broadcast',    $cur) ?>
        <?= sideLink('settings.php', 'bi-gear',              'ตั้งค่า',    $cur) ?>
    </div>
    <div class="mt-auto pt-3 border-top border-secondary">
        <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i>ออกจากระบบ</a>
    </div>
</nav>
