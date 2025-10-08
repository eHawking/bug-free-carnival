<?php
require_once __DIR__ . '/auth.php';
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . app_base() . 'admin/dashboard.php');
} else {
    header('Location: ' . app_base() . 'admin/login.php');
}
exit;
