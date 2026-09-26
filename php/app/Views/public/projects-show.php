<?php
/** @var array $project */
/** @var bool $isPreview */
use App\Models\Media;

$cover = $project['cover_media_id'] ? (new Media())->find((int) $project['cover_media_id']) : null;
$lifecycleLabels = ['idea' => 'Idea', 'in-progress' => 'In progress', 'completed' => 'Completed', 'archived' => 'Archived'];
?>
<main class="route-main detail-main">
  <?php if ($isPreview): ?>
    <div class="preview-banner" role="status">
      <span>Draft preview &mdash; only you can see this. Visitors still see the published version.</span>
      <a href="<?= base_url('preview/exit?to=' . urlencode('/projects/' . $project['slug'])) ?>">Exit preview</a>
    </div>
  <?php endif; ?>
  <header class="detail-header motion-in">
    <div class="detail-meta">
      <span><?= e($lifecycleLabels[$project['lifecycle_status']] ?? $project['lifecycle_status']) ?></span>
    </div>
    <h1 class="detail-title"><?= e($project['title']) ?></h1>
    <p class="detail-excerpt"><?= e($project['short_description']) ?></p>
    <?php if (!empty($project['technologies'])): ?>
      <ul class="project-tech-list">
        <?php foreach ($project['technologies'] as $tech): ?><li class="card-tag"><?= e($tech) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <?php if (!empty($project['repository_url']) || !empty($project['live_url'])): ?>
      <div class="project-links">
        <?php if (!empty($project['repository_url'])): ?>
          <a class="project-link-button" href="<?= e($project['repository_url']) ?>" target="_blank" rel="noopener noreferrer">Repository<span aria-hidden="true">&#8599;</span></a>
        <?php endif; ?>
        <?php if (!empty($project['live_url'])): ?>
          <a class="project-link-button" href="<?= e($project['live_url']) ?>" target="_blank" rel="noopener noreferrer">Live demo<span aria-hidden="true">&#8599;</span></a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </header>

  <?php if ($cover): ?>
    <div class="detail-cover">
      <img src="<?= e(Media::url($cover, 'large')) ?>" alt="<?= e($cover['alt']) ?>" width="<?= (int) $cover['width'] ?>" height="<?= (int) $cover['height'] ?>">
    </div>
  <?php endif; ?>

  <div class="detail-body"><?= $project['case_study_html'] ?></div>

  <?php if (!empty($project['problem_html'])): ?>
    <section><h2 class="section-heading">Problem</h2><div class="detail-body"><?= $project['problem_html'] ?></div></section>
  <?php endif; ?>
  <?php if (!empty($project['approach_html'])): ?>
    <section><h2 class="section-heading">Approach</h2><div class="detail-body"><?= $project['approach_html'] ?></div></section>
  <?php endif; ?>
  <?php if (!empty($project['results_html'])): ?>
    <section><h2 class="section-heading">Results</h2><div class="detail-body"><?= $project['results_html'] ?></div></section>
  <?php endif; ?>

  <?php if (!empty($project['gallery'])): ?>
    <section>
      <h2 class="section-heading">Gallery</h2>
      <div class="gallery-grid">
        <?php foreach ($project['gallery'] as $item): ?>
          <figure>
            <div class="gallery-item">
              <img src="<?= e(base_url('media/' . rawurlencode($item['filename']))) ?>" alt="<?= e($item['media_alt']) ?>" width="<?= (int) $item['media_width'] ?>" height="<?= (int) $item['media_height'] ?>">
            </div>
            <?php if (!empty($item['caption'])): ?><figcaption class="gallery-caption"><?= e($item['caption']) ?></figcaption><?php endif; ?>
          </figure>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>
