<?php
/** @var int $page */
/** @var int $totalPages */
/** @var string $basePath */
/** @var string|null $category */

if ($totalPages <= 1) {
    return;
}

$buildHref = static function (int $p) use ($basePath, $category): string {
    $query = [];
    if (!empty($category)) {
        $query['category'] = $category;
    }
    if ($p > 1) {
        $query['page'] = $p;
    }
    $qs = http_build_query($query);
    return base_url($basePath) . ($qs !== '' ? '?' . $qs : '');
};
?>
<nav class="pagination" aria-label="Pagination">
  <?php if ($page > 1): ?>
    <a href="<?= e($buildHref($page - 1)) ?>" rel="prev">&lsaquo;</a>
  <?php else: ?>
    <span class="disabled">&lsaquo;</span>
  <?php endif; ?>
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a href="<?= e($buildHref($p)) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a>
  <?php endfor; ?>
  <?php if ($page < $totalPages): ?>
    <a href="<?= e($buildHref($page + 1)) ?>" rel="next">&rsaquo;</a>
  <?php else: ?>
    <span class="disabled">&rsaquo;</span>
  <?php endif; ?>
</nav>
