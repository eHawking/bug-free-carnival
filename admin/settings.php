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
// Pricing moved to Plans. No per-SKU pricing on Settings.

// Reload current settings so the page reflects latest saved values
$code = get_setting('currency_code', $code);
$symbol = get_setting('currency_symbol', $symbol);
$rate = get_setting('currency_rate', $rate);
$rateNum = (float)$rate; if ($rateNum <= 0) $rateNum = 1.0;
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
                try{
                  set_setting('currency_code', $code);
                  set_setting('currency_symbol', $symbol);
                  set_setting('currency_rate', (string)(float)$rate);
                  $msg = 'Currency saved';
                }catch (Throwable $ex){
                  $err = 'Failed to save settings: ' . $ex->getMessage();
                }
            }
        // Pricing save removed; edit plans in Plans page.
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
  <link rel="stylesheet" href="../css/admin-ui.css">
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <h1>Settings</h1>
      <div class="nav">
        <button class="btn" id="themeToggle" type="button">Light mode</button>
        <a class="btn" href="dashboard.php">Dashboard</a>
        <a class="btn" href="plans.php">Plans</a>
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
          <button class="btn" type="button" id="btnRefreshRate">Refresh rate</button>
        </div>
      </form>
    </div>

    <!-- Pricing section removed. Manage pricing under Plans. -->
  </div>
  <script src="../js/theme-toggle.js"></script>
  <script src="../js/toast.js"></script>
  <script>
    (function(){
      <?php if ($msg): ?> toast(<?=json_encode($msg)?>, 'ok'); <?php endif; ?>
      <?php if ($err): ?> toast(<?=json_encode($err)?>, 'err'); <?php endif; ?>
    })();
  </script>
  <script>
    (function(){
      const btn = document.getElementById('btnRefreshRate');
      if (!btn) return;
      btn.addEventListener('click', async function(){
        btn.disabled = true; btn.textContent = 'Refreshing...';
        try{
          const res = await fetch('api/refresh-rate.php', { credentials:'same-origin' });
          const j = await res.json();
          if (j && j.ok && typeof j.rate !== 'undefined'){
            const inp = document.querySelector('input[name="currency_rate"]');
            if (inp) inp.value = j.rate;
            alert('Rate updated to '+j.rate+' for '+j.code);
          } else {
            alert('Failed to refresh rate');
          }
        }catch(e){ alert('Failed to refresh rate'); }
        finally { btn.disabled = false; btn.textContent = 'Refresh rate'; }
      });
    })();
  </script>
</body>
</html>
