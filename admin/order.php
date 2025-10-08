<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/util.php';
require_login();
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: orders.php'); exit; }

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $err = 'Invalid CSRF token';
    } else {
        $status = $_POST['status'] ?? '';
        $notes  = $_POST['notes'] ?? '';
        if ($status && in_array($status, order_statuses(), true)) {
            $stmt = $pdo->prepare('UPDATE orders SET status=?, notes=?, updated_at=? WHERE id=?');
            $stmt->execute([$status, $notes, date('Y-m-d H:i:s'), $id]);
            $msg = 'Order updated successfully';
        } else {
            $err = 'Invalid status';
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$o = $stmt->fetch();
if (!$o) { header('Location: orders.php'); exit; }
// Currency symbol
$sym = get_setting('currency_symbol', '$');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HONR Admin • Order <?=e($o['order_number'] ?: ('#'.$o['id']))?></title>
  <link rel="stylesheet" href="../css/theme-overrides.css">
  <link rel="stylesheet" href="../css/admin-ui.css">
  <style>
    .wrap{ max-width:980px; margin:24px auto; padding:0 16px; }
    .grid{ display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
    table{ width:100%; border-collapse:collapse; }
    td{ padding:6px 8px; vertical-align:top; }
  </style>
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <h1>Order <?=e($o['order_number'] ?: ('#'.$o['id']))?></h1>
      <div class="nav">
        <button class="btn" id="themeToggle" type="button">Light mode</button>
        <a class="btn" href="orders.php">Back to Orders</a>
        <a class="btn" href="settings.php">Settings</a>
        <a class="btn" href="logout.php">Logout (<?=e($_SESSION['admin_name'] ?? '')?>)</a>
      </div>
    </header>

    <?php if ($msg): ?><div class="msg ok"><?=e($msg)?></div><?php endif; ?>
    <?php if ($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>

    <div class="grid">
      <div class="card">
        <table>
          <tr><td class="k">Placed</td><td class="v"><?=e($o['created_at'])?></td></tr>
          <tr><td class="k">Updated</td><td class="v"><?=e($o['updated_at'])?></td></tr>
          <tr><td class="k">Name</td><td class="v"><?=e($o['name'])?></td></tr>
          <tr><td class="k">Email</td><td class="v"><?=e($o['email'])?></td></tr>
          <tr><td class="k">Phone</td><td class="v"><?=e($o['phone'])?></td></tr>
          <tr><td class="k">Address</td><td class="v"><?=e($o['address_line1'])?></td></tr>
          <tr><td class="k">City / ZIP</td><td class="v"><?=e($o['city'])?>, <?=e($o['zip'])?></td></tr>
          <tr><td class="k">SKU</td><td class="v"><?=e($o['sku'])?></td></tr>
          <tr><td class="k">Product</td><td class="v"><?=e($o['product_name'])?></td></tr>
          <tr><td class="k">Total</td><td class="v"><?=e($sym)?><?=e(number_format((float)$o['price'],2))?></td></tr>
          <tr><td class="k">Payment</td><td class="v"><?=e($o['payment_method'])?></td></tr>
          <tr><td class="k">IP</td><td class="v"><?=e($o['ip'])?></td></tr>
          <tr><td class="k">UA</td><td class="v" style="word-break:break-word;"><?=e($o['user_agent'])?></td></tr>
        </table>
      </div>
      <div class="card">
        <form method="post">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
          <label>Status</label>
          <select name="status">
            <?php foreach (order_statuses() as $st): ?>
              <option value="<?=e($st)?>" <?= $o['status']===$st?'selected':'' ?>><?=e(ucfirst($st))?></option>
            <?php endforeach; ?>
          </select>
          <label>Notes</label>
          <textarea name="notes" rows="6" placeholder="Internal notes for COD handling..."><?=e($o['notes'])?></textarea>
          <div style="margin-top:10px;">
            <button class="btn btn-primary" type="submit">Save</button>
          </div>
        </form>
      </div>
    </div>

    <?php if (!empty($o['raw_json'])): ?>
      <div class="card" style="margin-top:14px;">
        <div class="k" style="margin-bottom:6px;">Raw payload</div>
        <pre style="white-space:pre-wrap;word-break:break-word; font-size:12px; background:#fafafa; border:1px solid #e5e7eb; padding:10px; border-radius:8px;">
<?=e($o['raw_json'])?>
        </pre>
      </div>
    <?php endif; ?>
  </div>
  <script src="../js/theme-toggle.js"></script>
</body>
</html>
