<?php
/** @var array $redirects */
?>
<h1>Redirects</h1>
<p class="hint" style="margin-bottom:16px">Slug renames on published posts/projects create these automatically. You can also add manual redirects here.</p>
<table class="admin-table" style="margin-bottom:24px">
  <thead><tr><th>Source path</th><th>Target path</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($redirects as $redirect): ?>
      <?php $formId = 'redirect-form-' . $redirect['id']; ?>
      <tr>
        <td>
          <form method="post" action="<?= base_url('admin/redirects/' . $redirect['id'] . '/update') ?>" id="<?= $formId ?>">
            <?= csrf_field() ?>
            <input type="text" name="source_path" value="<?= e($redirect['source_path']) ?>">
          </form>
        </td>
        <td><input type="text" name="target_path" form="<?= $formId ?>" value="<?= e($redirect['target_path']) ?>"></td>
        <td style="white-space:nowrap;display:flex;gap:6px">
          <button type="submit" form="<?= $formId ?>" class="button small">Save</button>
          <form method="post" action="<?= base_url('admin/redirects/' . $redirect['id'] . '/delete') ?>" onsubmit="return confirm('Delete this redirect?')">
            <?= csrf_field() ?>
            <button type="submit" class="button small danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$redirects): ?><tr><td colspan="3" class="hint">No redirects yet.</td></tr><?php endif; ?>
  </tbody>
</table>

<h2 style="font-size:16px">Add redirect</h2>
<form method="post" action="<?= base_url('admin/redirects') ?>" class="form-grid" style="max-width:480px">
  <?= csrf_field() ?>
  <div class="form-row">
    <label for="source_path">Source path</label>
    <input type="text" id="source_path" name="source_path" placeholder="/blog/old-slug" required>
  </div>
  <div class="form-row">
    <label for="target_path">Target path</label>
    <input type="text" id="target_path" name="target_path" placeholder="/blog/new-slug" required>
  </div>
  <button type="submit" class="button">Add redirect</button>
</form>
