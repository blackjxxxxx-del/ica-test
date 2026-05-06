<?php
// ICA CMS - Database Configuration Example
// Copy this file to config.php on the server and fill in real credentials.

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// Site
define('SITE_NAME', 'ICA Thailand Hub');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Gmail SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@example.com');
define('SMTP_PASS', 'your_app_password');
define('MAIL_FROM', 'your_email@example.com');
define('MAIL_FROM_NAME', 'ICA Thailand Hub 2026');
