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
  <style>
    body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial; background:#f6f7f8; color:#111;}
    .wrap{ max-width:1100px; margin:24px auto; padding:0 16px; }
    header{ display:flex; justify-content:space-between; align-items:center; margin:10px 0 16px; }
    .btn{ display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:10px; border:1px solid #e5e7eb; text-decoration:none; color:#111; }
    .btn-primary{ background: var(--brand-primary); color: var(--brand-on-primary); border-color: var(--brand-primary); }
    .grid{ display:grid; grid-template-columns: repeat(5, 1fr); gap:12px; }
    .card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px; }
    .k{ font-size:12px; color:#6b7280; }
    .v{ font-weight:800; font-size:22px; }
    table{ width:100%; border-collapse:collapse; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
    th,td{ padding:10px 12px; border-bottom:1px solid #f0f1f2; text-align:left; font-size:14px; }
    th{ background:#fafafa; }
    tr:last-child td{ border-bottom:none; }
    .status{ padding:4px 8px; border-radius:999px; font-size:12px; border:1px solid #e5e7eb; }
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>HONR Admin • Dashboard</h1>
      <div>
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

    <h2 style="margin:18px 0 8px; font-size:16px;">Latest Orders</h2>
    <table>
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
