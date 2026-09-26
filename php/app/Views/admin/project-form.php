<?php
/** @var array|null $project */
/** @var array|null $coverMedia */
/** @var array|null $seoMedia */
use App\Models\Media;

$isEdit = $project !== null;
$action = $isEdit ? base_url('admin/projects/' . $project['id'] . '/edit') : base_url('admin/projects/new');
$editorPartial = __DIR__ . '/_richtext-editor.php';
$technologies = $project['technologies'] ?? [];
?>
<div class="toolbar">
  <h1><?= $isEdit ? 'Edit project' : 'New project' ?></h1>
  <?php if ($isEdit): ?>
    <div style="display:flex;gap:8px">
      <a href="<?= base_url('admin/projects/' . $project['id'] . '/preview-link') ?>" class="button secondary" target="_blank" rel="noopener">Preview</a>
      <?php if ($project['status'] === 'published' && empty($project['pending_draft_json'])): ?>
        <form method="post" action="<?= base_url('admin/projects/' . $project['id'] . '/unpublish') ?>" onsubmit="return confirm('Unpublish this project?')">
          <?= csrf_field() ?>
          <button type="submit" class="button secondary">Unpublish</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php if ($isEdit && !empty($project['pending_draft_json'])): ?>
  <div class="flash" style="background:#fff4e0;color:#a15c00;border:1px solid #f0d9a8">
    This project has unpublished edits staged as a draft. Visitors still see the previously published version until you click Publish below.
  </div>
<?php endif; ?>

<form method="post" action="<?= $action ?>" class="form-grid" style="max-width:820px">
  <?= csrf_field() ?>

  <div class="form-row">
    <label for="title">Title</label>
    <input type="text" id="title" name="title" maxlength="160" required value="<?= e($project['title'] ?? '') ?>">
  </div>
  <div class="form-row">
    <label for="slug">Slug</label>
    <input type="text" id="slug" name="slug" maxlength="120" value="<?= e($project['slug'] ?? '') ?>" placeholder="auto-generated from title if left blank">
  </div>
  <div class="form-row">
    <label for="short_description">Short description</label>
    <textarea id="short_description" name="short_description" maxlength="320" required><?= e($project['short_description'] ?? '') ?></textarea>
  </div>

  <?php $name = 'case_study_html'; $value = $project['case_study_html'] ?? ''; $label = 'Case study'; require $editorPartial; ?>
  <?php $name = 'problem_html'; $value = $project['problem_html'] ?? ''; $label = 'Problem (optional)'; require $editorPartial; ?>
  <?php $name = 'approach_html'; $value = $project['approach_html'] ?? ''; $label = 'Approach (optional)'; require $editorPartial; ?>
  <?php $name = 'results_html'; $value = $project['results_html'] ?? ''; $label = 'Results (optional — avoid fabricated outcome metrics)'; require $editorPartial; ?>

  <div class="form-row">
    <label>Cover image</label>
    <div id="cover_media_id_preview">
      <?php if ($coverMedia): ?><img src="<?= e(Media::url($coverMedia, 'thumbnail')) ?>" alt="" style="max-width:120px;border-radius:4px"><?php endif; ?>
    </div>
    <input type="hidden" id="cover_media_id" name="cover_media_id" value="<?= e((string) ($project['cover_media_id'] ?? '')) ?>">
    <button type="button" class="button secondary small" data-media-picker-for="cover_media_id" style="width:fit-content">Choose image</button>
  </div>

  <div class="form-row">
    <label>Gallery (max 12)</label>
    <div id="gallery-list">
      <?php foreach (($project['gallery'] ?? []) as $item): ?>
        <div class="gallery-editor-item">
          <img src="<?= e(base_url('media/' . rawurlencode($item['filename']))) ?>" alt="">
          <input type="hidden" name="gallery_media_ids[]" value="<?= (int) $item['media_id'] ?>">
          <input type="text" name="gallery_captions[<?= (int) $item['media_id'] ?>]" value="<?= e($item['caption'] ?? '') ?>" placeholder="Caption (optional)" style="flex:1">
          <button type="button" class="button small danger gallery-remove">Remove</button>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" id="gallery-add-button" class="button secondary small" style="width:fit-content">Add image to gallery</button>
  </div>

  <div class="form-row">
    <label for="technologies">Technologies (comma-separated, max 12)</label>
    <input type="text" id="technologies" name="technologies" value="<?= e(implode(', ', $technologies)) ?>" placeholder="TypeScript, PostgreSQL, Docker">
  </div>

  <div class="form-row">
    <label for="repository_url">Repository URL (optional)</label>
    <input type="url" id="repository_url" name="repository_url" value="<?= e($project['repository_url'] ?? '') ?>">
  </div>
  <div class="form-row">
    <label for="live_url">Live URL (optional)</label>
    <input type="url" id="live_url" name="live_url" value="<?= e($project['live_url'] ?? '') ?>">
  </div>

  <div class="form-row">
    <label for="lifecycle_status">Lifecycle status</label>
    <select id="lifecycle_status" name="lifecycle_status">
      <?php foreach (['idea' => 'Idea', 'in-progress' => 'In progress', 'completed' => 'Completed', 'archived' => 'Archived'] as $value => $labelText): ?>
        <option value="<?= $value ?>" <?= ($project['lifecycle_status'] ?? 'idea') === $value ? 'selected' : '' ?>><?= $labelText ?></option>
      <?php endforeach; ?>
    </select>
    <p class="hint">Independent of the draft/published status below.</p>
  </div>

  <div class="form-row">
    <label for="start_date">Start date</label>
    <input type="date" id="start_date" name="start_date" required value="<?= e($project['start_date'] ?? '') ?>">
  </div>
  <div class="form-row">
    <label for="end_date">End date (optional)</label>
    <input type="date" id="end_date" name="end_date" value="<?= e($project['end_date'] ?? '') ?>">
  </div>

  <div class="form-row inline">
    <input type="checkbox" id="featured" name="featured" value="1" <?= !empty($project['featured']) ? 'checked' : '' ?>>
    <label for="featured" style="font-weight:400">Featured</label>
  </div>

  <h2 style="font-size:15px;margin:8px 0 0">SEO (optional)</h2>
  <div class="form-row">
    <label for="seo_title">SEO title (max 70)</label>
    <input type="text" id="seo_title" name="seo_title" maxlength="70" value="<?= e($project['seo_title'] ?? '') ?>">
  </div>
  <div class="form-row">
    <label for="seo_description">SEO description (max 170)</label>
    <textarea id="seo_description" name="seo_description" maxlength="170"><?= e($project['seo_description'] ?? '') ?></textarea>
  </div>
  <div class="form-row">
    <label>Share image</label>
    <div id="seo_share_media_id_preview">
      <?php if ($seoMedia): ?><img src="<?= e(Media::url($seoMedia, 'thumbnail')) ?>" alt="" style="max-width:120px;border-radius:4px"><?php endif; ?>
    </div>
    <input type="hidden" id="seo_share_media_id" name="seo_share_media_id" value="<?= e((string) ($project['seo_share_media_id'] ?? '')) ?>">
    <button type="button" class="button secondary small" data-media-picker-for="seo_share_media_id" style="width:fit-content">Choose image</button>
  </div>

  <div style="display:flex;gap:10px;margin-top:8px">
    <button type="submit" name="intended_status" value="draft" class="button secondary">Save draft</button>
    <button type="submit" name="intended_status" value="published" class="button">Publish</button>
  </div>
</form>
