<?php
// config.php

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'clrms_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Empty password for XAMPP default

// URL Base (Optional: for QR Code generation or redirects)
define('BASE_URL', 'http://localhost/clrms/');
?>
