<?php
/** @var array $post */
/** @var array $related */
/** @var bool $isPreview */
use App\Models\Media;
use App\Security\ReadingTime;

$cover = $post['cover_media_id'] ? (new Media())->find((int) $post['cover_media_id']) : null;
$readingMinutes = ReadingTime::forHtml($post['content_html']);
$date = $post['published_at'] ? date('M j, Y', strtotime($post['published_at'])) : null;
?>
<main class="route-main detail-main">
  <?php if ($isPreview): ?>
    <div class="preview-banner" role="status">
      <span>Draft preview &mdash; only you can see this. Visitors still see the published version.</span>
      <a href="<?= base_url('preview/exit?to=' . urlencode('/blog/' . $post['slug'])) ?>">Exit preview</a>
    </div>
  <?php endif; ?>
  <header class="detail-header motion-in">
    <div class="detail-meta">
      <?php if ($date): ?><span><?= e($date) ?></span><?php endif; ?>
      <span><?= $readingMinutes ?> min read</span>
      <span><?= e($post['author_name']) ?></span>
    </div>
    <h1 class="detail-title"><?= e($post['title']) ?></h1>
    <p class="detail-excerpt"><?= e($post['excerpt']) ?></p>
    <?php if (!empty($post['categories'])): ?>
      <div class="detail-tags">
        <?php foreach ($post['categories'] as $category): ?><span class="card-tag"><?= e($category['name']) ?></span><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </header>

  <?php if ($cover): ?>
    <div class="detail-cover">
      <img src="<?= e(Media::url($cover, 'large')) ?>" alt="<?= e($cover['alt']) ?>" width="<?= (int) $cover['width'] ?>" height="<?= (int) $cover['height'] ?>">
    </div>
  <?php endif; ?>

  <div class="detail-body"><?= $post['content_html'] /* already sanitized on save */ ?></div>

  <?php if (!empty($post['tags'])): ?>
    <div class="detail-tags">
      <?php foreach ($post['tags'] as $tag): ?><span class="card-tag"><?= e($tag['name']) ?></span><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($related): ?>
    <section aria-labelledby="related-heading">
      <h2 id="related-heading" class="section-heading">Related posts</h2>
      <div class="card-grid">
        <?php foreach ($related as $relatedPost): ?>
          <?= render_partial('_post-card', ['post' => $relatedPost]) ?>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>
