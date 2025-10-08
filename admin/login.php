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
  <link rel="stylesheet" href="../css/admin-ui.css">
  <style>
    .wrap{ max-width:420px; margin:60px auto; }
    .inner{ padding:18px 20px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="topbar">
        <h1 class="m-0">HONR Admin • Login</h1>
      </div>
      <div class="inner">
        <?php if ($err): ?><div class="msg err"><?=e($err)?></div><?php endif; ?>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
          <label>Username</label>
          <input name="username" autocomplete="username" required>
          <label>Password</label>
          <input name="password" type="password" autocomplete="current-password" required>
          <div>
            <button class="btn btn-primary" type="submit">Login</button>
          </div>
        </form>
        <p style="margin-top:12px;font-size:12px;color:#6b7280">If no admin exists yet, go to <a href="setup.php">setup</a>.</p>
      </div>
    </div>
  </div>
</body>
</html>
