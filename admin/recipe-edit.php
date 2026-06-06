<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../lib/image_resize.php';   // ← ДОБАВЛЕНО

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_recipe'])) {

  $title  = trim($_POST['title'] ?? '');
  $minutes = (int)($_POST['minutes'] ?? 0);
  $level  = trim($_POST['level'] ?? '');
  $type   = trim($_POST['type'] ?? '');
  $source = trim($_POST['source'] ?? '');
  $intro  = trim($_POST['intro'] ?? '');

  $ingredients = normalize_lines($_POST['ingredients'] ?? '');
  $steps       = normalize_lines($_POST['steps'] ?? '');

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
    @unlink($dir . $base . '_preview.jpg');
    @unlink($dir . $base . '_gallery.jpg');

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
        $errors[] = 'Разрешены только jpg jpeg png webp.';
      }

      $maxBytes = 3 * 1024 * 1024;

      if (($_FILES['image_file']['size'] ?? 0) > $maxBytes) {
        $errors[] = 'Файл слишком большой.';
      }

      if (!$errors) {

        $uploadDirFs = __DIR__ . '/../img/uploads/';

        $tmpOriginal = $uploadDirFs . 'tmp-' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (!move_uploaded_file($tmp, $tmpOriginal)) {
          $errors[] = 'Не удалось сохранить файл.';
        } else {

          if ($currentImage) {
            $base = pathinfo($currentImage, PATHINFO_FILENAME);

            @unlink($uploadDirFs . $base . '.jpg');
            @unlink($uploadDirFs . $base . '_preview.jpg');
            @unlink($uploadDirFs . $base . '_gallery.jpg');
          }

          create_recipe_images($tmpOriginal, $id);

          $imagePath = '/img/uploads/' . $id . '.jpg';

          @unlink($tmpOriginal);
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
      'source' => $source,
      'intro' => $intro,
      'ingredients' => $ingredients,
      'steps' => $steps,
    ];

    $content = "<?php\nreturn " . php_array_export($new) . ";\n";

    $ok = file_put_contents($filePath, $content, LOCK_EX);

    if ($ok === false) {
      $errors[] = 'Не удалось сохранить файл рецепта.';
    } else {
      $success = 'Изменения сохранены.';
      $recipe = $new;
      $currentImage = $imagePath;
    }
  }
}

$minutesValue = 0;

if (!empty($recipe['time'])) {
  if (preg_match('~(\d+)~', $recipe['time'], $m)) {
    $minutesValue = (int)$m[1];
  }
}
?>