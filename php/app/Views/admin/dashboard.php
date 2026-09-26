<?php
/** @var array $recentPosts */
?>
<h1>Dashboard</h1>
<div class="toolbar" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;width:100%">
  <div class="admin-table" style="padding:18px;border:1px solid var(--border);background:#fff"><div class="hint">Posts</div><div style="font-size:28px;font-weight:700"><?= $publishedPostCount ?>/<?= $postCount ?></div><div class="hint">published / total</div></div>
  <div class="admin-table" style="padding:18px;border:1px solid var(--border);background:#fff"><div class="hint">Projects</div><div style="font-size:28px;font-weight:700"><?= $publishedProjectCount ?>/<?= $projectCount ?></div><div class="hint">published / total</div></div>
  <div class="admin-table" style="padding:18px;border:1px solid var(--border);background:#fff"><div class="hint">Media</div><div style="font-size:28px;font-weight:700"><?= $mediaCount ?></div><div class="hint">assets</div></div>
  <div class="admin-table" style="padding:18px;border:1px solid var(--border);background:#fff">
    <a class="button" href="<?= base_url('admin/posts/new') ?>" style="width:100%;justify-content:center;margin-bottom:8px">New post</a>
    <a class="button secondary" href="<?= base_url('admin/projects/new') ?>" style="width:100%;justify-content:center">New project</a>
  </div>
</div>

<h2 style="margin-top:32px;font-size:16px">Recent posts</h2>
<table class="admin-table">
  <thead><tr><th>Title</th><th>Status</th><th>Updated</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($recentPosts as $post): ?>
      <tr>
        <td><?= e($post['title']) ?></td>
        <td><span class="badge <?= $post['status'] ?>"><?= e($post['status']) ?></span></td>
        <td><?= e(date('M j, Y', strtotime($post['updated_at']))) ?></td>
        <td><a href="<?= base_url('admin/posts/' . $post['id'] . '/edit') ?>">Edit</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$recentPosts): ?><tr><td colspan="4" class="hint">No posts yet.</td></tr><?php endif; ?>
  </tbody>
</table>
