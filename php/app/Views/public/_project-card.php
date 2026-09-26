<?php
/** @var array $project */
use App\Models\Media;

$cover = $project['cover_media_id'] ? (new Media())->find((int) $project['cover_media_id']) : null;
$lifecycleLabels = ['idea' => 'Idea', 'in-progress' => 'In progress', 'completed' => 'Completed', 'archived' => 'Archived'];
?>
<a href="<?= base_url('projects/' . $project['slug']) ?>" class="card">
  <?php if ($cover): ?>
    <div class="card-cover">
      <img src="<?= e(Media::url($cover, 'medium')) ?>" alt="<?= e($cover['alt']) ?>" width="<?= (int) $cover['width'] ?>" height="<?= (int) $cover['height'] ?>" loading="lazy">
    </div>
  <?php endif; ?>
  <div class="card-body">
    <span class="card-status"><?= e($lifecycleLabels[$project['lifecycle_status']] ?? $project['lifecycle_status']) ?></span>
    <h3 class="card-title"><?= e($project['title']) ?></h3>
    <p class="card-excerpt"><?= e($project['short_description']) ?></p>
    <?php if (!empty($project['technologies'])): ?>
      <div class="card-tags">
        <?php foreach ($project['technologies'] as $tech): ?>
          <span class="card-tag"><?= e($tech) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</a>
