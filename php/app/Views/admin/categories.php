<?php
/** @var array $categories */
?>
<h1>Categories</h1>
<table class="admin-table" style="margin-bottom:24px">
  <thead><tr><th>Name</th><th>Slug</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($categories as $category): ?>
      <?php $formId = 'cat-form-' . $category['id']; ?>
      <tr>
        <td>
          <form method="post" action="<?= base_url('admin/categories/' . $category['id'] . '/update') ?>" id="<?= $formId ?>">
            <?= csrf_field() ?>
            <input type="text" name="name" value="<?= e($category['name']) ?>" maxlength="80">
          </form>
        </td>
        <td><input type="text" name="slug" form="<?= $formId ?>" value="<?= e($category['slug']) ?>" maxlength="120"></td>
        <td style="white-space:nowrap;display:flex;gap:6px">
          <button type="submit" form="<?= $formId ?>" class="button small">Save</button>
          <form method="post" action="<?= base_url('admin/categories/' . $category['id'] . '/delete') ?>" onsubmit="return confirm('Delete this category? Posts using it are not affected.')">
            <?= csrf_field() ?>
            <button type="submit" class="button small danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$categories): ?><tr><td colspan="3" class="hint">No categories yet.</td></tr><?php endif; ?>
  </tbody>
</table>

<h2 style="font-size:16px">Add category</h2>
<form method="post" action="<?= base_url('admin/categories') ?>" class="form-grid" style="max-width:480px">
  <?= csrf_field() ?>
  <div class="form-row">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" maxlength="80" required>
  </div>
  <div class="form-row">
    <label for="slug">Slug (optional, auto-generated from name)</label>
    <input type="text" id="slug" name="slug" maxlength="120">
  </div>
  <button type="submit" class="button">Add category</button>
</form>
