<?php
/** @var string $content */
/** @var string $pageTitle */
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$navItems = [
    ['href' => '/admin', 'label' => 'Dashboard', 'exact' => true],
    ['href' => '/admin/posts', 'label' => 'Posts'],
    ['href' => '/admin/projects', 'label' => 'Projects'],
    ['href' => '/admin/media', 'label' => 'Media'],
    ['href' => '/admin/categories', 'label' => 'Categories'],
    ['href' => '/admin/tags', 'label' => 'Tags'],
    ['href' => '/admin/redirects', 'label' => 'Redirects'],
    ['href' => '/admin/settings', 'label' => 'Site Settings'],
];
$flashSuccess = flash_get('success');
$flashError = flash_get('error');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Admin') ?> — Hasan Journal Admin</title>
<link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>">
<script src="<?= asset('assets/js/admin-editor.js') ?>" defer></script>
</head>
<body class="admin">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <a href="<?= base_url('/') ?>" class="admin-brand">hasan<span>.</span></a>
    <nav aria-label="Admin navigation">
      <?php foreach ($navItems as $item): ?>
        <?php $isActive = !empty($item['exact']) ? $currentPath === $item['href'] : str_starts_with($currentPath, $item['href']); ?>
        <a href="<?= base_url($item['href']) ?>" class="<?= $isActive ? 'active' : '' ?>"><?= e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar-footer">
      <a href="<?= base_url('admin/account') ?>">Account</a>
      <form method="post" action="<?= base_url('admin/logout') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="link-button">Log out</button>
      </form>
    </div>
  </aside>
  <main class="admin-main">
    <?php if ($flashSuccess): ?><div class="flash success"><?= e($flashSuccess) ?></div><?php endif; ?>
    <?php if ($flashError): ?><div class="flash error"><?= e($flashError) ?></div><?php endif; ?>
    <?= $content ?>
  </main>
</div>
<div id="media-picker-modal" class="media-picker-overlay" data-endpoint="<?= base_url('admin/media?format=json') ?>" hidden>
  <div class="media-picker-dialog">
    <h2>Choose an image <button type="button" class="media-picker-close">Close</button></h2>
    <div class="media-picker-grid"></div>
  </div>
</div>
</body>
</html>
