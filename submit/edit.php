<?php
require __DIR__ . '/../_init.php';
require __DIR__ . '/../lib/image_resize.php';   // ← уже есть, оставляем

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$userEmail = $_SESSION['user_email'] ?? '';
if ($userEmail === '') {
  header('Location: /login.php');
  exit;
}

$slug = trim($_GET['slug'] ?? '');
$slug = strtolower($slug);
$slug = preg_replace('~[^a-z0-9\-]~', '', $slug);

$pendingFile   = __DIR__ . '/../content/pending/'  . $slug . '.php';
$rejectedFile  = __DIR__ . '/../content/rejected/' . $slug . '.php';
$publishedFile = __DIR__ . '/../content/recipes/'  . $slug . '.php';

$dataFile = null;
$status   = null;

if ($slug && is_file($pendingFile)) {
  $dataFile = $pendingFile;
  $status   = 'pending';
} elseif ($slug && is_file($rejectedFile)) {
  $dataFile = $rejectedFile;
  $status   = 'rejected';
} elseif ($slug && is_file($publishedFile)) {
  $dataFile = $publishedFile;
  $status   = 'published';
}

if (!$dataFile) {
  http_response_code(404);
  echo "Recipe not found";
  exit;
}

$recipe = require $dataFile;
$author = $recipe['_meta']['author_email'] ?? '';
if ($author !== $userEmail) {
  http_response_code(403);
  echo "Forbidden";
  exit;
}

// helpers for textarea list
function lines_to_array($text){
  $lines = preg_split("~\R~u", trim((string)$text));
  $out = [];
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line !== '') $out[] = $line;
  }
  return $out;
}

