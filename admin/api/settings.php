<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../util.php';
header('Content-Type: application/json');

try {
    $code = get_setting('currency_code', 'USD');
    $symbol = get_setting('currency_symbol', '$');
    $rate = (float)get_setting('currency_rate', '1');
    if ($rate <= 0) { $rate = 1.0; }
    $assetsVersion = get_setting('assets_version', '');
    echo json_encode([
        'ok' => true,
        'currency' => [
            'code' => $code,
            'symbol' => $symbol,
            'rate' => $rate
        ],
        'assets_version' => $assetsVersion
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
