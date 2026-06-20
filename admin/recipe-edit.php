<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../lib/image_resize.php';

function h($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function normalize_lines(string $text): array {
  $lines = preg_split('~\R~u', trim($text));
  $out = [];
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line !== '') $out[] = $line;
  }
  return $out;
}

function php_array_export(array $arr): string {
  return var_export($arr, true);
}

$id = trim($_GET['id'] ?? '');
$id = strtolower($id);
$id = preg_replace('~[^a-z0-9\-]~', '', $id);

$recipesDir = __DIR__ . '/../content/recipes/';
$filePath = $recipesDir . $id . '.php';

if ($id === '' || !is_file($filePath)) {
  http_response_code(404);
  echo 'Recipe not found';
  exit;
}

$recipe = require $filePath;
if (!is_array($recipe)) $recipe = [];

$errors = [];
$success = '';

$currentImage = $recipe['image'] ?? null;

$currentOriginalNoteImage = $recipe['original_note_image'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_recipe'])) {

  $title  = trim($_POST['title'] ?? '');
  $minutes = (int)($_POST['minutes'] ?? 0);
  $level  = trim($_POST['level'] ?? '');
  $type   = trim($_POST['type'] ?? '');

  $sourceLabel = trim($_POST['source_label'] ?? '');
  $imageNote = trim($_POST['image_note'] ?? '');
  $intro  = trim($_POST['intro'] ?? '');
  $reconstructionConfidence = trim($_POST['reconstruction_confidence'] ?? '');
  $notesText = trim($_POST['notes'] ?? '');
  $originalNoteImage = $currentOriginalNoteImage;

  $ingredients = normalize_lines($_POST['ingredients'] ?? '');
  $steps       = normalize_lines($_POST['steps'] ?? '');
  $notes       = normalize_lines($notesText);

  if ($title === '') $errors[] = 'Введите название рецепта.';
  if ($type === '')  $errors[] = 'Выберите type.';
  if ($minutes <= 0) $errors[] = 'Укажите время приготовления.';

  if ($minutes <= 15) {
    $speed = 'quick';
  } elseif ($minutes <= 40) {
    $speed = 'middle';
  } else {
    $speed = 'long';
  }

  $time = $minutes . ' mins';

  $imagePath = $currentImage;

  // ---------- УДАЛЕНИЕ ФОТО ----------
  if (!empty($_POST['remove_image']) && $currentImage) {
    $base = pathinfo($currentImage, PATHINFO_FILENAME);
    $dir = __DIR__ . '/../img/uploads/';

    @unlink($dir . $base . '.jpg');
    @unlink($dir . $base . '.webp');
    @unlink($dir . $base . '_preview.jpg');
    @unlink($dir . $base . '_preview.webp');
    @unlink($dir . $base . '_gallery.jpg');
    @unlink($dir . $base . '_gallery.webp');

    $imagePath = null;
  }

  // ---------- ЗАМЕНА ФОТО ----------
  if (!empty($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Ошибка загрузки файла.';
    } else {

      $tmp  = $_FILES['image_file']['tmp_name'];
      $name = $_FILES['image_file']['name'];

      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png','webp'];

      if (!in_array($ext, $allowed, true)) {
        $errors[] = 'Разрешены только jpg/jpeg/png/webp.';
      }

      $maxBytes = 3 * 1024 * 1024;

      if (($_FILES['image_file']['size'] ?? 0) > $maxBytes) {
        $errors[] = 'Файл слишком большой.';
      }

      // ---------- ФОТО ОРИГИНАЛЬНОЙ ЗАПИСИ ТАТЬЯНЫ ----------

if (!empty($_POST['remove_original_note_image']) && $currentOriginalNoteImage) {
  $oldPath = __DIR__ . '/..' . $currentOriginalNoteImage;
  if (is_file($oldPath)) {
    @unlink($oldPath);
  }
  $originalNoteImage = '';
}

if (!empty($_FILES['original_note_image']) && $_FILES['original_note_image']['error'] !== UPLOAD_ERR_NO_FILE) {

  if ($_FILES['original_note_image']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Ошибка загрузки фото оригинальной записи.';
  } else {
    $tmp  = $_FILES['original_note_image']['tmp_name'];
    $name = $_FILES['original_note_image']['name'];

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allowed, true)) {
      $errors[] = 'Фото записи: разрешены только jpg/jpeg/png/webp.';
    }

    $maxBytes = 5 * 1024 * 1024;

    if (($_FILES['original_note_image']['size'] ?? 0) > $maxBytes) {
      $errors[] = 'Фото записи слишком большое.';
    }

    if (!$errors) {
      $notesDirFs = __DIR__ . '/../img/notes/';

      if (!is_dir($notesDirFs)) {
        $errors[] = 'Папка img/notes не найдена.';
      } else {
        if ($currentOriginalNoteImage) {
          $oldPath = __DIR__ . '/..' . $currentOriginalNoteImage;
          if (is_file($oldPath)) {
            @unlink($oldPath);
          }
        }

        $fileName = date('Ymd-His') . '-note-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetFs = $notesDirFs . $fileName;

        if (!move_uploaded_file($tmp, $targetFs)) {
          $errors[] = 'Не удалось сохранить фото оригинальной записи.';
        } else {
          $originalNoteImage = '/img/notes/' . $fileName;
        }
      }
    }
  }
}

      if (!$errors) {

        $uploadDirFs = __DIR__ . '/../img/uploads/';
        if (!is_dir($uploadDirFs)) {
          $errors[] = 'Папка img/uploads не найдена.';
        } else {
          $tmpOriginal = $uploadDirFs . 'tmp-' . bin2hex(random_bytes(4)) . '.' . $ext;

          if (!move_uploaded_file($tmp, $tmpOriginal)) {
            $errors[] = 'Не удалось сохранить файл.';
          } else {

            if ($currentImage) {
              $base = pathinfo($currentImage, PATHINFO_FILENAME);

              @unlink($uploadDirFs . $base . '.jpg');
              @unlink($uploadDirFs . $base . '.webp');
              @unlink($uploadDirFs . $base . '_preview.jpg');
              @unlink($uploadDirFs . $base . '_preview.webp');
              @unlink($uploadDirFs . $base . '_gallery.jpg');
              @unlink($uploadDirFs . $base . '_gallery.webp');
            }

            create_recipe_images($tmpOriginal, $id);

            $imagePath = '/img/uploads/' . $id . '.jpg';

            @unlink($tmpOriginal);
          }
        }
      }
    }
  }

  if (!$errors) {

    $new = [
      'title' => $title,
      'time'  => $time,
      'level' => $level,
      'type'  => $type,
      'speed' => $speed,
      'image' => $imagePath,

      'source' => $sourceLabel !== '' ? ['label' => $sourceLabel] : [],

      'image_note' => $imageNote,
      'original_note_image' => $originalNoteImage,
      'intro' => $intro,

      'reconstruction_confidence' => $reconstructionConfidence,
      'notes' => $notes,

      'ingredients' => $ingredients,
      'steps' => $steps,
    ];

    // сохраняем старую meta-информацию, если она была
    if (!empty($recipe['_meta']) && is_array($recipe['_meta'])) {
      $new['_meta'] = $recipe['_meta'];
    }

    $new['_meta']['updated_at'] = date('c');

    $content = "<?php\nreturn " . php_array_export($new) . ";\n";

    $ok = file_put_contents($filePath, $content, LOCK_EX);

    if ($ok === false) {
      $errors[] = 'Не удалось сохранить файл рецепта.';
    } else {
      $success = 'Изменения сохранены.';
      $recipe = $new;
      $currentImage = $imagePath;
      $currentOriginalNoteImage = $originalNoteImage;
    }
  }
}

