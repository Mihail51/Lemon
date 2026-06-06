<?php
require __DIR__ . '/_auth.php';

// Сканируем рецепты
$recipesDir = __DIR__ . '/../content/recipes/';
$files = glob($recipesDir . '*.php');

$items = [];
foreach ($files as $file) {
  $slug = basename($file, '.php');
  if ($slug[0] === '_') continue;

  $recipe = require $file;
  if (!is_array($recipe)) continue;

  $items[] = [
    'slug'  => $slug,
    'title' => $recipe['title'] ?? $slug,
    'type'  => $recipe['type'] ?? '',
    'speed' => $recipe['speed'] ?? '',
    'time'  => $recipe['time'] ?? '',
    'level' => $recipe['level'] ?? '',
  ];
}

// Сортировка по названию
usort($items, function($a, $b){
  return strcasecmp($a['title'], $b['title']);
});
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Рецепты — Админ</title>
  <style>
    body{font-family:Arial,sans-serif;max-width:1100px;margin:24px auto;padding:0 16px}
    a{display:inline-block;padding:10px 12px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#000}
    a:hover{background:#fafafa}
    .top{display:flex;justify-content:space-between;align-items:center;gap:12px}
    .top a{display:inline-block;padding:10px 12px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#000}
    .top form{margin:0}
    button{font-size:14px;padding:10px 12px}
    table{width:100%;border-collapse:collapse;margin-top:16px}
    th,td{border-bottom:1px solid #eee;padding:10px;text-align:left;vertical-align:top}
    th{background:#fafafa}
    .muted{color:#666;font-size:13px}
    .actions a{margin-right:10px}
    .badge{display:inline-block;padding:2px 8px;border:1px solid #ddd;border-radius:999px;font-size:12px}
  </style>
</head>
<body>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin:12px 0 18px">
    <a href="/admin/recipes.php">Рецепты</a>
    <a href="/admin/recipe-new.php">Добавить</a>
    <a href="/admin/pending.php">Модерация</a>
  </div>

  <div class="top">
    <div>
      <h2 style="margin:0">Рецепты (<?= count($items) ?>)</h2>
      <div class="muted">Список всех файлов из <code>content/recipes/</code></div>
    </div>

    <div style="display:flex;gap:10px;align-items:center">
      <a href="/admin/recipe-new.php">+ Добавить рецепт</a>
      <a href="/html/recipees.php" target="_blank">Открыть сайт</a>
      <form method="post">
        <button type="submit" name="logout" value="1">Выйти</button>
      </form>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Название</th>
        <th>Slug</th>
        <th>Type</th>
        <th>Speed</th>
        <th>Time</th>
        <th>Level</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $r): ?>
        <tr>
          <td>
            <b><?= htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') ?></b><br>
            <span class="muted">
              <a href="/html/recipe.php?id=<?= htmlspecialchars($r['slug'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">открыть</a>
            </span>
          </td>
          <td><code><?= htmlspecialchars($r['slug'], ENT_QUOTES, 'UTF-8') ?></code></td>
          <td><?= $r['type'] ? '<span class="badge">'.htmlspecialchars($r['type']).'</span>' : '' ?></td>
          <td><?= $r['speed'] ? '<span class="badge">'.htmlspecialchars($r['speed']).'</span>' : '' ?></td>
          <td><?= htmlspecialchars($r['time'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($r['level'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="actions">
            <a href="/admin/recipe-edit.php?id=<?= htmlspecialchars($r['slug'], ENT_QUOTES, 'UTF-8') ?>">Редактировать</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

</body>
</html>