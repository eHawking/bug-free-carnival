<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/util.php';
require_login();

$codes = [
  'USD' => ['symbol' => '$', 'name' => 'US Dollar'],
  'INR' => ['symbol' => '₹', 'name' => 'Indian Rupee'],
];

$msg = '';
$err = '';

$code = get_setting('currency_code', 'USD');
$symbol = get_setting('currency_symbol', '$');
$rate = get_setting('currency_rate', '1');
$rateNum = (float)$rate; if ($rateNum <= 0) $rateNum = 1.0;

// Pricing settings (base USD)
$skus = ['EPK06'=>6, 'EPK03'=>3, 'EPK02'=>2];
$pricing = [];
foreach ($skus as $sku=>$bottles){
    $storedTotalUsd = get_setting('price_total_usd_'.$sku, '');
    $storedOldUsd   = get_setting('price_old_total_usd_'.$sku, '');
    $totalCur = ($storedTotalUsd !== '') ? ((float)$storedTotalUsd * $rateNum) : '';
    $oldCur   = ($storedOldUsd   !== '') ? ((float)$storedOldUsd   * $rateNum) : '';
    $pricing[$sku] = [
        'total' => $totalCur,
        'old'   => $oldCur,
        'bottles' => $bottles,
    ];
}

// Reload current settings so the page reflects latest saved values
$code = get_setting('currency_code', $code);
$symbol = get_setting('currency_symbol', $symbol);
$rate = get_setting('currency_rate', $rate);
$rateNum = (float)$rate; if ($rateNum <= 0) $rateNum = 1.0;
$pricing = [];
foreach ($skus as $sku=>$bottles){
    $storedTotalUsd = get_setting('price_total_usd_'.$sku, '');
    $storedOldUsd   = get_setting('price_old_total_usd_'.$sku, '');
    $totalCur = ($storedTotalUsd !== '') ? ((float)$storedTotalUsd * $rateNum) : '';
    $oldCur   = ($storedOldUsd   !== '') ? ((float)$storedOldUsd   * $rateNum) : '';
    $pricing[$sku] = [
        'total' => $totalCur,
        'old'   => $oldCur,
        'bottles' => $bottles,
    ];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $err = 'Invalid CSRF token';
    } else {
        $which = $_POST['form'] ?? '';
        if ($which === 'currency') {
            $code = strtoupper(trim($_POST['currency_code'] ?? 'USD'));
            $symbol = trim($_POST['currency_symbol'] ?? '');
            $rate = trim($_POST['currency_rate'] ?? '1');
            if (!isset($codes[$code])) { $err = 'Unsupported currency code'; }
            if ($symbol === '') { $symbol = $codes[$code]['symbol']; }
            if (!is_numeric($rate) || (float)$rate <= 0) { $err = 'Rate must be > 0'; }
            if (!$err) {
                set_setting('currency_code', $code);
                set_setting('currency_symbol', $symbol);
                set_setting('currency_rate', (string)(float)$rate);
                $msg = 'Currency saved';
            }
        } elseif ($which === 'pricing') {
            // Read current currency and rate; values on this form are entered in selected currency
            $code = get_setting('currency_code', 'USD');
            $rateNum = (float)get_setting('currency_rate', '1');
            if ($rateNum <= 0) $rateNum = 1.0;
            foreach ($skus as $sku=>$bottles){
                $t = trim($_POST['price_total_usd_'.$sku] ?? ''); // entered in current currency
                $o = trim($_POST['price_old_total_usd_'.$sku] ?? '');
                if ($t !== '' && !is_numeric($t)) { $err = 'Totals must be numeric'; break; }
                if ($o !== '' && !is_numeric($o)) { $err = 'Old totals must be numeric'; break; }
            }
            if (!$err) {
                foreach ($skus as $sku=>$bottles){
                    $t = trim($_POST['price_total_usd_'.$sku] ?? '');
                    $o = trim($_POST['price_old_total_usd_'.$sku] ?? '');
                    if ($t !== '') {
                        // Save in current currency so pricing follows selected currency
                        set_setting('price_total_'.$sku, (string)$t);
                        // Also store USD for reference
                        $usd = (float)$t / $rateNum; // convert back to USD for storage
                        set_setting('price_total_usd_'.$sku, (string)$usd);
                    }
                    if ($o !== '') {
                        set_setting('price_old_total_'.$sku, (string)$o);
                        $usdOld = (float)$o / $rateNum;
                        set_setting('price_old_total_usd_'.$sku, (string)$usdOld);
                    }
                }
                $msg = 'Pricing saved for currency '.e($code);
            }
        } elseif ($which === 'images') {
            // Handle product image uploads; save to existing paths so website reflects updates
            $map = [
                'EPK06' => __DIR__ . '/../images/img-PRODx6.png',
                'EPK03' => __DIR__ . '/../images/img-PRODx3.png',
                'EPK02' => __DIR__ . '/../images/img-PRODx2.png',
            ];
            $allowed = ['image/png','image/jpeg','image/webp'];
            $limitBytes = 5 * 1024 * 1024; // 5MB
            $updated = [];
            foreach ($map as $sku=>$target){
                $key = 'image_'.$sku;
                if (!isset($_FILES[$key]) || !is_array($_FILES[$key])) continue;
                $f = $_FILES[$key];
                if ($f['error'] === UPLOAD_ERR_NO_FILE) continue;
                if ($f['error'] !== UPLOAD_ERR_OK) { $err = 'Upload error for '.$sku; break; }
                if ($f['size'] > $limitBytes) { $err = $sku.' image too large (max 5MB)'; break; }
                $type = mime_content_type($f['tmp_name']);
                if ($type && !in_array($type, $allowed, true)) { $err = $sku.' image type not allowed (png/jpg/webp)'; break; }
                // Ensure directory exists
                $dir = dirname($target);
                if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
                if (!move_uploaded_file($f['tmp_name'], $target)) { $err = 'Failed to save image for '.$sku; break; }
                @chmod($target, 0644);
                $updated[] = $sku;
            }
            if (!$err) {
                if ($updated) {
                    // Optional: bump assets_version to hint caches
                    set_setting('assets_version', (string)time());
                    $msg = 'Updated images: '.implode(', ', $updated);
                } else {
                    $msg = 'No images uploaded';
                }
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HONR Admin • Settings</title>
  <link rel="stylesheet" href="../css/theme-overrides.css">
  <style>
    body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial; background:#f6f7f8; color:#111;}
    .wrap{ max-width:720px; margin:24px auto; padding:0 16px; }
    header{ display:flex; justify-content:space-between; align-items:center; margin:10px 0 16px; }
    .btn{ display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:10px; border:1px solid #e5e7eb; text-decoration:none; color:#111; }
    .btn-primary{ background: var(--brand-primary); color: var(--brand-on-primary); border-color: var(--brand-primary); }
    .card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px; }
    label{ display:block; font-size:13px; margin:10px 0 6px; }
    input, select{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; }
    .msg{ margin:10px 0; padding:10px 12px; border-radius:10px; }
    .ok{ background:#ecfdf5; border:1px solid #10b981; }
    .err{ background:#fef2f2; border:1px solid #ef4444; }
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>Settings</h1>
      <div>
        <a class="btn" href="dashboard.php">Dashboard</a>
        <a class="btn" href="orders.php">Orders</a>
        <a class="btn" href="logout.php">Logout (<?=e($_SESSION['admin_name'] ?? '')?>)</a>
      </div>
    </header>

    <?php if ($msg): ?><div class="msg ok"><?=e($msg)?></div><?php endif; ?>
    <?php if ($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>

    <div class="card">
      <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="form" value="currency">
        <label>Currency</label>
        <select name="currency_code" onchange="var s=this.options[this.selectedIndex].dataset.symbol; if(s){ document.getElementById('currency_symbol').value=s }">
          <?php foreach ($codes as $k=>$meta): ?>
            <option value="<?=e($k)?>" data-symbol="<?=e($meta['symbol'])?>" <?= $code===$k?'selected':'' ?>><?=e($k.' — '.$meta['name'])?></option>
          <?php endforeach; ?>
        </select>
        <label>Symbol</label>
        <input id="currency_symbol" name="currency_symbol" value="<?=e($symbol)?>" />
        <label>Rate (multiplier vs USD)</label>
        <input name="currency_rate" type="number" step="0.0001" min="0.0001" value="<?=e($rate)?>" />
        <p style="font-size:12px;color:#6b7280">Example: If base prices are in USD and you want INR display, set code=INR, symbol=₹, and rate ~ 83.00 (update as needed).</p>
        <div style="margin-top:12px">
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>

    <div class="card" style="margin-top:14px;">
      <h3 style="margin:0 0 8px; font-size:16px;">Pricing (entered in <?=e($code)?>)</h3>
      <p style="margin:6px 0 12px; font-size:12px; color:#6b7280;">Tip: If you change currency/rate, click "Save" above first, then update pricing below. Values you enter here are in <?=e($code)?> and will be stored internally in USD.</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <input type="hidden" name="form" value="pricing">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:8px 6px; border-bottom:1px solid #e5e7eb;">SKU</th>
              <th style="text-align:left; padding:8px 6px; border-bottom:1px solid #e5e7eb;">Bottles</th>
              <th style="text-align:left; padding:8px 6px; border-bottom:1px solid #e5e7eb;">Total (USD)</th>
              <th style="text-align:left; padding:8px 6px; border-bottom:1px solid #e5e7eb;">Old total (USD)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pricing as $sku=>$p): ?>
              <tr>
                <td style="padding:8px 6px;"><?=e($sku)?></td>
                <td style="padding:8px 6px;"><?=e($p['bottles'])?></td>
                <td style="padding:8px 6px;"><input name="price_total_usd_<?=e($sku)?>" type="number" step="0.01" min="0" value="<?=e($p['total'])?>" /></td>
                <td style="padding:8px 6px;"><input name="price_old_total_usd_<?=e($sku)?>" type="number" step="0.01" min="0" value="<?=e($p['old'])?>" /></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p style="font-size:12px;color:#6b7280; margin-top:6px;">These are the base USD totals. Displayed values on the site and checkout will use the current currency rate and symbol.</p>
        <div style="margin-top:12px">
          <button class="btn btn-primary" type="submit">Save Pricing</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