// ---------- значения для формы ----------

$minutesValue = 0;
if (!empty($recipe['time'])) {
  if (preg_match('~(\d+)~', $recipe['time'], $m)) {
    $minutesValue = (int)$m[1];
  }
}

$title = $recipe['title'] ?? '';
$type = $recipe['type'] ?? '';
$level = $recipe['level'] ?? '';
$intro = $recipe['intro'] ?? '';
$imageNote = $recipe['image_note'] ?? '';
$reconstructionConfidence = $recipe['reconstruction_confidence'] ?? '';

$sourceLabel = '';
if (!empty($recipe['source'])) {
  if (is_array($recipe['source'])) {
    $sourceLabel = $recipe['source']['label'] ?? '';
  } else {
    $sourceLabel = (string)$recipe['source'];
  }
}

$ingredientsText = !empty($recipe['ingredients']) ? implode("\n", (array)$recipe['ingredients']) : '';
$stepsText = !empty($recipe['steps']) ? implode("\n", (array)$recipe['steps']) : '';
$notesText = !empty($recipe['notes']) ? implode("\n", (array)$recipe['notes']) : '';
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Редактировать рецепт — Админ</title>

  <style>
    body{font-family:Arial,sans-serif;max-width:900px;margin:24px auto;padding:0 16px}
    label{display:block;margin:14px 0 6px;font-weight:700}
    input,select,textarea,button{width:100%;font-size:16px;padding:10px;box-sizing:border-box}
    textarea{min-height:120px;font-family:inherit}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .note{font-size:13px;color:#666;margin-top:6px}
    .ok{background:#e9ffe9;border:1px solid #b9e6b9;padding:12px;margin:12px 0;border-radius:8px}
    .err{background:#ffe9e9;border:1px solid #e6b9b9;padding:12px;margin:12px 0;border-radius:8px}
    .top{display:flex;gap:10px;flex-wrap:wrap;margin:12px 0 18px}
    .top a{display:inline-block;padding:10px 12px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#000}
    .top a:hover{background:#fafafa}
    img.preview{max-width:260px;height:auto;border:1px solid #ddd;border-radius:8px;margin:8px 0}
    button{margin-top:18px;cursor:pointer}
  </style>
</head>
<body>

<div class="top">
  <a href="/admin/recipes.php">← Рецепты</a>
  <a href="/admin/pending.php">Модерация</a>
  <a href="/html/recipe.php?id=<?= h($id) ?>" target="_blank">Открыть на сайте</a>
</div>

<h2>Редактировать рецепт</h2>
<div class="note">Админское редактирование опубликованного рецепта: <code><?= h($id) ?></code></div>

<?php if ($success): ?>
  <div class="ok"><b><?= h($success) ?></b></div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="err">
    <b>Ошибки:</b>
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?= h($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" class="recipe-form" enctype="multipart/form-data">

  <label>Название *</label>
  <input name="title" required value="<?= h($title) ?>">

  <div class="grid">
    <div>
      <label>Время приготовления (минуты) *</label>
      <input type="number" name="minutes" min="1" required value="<?= h((string)$minutesValue) ?>">
    </div>

    <div>
      <label>Type *</label>
      <select name="type" required>
        <?php
          $opts = ['salting','fish','baking','dessert','soup','meat','other'];
          foreach ($opts as $opt) {
            $sel = ($type === $opt) ? 'selected' : '';
            echo '<option value="' . h($opt) . '" ' . $sel . '>' . h($opt) . '</option>';
          }
        ?>
      </select>
    </div>
  </div>

  <label>Level</label>
  <select name="level">
    <?php
      foreach (['','Easy','Medium','Hard'] as $opt) {
        $sel = ($level === $opt) ? 'selected' : '';
        $label = $opt === '' ? '—' : $opt;
        echo '<option value="' . h($opt) . '" ' . $sel . '>' . h($label) . '</option>';
      }
    ?>
  </select>

  <label>Фото</label>

  <?php if (!empty($currentImage)): ?>
    <img class="preview" src="<?= h($currentImage) ?>" alt="">
    <label style="font-weight:400">
      <input type="checkbox" name="remove_image" value="1" style="width:auto">
      Удалить текущее фото
    </label>
  <?php else: ?>
    <div class="note">Фото пока нет.</div>
  <?php endif; ?>

  <label>Заменить фото</label>
  <input type="file" name="image_file" accept="image/*">

  <label>Источник рецепта / подпись источника (опционально)</label>
  <select name="source_label">
    <?php
      $sources = [
        '' => '— не указывать —',
        'Восстановлено по записи Татьяны' => 'Восстановлено по записи Татьяны',
        'Семейный рецепт' => 'Семейный рецепт',
        'YouTube' => 'YouTube',
        'Книга' => 'Книга',
        'Сайт' => 'Сайт',
        'Фото из интернета' => 'Фото из интернета',
        'Другое' => 'Другое',
      ];

      foreach ($sources as $value => $label) {
        $sel = ($sourceLabel === $value) ? 'selected' : '';
        echo '<option value="' . h($value) . '" ' . $sel . '>' . h($label) . '</option>';
      }
    ?>
  </select>

  <label>Пометка к фото (опционально)</label>
  <textarea name="image_note" placeholder="Например: Фото иллюстративное. Оригинальное фото блюда не сохранилось."><?= h($imageNote) ?></textarea>

  <label>Фото оригинальной записи Татьяны (опционально)</label>

<?php if (!empty($currentOriginalNoteImage)): ?>
  <img class="preview" src="<?= h($currentOriginalNoteImage) ?>" alt="Оригинальная запись Татьяны">

  <label style="font-weight:400">
    <input type="checkbox" name="remove_original_note_image" value="1" style="width:auto">
    Удалить текущее фото оригинальной записи
  </label>
<?php else: ?>
  <div class="note">Фото оригинальной записи пока не загружено.</div>
<?php endif; ?>

<input type="file" name="original_note_image" accept="image/*">
<div class="note">Можно загрузить фото листочка, тетради или другой записи, по которой восстановлен рецепт.</div>

  <label>Краткое описание (опционально)</label>
  <textarea name="intro"><?= h($intro) ?></textarea>

  <label>Уровень уверенности восстановления (опционально)</label>
  <select name="reconstruction_confidence">
    <?php
      $confidenceOptions = [
        '' => '— не указывать —',
        'high' => 'Высокий',
        'medium' => 'Средний',
        'low' => 'Низкий',
      ];

      foreach ($confidenceOptions as $value => $label) {
        $sel = ($reconstructionConfidence === $value) ? 'selected' : '';
        echo '<option value="' . h($value) . '" ' . $sel . '>' . h($label) . '</option>';
      }
    ?>
  </select>

  <label>Примечания (опционально, каждое с новой строки)</label>
  <textarea name="notes" placeholder="Например: Температура 170–180°C указана как предположение, так как в записи её нет."><?= h($notesText) ?></textarea>

  <label>Ингредиенты (каждый с новой строки)</label>
  <textarea name="ingredients"><?= h($ingredientsText) ?></textarea>

  <label>Шаги приготовления (каждый с новой строки)</label>
  <textarea name="steps"><?= h($stepsText) ?></textarea>

  <button type="submit" name="save_recipe" value="1">Сохранить изменения</button>

</form>

</body>
</html>