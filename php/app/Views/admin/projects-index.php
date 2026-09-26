<?php
/** @var array $projects */
$lifecycleLabels = ['idea' => 'Idea', 'in-progress' => 'In progress', 'completed' => 'Completed', 'archived' => 'Archived'];
?>
<div class="toolbar">
  <h1>Projects</h1>
  <a href="<?= base_url('admin/projects/new') ?>" class="button">New project</a>
</div>
<table class="admin-table">
  <thead><tr><th>Title</th><th>Status</th><th>Lifecycle</th><th>Updated</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($projects as $project): ?>
      <?php $hasPending = !empty($project['pending_draft_json']); ?>
      <tr>
        <td><?= e($project['title']) ?></td>
        <td>
          <span class="badge <?= $project['status'] ?>"><?= e($project['status']) ?></span>
          <?php if ($hasPending): ?><span class="badge draft">pending edits</span><?php endif; ?>
        </td>
        <td><?= e($lifecycleLabels[$project['lifecycle_status']] ?? $project['lifecycle_status']) ?></td>
        <td><?= e(date('M j, Y', strtotime($project['updated_at']))) ?></td>
        <td style="white-space:nowrap;display:flex;gap:8px">
          <a href="<?= base_url('admin/projects/' . $project['id'] . '/edit') ?>">Edit</a>
          <a href="<?= base_url('admin/projects/' . $project['id'] . '/versions') ?>">History</a>
          <form method="post" action="<?= base_url('admin/projects/' . $project['id'] . '/delete') ?>" onsubmit="return confirm('Delete this project permanently?')">
            <?= csrf_field() ?>
            <button type="submit" class="button small danger">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$projects): ?><tr><td colspan="5" class="hint">No projects yet.</td></tr><?php endif; ?>
  </tbody>
</table>