// удалить upload-картинку по URL вида /img/uploads/NAME.ext (удаляем варианты .jpg/_preview/_gallery)
function delete_upload_variants_by_url(string $url): void {
  // удаляем только если это наш uploads
  if (strpos($url, '/img/uploads/') !== 0) return;

  $base = pathinfo($url, PATHINFO_FILENAME); // без расширения
  $dir  = __DIR__ . '/../img/uploads/';

  // Мы теперь создаём JPG-версии через image_resize.php
  @unlink($dir . $base . '.jpg');
  @unlink($dir . $base . '_preview.jpg');
  @unlink($dir . $base . '_gallery.jpg');

  // На всякий случай чистим возможные “старые” форматы (если когда-то сохранялись как png/webp)
  @unlink($dir . $base . '.jpeg');
  @unlink($dir . $base . '.png');
  @unlink($dir . $base . '.webp');
  @unlink($dir . $base . '_preview.jpeg');
  @unlink($dir . $base . '_preview.png');
  @unlink($dir . $base . '_preview.webp');
  @unlink($dir . $base . '_gallery.jpeg');
  @unlink($dir . $base . '_gallery.png');
  @unlink($dir . $base . '_gallery.webp');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $type  = trim($_POST['type'] ?? '');
  $time  = trim($_POST['time'] ?? '');
  $level = trim($_POST['level'] ?? '');
  $image = trim($_POST['image'] ?? '');
  $source_label = trim($_POST['source_label'] ?? '');

  // текущие значения из рецепта (чтобы уметь удалять/добавлять)
  $oldMainImage = (string)($recipe['image'] ?? '');
  $gallery = $recipe['gallery'] ?? [];
  if (!is_array($gallery)) $gallery = [];

  // 1) Удаление текущего главного фото (и файлов, если это upload)
  if (!empty($_POST['delete_image'])) {
    if ($oldMainImage !== '') {
      delete_upload_variants_by_url($oldMainImage);
    }
    $image = '';
  }

  // 2) Загрузка главного фото (имеет приоритет над URL)
  if (!empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {

    $tmp  = $_FILES['image_file']['tmp_name'];
    $name = $_FILES['image_file']['name'];

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allowed, true)) {
      $error = 'Неверный формат фото. Разрешены jpg/jpeg/png/webp.';
    } else {

      $destDir = __DIR__ . '/../img/uploads/';
      if (!is_dir($destDir)) mkdir($destDir, 0775, true);

      // сначала сохраняем исходник временно
      $tmpOriginal = $destDir . 'tmp-' . bin2hex(random_bytes(4)) . '.' . $ext;

      if (!move_uploaded_file($tmp, $tmpOriginal)) {
        $error = 'Не удалось сохранить файл (проверь права).';
      } else {

        // удаляем старое главное фото (если было upload)
        if ($oldMainImage !== '') {
          delete_upload_variants_by_url($oldMainImage);
        }

        // базовое имя: r_<slug>_<timestamp>
        $baseName = 'r_' . $slug . '_' . time();

        // создаём 3 версии (1200x800 / 400x300 / 1200x1200)
        create_recipe_images($tmpOriginal, $baseName);

        // записываем в рецепт "главную" jpg
        $image = '/img/uploads/' . $baseName . '.jpg';

        // если загрузили файл — это уже не “фото из интернета”
        if ($source_label === 'Фото из интернета') {
          $source_label = '';
        }

        @unlink($tmpOriginal);
      }
    }
  }

  // 3) ГАЛЕРЕЯ (несколько фото)
  if ($error === '' && !empty($_FILES['gallery_files']) && !empty($_FILES['gallery_files']['name']) && is_array($_FILES['gallery_files']['name'])) {

    $destDir = __DIR__ . '/../img/uploads/';
    if (!is_dir($destDir)) mkdir($destDir, 0775, true);

    $allowed = ['jpg','jpeg','png','webp'];
    $count = count($_FILES['gallery_files']['name']);

    for ($i = 0; $i < $count; $i++) {
      $err = $_FILES['gallery_files']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
      if ($err === UPLOAD_ERR_NO_FILE) continue;
      if ($err !== UPLOAD_ERR_OK) continue;

      $tmp  = $_FILES['gallery_files']['tmp_name'][$i] ?? '';
      $name = $_FILES['gallery_files']['name'][$i] ?? '';

      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed, true)) continue;

      // временно сохраняем исходник
      $tmpOriginal = $destDir . 'tmp-' . bin2hex(random_bytes(4)) . '.' . $ext;
      if (!move_uploaded_file($tmp, $tmpOriginal)) continue;

      // базовое имя кадра галереи: r_<slug>_g_<timestamp>_<rand>
      $baseName = 'r_' . $slug . '_g_' . time() . '_' . bin2hex(random_bytes(2));

      create_recipe_images($tmpOriginal, $baseName);

      $gallery[] = '/img/uploads/' . $baseName . '.jpg';

      @unlink($tmpOriginal);
    }
  }

  $ingredientsText = $_POST['ingredients'] ?? '';
  $stepsText       = $_POST['steps'] ?? '';

  if ($title === '') {
    $error = 'Title обязателен.';
  } elseif ($error === '') {

    $recipe['title'] = $title;
    $recipe['type']  = $type;
    $recipe['time']  = $time;
    $recipe['level'] = $level;
    $recipe['image'] = $image;

    // галерея
    if (!empty($gallery)) {
      $recipe['gallery'] = array_values($gallery);
    } else {
      unset($recipe['gallery']);
    }

    // source (если есть)
    if ($source_label !== '') {
      $recipe['source'] = ['label' => $source_label];
    } else {
      unset($recipe['source']);
    }

    $recipe['ingredients'] = lines_to_array($ingredientsText);
    $recipe['steps']       = lines_to_array($stepsText);

    // meta update time
    $recipe['_meta']['updated_at'] = date('c');

    // save back to current file
    $content = "<?php\nreturn " . var_export($recipe, true) . ";\n";
    file_put_contents($dataFile, $content, LOCK_EX);

    // если был отклонён — вернуть на модерацию
    if ($status === 'rejected') {
      $newFile = __DIR__ . '/../content/pending/' . $slug . '.php';

      unset($recipe['_meta']['reject_reason']);
      unset($recipe['_meta']['rejected_at']);

      $recipe['_meta']['updated_at'] = date('c');

      $content = "<?php\nreturn " . var_export($recipe, true) . ";\n";
      file_put_contents($newFile, $content, LOCK_EX);

      unlink($dataFile); // удалить из rejected
    }

    // если был опубликован — отправить правку на модерацию (pending)
    if ($status === 'published') {

      $newFile = __DIR__ . '/../content/pending/' . $slug . '.php';

      $recipe['_meta']['status'] = 'pending_update';
      $recipe['_meta']['original_slug'] = $slug;
      $recipe['_meta']['updated_at'] = date('c');

      $content = "<?php\nreturn " . var_export($recipe, true) . ";\n";
      file_put_contents($newFile, $content, LOCK_EX);
    }

    $success = 'Сохранено ✅';
  }
}

