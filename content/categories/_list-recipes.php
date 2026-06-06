<?php
// content/categories/_list-recipes.php
// Ожидает:
// - $filterKey (например 'type' или 'speed')
// - $filterValue (например 'fish' или 'quick')

// Автоматически сканируем папку recipes
$recipesDir = __DIR__ . '/../recipes/';
$files = glob($recipesDir . '*.php');

$slugs = [];

foreach ($files as $file) {
  $slug = basename($file, '.php');

  // пропускаем служебные файлы (_index, _template и т.п.)
  if ($slug[0] === '_') continue;

  $slugs[] = $slug;
}
?>

<div class="recipees">
<?php
foreach ($slugs as $slug) {
  $file = __DIR__ . '/../recipes/' . $slug . '.php';
  if (!is_file($file)) continue;

  $recipe = require $file;

  // фильтр (если задан)
  if (isset($filterKey, $filterValue)) {
    if (($recipe[$filterKey] ?? '') !== $filterValue) continue;
  }

  include __DIR__ . '/../../templates/recipe-card.php';
}
?>
</div>
