<?php
/** @var array $posts */
?>
<div class="toolbar">
  <h1>Posts</h1>
  <a href="<?= base_url('admin/posts/new') ?>" class="button">New post</a>
</div>
<table class="admin-table">
  <thead><tr><th>Title</th><th>Status</th><th>Featured</th><th>Updated</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($posts as $post): ?>
      <?php $hasPending = !empty($post['pending_draft_json']); ?>
      <tr>
        <td><?= e($post['title']) ?></td>
        <td>
          <span class="badge <?= $post['status'] ?>"><?= e($post['status']) ?></span>
          <?php if ($hasPending): ?><span class="badge draft">pending edits</span><?php endif; ?>
        </td>
        <td><?= $post['featured'] ? 'Yes' : '' ?></td>
        <td><?= e(date('M j, Y', strtotime($post['updated_at']))) ?></td>
        <td style="white-space:nowrap;display:flex;gap:8px">
          <a href="<?= base_url('admin/posts/' . $post['id'] . '/edit') ?>">Edit</a>
          <a href="<?= base_url('admin/posts/' . $post['id'] . '/versions') ?>">History</a>
          <form method="post" action="<?= base_url('admin/posts/' . $post['id'] . '/delete') ?>" onsubmit="return confirm('Delete this post permanently?')">
            <?= csrf_field() ?>
            <button type="submit" class="button small danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$posts): ?><tr><td colspan="5" class="hint">No posts yet.</td></tr><?php endif; ?>
  </tbody>
</table>
