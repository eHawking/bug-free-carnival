<?php
// Rename values below to match your Plesk MySQL database and upload.
// Example: DB_HOST='localhost', DB_NAME='honr', DB_USER='honr_user', DB_PASS='StrongPassword!'

define('DB_HOST', 'localhost');
define('DB_NAME', 'honr');
define('DB_USER', 'honr_user');
define('DB_PASS', 'change_me');

// Public API secret for order creation endpoint (update in production!)
define('API_SECRET', 'change_me_api_secret');

// Default timezone for timestamps
date_default_timezone_set('UTC');

// App base path (domain root). If site is in a subdir, set accordingly, e.g., '/honr/'.
define('APP_BASE', '/');

// Session name to avoid collisions
ini_set('session.name', 'HONRSESSID');
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
?>
