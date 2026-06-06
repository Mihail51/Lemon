<?php
require __DIR__ . '/../_init.php';

$userEmail = $_SESSION['user_email'] ?? '';
if ($userEmail === '') {
  header('Location: /login.php');
  exit;
}

function save_php_array($file, $arr){
  $content = "<?php\nreturn " . var_export($arr, true) . ";\n";
  file_put_contents($file, $content, LOCK_EX);
}

$slug = trim($_GET['slug'] ?? '');
$slug = strtolower($slug);
$slug = preg_replace('~[^a-z0-9\-]~', '', $slug);

$publishedFile = __DIR__ . '/../content/recipes/' . $slug . '.php';
if (!$slug || !is_file($publishedFile)) {
  http_response_code(404);
  echo "Recipe not found";
  exit;
}

$recipe = require $publishedFile;

// проверка авторства
$author = $recipe['_meta']['author_email'] ?? '';
if ($author !== $userEmail) {
  http_response_code(403);
  echo "Forbidden";
  exit;
}

// создаём pending-копию (обновление)
$pendingDir = __DIR__ . '/../content/pending/';
if (!is_dir($pendingDir)) mkdir($pendingDir, 0775, true);

$pendingFile = $pendingDir . $slug . '.php';

// meta: помечаем как update
$recipe['_meta']['status'] = 'pending_update';
$recipe['_meta']['updated_at'] = date('c');
$recipe['_meta']['original_slug'] = $slug;

// сохраняем в pending (перезаписываем, если уже есть — это нормально)
save_php_array($pendingFile, $recipe);

// ведём автора в наш edit.php (который умеет редактировать pending)
header('Location: /submit/edit.php?slug=' . urlencode($slug));
exit;