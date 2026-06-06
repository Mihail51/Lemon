<?php
// admin/recipe-new.php
// Добавление рецепта (без БД). Сохраняет файл в /content/recipes/<slug>.php

require __DIR__ . '/_auth.php';
require __DIR__ . '/../lib/image_resize.php'; // ✅ генерация main/preview/gallery + webp (+ watermark если есть)

// --- ПРОСТАЯ ЗАЩИТА ---
if (!isset($_SESSION['admin_ok'])) {
  if (isset($_POST['password']) && $_POST['password'] === $ADMIN_PASSWORD) {
    $_SESSION['admin_ok'] = true;
    header('Location: recipe-new.php');
    exit;
  }
  ?>
  <!doctype html>
  <html lang="ru">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
      body{font-family:Arial,sans-serif;max-width:520px;margin:40px auto;padding:0 16px}
      input,button{font-size:16px;padding:10px}
      a{display:inline-block;padding:10px 12px;border:1px solid #ddd;border-radius:8px;text-decoration:none;color:#000}
      a:hover{background:#fafafa}
      .row{display:flex;gap:10px}
    </style>
  </head>
  <body>
    <h2>Вход</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin:12px 0 18px">
      <a href="/admin/recipes.php">Рецепты</a>
      <a href="/admin/recipe-new.php">Добавить</a>
      <a href="/admin/pending.php">Модерация</a>
    </div>
    <form method="post">
      <div class="row">
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
      </div>
    </form>
  </body>
  </html>
  <?php
  exit;
}

// --- HELPERS ---
function slugify(string $str): string {
  $str = trim($str);

  if (function_exists('mb_strtolower')) $str = mb_strtolower($str, 'UTF-8');
  else $str = strtolower($str);

  $map = [
    'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
    'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
    'х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    'А'=>'a','Б'=>'b','В'=>'v','Г'=>'g','Д'=>'d','Е'=>'e','Ё'=>'e','Ж'=>'zh','З'=>'z','И'=>'i','Й'=>'y',
    'К'=>'k','Л'=>'l','М'=>'m','Н'=>'n','О'=>'o','П'=>'p','Р'=>'r','С'=>'s','Т'=>'t','У'=>'u','Ф'=>'f',
    'Х'=>'h','Ц'=>'c','Ч'=>'ch','Ш'=>'sh','Щ'=>'sch','Ъ'=>'','Ы'=>'y','Ь'=>'','Э'=>'e','Ю'=>'yu','Я'=>'ya',
  ];
  $str = strtr($str, $map);

  $str = preg_replace('~[^a-z0-9]+~', '-', $str);
  $str = trim($str, '-');

  return $str ?: 'recipe';
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

// --- SAVE ---
$errors = [];
$success = '';
$createdSlug = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_recipe'])) {

  $title   = trim($_POST['title'] ?? '');
  $level   = trim($_POST['level'] ?? '');
  $type    = trim($_POST['type'] ?? '');
  $minutes = (int)($_POST['minutes'] ?? 0);
  $source  = trim($_POST['source'] ?? '');
  $intro   = trim($_POST['intro'] ?? '');

  $ingredientsText = $_POST['ingredients'] ?? '';
  $stepsText       = $_POST['steps'] ?? '';

  if ($title === '') $errors[] = 'Введите название рецепта.';
  if ($type === '')  $errors[] = 'Выберите type (тип).';
  if ($minutes <= 0) $errors[] = 'Укажите время приготовления.';

  if ($minutes <= 15) $speed = 'quick';
  elseif ($minutes <= 40) $speed = 'middle';
  else $speed = 'long';

  $time = $minutes . ' mins';

  $slug = slugify($title);
  $slug = preg_replace('~[^a-z0-9\-]~', '', strtolower($slug));
  $slug = trim($slug, '-');

  if ($slug === '') $errors[] = 'Не удалось создать slug.';
  if ($slug !== '' && $slug[0] === '_') $errors[] = 'Slug не должен начинаться с "_"';

  $ingredients = normalize_lines($ingredientsText);
  $steps       = normalize_lines($stepsText);

  $uploadDirFs = __DIR__ . '/../img/uploads/';
  if (!is_dir($uploadDirFs)) {
    $errors[] = 'Не найдена папка /img/uploads/. Создай public_html/img/uploads/.';
  }

  // ---------- MAIN IMAGE ----------
  $imagePath = null;

  if (!$errors && !empty($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Ошибка загрузки файла изображения.';
    } else {
      $tmp  = $_FILES['image_file']['tmp_name'];
      $name = $_FILES['image_file']['name'];

      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png','webp'];
      if (!in_array($ext, $allowed, true)) $errors[] = 'Разрешены только изображения: jpg, jpeg, png, webp.';

      $maxBytes = 3 * 1024 * 1024;
      if (($_FILES['image_file']['size'] ?? 0) > $maxBytes) $errors[] = 'Файл слишком большой (максимум 3 MB).';

      if (!$errors) {
        $tmpOriginal = $uploadDirFs . 'tmp-' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (!move_uploaded_file($tmp, $tmpOriginal)) {
          $errors[] = 'Не удалось сохранить изображение (проверь права).';
        } else {
          // ✅ генерим slug.jpg + slug_preview + slug_gallery (+ webp)
          create_recipe_images($tmpOriginal, $slug);
          $imagePath = '/img/uploads/' . $slug . '.jpg';
          @unlink($tmpOriginal);
        }
      }
    }
  }

  // ---------- GALLERY IMAGES (MULTIPLE) ----------
  $gallery = [];

  if (!$errors && !empty($_FILES['gallery_files']) && !empty($_FILES['gallery_files']['name']) && is_array($_FILES['gallery_files']['name'])) {

    $count = count($_FILES['gallery_files']['name']);
    for ($i = 0; $i < $count; $i++) {

      $err = $_FILES['gallery_files']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
      if ($err === UPLOAD_ERR_NO_FILE) continue;
      if ($err !== UPLOAD_ERR_OK) continue;

      $tmp  = $_FILES['gallery_files']['tmp_name'][$i] ?? '';
      $name = $_FILES['gallery_files']['name'][$i] ?? '';

      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png','webp'];
      if (!in_array($ext, $allowed, true)) continue;

      $maxBytes = 3 * 1024 * 1024;
      if (($_FILES['gallery_files']['size'][$i] ?? 0) > $maxBytes) continue;

      $tmpOriginal = $uploadDirFs . 'tmp-' . bin2hex(random_bytes(4)) . '.' . $ext;
      if (!move_uploaded_file($tmp, $tmpOriginal)) continue;

      $baseName = $slug . '_g' . (count($gallery) + 1);
      create_recipe_images($tmpOriginal, $baseName);
      $gallery[] = '/img/uploads/' . $baseName . '.jpg';

      @unlink($tmpOriginal);
    }
  }

  // ---------- SAVE RECIPE FILE ----------
  if (!$errors) {
    $recipe = [
      'title' => $title,
      'time'  => $time,
      'level' => $level,
      'type'  => $type,
      'speed' => $speed,
      'image' => $imagePath,
      'gallery' => $gallery,
      'source' => $source, // если пусто — не покажется
      'intro' => $intro,
      'ingredients' => $ingredients,
      'steps' => $steps,
    ];

    $content = "<?php\nreturn " . php_array_export($recipe) . ";\n";

    $targetDir  = __DIR__ . '/../content/recipes/';
    $targetFile = $targetDir . $slug . '.php';

    if (!is_dir($targetDir)) $errors[] = 'Не найдена папка content/recipes.';
    if (file_exists($targetFile)) $errors[] = "Файл уже существует: {$slug}.php";

    if (!$errors) {
      $ok = file_put_contents($targetFile, $content, LOCK_EX);
      if ($ok === false) {
        $errors[] = 'Не удалось сохранить файл (проверь права).';
      } else {
        $success = 'Рецепт сохранён!';
        $createdSlug = $slug;
      }
    }
  }
}

// --- UI ---
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Добавить рецепт</title>
  <style>
    body{font-family:Arial,sans-serif;max-width:980px;margin:24px auto;padding:0 16px}
    label{display:block;margin:14px 0 6px;font-weight:700}
    input,select,textarea,button{width:100%;font-size:16px;padding:10px;box-sizing:border-box}
    textarea{min-height:120px;font-family:inherit}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .note{font-size:13px;color:#666;margin-top:6px}
    .row{display:flex;gap:10px;align-items:center}
    .row button{width:auto}
    .ok{background:#e9ffe9;border:1px solid #b9e6b9;padding:12px;margin:12px 0}
    .err{background:#ffe9e9;border:1px solid #e6b9b9;padding:12px;margin:12px 0}
    a{color:#0a66c2}
  </style>
</head>
<body>

<div class="row" style="justify-content:space-between">
  <h2 style="margin:0">Добавить рецепт</h2>
  <form method="post" style="margin:0">
    <button type="submit" name="logout" value="1">Выйти</button>
  </form>
</div>

<?php if ($success): ?>
  <div class="ok">
    <b><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></b><br>
    Открыть рецепт:
    <a href="/html/recipe.php?id=<?= htmlspecialchars($createdSlug, ENT_QUOTES, 'UTF-8') ?>" target="_blank">
      /html/recipe.php?id=<?= htmlspecialchars($createdSlug, ENT_QUOTES, 'UTF-8') ?>
    </a><br>
    Открыть список:
    <a href="/html/recipees.php" target="_blank">/html/recipees.php</a>
  </div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="err">
    <b>Ошибки:</b>
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <div class="grid">
    <div>
      <label>Название *</label>
      <input name="title" required value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <div class="note">Например: Рыба по-мароккански</div>
    </div>

    <div>
      <label>Время приготовления (минуты) *</label>
      <input type="number" name="minutes" min="1" required value="<?= htmlspecialchars($_POST['minutes'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div>
      <label>Сложность</label>
      <select name="level">
        <?php
          $cur = $_POST['level'] ?? '';
          foreach (['','Easy','Medium','Hard'] as $opt) {
            $sel = ($cur === $opt) ? 'selected' : '';
            $label = $opt === '' ? '—' : $opt;
            echo "<option value=\"".htmlspecialchars($opt)."\" $sel>$label</option>";
          }
        ?>
      </select>
    </div>

    <div>
      <label>Type (тип) *</label>
      <select name="type" required>
        <?php
          $cur = $_POST['type'] ?? '';
          $opts = ['salting','fish','baking','dessert','soup','meat','other'];
          foreach ($opts as $opt) {
            $sel = ($cur === $opt) ? 'selected' : '';
            echo "<option value=\"$opt\" $sel>$opt</option>";
          }
        ?>
      </select>
    </div>

    <div>
      <label>Фото (главное)</label>
      <input type="file" name="image_file" accept="image/*">
      <div class="note">Выберите 1 фото. Система сделает main/preview/gallery + webp автоматически.</div>

      <label style="margin-top:10px">Галерея (несколько фото)</label>
      <input type="file" name="gallery_files[]" accept="image/*" multiple>
      <div class="note">Выберите несколько файлов — они станут галереей на странице рецепта.</div>
    </div>

    <div>
      <label>Источник фото (если не твоё)</label>
      <input name="source" value="<?= htmlspecialchars($_POST['source'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <div class="note">Например: Фото из интернета (если пусто — не показывается)</div>
    </div>
  </div>

  <label>Короткое описание (intro)</label>
  <textarea name="intro"><?= htmlspecialchars($_POST['intro'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

  <label>Ингредиенты (каждый с новой строки)</label>
  <textarea name="ingredients"><?= htmlspecialchars($_POST['ingredients'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

  <label>Шаги приготовления (каждый с новой строки)</label>
  <textarea name="steps"><?= htmlspecialchars($_POST['steps'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

  <div class="row" style="margin-top:14px">
    <button type="submit" name="save_recipe" value="1">Сохранить рецепт</button>
  </div>
</form>

</body>
</html>