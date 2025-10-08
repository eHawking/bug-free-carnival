<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../util.php';
header('Content-Type: application/json');

try {
    $skus = ['EPK06'=>6, 'EPK03'=>3, 'EPK02'=>2];
    $out = [];
    $code = get_setting('currency_code', 'USD');
    $symbol = get_setting('currency_symbol', '$');
    $rate = (float)get_setting('currency_rate', '1');
    if ($rate <= 0) $rate = 1.0;

    foreach ($skus as $sku=>$bottles){
        // Preferred: current-currency amounts
        $curTotal = get_setting('price_total_'.$sku, null);
        $curOld   = get_setting('price_old_total_'.$sku, null);

        if ($curTotal === null) {
            // Fallback: legacy USD keys converted to current currency
            $usdTotal = (float)get_setting('price_total_usd_'.$sku, '0');
            $curTotal = (string)($usdTotal * $rate);
        }
        if ($curOld === null) {
            $usdOld = (float)get_setting('price_old_total_usd_'.$sku, '0');
            $curOld = (string)($usdOld * $rate);
        }

        $out[$sku] = [
            'total' => (float)$curTotal,
            'old_total' => (float)$curOld,
            'bottles' => $bottles,
        ];
    }
    echo json_encode([
        'ok'=>true,
        'currency' => ['code'=>$code, 'symbol'=>$symbol, 'rate'=>$rate],
        'pricing'=>$out
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false]);
}
