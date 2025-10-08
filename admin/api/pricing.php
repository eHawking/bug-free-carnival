<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../util.php';
header('Content-Type: application/json');

try {
    $skus = ['EPK06'=>6, 'EPK03'=>3, 'EPK02'=>2];
    $out = [];
    foreach ($skus as $sku=>$bottles){
        $total = (float)get_setting('price_total_usd_'.$sku, '0');
        $old   = (float)get_setting('price_old_total_usd_'.$sku, '0');
        $out[$sku] = [
            'total_usd' => $total,
            'old_total_usd' => $old,
            'bottles' => $bottles,
        ];
    }
    echo json_encode(['ok'=>true, 'pricing'=>$out]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false]);
}
