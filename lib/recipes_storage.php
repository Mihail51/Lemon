<?php
declare(strict_types=1);

function now_str(): string {
  return date('Y-m-d H:i:s');
}

function recipe_read(string $file): array {
  if (!is_file($file)) {
    throw new RuntimeException("Recipe file not found: $file");
  }
  $data = require $file;
  if (!is_array($data)) {
    throw new RuntimeException("Recipe file invalid (must return array): $file");
  }
  return $data;
}

function ensure_dir(string $dir): void {
  if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
    throw new RuntimeException("Cannot create dir: $dir");
  }
}

function recipe_write_atomic(string $targetFile, array $data): void {
  $dir = dirname($targetFile);
  ensure_dir($dir);

  $tmp = $targetFile . '.' . uniqid('tmp_', true);
  $php = "<?php\nreturn " . var_export($data, true) . ";\n";

  if (file_put_contents($tmp, $php, LOCK_EX) === false) {
    throw new RuntimeException("Cannot write temp file: $tmp");
  }

  // Windows: rename не заменяет существующий файл — удаляем заранее
  if (is_file($targetFile)) {
    if (!unlink($targetFile)) {
      @unlink($tmp);
      throw new RuntimeException("Cannot remove old target: $targetFile");
    }
  }

  if (!rename($tmp, $targetFile)) {
    @unlink($tmp);
    throw new RuntimeException("Cannot rename $tmp to $targetFile");
  }

  @chmod($targetFile, 0664);
}

function backup_published(string $publishedFile, string $historyDir, string $slug): ?string {
  if (!is_file($publishedFile)) return null;

  ensure_dir($historyDir);

  $stamp  = date('Ymd_His');
  $backup = rtrim($historyDir, '/\\') . "/{$slug}__{$stamp}.php";

  if (!copy($publishedFile, $backup)) {
    throw new RuntimeException("Cannot backup file to: $backup");
  }
  return $backup;
}

/**
 * Пытаемся понять: это локальный upload или внешний URL.
 * Возвращает относительный путь типа "uploads/recipes/xxx.jpg" или null.
 */
function recipe_extract_upload_relpath(?string $image): ?string {
  if (!$image) return null;

  $image = trim($image);

  // Внешний URL -> не трогаем
  if (preg_match('~^https?://~i', $image)) {
    return null;
  }

  // Поддержим "/uploads/..." и "uploads/..."
  $image = ltrim($image, '/');

  // Разрешаем удалять ТОЛЬКО из uploads/
  if (strpos($image, 'uploads/') !== 0) {
    return null;
  }

  return $image;
}

/**
 * Безопасно удаляет старый upload-файл, если он заменён.
 *
 * $publicRoot — путь к public_html (или корню сайта, где папка uploads)
 */
function recipe_delete_old_upload_if_changed(array $oldRecipe, array $newRecipe, string $publicRoot): void {

  $oldImage = $oldRecipe['image'] ?? '';
  $newImage = $newRecipe['image'] ?? '';

  if (!$oldImage) return;

  // Если внешний URL — не трогаем
  if (preg_match('~^https?://~i', $oldImage)) {
    return;
  }

  $oldRel = ltrim($oldImage, '/');

  // ✅ Разрешаем удаление только из img/uploads/
  if (strpos($oldRel, 'img/uploads/') !== 0) {
    return;
  }

  // Если не изменилось — не удаляем
  if (ltrim((string)$newImage, '/') === $oldRel) {
    return;
  }

  $publicRoot = rtrim($publicRoot, '/\\');

  $uploadsRoot = realpath($publicRoot . '/img/uploads');
  if ($uploadsRoot === false) return;

  $oldAbs = realpath($publicRoot . '/' . $oldRel);

  if ($oldAbs === false || !is_file($oldAbs)) return;

  // Защита: удаляем только внутри img/uploads
  if (strpos($oldAbs, $uploadsRoot) !== 0) return;

  @unlink($oldAbs);
}

function recipe_log_action(string $publicRoot, string $message): void {
  $logDir = $publicRoot . '/content';
  $logFile = $logDir . '/modlog.log';

  $line = date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;

  @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}