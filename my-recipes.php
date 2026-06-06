<?php
require __DIR__ . '/_init.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$userEmail = $_SESSION['user_email'] ?? '';
if ($userEmail === '') {
  header('Location: /login.php');
  exit;
}

function collect_by_author($dir, $userEmail) {
  if (!is_dir($dir)) return [];
  $files = glob(rtrim($dir,'/\\') . '/*.php') ?: [];
  $out = [];

  foreach ($files as $file) {
    $slug = basename($file, '.php');
    $recipe = require $file;

    $author = $recipe['_meta']['author_email'] ?? '';
    if ($author !== $userEmail) continue;

    $out[] = [
      'slug' => $slug,
      'title' => $recipe['title'] ?? $slug,
      'created' => $recipe['_meta']['created_at'] ?? '',
      'updated' => $recipe['_meta']['updated_at'] ?? '',
      'type' => $recipe['type'] ?? '',
      'time' => $recipe['time'] ?? '',
      'image' => $recipe['image'] ?? '',
    ];
  }

  // новые выше
  usort($out, fn($a,$b) => strcmp($b['created'], $a['created']));
  return $out;
}

$pending = collect_by_author(__DIR__ . '/content/pending', $userEmail);
$published = collect_by_author(__DIR__ . '/content/recipes', $userEmail);
$rejected = collect_by_author(__DIR__ . '/content/rejected', $userEmail);
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
.h{margin:22px 0 10px}
.badge{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;border:1px solid #ddd}
.badge--p{background:#fff6e6}
.badge--ok{background:#e9ffe9;border-color:#b9e6b9}
.badge--rejected{background:#ffe9e9;border-color:#e6b9b9;color:#900;}
</style>
</head>
<body>

<h2>Мои рецепты</h2>

<div class="top">
  <span class="muted">Вы вошли как: <b><?= h($userEmail) ?></b></span>
  <a class="btn" href="/logout.php">Выйти</a>
  <a class="btn" href="/submit/recipe.php">Добавить рецепт</a>
  <a class="btn" href="/">На главную</a>
</div>

<h3 class="h">На модерации</h3>

<?php if (!$pending): ?>
  <div class="muted">Нет рецептов на модерации.</div>
<?php endif; ?>

<?php foreach ($pending as $r): ?>
  <div class="card">
    <div class="row">
      <span class="badge badge--p">pending</span>
      <h3 style="margin:0"><?= h($r['title']) ?></h3>
    </div>
    <div class="muted">
      <?= $r['type'] ? 'Тип: ' . h($r['type']) . ' · ' : '' ?>
      <?= $r['time'] ? 'Время: ' . h($r['time']) . ' · ' : '' ?>
      <?= $r['created'] ? 'Отправлен: ' . h($r['created']) : '' ?>
    </div>
    <div class="row">
      <a class="btn" href="/submit/edit.php?slug=<?= h($r['slug']) ?>">✏ Редактировать</a>
    </div>
  </div>
<?php endforeach; ?>

<h3 class="h">Отклонённые</h3>

<?php if (empty($rejected)): ?>
  <div class="muted">Нет отклонённых рецептов.</div>
<?php else: ?>

  <?php foreach ($rejected as $rej): ?>
    <?php
      $file = __DIR__ . '/content/rejected/' . $rej['slug'] . '.php';
      $full = is_file($file) ? (require $file) : [];
      $reason = $full['_meta']['reject_reason'] ?? 'Без причины';
      $rejectedAt = $full['_meta']['rejected_at'] ?? '';
    ?>
    <div class="card">
      <div class="row">
        <span class="badge badge--rejected">rejected</span>
        <h3 style="margin:0"><?= h($rej['title']) ?></h3>
      </div>

      <div class="muted">
        <?= $rejectedAt ? 'Отклонено: ' . h($rejectedAt) . ' · ' : '' ?>
        Причина: <b><?= h($reason) ?></b>
      </div>

      <div class="row">
        <a class="btn" href="/submit/edit.php?slug=<?= h($rej['slug']) ?>">Исправить и отправить снова</a>
      </div>
    </div>
  <?php endforeach; ?>

<?php endif; ?>

<h3 class="h">Опубликованные</h3>

<?php if (!$published): ?>
  <div class="muted">Пока нет опубликованных рецептов.</div>
<?php endif; ?>

<?php foreach ($published as $r): ?>
  <div class="card">
    <div class="row">
      <span class="badge badge--ok">published</span>
      <h3 style="margin:0"><?= h($r['title']) ?></h3>
    </div>
    <div class="muted">
      <?= $r['type'] ? 'Тип: ' . h($r['type']) . ' · ' : '' ?>
      <?= $r['time'] ? 'Время: ' . h($r['time']) . ' · ' : '' ?>
      <?= $r['created'] ? 'Создан: ' . h($r['created']) . ' · ' : '' ?>
      <?= $r['updated'] ? 'Обновлён: ' . h($r['updated']) : '' ?>
    </div>
    <div class="row">
      <a class="btn" href="/html/recipe.php?id=<?= h($r['slug']) ?>">Открыть</a>
      <a class="btn" href="/submit/propose-update.php?slug=<?= h($r['slug']) ?>">Предложить правку</a>
    </div>
  </div>
<?php endforeach; ?>

</body>
</html>