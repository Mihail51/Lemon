<?php
// content/categories/_recipes-filter.php
// Ожидает: $filter (callable) или $type/$speed

$recipesDir = __DIR__ . '/../recipes/';
$files = glob($recipesDir . '*.php');

echo '<div class="recipees">';

foreach ($files as $file) {
  $slug = basename($file, '.php');
  if ($slug[0] === '_') continue;

  $recipe = require $file;

  if (isset($filter) && is_callable($filter)) {
    if (!$filter($recipe)) continue;
  }

  include __DIR__ . '/../../templates/recipe-card.php';
}

echo '</div>';
