<?php
require __DIR__ . '/_init.php';

$userEmail = $_SESSION['user_email'] ?? '';
if ($userEmail === '') {
  header('Location: /login.php');
  exit;
}

$pendingDir = __DIR__ . '/content/pending/';
$files = is_dir($pendingDir) ? glob($pendingDir . '*.php') : [];

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Мои рецепты</title>
  <style>
    body{font-family:Arial,sans-serif;max-width:980px;margin:24px auto;padding:0 16px}
    .top{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:12px 0 18px}
    .btn{display:inline-block;padding:10px 12px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#000}
    .btn:hover{background:#fafafa}
    .card{border:1px solid #ddd;border-radius:10px;padding:14px;margin-bottom:14px}
    .row{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:10px}
    .muted{color:#666}
    .ok{background:#e9ffe9;border:1px solid #b9e6b9;padding:12px;margin:12px 0;border-radius:10px}
  </style>
</head>
<body>

<h2>Мои рецепты (на модерации)</h2>

<div class="top">
  <span class="muted">Вы вошли как: <b><?= h($userEmail) ?></b></span>
  <a class="btn" href="/logout.php">Выйти</a>
  <a class="btn" href="/submit/recipe.php">Добавить рецепт</a>
</div>

<?php
$mine = [];

foreach ($files as $file) {
  $slug = basename($file, '.php');

  $recipe = require $file;
  $author = $recipe['_meta']['author_email'] ?? '';

  if ($author !== $userEmail) continue;

  $mine[] = [
    'slug' => $slug,
    'title' => $recipe['title'] ?? $slug,
    'created' => $recipe['_meta']['created_at'] ?? '',
    'type' => $recipe['type'] ?? '',
    'time' => $recipe['time'] ?? '',
  ];
}
?>

<?php if (!$mine): ?>
  <div class="ok">У вас пока нет рецептов на модерации.</div>
<?php endif; ?>

<?php foreach ($mine as $r): ?>
  <div class="card">
    <h3><?= h($r['title']) ?></h3>
    <div class="muted">
      <?= $r['type'] ? 'Тип: ' . h($r['type']) . ' · ' : '' ?>
      <?= $r['time'] ? 'Время: ' . h($r['time']) . ' · ' : '' ?>
      <?= $r['created'] ? 'Отправлен: ' . h($r['created']) : '' ?>
    </div>

    <div class="row">
      <!-- edit.php сделаем следующим этапом -->
      <a class="btn" href="/submit/edit.php?slug=<?= h($r['slug']) ?>">✏ Редактировать</a>
    </div>
  </div>
<?php endforeach; ?>

</body>
</html>