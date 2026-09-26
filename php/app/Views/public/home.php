<?php
/** @var array $settings */
$interests = $settings['interests'] ?? [];
?>
<main class="route-main home-main">
  <section class="hero motion-in" aria-label="Hello, I'm <?= e($settings['author_display_name']) ?>">
    <div class="hero-top">
      <span class="eyebrow">A little corner of the internet</span>
      <span class="hero-mark" aria-hidden="true">&#10038;</span>
    </div>
    <h1><?= e($settings['hero_greeting']) ?></h1>
    <div class="hero-bottom">
      <p>Thinking out loud.<br>Figuring things out.</p>
      <span class="edition">THE PERSONAL EDITION</span>
    </div>
  </section>
  <section class="intro" aria-labelledby="intro-title">
    <div class="intro-top eyebrow">
      <span class="line" aria-hidden="true"></span>
      Behind the words
    </div>
    <div>
      <h2 id="intro-title"><?= e($settings['intro_heading']) ?></h2>
      <p class="intro-description"><?= e($settings['intro_body']) ?></p>
    </div>
    <?php if ($interests): ?>
    <div class="topics">
      <p class="topics-label eyebrow">Things on my mind</p>
      <ul class="topic-list">
        <?php foreach ($interests as $interest): ?>
          <li class="topic"><?= e($interest) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </section>
</main>
