<?php
/** @var array $user */
?>
<h1>Account</h1>
<form method="post" action="<?= base_url('admin/account') ?>" class="form-grid">
  <?= csrf_field() ?>
  <div class="form-row">
    <label for="display_name">Display name</label>
    <input type="text" id="display_name" name="display_name" value="<?= e($user['display_name']) ?>">
  </div>
  <div class="form-row">
    <label>Email</label>
    <input type="text" value="<?= e($user['email']) ?>" disabled>
    <p class="hint">Email cannot be changed here.</p>
  </div>
  <hr style="border:none;border-top:1px solid var(--border);width:100%">
  <div class="form-row">
    <label for="current_password">Current password</label>
    <input type="password" id="current_password" name="current_password">
    <p class="hint">Only required if setting a new password below.</p>
  </div>
  <div class="form-row">
    <label for="new_password">New password</label>
    <input type="password" id="new_password" name="new_password" minlength="8">
  </div>
  <button type="submit" class="button">Save changes</button>
</form>
