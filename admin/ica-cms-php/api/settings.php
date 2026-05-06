<?php
/**
 * api/settings.php — Public JSON endpoint for website content
 * Called by content-loader.js on all HTML pages.
 * Returns dates, announcements, and button states.
 * No authentication required (public data only).
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $rows = getDB()->query("SELECT `key`, value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    echo json_encode(['error' => 'db']);
    exit;
}

// Only expose public-facing keys
$publicKeys = [
    // Important dates
    'date_callOpen', 'date_subDeadline', 'date_notification',
    'date_earlyBird', 'date_stdReg', 'date_techCheck',
    'date_reception', 'date_day1', 'date_day2', 'date_proceedings',
    // Announcements
    'notice_submission', 'notice_registration',
    // Button links
    'btn_submit_state', 'btn_submit_url',
    'btn_register_state', 'btn_register_url',
    // Feature toggles
    'show_news', 'show_gallery', 'show_speakers', 'show_sponsors',
    // Countdown dates
    'countdown_submit_date', 'countdown_register_date',
    // Analytics & SEO
    'ga4_id', 'recaptcha_site_key', 'og_image',
];

$out = [];
foreach ($publicKeys as $k) {
    $out[$k] = $rows[$k] ?? null;
}

echo json_encode($out);
