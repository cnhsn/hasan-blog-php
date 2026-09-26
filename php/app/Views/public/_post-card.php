<?php
/** @var array $post */
use App\Models\Media;
use App\Security\ReadingTime;

$cover = $post['cover_media_id'] ? (new Media())->find((int) $post['cover_media_id']) : null;
$readingMinutes = ReadingTime::forHtml($post['content_html']);
$date = $post['published_at'] ? date('M j, Y', strtotime($post['published_at'])) : null;
?>
<a href="<?= base_url('blog/' . $post['slug']) ?>" class="card">
  <?php if ($cover): ?>
    <div class="card-cover">
      <img src="<?= e(Media::url($cover, 'medium')) ?>" alt="<?= e($cover['alt']) ?>" width="<?= (int) $cover['width'] ?>" height="<?= (int) $cover['height'] ?>" loading="lazy">
    </div>
  <?php endif; ?>
  <div class="card-body">
    <div class="card-meta">
      <?php if ($date): ?><span><?= e($date) ?></span><?php endif; ?>
      <span><?= $readingMinutes ?> min read</span>
    </div>
    <h3 class="card-title"><?= e($post['title']) ?></h3>
    <p class="card-excerpt"><?= e($post['excerpt']) ?></p>
    <?php if (!empty($post['categories'])): ?>
      <div class="card-tags">
        <?php foreach ($post['categories'] as $category): ?>
          <span class="card-tag"><?= e($category['name']) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</a>
