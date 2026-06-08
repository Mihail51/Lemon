<?php
require __DIR__ . '/../_init.php';

$userEmail = $_SESSION['user_email'] ?? '';

if ($userEmail === '') {
  header('Location: /login.php');
  exit;
}
// submit/recipe.php — публичная форма "Предложить рецепт" (уходит в pending)

function slugify(string $str): string {
  $str = trim($str);

  if (function_exists('mb_strtolower')) {
    $str = mb_strtolower($str, 'UTF-8');
  } else {
    $str = strtolower($str);
  }

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

$errors = [];
$success = '';

$title = '';
$minutes = '';
$type = '';
$intro = '';
$sourceLabel = '';
$imageNote = '';
$confidence = '';
$notesText = '';
$ingredientsText = '';
$stepsText = '';
$authorName = '';
$authorContact = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title  = trim($_POST['title'] ?? '');
  $minutes = (int)($_POST['minutes'] ?? 0);
  $type   = trim($_POST['type'] ?? '');
  $intro  = trim($_POST['intro'] ?? '');
  $sourceLabel = trim($_POST['source_label'] ?? '');
  $imageNote = trim($_POST['image_note'] ?? '');
  $confidence = trim($_POST['reconstruction_confidence'] ?? '');
  $notesText = trim($_POST['notes'] ?? '');
  $ingredientsText = trim($_POST['ingredients'] ?? '');
  $stepsText = trim($_POST['steps'] ?? '');
  $authorName = trim($_POST['author_name'] ?? '');
  $authorContact = trim($_POST['author_contact'] ?? '');

  if ($title === '') $errors[] = 'Введите название рецепта.';
  if ($minutes <= 0) $errors[] = 'Укажите время приготовления (минуты).';
  if ($type === '') $errors[] = 'Выберите тип.';
  if ($ingredientsText === '') $errors[] = 'Добавьте ингредиенты.';
  if ($stepsText === '') $errors[] = 'Добавьте шаги приготовления.';

  // speed автоматически
  if ($minutes <= 15) $speed = 'quick';
  elseif ($minutes <= 40) $speed = 'middle';
  else $speed = 'long';

  $time = $minutes . ' mins';

  $ingredients = normalize_lines($ingredientsText);
  $steps       = normalize_lines($stepsText);
  $notes       = normalize_lines($notesText);

  // фото (опционально)
  $imagePath = null;
  if (!empty($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Ошибка загрузки изображения.';
    } else {
      $tmp  = $_FILES['image_file']['tmp_name'];
      $name = $_FILES['image_file']['name'];
      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png','webp'];
      if (!in_array($ext, $allowed, true)) $errors[] = 'Разрешены только jpg, jpeg, png, webp.';
      $maxBytes = 3 * 1024 * 1024;
      if (($_FILES['image_file']['size'] ?? 0) > $maxBytes) $errors[] = 'Файл слишком большой (максимум 3 MB).';

      if (!$errors) {
        $uploadDirFs = __DIR__ . '/../img/uploads/';
        if (!is_dir($uploadDirFs)) {
          $errors[] = 'Папка /img/uploads/ не найдена.';
        } else {
          $unique = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
          $fileName = $unique . '.' . $ext;
          $targetFs = $uploadDirFs . $fileName;

          if (!move_uploaded_file($tmp, $targetFs)) {
            $errors[] = 'Не удалось сохранить изображение.';
          } else {
            $imagePath = '/img/uploads/' . $fileName;
          }
        }
      }
    }
  }

  if (!$errors) {
    $slugBase = slugify($title);
    $pendingDir = __DIR__ . '/../content/pending/';
    if (!is_dir($pendingDir)) {
      $errors[] = 'Папка content/pending не найдена (создай её).';
    } else {
      // уникальный slug в pending
      $slug = $slugBase;
      $i = 2;
      while (is_file($pendingDir . $slug . '.php') || is_file(__DIR__ . '/../content/recipes/' . $slug . '.php')) {
        $slug = $slugBase . '-' . $i;
        $i++;
      }

      $authorEmail = $_SESSION['user_email'] ?? $authorContact ?? '';

      $recipe = [
        'title' => $title,
        'time'  => $time,
        'level' => '',       // посетителю пока не даём сложность
        'type'  => $type,
        'speed' => $speed,
        'image' => $imagePath,

        // источник рецепта
        'source' => $sourceLabel !== '' ? ['label' => $sourceLabel] : [],

        // пометка к фото
        'image_note' => $imageNote,

        'intro' => $intro,

        // для восстановленных рецептов
        'reconstruction_confidence' => $confidence,
        'notes' => $notes,

        'ingredients' => $ingredients,
        'steps' => $steps,

        // мета для модерации
        '_meta' => [
          'status' => 'pending',
          'created_at' => date('c'),
          'author_email' => $authorEmail,
          'author_name' => $authorName,
        ],
      ];

      $content = "<?php\nreturn " . var_export($recipe, true) . ";\n";
      $ok = file_put_contents($pendingDir . $slug . '.php', $content, LOCK_EX);

      if ($ok === false) {
        $errors[] = 'Не удалось сохранить заявку.';
      } else {
        $success = 'Спасибо! Рецепт отправлен на модерацию.';
        // очистим поля
        $title = $intro = $sourceLabel = $imageNote = $confidence = $notesText = $ingredientsText = $stepsText = $authorName = $authorContact = '';
        $minutes = '';
        $type = '';
      }
    }
  }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Предложить рецепт</title>
  <style>
    body{font-family:Arial,sans-serif;max-width:820px;margin:24px auto;padding:0 16px}
    label{display:block;margin:14px 0 6px;font-weight:700}
    input,select,textarea,button{width:100%;font-size:16px;padding:10px;box-sizing:border-box}
    textarea{min-height:120px;font-family:inherit}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .note{font-size:13px;color:#666;margin-top:6px}
    .ok{background:#e9ffe9;border:1px solid #b9e6b9;padding:12px;margin:12px 0}
    .err{background:#ffe9e9;border:1px solid #e6b9b9;padding:12px;margin:12px 0}
  </style>
</head>
<body>

<h2>Предложить рецепт</h2>
<div class="note">Рецепт появится на сайте после проверки (модерации).</div>

<?php if ($success): ?>
  <div class="ok"><b><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></b></div>
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
  <label>Название *</label>
  <input name="title" required value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">

  <div class="grid">
    <div>
      <label>Время приготовления (минуты) *</label>
      <input type="number" name="minutes" min="1" required value="<?= htmlspecialchars((string)$minutes, ENT_QUOTES, 'UTF-8') ?>">
      <div class="note">Скорость (quick/middle/long) определится автоматически.</div>
    </div>

    <div>
      <label>Тип *</label>
      <select name="type" required>
        <?php
          $opts = ['salting','fish','baking','dessert','soup','meat','other'];
          foreach ($opts as $opt) {
            $sel = ($type === $opt) ? 'selected' : '';
            echo "<option value=\"$opt\" $sel>$opt</option>";
          }
        ?>
      </select>
    </div>
  </div>

  <label>Фото (опционально)</label>
  <input type="file" name="image_file" accept="image/*">
  <div class="note">Можно перетащить или выбрать файл (jpg/png/webp).</div>

  <label>Короткое описание (опционально)</label>
  <textarea name="intro"><?= htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') ?></textarea>

  <label>Источник рецепта (опционально)</label>
  <select name="source_label">
    <?php
      $sources = [
        '' => '— не указывать —',
        'Восстановлено по записи Татьяны' => 'Восстановлено по записи Татьяны',
        'Семейный рецепт' => 'Семейный рецепт',
        'YouTube' => 'YouTube',
        'Книга' => 'Книга',
        'Сайт' => 'Сайт',
        'Другое' => 'Другое',
      ];

      foreach ($sources as $value => $label) {
        $sel = ($sourceLabel === $value) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" ' . $sel . '>' .
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8') .
            '</option>';
      }
    ?>
  </select>

  <label>Пометка к фото (опционально)</label>
  <textarea name="image_note" placeholder="Например: Фото иллюстративное. Оригинальное фото блюда не сохранилось."><?= htmlspecialchars($imageNote, ENT_QUOTES, 'UTF-8') ?></textarea>

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
        $sel = ($confidence === $value) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '" ' . $sel . '>' .
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8') .
            '</option>';
      }
    ?>
  </select>

  <label>Примечания (опционально, каждое с новой строки)</label>
  <textarea name="notes" placeholder="Например: Температура 170–180°C указана как предположение, так как в записи её нет."><?= htmlspecialchars($notesText, ENT_QUOTES, 'UTF-8') ?></textarea>

  <label>Ингредиенты (каждый с новой строки) *</label>
  <textarea name="ingredients" required><?= htmlspecialchars($ingredientsText, ENT_QUOTES, 'UTF-8') ?></textarea>

  <label>Шаги приготовления (каждый с новой строки) *</label>
  <textarea name="steps" required><?= htmlspecialchars($stepsText, ENT_QUOTES, 'UTF-8') ?></textarea>

  <div class="grid">
    <div>
      <label>Ваше имя (опционально)</label>
      <input name="author_name" value="<?= htmlspecialchars($authorName, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label>Контакт (опционально)</label>
      <input name="author_contact" value="<?= htmlspecialchars($authorContact, ENT_QUOTES, 'UTF-8') ?>">
      <div class="note">Например email или телефон (если хотите, чтобы мы могли уточнить детали).</div>
    </div>
  </div>

  <button type="submit">Отправить на модерацию</button>
</form>

</body>
</html>