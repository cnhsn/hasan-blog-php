<?php
/** @var array $settings */
/** @var string $pageTitle */
/** @var string $metaDescription */
/** @var string $canonical */
/** @var string $content */

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$navItems = [
    ['href' => '/', 'label' => $settings['nav_home_label'] ?? 'Home'],
    ['href' => '/blog', 'label' => $settings['nav_blog_label'] ?? 'Blog'],
    ['href' => '/projects', 'label' => $settings['nav_projects_label'] ?? 'Projects'],
];
$socialLinks = $settings['social_links'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="theme-color" content="#152aee">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%23152aee'/%3E%3Ctext x='7' y='25' fill='white' font-family='Arial' font-size='26' font-weight='bold'%3Eh%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="<?= asset('assets/css/site.css') ?>">
<script src="<?= asset('assets/js/site.js') ?>" defer></script>
</head>
<body>
<div class="site-shell">
<header class="site-header">
  <a class="brand" href="<?= base_url('/') ?>" aria-label="Hasan home">hasan<span>.</span></a>
  <nav class="site-nav" aria-label="Main navigation">
    <?php foreach ($navItems as $item): ?>
      <?php $isActive = $item['href'] === '/' ? $currentPath === '/' : str_starts_with($currentPath, $item['href']); ?>
      <a href="<?= base_url($item['href']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
    <?php endforeach; ?>
  </nav>
</header>
<?= $content ?>
<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> <?= e($settings['copyright_name'] ?? 'Hasan') ?></p>
  <div class="footer-right">
    <span>Always a work in progress.</span>
    <?php foreach ($socialLinks as $link): ?>
      <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($link['label']) ?></a>
    <?php endforeach; ?>
  </div>
</footer>
</div>
</body>
</html>
