<?php
/**
 * api/register.php
 * Public endpoint — called when user clicks Pay (no discount flow)
 * Records the registration as "pending payment" then the frontend opens Chula OFAS URL.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

require_once __DIR__ . '/../includes/db.php';

// ── Price map (mirrors registration-payment.html) ─────────────────────────────
$PRICE_MAP = [
    'onsite' => [
        'student'  => [
            'early'    => ['none' => ['price' => 3000, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=9aiLPPbPYWmBMdu3a5XIDQ'],
                           '20pct' => ['price' => 2400, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=VMTejit_AIMZyEV-WIsB4w'],
                           '100pct' => ['price' => 0, 'url' => null]],
            'standard' => ['none' => ['price' => 4500, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=CRXtjZ4Z1cGoZMJhISte6g'],
                           '20pct' => ['price' => 3600, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=Ohsi8u1aEML0teGkThWVwQ'],
                           '100pct' => ['price' => 0, 'url' => null]],
        ],
        'academic' => [
            'early'    => ['none' => ['price' => 5000, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=xZ_6PEN01bQLvZ47BGcTsQ'],
                           '20pct' => ['price' => 4000, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=Kx-hxOTLzC6iS19WxA7g4w'],
                           '100pct' => ['price' => 0, 'url' => null]],
            'standard' => ['none' => ['price' => 8000, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=yNyQXCjQXg7IGF7HEwBLfg'],
                           '20pct' => ['price' => 6400, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=Vxp7PXyRrXhY2VNClLwxeA'],
                           '100pct' => ['price' => 0, 'url' => null]],
        ],
    ],
    'virtual' => [
        'student'  => [
            'early'    => ['none' => ['price' => 2000, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=yYaWaIkEK7m70pjvlb7yqQ'],
                           '20pct' => ['price' => 1600, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=_v8Zu7FWvQGUNfxOTQToqw'],
                           '100pct' => ['price' => 0, 'url' => null]],
            'standard' => ['none' => ['price' => 3500, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=6yj5RYkypZIaSS8zoKWrRA'],
                           '20pct' => ['price' => 2800, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=Lh__Pmmigs2IgchDGa83jA'],
                           '100pct' => ['price' => 0, 'url' => null]],
        ],
        'academic' => [
            'early'    => ['none' => ['price' => 3500, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=o4cEZKdqAIqhIT8kf5-X2Q'],
                           '20pct' => ['price' => 2800, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=hqI8xQcw8M5pkQv-TAEwig'],
                           '100pct' => ['price' => 0, 'url' => null]],
            'standard' => ['none' => ['price' => 5500, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=37xkkPSRrJeiaBg-WDdF0A'],
                           '20pct' => ['price' => 4400, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=YJgIi1xltB27YpRvZYxMmA'],
                           '100pct' => ['price' => 0, 'url' => null]],
        ],
    ],
    'non' => [
        'all' => [
            'early'    => ['none' => ['price' => 2500, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=wFd5cxYh6sGPyv5xM3HZbw'],
                           '20pct' => ['price' => 2000, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=NyDbLMLs1F9z_123qBtHiw'],
                           '100pct' => ['price' => 0, 'url' => null]],
            'standard' => ['none' => ['price' => 4500, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=T67Rvql7XsczWPVYUrNTvA'],
                           '20pct' => ['price' => 3600, 'url' => 'https://ofas.chula.ac.th/Service/DetailTraining?data=fjJIj8Ww439WWy10dOdh4Q'],
                           '100pct' => ['price' => 0, 'url' => null]],
        ],
    ],
];

$data         = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$fullName     = trim($data['fullName']       ?? '');
$email        = trim($data['email']          ?? '');
$format       = trim($data['format']         ?? '');
$attendeeSts  = trim($data['attendeeStatus'] ?? '');
$rate         = trim($data['rate']           ?? '');

if (!$fullName || !$email || !$format || !$rate) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}

$statusKey = ($format === 'non') ? 'all' : $attendeeSts;
$priceData = $PRICE_MAP[$format][$statusKey][$rate]['none'] ?? null;
if (!$priceData) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid combination']);
    exit;
}

try {
    $db = getDB();
    $db->prepare("
        INSERT INTO registrations
            (full_name, email, format, attendee_status, discount_tier,
             selected_rate, price, payment_url, payment_status, discount_approval_status)
        VALUES (?, ?, ?, ?, 'none', ?, ?, ?, 'pending', 'not_required')
    ")->execute([
        $fullName,
        $email,
        $format,
        $attendeeSts ?: null,
        $rate,
        $priceData['price'],
        $priceData['url'],
    ]);

    echo json_encode(['id' => $db->lastInsertId(), 'paymentUrl' => $priceData['url']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
