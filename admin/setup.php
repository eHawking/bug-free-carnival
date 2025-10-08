<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/util.php';

$pdo = db();
$errors = [];
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) { $errors[] = 'Invalid CSRF token'; }

    $adminUser = trim($_POST['username'] ?? '');
    $adminPass = trim($_POST['password'] ?? '');

    if (!$errors) {
        try {
            // Create tables
            $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(190) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(32) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                name VARCHAR(190) NULL,
                email VARCHAR(190) NULL,
                phone VARCHAR(50) NULL,
                address_line1 VARCHAR(255) NULL,
                city VARCHAR(120) NULL,
                zip VARCHAR(30) NULL,
                sku VARCHAR(20) NULL,
                product_name VARCHAR(190) NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0,
                payment_method VARCHAR(20) NOT NULL DEFAULT 'COD',
                status ENUM('pending','confirmed','shipped','delivered','canceled') NOT NULL DEFAULT 'pending',
                notes TEXT NULL,
                raw_json LONGTEXT NULL,
                ip VARCHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                INDEX idx_status (status),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Settings KV table
            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                k VARCHAR(64) NOT NULL PRIMARY KEY,
                v TEXT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Seed default currency settings if not present
            $now = date('Y-m-d H:i:s');
            $pdo->prepare('INSERT IGNORE INTO settings (k, v, updated_at) VALUES (?,?,?)')
                ->execute(['currency_code','USD',$now]);
            $pdo->prepare('INSERT IGNORE INTO settings (k, v, updated_at) VALUES (?,?,?)')
                ->execute(['currency_symbol','$',$now]);
            $pdo->prepare('INSERT IGNORE INTO settings (k, v, updated_at) VALUES (?,?,?)')
                ->execute(['currency_rate','1',$now]);

            // Seed pricing (base USD totals and old strike-through totals)
            $pdo->prepare('INSERT IGNORE INTO settings (k, v, updated_at) VALUES (?,?,?)')
                ->execute(['price_total_usd_EPK06','294',$now]);
            $pdo->prepare('INSERT IGNORE INTO settings (k, v, updated_at) VALUES (?,?,?)')
                ->execute(['price_total_usd_EPK03','177',$now]);
            $pdo->prepare('INSERT IGNORE INTO settings (k, v, updated_at) VALUES (?,?,?)')
                ->execute(['price_total_usd_EPK02','138',$now]);

            $pdo->prepare('INSERT IGNORE INTO settings (k, v, updated_at) VALUES (?,?,?)')
                ->execute(['price_old_total_usd_EPK06','1074',$now]);
            $pdo->prepare('INSERT IGNORE INTO settings (k, v, updated_at) VALUES (?,?,?)')
                ->execute(['price_old_total_usd_EPK03','537',$now]);
            $pdo->prepare('INSERT IGNORE INTO settings (k, v, updated_at) VALUES (?,?,?)')
                ->execute(['price_old_total_usd_EPK02','358',$now]);

            // Plans table: store pricing card content in selected currency (no USD conversion)
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

            // Seed initial plans if not existing
            $seedNow = date('Y-m-d H:i:s');
            // EPK06
            $stmt = $pdo->prepare('INSERT IGNORE INTO plans (sku, title, subtitle, bottles, total_price, old_total_price, shipping_text, features, image_main, sort, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute(['EPK06','Best Value','180 Days, 6 Bottles',6,294,1074,'FREE US SHIPPING',"YOU SAVE $780!\n2 FREE E-BOOKS!\nBIGGEST DISCOUNT\n60-DAYS GUARANTEE",'/images/img-PRODx6.png',10,$seedNow]);
            // EPK03
            $stmt->execute(['EPK03','Most Popular','90 Days, 3 Bottles',3,177,537,'FREE US SHIPPING',"YOU SAVE $360!\n2 FREE E-BOOKS!\n60-DAYS GUARANTEE",'/images/img-PRODx3.png',20,$seedNow]);
            // EPK02
            $stmt->execute(['EPK02','Try Two','60 Days, 2 Bottles',2,138,358,'SHIPPING',"YOU SAVE $220!",'/images/img-PRODx2.png',30,$seedNow]);

            // Seed admin user if provided and doesn't exist
            if ($adminUser !== '' && $adminPass !== '') {
                $stmt = $pdo->prepare('SELECT id FROM admin_users WHERE username = ?');
                $stmt->execute([$adminUser]);
                if (!$stmt->fetch()) {
                    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash, created_at) VALUES (?,?,?)');
                    $stmt->execute([$adminUser, $hash, date('Y-m-d H:i:s')]);
                }
            }
            $ok = 'Database initialized successfully.';
        } catch (Throwable $e) {
            $errors[] = 'Setup failed: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HONR Admin • Setup</title>
  <link rel="stylesheet" href="../css/theme-overrides.css">
  <style>
    body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial; background:#f6f7f8; color:#111;}
    .wrap{ max-width:720px; margin:40px auto; background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,.05); }
    header{ padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:800; }
    .inner{ padding:18px 20px; }
    label{ display:block; font-size:13px; margin:10px 0 6px; }
    input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; }
    .row{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .btn{ display:inline-flex; align-items:center; justify-content:center; gap:10px; padding:10px 14px; border-radius:10px; border:1px solid transparent; cursor:pointer; font-weight:800; }
    .btn-primary{ background: var(--brand-primary); color: var(--brand-on-primary); border-color: var(--brand-primary); }
    .btn-primary:hover{ background: var(--brand-primary-dark); border-color: var(--brand-primary-dark); }
    .msg{ padding:10px 12px; border-radius:10px; margin-bottom:10px; }
    .msg.ok{ background:#ecfdf5; border:1px solid #10b981; }
    .msg.err{ background:#fef2f2; border:1px solid #ef4444; }
  </style>
</head>
<body>
  <div class="wrap">
    <header>HONR Admin • Setup</header>
    <div class="inner">
      <?php if ($ok): ?><div class="msg ok"><?=e($ok)?></div><?php endif; ?>
      <?php foreach ($errors as $er): ?><div class="msg err"><?=e($er)?></div><?php endforeach; ?>

      <p>Update database credentials in <code>admin/config.php</code> first. Then submit to create tables and the first admin user.</p>

      <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <div class="row">
          <div>
            <label>Admin username</label>
            <input name="username" placeholder="admin" required>
          </div>
          <div>
            <label>Admin password</label>
            <input name="password" type="password" required>
          </div>
        </div>
        <div style="margin-top:14px">
          <button class="btn btn-primary" type="submit">Initialize</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
