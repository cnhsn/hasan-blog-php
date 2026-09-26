<?php
/** @var array $items */
use App\Models\Media;
?>
<h1>Media</h1>

<h2 style="font-size:16px">Upload</h2>
<form method="post" action="<?= base_url('admin/media/upload') ?>" enctype="multipart/form-data" class="form-grid" style="max-width:480px;margin-bottom:32px">
  <?= csrf_field() ?>
  <div class="form-row">
    <label for="file">File (JPEG, PNG or WebP, max 10 MiB)</label>
    <input type="file" id="file" name="file" accept="image/jpeg,image/png,image/webp" required>
  </div>
  <div class="form-row">
    <label for="alt">Alt text</label>
    <input type="text" id="alt" name="alt" maxlength="200" required>
  </div>
  <div class="form-row">
    <label for="caption">Caption (optional)</label>
    <input type="text" id="caption" name="caption" maxlength="300">
  </div>
  <div class="form-row inline">
    <input type="checkbox" id="visibility" name="visibility" value="public">
    <label for="visibility" style="font-weight:400">Make public immediately</label>
  </div>
  <button type="submit" class="button">Upload</button>
</form>

<h2 style="font-size:16px">Library</h2>
<div class="media-grid">
  <?php foreach ($items as $item): ?>
    <div class="media-item">
      <img src="<?= e(Media::url($item, 'thumbnail')) ?>" alt="<?= e($item['alt']) ?>">
      <div class="media-body">
        <p><?= e($item['original_filename']) ?> &middot; <?= (int) $item['width'] ?>&times;<?= (int) $item['height'] ?></p>
        <span class="badge <?= $item['visibility'] ?>"><?= e($item['visibility']) ?></span>
        <form method="post" action="<?= base_url('admin/media/' . $item['id'] . '/visibility') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="visibility" value="<?= $item['visibility'] === 'public' ? 'private' : 'public' ?>">
          <button type="submit" class="button small secondary" style="width:100%;margin-top:4px">
            Make <?= $item['visibility'] === 'public' ? 'private' : 'public' ?>
          </button>
        </form>
        <form method="post" action="<?= base_url('admin/media/' . $item['id'] . '/delete') ?>" onsubmit="return confirm('Delete this file permanently?')">
          <?= csrf_field() ?>
          <button type="submit" class="button small danger" style="width:100%;margin-top:4px">Delete</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$items): ?><p class="hint">No media uploaded yet.</p><?php endif; ?>
</div>
