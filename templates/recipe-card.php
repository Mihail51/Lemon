<?php
// templates/recipe-card.php
// Ожидает: $slug, $recipe

require_once __DIR__ . '/../lib/image_helper.php';

$title = $recipe['title'] ?? '';
$time  = $recipe['time'] ?? '';
$image = $recipe['image'] ?? null;

// source может быть строкой или массивом вида ['label' => '...']
$source = '';
if (!empty($recipe['source'])) {
  if (is_array($recipe['source'])) {
    $source = $recipe['source']['label'] ?? '';
  } else {
    $source = (string)$recipe['source'];
  }
}

// определяем preview изображения
$preview = null;
$previewWebp = null;

if (!empty($image)) {
  $preview = recipe_image($image, 'preview');
  $previewWebp = recipe_image_webp($image, 'preview');

  // если preview-файла нет физически — берём оригинал
  if (empty($preview) || !is_file(__DIR__ . '/..' . $preview)) {
    $preview = $image;
  }

  // если webp-файла нет физически — не выводим source webp
  if (empty($previewWebp) || !is_file(__DIR__ . '/..' . $previewWebp)) {
    $previewWebp = '';
  }
}
?>

<figure>

  <a href="/html/recipe.php?id=<?= urlencode($slug) ?>">

    <?php if (!empty($image)): ?>

      <picture>

        <?php if (!empty($previewWebp)): ?>
          <source
            srcset="<?= htmlspecialchars($previewWebp, ENT_QUOTES, 'UTF-8') ?>"
            type="image/webp">
        <?php endif; ?>

        <img
          class="recipees-foto"
          src="<?= htmlspecialchars($preview, ENT_QUOTES, 'UTF-8') ?>"
          alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
          loading="lazy">

      </picture>

    <?php else: ?>

      <img
        class="recipees-foto recipees-foto--empty"
        src=""
        alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">

    <?php endif; ?>

  </a>

  <?php if ($source !== ''): ?>
    <div class="recipees-source">
      <?= htmlspecialchars($source, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <figcaption>

    <h4><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h4>

    <div class="icon">
      <p class="time"><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></p>
      <img src="../img/Line.png" alt="">
      <p class="comment">0</p>
      <p class="view">0</p>
    </div>

  </figcaption>

</figure>