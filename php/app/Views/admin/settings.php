<?php
/** @var array $settings */
$socialLinks = $settings['social_links'] ?? [];
if (count($socialLinks) < 3) {
    $socialLinks = array_pad($socialLinks, 3, ['label' => '', 'url' => '']);
}
?>
<h1>Site Settings</h1>
<form method="post" action="<?= base_url('admin/settings') ?>" class="form-grid" style="max-width:720px">
  <?= csrf_field() ?>

  <div class="form-row">
    <label for="site_title">Site title (max 100)</label>
    <input type="text" id="site_title" name="site_title" maxlength="100" required value="<?= e($settings['site_title']) ?>">
  </div>
  <div class="form-row">
    <label for="short_description">Short description (max 300)</label>
    <textarea id="short_description" name="short_description" maxlength="300" required><?= e($settings['short_description']) ?></textarea>
  </div>
  <div class="form-row">
    <label for="author_display_name">Author display name</label>
    <input type="text" id="author_display_name" name="author_display_name" maxlength="80" value="<?= e($settings['author_display_name']) ?>">
  </div>
  <div class="form-row">
    <label for="hero_greeting">Hero greeting (max 80)</label>
    <input type="text" id="hero_greeting" name="hero_greeting" maxlength="80" required value="<?= e($settings['hero_greeting']) ?>">
  </div>
  <div class="form-row">
    <label for="intro_heading">Intro heading (max 120)</label>
    <input type="text" id="intro_heading" name="intro_heading" maxlength="120" required value="<?= e($settings['intro_heading']) ?>">
  </div>
  <div class="form-row">
    <label for="intro_body">Intro body (max 400)</label>
    <textarea id="intro_body" name="intro_body" maxlength="400" required><?= e($settings['intro_body']) ?></textarea>
  </div>
  <div class="form-row">
    <label for="interests">Interests (comma-separated, max 5)</label>
    <input type="text" id="interests" name="interests" value="<?= e(implode(', ', $settings['interests'] ?? [])) ?>">
  </div>
  <div class="form-row">
    <label for="copyright_name">Copyright name</label>
    <input type="text" id="copyright_name" name="copyright_name" maxlength="80" value="<?= e($settings['copyright_name']) ?>">
  </div>

  <div class="form-row">
    <label>Social links (https only, max 6)</label>
    <?php foreach ($socialLinks as $link): ?>
      <div style="display:flex;gap:8px;margin-bottom:6px">
        <input type="text" name="social_label[]" placeholder="Label" value="<?= e($link['label'] ?? '') ?>" style="flex:1">
        <input type="url" name="social_url[]" placeholder="https://..." value="<?= e($link['url'] ?? '') ?>" style="flex:2">
      </div>
    <?php endforeach; ?>
  </div>

  <h2 style="font-size:15px;margin:8px 0 0">Navigation labels</h2>
  <div class="form-row">
    <label for="nav_home_label">Home label</label>
    <input type="text" id="nav_home_label" name="nav_home_label" maxlength="30" value="<?= e($settings['nav_home_label']) ?>">
  </div>
  <div class="form-row">
    <label for="nav_blog_label">Blog label</label>
    <input type="text" id="nav_blog_label" name="nav_blog_label" maxlength="30" value="<?= e($settings['nav_blog_label']) ?>">
  </div>
  <div class="form-row">
    <label for="nav_projects_label">Projects label</label>
    <input type="text" id="nav_projects_label" name="nav_projects_label" maxlength="30" value="<?= e($settings['nav_projects_label']) ?>">
  </div>

  <h2 style="font-size:15px;margin:8px 0 0">Default SEO</h2>
  <div class="form-row">
    <label for="default_seo_title">Default SEO title (max 70)</label>
    <input type="text" id="default_seo_title" name="default_seo_title" maxlength="70" required value="<?= e($settings['default_seo_title']) ?>">
  </div>
  <div class="form-row">
    <label for="default_seo_description">Default SEO description (max 170)</label>
    <textarea id="default_seo_description" name="default_seo_description" maxlength="170" required><?= e($settings['default_seo_description']) ?></textarea>
  </div>

  <button type="submit" class="button">Save settings</button>
</form>
