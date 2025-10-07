<?php
require_once __DIR__ . '/auth.php';
logout();
header('Location: ' . APP_BASE . 'admin/login.php');
exit;