// for form prefill
$title = $recipe['title'] ?? '';
$type  = $recipe['type'] ?? '';
$time  = $recipe['time'] ?? '';
$level = $recipe['level'] ?? '';
$image = $recipe['image'] ?? '';
$source_label = $recipe['source']['label'] ?? '';

$ingredientsText = !empty($recipe['ingredients']) ? implode("\n", (array)$recipe['ingredients']) : '';
$stepsText       = !empty($recipe['steps']) ? implode("\n", (array)$recipe['steps']) : '';
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Редактировать рецепт</title>
<style>
body{font-family:Arial,sans-serif;max-width:820px;margin:24px auto;padding:0 16px}
a{color:#000}
.row{display:flex;gap:10px;flex-wrap:wrap;margin:10px 0}
input,select,textarea,button{width:100%;padding:10px;margin:6px 0;font-size:16px}
textarea{min-height:140px}
.btn{display:inline-block;padding:10px 12px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#000}
.btn:hover{background:#fafafa}
.ok{background:#e9ffe9;border:1px solid #b9e6b9;padding:10px;border-radius:10px}
.err{background:#ffe9e9;border:1px solid #e6b9b9;padding:10px;border-radius:10px}
small{color:#666}
</style>
</head>
<body>

<h2>Редактировать (<?= $status === 'rejected' ? 'отклонено' : 'на модерации' ?>)</h2>

<div class="row">
  <a class="btn" href="/my.php">← Мои рецепты</a>
  <a class="btn" href="/logout.php">Выйти</a>
</div>

<?php if ($success): ?><div class="ok"><?= h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>Title *</label>
  <input name="title" value="<?= h($title) ?>" required>

  <label>Type</label>
  <input name="type" value="<?= h($type) ?>" placeholder="fish / baking / salting ...">

  <label>Time</label>
  <input name="time" value="<?= h($time) ?>" placeholder="например: 40 mins">

  <label>Level</label>
  <select name="level">
    <?php
      $cur = $level;
      foreach (['','Easy','Medium','Hard'] as $opt) {
        $sel = ($cur === $opt) ? 'selected' : '';
        $label = $opt === '' ? '—' : $opt;
        echo "<option value=\"".h($opt)."\" $sel>$label</option>";
      }
    ?>
  </select>

  <label>Фото (URL или загрузка)</label>

<?php if (!empty($image)): ?>
  <div style="margin:8px 0">
    <img src="<?= h($image) ?>" alt="" style="max-width:240px;height:auto;border:1px solid #ddd;border-radius:8px">
  </div>

  <label style="display:flex;gap:8px;align-items:center">
    <input type="checkbox" name="delete_image" value="1">
    Удалить текущее фото
  </label>
<?php endif; ?>

<label>Image URL (если фото из интернета)</label>
<input name="image" value="<?= h($image) ?>" placeholder="https://...">

<label>Загрузить фото с компьютера (заменит текущее)</label>
<input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp">

<label>Галерея (несколько фото блюда)</label>
<input type="file" name="gallery_files[]" accept=".jpg,.jpeg,.png,.webp" multiple>
<small>Можно выбрать сразу несколько файлов. Они добавятся в галерею рецепта.</small>

<label>Подпись к фото (например: Фото из интернета)</label>
<input name="source_label" value="<?= h($source_label) ?>" placeholder="Фото из интернета">

  <label>Ingredients (по одному на строку)</label>
  <textarea name="ingredients"><?= h($ingredientsText) ?></textarea>

  <label>Directions / Steps (по одному пункту на строку)</label>
  <textarea name="steps"><?= h($stepsText) ?></textarea>

  <button type="submit" name="save_recipe" value="1">Сохранить</button>
  <small>Рецепт остаётся на модерации до подтверждения админом.</small>
</form>

</body>
</html>