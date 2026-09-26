<?php
/** @var string $step */
/** @var string $csrf */
function ie(mixed $v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hasan Journal — Installation</title>
<style>
  :root { --cobalt: #152aee; --dark: #15161a; --gray: #686970; --border: #d4d5d9; }
  * { box-sizing: border-box; }
  body { font-family: -apple-system, Arial, sans-serif; background: #f3f4f4; color: var(--dark); margin: 0; padding: 40px 20px; }
  .wrap { max-width: 560px; margin: 0 auto; background: #fff; border: 1px solid var(--border); padding: 40px; }
  h1 { font-size: 24px; margin: 0 0 4px; }
  .subtitle { color: var(--gray); font-size: 14px; margin: 0 0 28px; }
  .steps { display: flex; gap: 8px; margin-bottom: 28px; font-family: monospace; font-size: 12px; color: var(--gray); }
  .steps span.active { color: var(--cobalt); font-weight: bold; }
  label { display: block; font-size: 13px; font-weight: 600; margin: 16px 0 6px; }
  input[type=text], input[type=email], input[type=password], input[type=number] {
    width: 100%; padding: 10px 12px; border: 1px solid var(--border); font-size: 14px; border-radius: 4px;
  }
  button { margin-top: 24px; background: var(--cobalt); color: #fff; border: none; padding: 12px 20px; font-size: 14px; border-radius: 4px; cursor: pointer; }
  button:hover { opacity: .9; }
  .error { background: #fee; border: 1px solid #f99; color: #900; padding: 12px 16px; border-radius: 4px; font-size: 14px; margin-bottom: 16px; }
  .check-list { list-style: none; padding: 0; margin: 0; }
  .check-list li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
  .ok { color: #0a7; font-weight: bold; }
  .fail { color: #c00; font-weight: bold; }
  .hint { font-size: 12px; color: var(--gray); margin-top: 4px; }
</style>
</head>
<body>
<div class="wrap">
  <h1>Hasan Journal</h1>
  <p class="subtitle">Installation wizard</p>
  <div class="steps">
    <span<?= $step === 'requirements' ? ' class="active"' : '' ?>>1. Requirements</span> &rarr;
    <span<?= $step === 'database' ? ' class="active"' : '' ?>>2. Database</span> &rarr;
    <span<?= $step === 'site' ? ' class="active"' : '' ?>>3. Site &amp; owner</span> &rarr;
    <span<?= $step === 'done' ? ' class="active"' : '' ?>>4. Done</span>
  </div>

  <?php if ($step === 'requirements'): ?>
    <?php /** @var array $checks *//** @var bool $allPass */ ?>
    <ul class="check-list">
      <?php foreach ($checks as $label => $pass): ?>
        <li><?= ie($label) ?> <span class="<?= $pass ? 'ok' : 'fail' ?>"><?= $pass ? 'OK' : 'MISSING' ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($allPass): ?>
      <form method="post" action="install.php">
        <input type="hidden" name="step" value="database">
        <input type="hidden" name="csrf" value="<?= ie($csrf) ?>">
        <button type="submit">Continue</button>
      </form>
    <?php else: ?>
      <p class="error">Fix the missing requirements above, then reload this page.</p>
    <?php endif; ?>

  <?php elseif ($step === 'database'): ?>
    <?php /** @var string|null $error *//** @var array $old */ ?>
    <?php if ($error): ?><div class="error"><?= ie($error) ?></div><?php endif; ?>
    <form method="post" action="install.php">
      <input type="hidden" name="step" value="database">
      <input type="hidden" name="csrf" value="<?= ie($csrf) ?>">
      <label for="db_host">Database host</label>
      <input type="text" id="db_host" name="db_host" value="<?= ie($old['db_host'] ?? 'localhost') ?>" required>
      <label for="db_port">Port</label>
      <input type="number" id="db_port" name="db_port" value="<?= ie($old['db_port'] ?? 3306) ?>" required>
      <label for="db_name">Database name</label>
      <input type="text" id="db_name" name="db_name" value="<?= ie($old['db_name'] ?? '') ?>" required>
      <p class="hint">Most shared hosts require you to create this database (and its user) in cPanel first.</p>
      <label for="db_user">Database user</label>
      <input type="text" id="db_user" name="db_user" value="<?= ie($old['db_user'] ?? '') ?>" required>
      <label for="db_pass">Database password</label>
      <input type="password" id="db_pass" name="db_pass">
      <button type="submit">Test connection &amp; create schema</button>
    </form>

  <?php elseif ($step === 'site'): ?>
    <?php /** @var string|null $error *//** @var array $old */ ?>
    <?php if ($error): ?><div class="error"><?= ie($error) ?></div><?php endif; ?>
    <form method="post" action="install.php">
      <input type="hidden" name="step" value="site">
      <input type="hidden" name="csrf" value="<?= ie($csrf) ?>">
      <label for="base_url">Site URL</label>
      <input type="text" id="base_url" name="base_url" placeholder="https://example.com" value="<?= ie($old['base_url'] ?? '') ?>" required>
      <p class="hint">No trailing slash. Must be https:// unless this is a local/loopback install.</p>
      <label for="display_name">Display name</label>
      <input type="text" id="display_name" name="display_name" value="<?= ie($old['display_name'] ?? 'Hasan') ?>">
      <label for="email">Owner email</label>
      <input type="email" id="email" name="email" value="<?= ie($old['email'] ?? '') ?>" required>
      <label for="password">Owner password</label>
      <input type="password" id="password" name="password" required minlength="8">
      <label for="password_confirm">Confirm password</label>
      <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
      <button type="submit">Create site</button>
    </form>

  <?php elseif ($step === 'done'): ?>
    <?php /** @var string $baseUrl */ ?>
    <p>Your site is ready.</p>
    <ul>
      <li><a href="<?= ie($baseUrl) ?>/">Visit the site</a></li>
      <li><a href="<?= ie($baseUrl) ?>/admin/login">Log in to the admin panel</a></li>
    </ul>
    <p class="hint">For your own safety, delete or rename <code>install.php</code> now — anyone who can reach the requirements/database steps before you finish could otherwise interfere with setup. This app already refuses to run the installer again once <code>app/config.php</code> exists, but removing the file is one less thing to think about.</p>
  <?php endif; ?>
</div>
</body>
</html>
