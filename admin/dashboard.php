<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/util.php';
require_login();
$pdo = db();
// Currency symbol
$sym = get_setting('currency_symbol', '$');

// Stats
$counts = [];
foreach (order_statuses() as $st) {
    $stmt = $pdo->prepare('SELECT COUNT(*) c FROM orders WHERE status = ?');
    $stmt->execute([$st]);
    $counts[$st] = (int)$stmt->fetchColumn();
}
$stmt = $pdo->query('SELECT COUNT(*) FROM orders');
$total = (int)$stmt->fetchColumn();

// Latest orders
$stmt = $pdo->query('SELECT id, order_number, created_at, name, sku, price, status FROM orders ORDER BY created_at DESC LIMIT 10');
$latest = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HONR Admin • Dashboard</title>
  <link rel="stylesheet" href="../css/theme-overrides.css">
  <link rel="stylesheet" href="../css/admin-ui.css">
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <h1>HONR Admin • Dashboard</h1>
      <div class="nav">
        <a class="btn" href="settings.php">Settings</a>
        <a class="btn" href="plans.php">Plans</a>
        <a class="btn" href="orders.php">Orders</a>
        <a class="btn" href="logout.php">Logout (<?=e($_SESSION['admin_name'] ?? '')?>)</a>
      </div>
    </header>

    <div class="grid">
      <div class="card"><div class="k">Total Orders</div><div class="v"><?=e($total)?></div></div>
      <?php foreach ($counts as $k=>$v): ?>
        <div class="card"><div class="k"><?=e(ucfirst($k))?></div><div class="v"><?=e($v)?></div></div>
      <?php endforeach; ?>
    </div>

    <h2 class="mt-3" style="font-size:16px;">Latest Orders</h2>
    <table class="table">
      <thead><tr><th>#</th><th>Date</th><th>Name</th><th>SKU</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($latest as $o): ?>
          <tr>
            <td><?=e($o['order_number'] ?: ('#'.$o['id']))?></td>
            <td><?=e($o['created_at'])?></td>
            <td><?=e($o['name'])?></td>
            <td><?=e($o['sku'])?></td>
            <td><?=e($sym)?><?=e(number_format((float)$o['price'],2))?></td>
            <td><span class="status"><?=e($o['status'])?></span></td>
            <td><a class="btn btn-primary" href="order.php?id=<?=e($o['id'])?>">View</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$latest): ?><tr><td colspan="7" style="text-align:center;color:#6b7280">No orders yet</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
