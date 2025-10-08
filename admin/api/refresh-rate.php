<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../util.php';
header('Content-Type: application/json');

try {
    // Auth: allow logged-in admin OR API secret
    $secret = $_GET['secret'] ?? $_POST['secret'] ?? '';
    $allowBySecret = defined('API_SECRET') && API_SECRET && hash_equals(API_SECRET, (string)$secret);
    if (empty($_SESSION['admin_id']) && !$allowBySecret) {
        http_response_code(403);
        echo json_encode(['ok'=>false, 'error'=>'forbidden']);
        exit;
    }
    $code = strtoupper(get_setting('currency_code', 'USD'));
    if ($code === 'USD') {
        set_setting('currency_rate', '1');
        echo json_encode(['ok'=>true, 'code'=>$code, 'rate'=>1]);
        exit;
    }
    $url = 'https://api.exchangerate.host/latest?base=USD&symbols=' . rawurlencode($code);
    $resp = @file_get_contents($url);
    if ($resp === false) { throw new Exception('fetch failed'); }
    $j = json_decode($resp, true);
    if (!is_array($j) || empty($j['rates'][$code])) { throw new Exception('bad payload'); }
    $rate = (float)$j['rates'][$code];
    if ($rate <= 0) { throw new Exception('invalid rate'); }
    set_setting('currency_rate', (string)$rate);
    echo json_encode(['ok'=>true, 'code'=>$code, 'rate'=>$rate]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false]);
}
