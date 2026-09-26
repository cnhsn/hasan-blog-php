<?php
/** @var array $project */
/** @var array $versions */
?>
<div class="toolbar">
  <h1>Version history: <?= e($project['title']) ?></h1>
  <a href="<?= base_url('admin/projects/' . $project['id'] . '/edit') ?>" class="button secondary">Back to editor</a>
</div>
<ul class="version-list">
  <?php foreach ($versions as $version): ?>
    <?php $data = json_decode($version['data_json'], true); ?>
    <li>
      <div>
        <strong><?= e($data['title'] ?? '') ?></strong>
        <span class="badge <?= $version['status'] ?>"><?= e($version['status']) ?></span>
        <div class="hint"><?= e(date('M j, Y g:i a', strtotime($version['created_at']))) ?></div>
      </div>
      <form method="post" action="<?= base_url('admin/projects/' . $project['id'] . '/versions/' . $version['id'] . '/restore') ?>" onsubmit="return confirm('Restore this version into a draft? It will not go live until you publish it.')">
        <?= csrf_field() ?>
        <button type="submit" class="button small secondary">Restore as draft</button>
      </form>
    </li>
  <?php endforeach; ?>
  <?php if (!$versions): ?><li class="hint">No previous versions yet.</li><?php endif; ?>
</ul>
