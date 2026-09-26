<?php
/** @var string|null $error */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log in — Hasan Journal Admin</title>
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
</head>
<body class="admin">
<div class="login-wrap">
  <h1>hasan<span style="color:#152aee">.</span> admin</h1>
  <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" action="<?= base_url('admin/login') ?>" class="form-grid">
    <?= csrf_field() ?>
    <div class="form-row">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" required autofocus>
    </div>
    <div class="form-row">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>
    </div>
    <button type="submit" class="button">Log in</button>
  </form>
  <p class="hint" style="margin-top:16px"><a href="<?= base_url('admin/forgot-password') ?>">Forgot your password?</a></p>
</div>
</body>
</html>
