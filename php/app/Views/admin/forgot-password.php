<?php
/** @var bool $sent */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot password — Hasan Journal Admin</title>
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
</head>
<body class="admin">
<div class="login-wrap">
  <h1>Reset password</h1>
  <?php if ($sent): ?>
    <p>If that email address has an account, a reset link has been sent to it.</p>
    <p class="hint"><a href="<?= base_url('admin/login') ?>">Back to login</a></p>
  <?php else: ?>
    <form method="post" action="<?= base_url('admin/forgot-password') ?>" class="form-grid">
      <?= csrf_field() ?>
      <div class="form-row">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autofocus>
      </div>
      <button type="submit" class="button">Send reset link</button>
    </form>
    <p class="hint" style="margin-top:16px"><a href="<?= base_url('admin/login') ?>">Back to login</a></p>
  <?php endif; ?>
</div>
</body>
</html>
