<?php
/** @var array|null $post */
/** @var array $categories */
/** @var array $tags */
/** @var array $selectedCategoryIds */
/** @var array $selectedTagIds */
/** @var array|null $coverMedia */
/** @var array|null $seoMedia */
use App\Models\Media;

$isEdit = $post !== null;
$action = $isEdit ? base_url('admin/posts/' . $post['id'] . '/edit') : base_url('admin/posts/new');
$editorPartial = __DIR__ . '/_richtext-editor.php';
?>
<div class="toolbar">
  <h1><?= $isEdit ? 'Edit post' : 'New post' ?></h1>
  <?php if ($isEdit): ?>
    <div style="display:flex;gap:8px">
      <a href="<?= base_url('admin/posts/' . $post['id'] . '/preview-link') ?>" class="button secondary" target="_blank" rel="noopener">Preview</a>
      <?php if ($post['status'] === 'published' && empty($post['pending_draft_json'])): ?>
        <form method="post" action="<?= base_url('admin/posts/' . $post['id'] . '/unpublish') ?>" onsubmit="return confirm('Unpublish this post? It will no longer be visible to visitors.')">
          <?= csrf_field() ?>
          <button type="submit" class="button secondary">Unpublish</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php if ($isEdit && !empty($post['pending_draft_json'])): ?>
  <div class="flash" style="background:#fff4e0;color:#a15c00;border:1px solid #f0d9a8">
    This post has unpublished edits staged as a draft. Visitors still see the previously published version until you click Publish below.
  </div>
<?php endif; ?>

<form method="post" action="<?= $action ?>" class="form-grid" style="max-width:820px">
  <?= csrf_field() ?>

  <div class="form-row">
    <label for="title">Title</label>
    <input type="text" id="title" name="title" maxlength="160" required value="<?= e($post['title'] ?? '') ?>">
  </div>

  <div class="form-row">
    <label for="slug">Slug</label>
    <input type="text" id="slug" name="slug" maxlength="120" value="<?= e($post['slug'] ?? '') ?>" placeholder="auto-generated from title if left blank">
  </div>

  <div class="form-row">
    <label for="excerpt">Excerpt</label>
    <textarea id="excerpt" name="excerpt" maxlength="320" required><?= e($post['excerpt'] ?? '') ?></textarea>
  </div>

  <?php
  $name = 'content_html';
  $value = $post['content_html'] ?? '';
  $label = 'Content';
  require $editorPartial;
  ?>

  <div class="form-row">
    <label>Cover image</label>
    <div id="cover_media_id_preview">
      <?php if ($coverMedia): ?><img src="<?= e(Media::url($coverMedia, 'thumbnail')) ?>" alt="" style="max-width:120px;border-radius:4px"><?php endif; ?>
    </div>
    <input type="hidden" id="cover_media_id" name="cover_media_id" value="<?= e((string) ($post['cover_media_id'] ?? '')) ?>">
    <button type="button" class="button secondary small" data-media-picker-for="cover_media_id" style="width:fit-content">Choose image</button>
  </div>

  <div class="form-row">
    <label for="author_name">Author name</label>
    <input type="text" id="author_name" name="author_name" maxlength="80" value="<?= e($post['author_name'] ?? 'Hasan') ?>">
  </div>

  <?php if ($categories): ?>
  <div class="form-row">
    <label>Categories</label>
    <div class="checkbox-grid">
      <?php foreach ($categories as $category): ?>
        <label><input type="checkbox" name="categories[]" value="<?= $category['id'] ?>" <?= in_array($category['id'], $selectedCategoryIds, true) ? 'checked' : '' ?>><?= e($category['name']) ?></label>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($tags): ?>
  <div class="form-row">
    <label>Tags (max 12)</label>
    <div class="checkbox-grid">
      <?php foreach ($tags as $tag): ?>
        <label><input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" <?= in_array($tag['id'], $selectedTagIds, true) ? 'checked' : '' ?>><?= e($tag['name']) ?></label>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="form-row inline">
    <input type="checkbox" id="featured" name="featured" value="1" <?= !empty($post['featured']) ? 'checked' : '' ?>>
    <label for="featured" style="font-weight:400">Featured</label>
  </div>

  <h2 style="font-size:15px;margin:8px 0 0">SEO (optional)</h2>
  <div class="form-row">
    <label for="seo_title">SEO title (max 70)</label>
    <input type="text" id="seo_title" name="seo_title" maxlength="70" value="<?= e($post['seo_title'] ?? '') ?>">
  </div>
  <div class="form-row">
    <label for="seo_description">SEO description (max 170)</label>
    <textarea id="seo_description" name="seo_description" maxlength="170"><?= e($post['seo_description'] ?? '') ?></textarea>
  </div>
  <div class="form-row">
    <label>Share image</label>
    <div id="seo_share_media_id_preview">
      <?php if ($seoMedia): ?><img src="<?= e(Media::url($seoMedia, 'thumbnail')) ?>" alt="" style="max-width:120px;border-radius:4px"><?php endif; ?>
    </div>
    <input type="hidden" id="seo_share_media_id" name="seo_share_media_id" value="<?= e((string) ($post['seo_share_media_id'] ?? '')) ?>">
    <button type="button" class="button secondary small" data-media-picker-for="seo_share_media_id" style="width:fit-content">Choose image</button>
  </div>

  <div style="display:flex;gap:10px;margin-top:8px">
    <button type="submit" name="intended_status" value="draft" class="button secondary">Save draft</button>
    <button type="submit" name="intended_status" value="published" class="button">Publish</button>
  </div>
</form>
