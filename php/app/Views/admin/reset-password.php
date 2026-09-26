<?php
/** @var string $token */
/** @var bool $valid */
/** @var string|null $error */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset password — Hasan Journal Admin</title>
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
</head>
<body class="admin">
<div class="login-wrap">
  <h1>Set a new password</h1>
  <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
  <?php if (!$valid): ?>
    <p>This reset link is invalid or has expired.</p>
    <p class="hint"><a href="<?= base_url('admin/forgot-password') ?>">Request a new one</a></p>
  <?php else: ?>
    <form method="post" action="<?= base_url('admin/reset-password') ?>" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="form-row">
        <label for="password">New password</label>
        <input type="password" id="password" name="password" required minlength="8" autofocus>
      </div>
      <div class="form-row">
        <label for="password_confirm">Confirm password</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
      </div>
      <button type="submit" class="button">Update password</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
