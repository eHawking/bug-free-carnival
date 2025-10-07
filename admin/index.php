<?php
require_once __DIR__ . '/auth.php';
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . APP_BASE . 'admin/dashboard.php');
} else {
    header('Location: ' . APP_BASE . 'admin/login.php');
}
exit;
