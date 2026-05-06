<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo 'PHP Version: ' . phpversion() . '<br>';

// Test DB
try {
    require_once __DIR__ . '/includes/db.php';
    $db = getDB();
    echo 'DB: OK<br>';
} catch (Exception $e) {
    echo 'DB Error: ' . $e->getMessage() . '<br>';
}

// Test includes
try {
    require_once __DIR__ . '/includes/functions.php';
    echo 'functions.php: OK<br>';
} catch (Exception $e) {
    echo 'functions.php Error: ' . $e->getMessage() . '<br>';
}

try {
    require_once __DIR__ . '/includes/auth.php';
    echo 'auth.php: OK<br>';
} catch (Exception $e) {
    echo 'auth.php Error: ' . $e->getMessage() . '<br>';
}

echo 'Done';
