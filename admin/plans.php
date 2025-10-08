<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/util.php';
require_login();
$pdo = db();

// Current currency context
$code = get_setting('currency_code', 'USD');
$symbol = get_setting('currency_symbol', '$');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $err = 'Invalid CSRF token';
    } else {
        // Update each plan
        $ids = $_POST['id'] ?? [];
        foreach ($ids as $i => $id) {
            $id = (int)$id;
            $sku = trim($_POST['sku'][$i] ?? '');
            $title = trim($_POST['title'][$i] ?? '');
            $subtitle = trim($_POST['subtitle'][$i] ?? '');
            $bottles = max(1, (int)($_POST['bottles'][$i] ?? 1));
            $total_price = (float)($_POST['total_price'][$i] ?? 0);
            $old_total_price = $_POST['old_total_price'][$i] !== '' ? (float)$_POST['old_total_price'][$i] : null;
            $shipping_text = trim($_POST['shipping_text'][$i] ?? '');
            $features = trim($_POST['features'][$i] ?? '');
            $image_main = trim($_POST['image_main'][$i] ?? '');

            // Handle file upload for this index
            if (!empty($_FILES['image_file']['name'][$i]) && $_FILES['image_file']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['image_file']['tmp_name'][$i];
                $name = basename($_FILES['image_file']['name'][$i]);
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                    $targetDir = realpath(__DIR__ . '/../images');
                    if (!$targetDir) { $targetDir = __DIR__ . '/../images'; if (!is_dir($targetDir)) { @mkdir($targetDir, 0775, true); } }
                    $safe = preg_replace('/[^a-zA-Z0-9._-]/','_', $name);
                    $uniq = date('YmdHis') . '_' . $safe;
                    $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $uniq;
                    if (@move_uploaded_file($tmp, $dest)) {
                        $image_main = '/images/' . $uniq; // store as web path
                    }
                }
            }

            $stmt = $pdo->prepare('UPDATE plans SET sku=?, title=?, subtitle=?, bottles=?, total_price=?, old_total_price=?, shipping_text=?, features=?, image_main=?, updated_at=? WHERE id=?');
            $stmt->execute([$sku, $title, $subtitle, $bottles, $total_price, $old_total_price, $shipping_text, $features, $image_main, date('Y-m-d H:i:s'), $id]);
        }
        $msg = 'Plans updated';
    }
}

$rows = $pdo->query('SELECT * FROM plans ORDER BY sort ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HONR Admin • Plans</title>
  <link rel="stylesheet" href="../css/theme-overrides.css">
  <style>
    body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial; background:#f6f7f8; color:#111;}
    .wrap{ max-width:1100px; margin:24px auto; padding:0 16px; }
    header{ display:flex; justify-content:space-between; align-items:center; margin:10px 0 16px; }
    .btn{ display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:10px; border:1px solid #e5e7eb; text-decoration:none; color:#111; }
    .btn-primary{ background: var(--brand-primary); color: var(--brand-on-primary); border-color: var(--brand-primary); }
    .card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px; }
    table{ width:100%; border-collapse:collapse; }
    th,td{ padding:8px; border-bottom:1px solid #f0f1f2; text-align:left; vertical-align:top; }
    th{ background:#fafafa; }
    .img-thumb{ width:100px; height:auto; border:1px solid #e5e7eb; border-radius:8px; }
    .msg{ margin:10px 0; padding:10px 12px; border-radius:10px; background:#ecfdf5; border:1px solid #10b981; }
    input[type="text"], input[type="number"], textarea{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px 10px; }
    textarea{ min-height:80px; }
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <h1>Plans (currency: <?=e($code)?>, symbol: <?=e($symbol)?>)</h1>
      <div>
        <a class="btn" href="settings.php">Settings</a>
        <a class="btn" href="dashboard.php">Dashboard</a>
        <a class="btn" href="logout.php">Logout (<?=e($_SESSION['admin_name'] ?? '')?>)</a>
      </div>
    </header>

    <?php if ($msg): ?><div class="msg"><?=e($msg)?></div><?php endif; ?>

    <div class="card">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <table>
          <thead>
            <tr>
              <th>SKU</th>
              <th>Title / Subtitle</th>
              <th>Bottles</th>
              <th>Total (<?=e($code)?>)</th>
              <th>Old total (<?=e($code)?>)</th>
              <th>Shipping</th>
              <th>Features (one per line)</th>
              <th>Image</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $i=>$r): ?>
              <tr>
                <td>
                  <input type="hidden" name="id[]" value="<?=e($r['id'])?>">
                  <input type="text" name="sku[]" value="<?=e($r['sku'])?>">
                </td>
                <td>
                  <input type="text" name="title[]" placeholder="Title" value="<?=e($r['title'])?>" style="margin-bottom:6px;">
                  <input type="text" name="subtitle[]" placeholder="Subtitle" value="<?=e($r['subtitle'])?>">
                </td>
                <td><input type="number" min="1" name="bottles[]" value="<?=e($r['bottles'])?>"></td>
                <td><input type="number" step="0.01" min="0" name="total_price[]" value="<?=e($r['total_price'])?>"></td>
                <td><input type="number" step="0.01" min="0" name="old_total_price[]" value="<?=e($r['old_total_price'])?>"></td>
                <td><input type="text" name="shipping_text[]" value="<?=e($r['shipping_text'])?>"></td>
                <td><textarea name="features[]" placeholder="One line per feature..."><?=e($r['features'])?></textarea></td>
                <td>
                  <?php if (!empty($r['image_main'])): ?>
                    <img class="img-thumb" src="<?=e($r['image_main'])?>" alt="">
                  <?php endif; ?>
                  <input type="hidden" name="image_main[]" value="<?=e($r['image_main'])?>">
                  <input type="file" name="image_file[]" accept=".jpg,.jpeg,.png,.webp">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="margin-top:12px">
          <button class="btn btn-primary" type="submit">Save Plans</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
