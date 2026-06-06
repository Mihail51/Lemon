<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// html/recipe.php — единая страница рецепта по ?id=...

$id = $_GET['id'] ?? '';
$id = preg_replace('~[^a-z0-9\-]~', '', strtolower($id));

$preview = (($_GET['preview'] ?? '') === 'pending');

$publishedFile = __DIR__ . '/../content/recipes/' . $id . '.php';
$pendingFile   = __DIR__ . '/../content/pending/' . $id . '.php';

// Если это предпросмотр — пытаемся показать pending-версию
if ($preview) {
  if (is_file($pendingFile)) {
    $dataFile = $pendingFile;
  } elseif (is_file($publishedFile)) {
    // если pending уже пропал (например, утвердили), покажем опубликованную
    $dataFile = $publishedFile;
  } else {
    http_response_code(404);
    echo "Recipe not found";
    exit;
  }
} else {
  // обычный просмотр только опубликованных
  $dataFile = $publishedFile;

  if (!$id || !is_file($dataFile)) {
    http_response_code(404);
    echo "Recipe not found";
    exit;
  }
}

$recipe = require $dataFile; // файл должен return массив

include __DIR__ . '/../templates/recipe.php';