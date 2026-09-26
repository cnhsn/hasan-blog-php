<?php
/** @var array $posts */
/** @var int $totalPages */
/** @var int $page */
/** @var array $categories */
/** @var string|null $activeCategory */
?>
<main class="route-main collection-main">
  <div class="collection-heading motion-in">
    <div>
      <p class="eyebrow">01 / THE JOURNAL</p>
      <h1>Notes &amp; <em>ideas.</em></h1>
    </div>
    <p class="collection-summary">Thoughts on technology, the things I learn, and life between projects.</p>
  </div>

  <?php if ($categories): ?>
  <div class="filter-bar" role="group" aria-label="Filter by category">
    <a href="<?= base_url('blog') ?>" class="filter-chip"<?= !$activeCategory ? ' aria-current="true"' : '' ?>>All</a>
    <?php foreach ($categories as $category): ?>
      <a href="<?= base_url('blog?category=' . urlencode($category['slug'])) ?>" class="filter-chip"<?= $activeCategory === $category['slug'] ? ' aria-current="true"' : '' ?>><?= e($category['name']) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (empty($posts)): ?>
    <section class="empty-state" aria-labelledby="empty-title">
      <span class="empty-count" aria-hidden="true">00</span>
      <h2 id="empty-title">No posts published yet.</h2>
      <p>This journal is waiting for its first entry. Check back for new writing.</p>
      <a class="text-link" href="<?= base_url('projects') ?>">Explore projects<span aria-hidden="true">&#8599;</span></a>
    </section>
  <?php else: ?>
    <div class="card-grid">
      <?php foreach ($posts as $post): ?>
        <?= render_partial('_post-card', ['post' => $post]) ?>
      <?php endforeach; ?>
    </div>
    <?= render_partial('_pagination', ['page' => $page, 'totalPages' => $totalPages, 'basePath' => 'blog', 'category' => $activeCategory]) ?>
  <?php endif; ?>
</main>
