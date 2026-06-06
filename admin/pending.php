<?php
require __DIR__ . '/_auth.php';

// функции хранения
require __DIR__ . '/../lib/recipes_storage.php';

$pendingDir  = __DIR__ . '/../content/pending/';
$recipesDir  = __DIR__ . '/../content/recipes/';
$rejectedDir = __DIR__ . '/../content/rejected/';
$historyDir  = __DIR__ . '/../content/history/';

// 👉 корень сайта (где лежит uploads/)
$publicRoot = realpath(__DIR__ . '/..');

if (!is_dir($pendingDir)) {
  die('Папка content/pending не найдена.');
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $slug = trim($_POST['slug'] ?? '');
  $slug = preg_replace('~[^a-z0-9\-]~', '', strtolower($slug));

  if ($slug === '') {
    die('Некорректный slug.');
  }

  $pendingFile = $pendingDir . $slug . '.php';

  if (isset($_POST['approve'])) {

    if (!is_file($pendingFile)) {
      die('Этот рецепт уже обработан другим администратором.');
    }

    $pending  = recipe_read($pendingFile);
    $status   = $pending['_meta']['status'] ?? 'pending';
    $original = $pending['_meta']['original_slug'] ?? $slug;

    // Определяем slug публикации
    $targetSlug = ($status === 'pending_update') ? $original : $slug;
    $targetFile = $recipesDir . $targetSlug . '.php';

    if ($status === 'pending_update') {

      // 🔹 1. Бэкап старой версии
      backup_published($targetFile, $historyDir, $targetSlug);

      // 🔹 2. Удаляем старый upload, если картинка изменилась
      if (is_file($targetFile)) {
        $oldPublished = recipe_read($targetFile);

        recipe_delete_old_upload_if_changed(
          $oldPublished,
          $pending,
          (string)$publicRoot
        );
      }

    } else {
      // защита от случайной перезаписи
      if (is_file($targetFile)) {
        die('Рецепт с таким slug уже опубликован. Для правки используйте pending_update.');
      }
    }

    // Обновляем meta
    $pending['_meta']['status'] = 'published';
    $pending['_meta']['published_at'] = date('c');

    if (!empty($_SESSION['user_id'])) {
      $pending['_meta']['moderated_by'] = (int)$_SESSION['user_id'];
    }
    $pending['_meta']['moderated_at'] = date('c');

    // Атомарная запись
    recipe_write_atomic($targetFile, $pending);

    unlink($pendingFile);

    recipe_log_action(
      (string)$publicRoot,
      'approve slug=' . $targetSlug . ' admin=' . ($_SESSION['user_id'] ?? 0)
    );

    header('Location: pending.php');
    exit;
  }

  if (isset($_POST['reject'])) {

    if (!is_file($pendingFile)) {
      die('Этот рецепт уже обработан другим администратором.');
    }

    $pending = recipe_read($pendingFile);

    $reason = trim($_POST['reject_reason'] ?? '');
    if ($reason === '') $reason = 'Без причины';

    $pending['_meta']['status'] = 'rejected';
    $pending['_meta']['rejected_at'] = date('c');
    $pending['_meta']['reject_reason'] = $reason;

    if (!empty($_SESSION['user_id'])) {
      $pending['_meta']['moderated_by'] = (int)$_SESSION['user_id'];
    }
    $pending['_meta']['moderated_at'] = date('c');

    ensure_dir($rejectedDir);

    $rejectedFile = $rejectedDir . $slug . '.php';

    recipe_write_atomic($rejectedFile, $pending);

    unlink($pendingFile);

    recipe_log_action(
      (string)$publicRoot,
      'reject slug=' . $slug .
      ' admin=' . ($_SESSION['user_id'] ?? 0) .
      ' reason="' . $reason . '"'
    );

    header('Location: pending.php');
    exit;
  }
}

$files = glob($pendingDir . '*.php');
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Модерация рецептов</title>
<style>
body{font-family:Arial,sans-serif;max-width:980px;margin:24px auto;padding:0 16px}
a{display:inline-block;padding:10px 12px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#000}
a:hover{background:#fafafa}
.card{border:1px solid #ddd;border-radius:10px;padding:14px;margin-bottom:14px}
.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
button{padding:8px 12px;cursor:pointer}
.ok{background:#e9ffe9;border:1px solid #b9e6b9;padding:12px;margin:12px 0}
</style>
</head>
<body>

<h2>Модерация рецептов</h2>

<?php if (!$files): ?>
  <div class="ok">Нет рецептов на модерации 🎉</div>
<?php endif; ?>

<?php foreach ($files as $file): ?>
<?php
  $slug = basename($file, '.php');
  $recipe = require $file;
?>
<div class="card">
  <h3><?= htmlspecialchars($recipe['title'] ?? $slug, ENT_QUOTES, 'UTF-8') ?></h3>

  <div class="row">
    <form method="post" style="margin:0">
      <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" name="approve">✅ Принять</button>
      <button type="submit" name="reject">❌ Отклонить</button>
      <input type="text" name="reject_reason" placeholder="Причина отказа">
    </form>

    <?php
      // ✅ ВАРИАНТ D: правильный preview для pending_update
      $status   = $recipe['_meta']['status'] ?? 'pending';
      $original = $recipe['_meta']['original_slug'] ?? $slug;

      $previewId = ($status === 'pending_update') ? $original : $slug;
    ?>
    <a href="/html/recipe.php?id=<?= htmlspecialchars($previewId, ENT_QUOTES, 'UTF-8') ?>&preview=pending" target="_blank">
      👁 Предпросмотр
    </a>
  </div>
</div>
<?php endforeach; ?>

</body>
</html>