<?php
require __DIR__ . '/../_init.php';
// templates/category.php

if (empty($content_file) || !is_file($content_file)) {
  http_response_code(500);
  echo 'Category template: invalid $content_file';
  exit;
}

$h1_prefix = $h1_prefix ?? 'Recipees';

if (!isset($h1_title)) {
  http_response_code(500);
  echo 'Category template: missing $h1_title';
  exit;
}

$page_title = $page_title ?? ($h1_prefix . ' ' . $h1_title);
$title = strip_tags($page_title);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
  <meta name="description" content="#" />
  <meta name="keywords" content="#" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- ВАЖНО: абсолютные пути, чтобы работало из /html/... -->
  <link rel="stylesheet" href="/css/recipees.css">
  <link rel="stylesheet" href="/css/header.css">
  <link rel="stylesheet" href="/css/footer.css">
</head>

<body>
  <?php include __DIR__ . '/../partials/header.php'; ?>

  <main class="main">
    <div class="container">

      <h1 class="title-page">
        <span><?= htmlspecialchars($h1_prefix ?? 'Recipees', ENT_QUOTES, 'UTF-8') ?></span>
        <?= htmlspecialchars($h1_title ?? '', ENT_QUOTES, 'UTF-8') ?>
      </h1>

      <?php include $content_file; ?>

    </div>
  </main>

  <?php include __DIR__ . '/../partials/footer.php'; ?>



</body>
</html>
