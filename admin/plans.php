<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/util.php';
require_login();
$pdo = db();

// Ensure plans schema exists and contains required columns
function ensure_plans_schema(PDO $pdo){
  try{
    $cols = [];
    $st = $pdo->query('SHOW COLUMNS FROM plans');
    while($r = $st->fetch(PDO::FETCH_ASSOC)) { $cols[$r['Field']] = true; }
  } catch (Throwable $e) {
    // Table missing: create
    $pdo->exec("CREATE TABLE IF NOT EXISTS plans (
      id INT AUTO_INCREMENT PRIMARY KEY,
      sku VARCHAR(10) NOT NULL UNIQUE,
      title VARCHAR(190) NOT NULL,
      subtitle VARCHAR(190) NULL,
      bottles INT NOT NULL DEFAULT 1,
      total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
      old_total_price DECIMAL(12,2) NULL,
      shipping_text VARCHAR(190) NULL,
      features TEXT NULL,
      image_main VARCHAR(255) NULL,
      sort INT NOT NULL DEFAULT 0,
      updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    return;
  }
  // Add any missing columns
  $adds = [];
  if (!isset($cols['subtitle'])) $adds[] = 'ADD COLUMN subtitle VARCHAR(190) NULL AFTER title';
  if (!isset($cols['bottles'])) $adds[] = 'ADD COLUMN bottles INT NOT NULL DEFAULT 1 AFTER subtitle';
  if (!isset($cols['total_price'])) $adds[] = 'ADD COLUMN total_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER bottles';
  if (!isset($cols['old_total_price'])) $adds[] = 'ADD COLUMN old_total_price DECIMAL(12,2) NULL AFTER total_price';
  if (!isset($cols['shipping_text'])) $adds[] = 'ADD COLUMN shipping_text VARCHAR(190) NULL AFTER old_total_price';
  if (!isset($cols['features'])) $adds[] = 'ADD COLUMN features TEXT NULL AFTER shipping_text';
  if (!isset($cols['image_main'])) $adds[] = 'ADD COLUMN image_main VARCHAR(255) NULL AFTER features';
  if (!isset($cols['sort'])) $adds[] = 'ADD COLUMN sort INT NOT NULL DEFAULT 0 AFTER image_main';
  if (!isset($cols['updated_at'])) $adds[] = 'ADD COLUMN updated_at DATETIME NOT NULL AFTER sort';
  if ($adds){
    $sql = 'ALTER TABLE plans ' . implode(', ', $adds);
    $pdo->exec($sql);
  }
}

// Current currency context
$code = get_setting('currency_code', 'USD');
$symbol = get_setting('currency_symbol', '$');

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $err = 'Invalid CSRF token';
    } else {
        // Make sure schema is ready
        ensure_plans_schema($pdo);
        // Update each plan
        $ids = $_POST['id'] ?? [];
        try{
          foreach ($ids as $i => $id) {
              $id = (int)$id;
              $sku = trim($_POST['sku'][$i] ?? '');
              $title = trim($_POST['title'][$i] ?? '');
              $subtitle = trim($_POST['subtitle'][$i] ?? '');
              $bottles = max(1, (int)($_POST['bottles'][$i] ?? 1));
              $total_price = (float)($_POST['total_price'][$i] ?? 0);
              $old_total_price = isset($_POST['old_total_price'][$i]) && $_POST['old_total_price'][$i] !== '' ? (float)$_POST['old_total_price'][$i] : null;
              $shipping_text = trim($_POST['shipping_text'][$i] ?? '');
              $features = trim($_POST['features'][$i] ?? '');
              $image_main = trim($_POST['image_main'][$i] ?? '');

              // Handle file upload for this index
              if (!empty($_FILES['image_file']['name'][$i]) && isset($_FILES['image_file']['error'][$i]) && $_FILES['image_file']['error'][$i] === UPLOAD_ERR_OK) {
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
        } catch (Throwable $ex) {
          // Attempt schema fix and report error
          try { ensure_plans_schema($pdo); } catch(Throwable $e2) {}
          $err = 'Failed to save plans. Please reload the page and try again.';
        }
    }
}

// Ensure plans table exists; attempt query and auto-create/seed on failure
try {
  $rows = $pdo->query('SELECT * FROM plans ORDER BY sort ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  // Create table and seed defaults, then retry
  $pdo->exec("CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(10) NOT NULL UNIQUE,
    title VARCHAR(190) NOT NULL,
    subtitle VARCHAR(190) NULL,
    bottles INT NOT NULL DEFAULT 1,
    total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    old_total_price DECIMAL(12,2) NULL,
    shipping_text VARCHAR(190) NULL,
    features TEXT NULL,
    image_main VARCHAR(255) NULL,
    sort INT NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
  $seedNow = date('Y-m-d H:i:s');
  $stmt = $pdo->prepare('INSERT IGNORE INTO plans (sku, title, subtitle, bottles, total_price, old_total_price, shipping_text, features, image_main, sort, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
  $stmt->execute(['EPK06','Best Value','180 Days, 6 Bottles',6,294,1074,'FREE US SHIPPING',"YOU SAVE $780!\n2 FREE E-BOOKS!\nBIGGEST DISCOUNT\n60-DAYS GUARANTEE",'/images/img-PRODx6.png',10,$seedNow]);
  $stmt->execute(['EPK03','Most Popular','90 Days, 3 Bottles',3,177,537,'FREE US SHIPPING',"YOU SAVE $360!\n2 FREE E-BOOKS!\n60-DAYS GUARANTEE",'/images/img-PRODx3.png',20,$seedNow]);
  $stmt->execute(['EPK02','Try Two','60 Days, 2 Bottles',2,138,358,'SHIPPING',"YOU SAVE $220!",'/images/img-PRODx2.png',30,$seedNow]);
  $rows = $pdo->query('SELECT * FROM plans ORDER BY sort ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HONR Admin • Plans</title>
  <link rel="stylesheet" href="../css/theme-overrides.css">
  <link rel="stylesheet" href="../css/admin-ui.css">
  <style>
  </style>
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <h1>Plans (currency: <?=e($code)?>, symbol: <?=e($symbol)?>)</h1>
      <div class="nav">
        <button class="btn" id="themeToggle" type="button">Light mode</button>
        <a class="btn" href="settings.php">Settings</a>
        <a class="btn" href="dashboard.php">Dashboard</a>
        <a class="btn" href="logout.php">Logout (<?=e($_SESSION['admin_name'] ?? '')?>)</a>
      </div>
    </header>

    <?php if ($msg): ?><div class="msg ok"><?=e($msg)?></div><?php endif; ?>
    <?php if ($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>

    <div class="card">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <div class="table-wrap">
        <table class="table">
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
        </div>
        <div style="margin-top:12px">
          <button class="btn btn-primary" type="submit">Save Plans</button>
        </div>
      </form>
    </div>
  </div>
  <script src="../js/theme-toggle.js"></script>
</body>
</html>
