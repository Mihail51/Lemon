<?php
require __DIR__ . '/../_init.php';

// templates/recipe.php

if (!isset($recipe) || !is_array($recipe)) {
  http_response_code(500);
  echo 'Recipe template: missing $recipe';
  exit;
}

$title = $recipe['title'] ?? 'Recipe';

// source может быть строкой или массивом вида ['label' => '...']
$sourceLabel = '';
if (!empty($recipe['source'])) {
  if (is_array($recipe['source'])) {
    $sourceLabel = (string)($recipe['source']['label'] ?? '');
  } else {
    $sourceLabel = (string)$recipe['source'];
  }
}

// ---------- GALLERY HELPERS ----------
function recipe_file_exists_by_url(string $url): bool {
  // поддерживаем только локальные /img/...
  if ($url === '' || $url[0] !== '/') return false;
  $fs = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/\\') . str_replace(['..'], '', $url);
  return is_file($fs);
}

function recipe_guess_gallery(string $mainImageUrl, int $max = 10): array {
  // Ищем slug_g1.jpg ... slug_gN.jpg рядом с main image
  // main: /img/uploads/slug.jpg
  $out = [];
  if ($mainImageUrl === '') return $out;

  $pathInfo = pathinfo($mainImageUrl);
  $dir  = $pathInfo['dirname'] ?? '';
  $base = $pathInfo['filename'] ?? '';

  if ($dir === '' || $base === '') return $out;

  for ($i = 1; $i <= $max; $i++) {
    $u = $dir . '/' . $base . '_g' . $i . '.jpg';
    if (recipe_file_exists_by_url($u)) $out[] = $u;
  }
  return $out;
}

// Собираем галерею:
// 1) если есть $recipe['gallery'] (массив) — используем его
// 2) если нет — пробуем угадать по slug_g1..g10
$gallery = [];
if (!empty($recipe['gallery']) && is_array($recipe['gallery'])) {
  foreach ($recipe['gallery'] as $u) {
    $u = (string)$u;
    if ($u !== '') $gallery[] = $u;
  }
} else {
  $mainUrl = (string)($recipe['image'] ?? '');
  $gallery = recipe_guess_gallery($mainUrl, 10);
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- абсолютные пути, как в category.php -->
  <link rel="stylesheet" href="/css/recipe.css">
  <link rel="stylesheet" href="/css/header.css">
  <link rel="stylesheet" href="/css/footer.css">

  <style>
    /* Минимальные стили галереи (можно потом перенести в recipe.css) */
    .recipe__gallery{margin-top:12px}
    .recipe__gallery-title{margin:0 0 8px;font-size:14px;opacity:.8}
    .recipe__gallery-grid{display:flex;flex-wrap:wrap;gap:8px}
    .recipe__gallery-item{display:block;width:92px;height:92px;overflow:hidden;border-radius:10px;border:1px solid #eee;background:#fafafa}
    .recipe__gallery-item img{width:100%;height:100%;object-fit:cover;display:block}
    .recipe__brand{margin-top:10px;text-align:center;opacity:.7;font-size:12px}
  </style>
</head>
<body>

<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="main">
  <div class="container">

    <h1 class="title-page">
      <span>Recipe</span>
      <?= htmlspecialchars($recipe['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
    </h1>

    <div id="recipe_wrap">
      <div class="recipe">

        <div class="recipe__image-block">
          <?php if (!empty($recipe['image'])): ?>
            <img class="recipe__img"
                src="<?= htmlspecialchars($recipe['image'], ENT_QUOTES, 'UTF-8') ?>"
                alt="">
          <?php else: ?>
            <div class="recipe__img recipe__img--empty">No image</div>
          <?php endif; ?>

          <?php if ($sourceLabel !== ''): ?>
            <div class="recipe__source">
              <?= htmlspecialchars($sourceLabel, ENT_QUOTES, 'UTF-8') ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($gallery)): ?>
            <div class="recipe__gallery">
              <div class="recipe__gallery-title">Gallery</div>
              <div class="recipe__gallery-grid">
                <?php foreach ($gallery as $g): ?>
                  <a class="recipe__gallery-item" href="<?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                    <img src="<?= htmlspecialchars($g, ENT_QUOTES, 'UTF-8') ?>" alt="">
                  </a>
                <?php endforeach; ?>
              </div>

              <!-- “Надпись без текста” — пока просто место -->
              <div class="recipe__brand"> </div>
            </div>
          <?php endif; ?>

        </div>

        <div class="annotation">
          <h2><?= htmlspecialchars($recipe['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>

          <div class="recipe_info">
            <div class="icons">
              <?php if (!empty($recipe['time'])): ?>
                <p class="time"><?= htmlspecialchars($recipe['time'], ENT_QUOTES, 'UTF-8') ?></p>
              <?php endif; ?>
              <?php if (!empty($recipe['level'])): ?>
                <p class="size easy"><?= htmlspecialchars($recipe['level'], ENT_QUOTES, 'UTF-8') ?></p>
              <?php endif; ?>
            </div>
          </div>

          <div class="description">

              <?php if (!empty($recipe['ingredients'])): ?>
                <div class="composition">
                  <h3>Ingredients</h3>
                  <ul>
                    <?php foreach ($recipe['ingredients'] as $item): ?>
                      <li><?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <?php if (!empty($recipe['steps'])): ?>
                <div class="directions">
                  <h3>Directions</h3>
                  <ol>
                    <?php foreach ($recipe['steps'] as $step): ?>
                      <li><?= htmlspecialchars($step, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                  </ol>
                </div>
              <?php endif; ?>

            </div>

        </div>
      </div>
    </div>

  </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>