<?php
/** @var array $projects */
/** @var int $totalPages */
/** @var int $page */
?>
<main class="route-main collection-main">
  <div class="collection-heading motion-in">
    <div>
      <p class="eyebrow">02 / THE WORKBENCH</p>
      <h1>Things I <em>build.</em></h1>
    </div>
    <p class="collection-summary">A place for experiments, side projects, and ideas turned into something useful.</p>
  </div>

  <?php if (empty($projects)): ?>
    <section class="empty-state" aria-labelledby="empty-title">
      <span class="empty-count" aria-hidden="true">00</span>
      <h2 id="empty-title">No projects published yet.</h2>
      <p>The workbench is ready. Projects will appear here as they&rsquo;re added.</p>
      <a class="text-link" href="<?= base_url('blog') ?>">Visit the blog<span aria-hidden="true">&#8599;</span></a>
    </section>
  <?php else: ?>
    <div class="card-grid">
      <?php foreach ($projects as $project): ?>
        <?= render_partial('_project-card', ['project' => $project]) ?>
      <?php endforeach; ?>
    </div>
    <?= render_partial('_pagination', ['page' => $page, 'totalPages' => $totalPages, 'basePath' => 'projects', 'category' => null]) ?>
  <?php endif; ?>
</main>
