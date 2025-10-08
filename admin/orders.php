<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/util.php';
require_login();
$pdo = db();
$sym = get_setting('currency_symbol', '$');

$statuses = order_statuses();
$filter_status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Handle inline status update
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $msg = 'Invalid CSRF token';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $st = $_POST['status'] ?? '';
        if ($id > 0 && in_array($st, $statuses, true)) {
            $stmt = $pdo->prepare('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?');
            $stmt->execute([$st, date('Y-m-d H:i:s'), $id]);
            $msg = 'Order #' . $id . ' updated to ' . $st;
        }
    }
}

$where = [];
$params = [];
if ($filter_status && in_array($filter_status, $statuses, true)) { $where[] = 'status = ?'; $params[] = $filter_status; }
if ($search !== '') { $where[] = '(order_number LIKE ? OR name LIKE ? OR email LIKE ?)'; $q = '%' . $search . '%'; $params[] = $q; $params[] = $q; $params[] = $q; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

// Fetch
$sql = "SELECT id, order_number, created_at, name, email, phone, sku, price, status FROM orders $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HONR Admin • Orders</title>
  <link rel="stylesheet" href="../css/theme-overrides.css">
  <link rel="stylesheet" href="../css/admin-ui.css">
  <style>
    body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial; background:#f6f7f8; color:#111;}
    .wrap{ max-width:1100px; margin:24px auto; padding:0 16px; }
    header{ display:flex; justify-content:space-between; align-items:center; margin:10px 0 16px; }
    .btn{ display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:10px; border:1px solid #e5e7eb; text-decoration:none; color:#111; }
    .btn-primary{ background: var(--brand-primary); color: var(--brand-on-primary); border-color: var(--brand-primary); }
    .filters{ display:flex; gap:8px; align-items:center; }
    input, select{ border:1px solid #e5e7eb; border-radius:8px; padding:8px 10px; }
    table{ width:100%; border-collapse:collapse; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
    th,td{ padding:10px 12px; border-bottom:1px solid #f0f1f2; text-align:left; font-size:14px; }
    th{ background:#fafafa; }
    .status{ padding:4px 8px; border-radius:999px; font-size:12px; border:1px solid #e5e7eb; }
    .msg{ margin:10px 0; padding:10px 12px; border-radius:10px; background:#ecfdf5; border:1px solid #10b981; }
    .pagination{ margin-top:12px; display:flex; gap:8px; }
  </style>
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <h1>Orders</h1>
      <div class="nav">
        <a class="btn" href="dashboard.php">Dashboard</a>
        <a class="btn" href="settings.php">Settings</a>
        <a class="btn" href="logout.php">Logout (<?=e($_SESSION['admin_name'] ?? '')?>)</a>
      </div>
    </header>

    <form class="filters" method="get" action="">
      <select name="status">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $st): ?>
          <option value="<?=e($st)?>" <?= $filter_status===$st?'selected':'' ?>><?=e(ucfirst($st))?></option>
        <?php endforeach; ?>
      </select>
      <input name="q" placeholder="Search order # / name / email" value="<?=e($search)?>">
      <button class="btn btn-primary" type="submit">Apply</button>
    </form>

    <?php if ($msg): ?><div class="msg"><?=e($msg)?></div><?php endif; ?>

    <table class="table" style="margin-top:12px;">
      <thead><tr><th>#</th><th>Date</th><th>Name</th><th>Email</th><th>Phone</th><th>SKU</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $o): ?>
          <tr>
            <td><?=e($o['order_number'] ?: ('#'.$o['id']))?></td>
            <td><?=e($o['created_at'])?></td>
            <td><?=e($o['name'])?></td>
            <td><?=e($o['email'])?></td>
            <td><?=e($o['phone'])?></td>
            <td><?=e($o['sku'])?></td>
            <td><?=e($sym)?><?=e(number_format((float)$o['price'],2))?></td>
            <td>
              <form method="post" style="display:flex; gap:6px; align-items:center">
                <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
                <input type="hidden" name="id" value="<?=e($o['id'])?>">
                <select name="status">
                  <?php foreach ($statuses as $st): ?>
                    <option value="<?=e($st)?>" <?= $o['status']===$st?'selected':'' ?>><?=e(ucfirst($st))?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" name="update_status" value="1">Save</button>
              </form>
            </td>
            <td><a class="btn" href="order.php?id=<?=e($o['id'])?>">View</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9" style="text-align:center;color:#6b7280">No orders</td></tr><?php endif; ?>
      </tbody>
    </table>

    <div class="pagination">
      <?php if ($page>1): ?><a class="btn" href="?<?=http_build_query(['status'=>$filter_status,'q'=>$search,'page'=>$page-1])?>">Prev</a><?php endif; ?>
      <span style="align-self:center;">Page <?=e($page)?> / <?=e($pages)?></span>
      <?php if ($page<$pages): ?><a class="btn" href="?<?=http_build_query(['status'=>$filter_status,'q'=>$search,'page'=>$page+1])?>">Next</a><?php endif; ?>
    </div>
  </div>
</body>
</html>
