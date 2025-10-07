<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/util.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        $err = 'Invalid request.';
    } else {
        $u = trim($_POST['username'] ?? '');
        $p = trim($_POST['password'] ?? '');
        if (login($u, $p)) {
            header('Location: ' . APP_BASE . 'admin/dashboard.php');
            exit;
        }
        $err = 'Invalid username or password';
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HONR Admin • Login</title>
  <link rel="stylesheet" href="../css/theme-overrides.css">
  <style>
    body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial; background:#f6f7f8; color:#111;}
    .wrap{ max-width:420px; margin:60px auto; background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,.05); }
    header{ padding:16px 20px; border-bottom:1px solid #e5e7eb; font-weight:800; }
    .inner{ padding:18px 20px; }
    label{ display:block; font-size:13px; margin:10px 0 6px; }
    input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; }
    .btn{ display:inline-flex; align-items:center; justify-content:center; gap:10px; padding:10px 14px; border-radius:10px; border:1px solid transparent; cursor:pointer; font-weight:800; }
    .btn-primary{ background: var(--brand-primary); color: var(--brand-on-primary); border-color: var(--brand-primary); }
    .btn-primary:hover{ background: var(--brand-primary-dark); border-color: var(--brand-primary-dark); }
    .msg{ padding:10px 12px; border-radius:10px; margin-bottom:10px; }
    .msg.err{ background:#fef2f2; border:1px solid #ef4444; }
  </style>
</head>
<body>
  <div class="wrap">
    <header>HONR Admin • Login</header>
    <div class="inner">
      <?php if ($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
        <label>Username</label>
        <input name="username" autocomplete="username" required>
        <label>Password</label>
        <input name="password" type="password" autocomplete="current-password" required>
        <div style="margin-top:14px">
          <button class="btn btn-primary" type="submit">Login</button>
        </div>
      </form>
      <p style="margin-top:12px;font-size:12px;color:#6b7280">If no admin exists yet, go to <a href="setup.php">setup</a>.</p>
    </div>
  </div>
</body>
</html>
