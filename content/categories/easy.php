<?php
$slugs = require __DIR__ . '/../recipes/_index.php';
?>
<div class="recipees">
<?php
foreach ($slugs as $slug) {
  $file = __DIR__ . '/../recipes/' . $slug . '.php';
  if (!is_file($file)) continue;

  $recipe = require $file;
  if (($recipe['level'] ?? '') !== 'Easy') continue;

  include __DIR__ . '/../../templates/recipe-card.php';
}
?>
</div>
