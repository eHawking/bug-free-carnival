<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../util.php';
header('Content-Type: application/json');

try {
    $pdo = db();
    $code = get_setting('currency_code', 'USD');
    $symbol = get_setting('currency_symbol', '$');
    $rate = (float)get_setting('currency_rate', '1');
    if ($rate <= 0) { $rate = 1.0; }

    $stmt = $pdo->query('SELECT sku, title, subtitle, bottles, total_price, old_total_price, shipping_text, features, image_main, sort FROM plans ORDER BY sort ASC, sku ASC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $plans = [];
    foreach ($rows as $r) {
        $features = [];
        if (!empty($r['features'])) {
            $features = preg_split('/\r?\n/', (string)$r['features']);
            $features = array_values(array_filter(array_map('trim', $features), function($s){ return $s !== ''; }));
        }
        $plans[] = [
            'sku' => $r['sku'],
            'title' => $r['title'],
            'subtitle' => $r['subtitle'],
            'bottles' => (int)$r['bottles'],
            'total_price' => (float)$r['total_price'],
            'old_total_price' => isset($r['old_total_price']) ? (float)$r['old_total_price'] : null,
            'shipping_text' => $r['shipping_text'],
            'features' => $features,
            'image_main' => $r['image_main'],
            'sort' => (int)$r['sort'],
        ];
    }

    echo json_encode([
        'ok' => true,
        'currency' => [ 'code' => $code, 'symbol' => $symbol, 'rate' => $rate ],
        'plans' => $plans
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
