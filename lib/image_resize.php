<?php

function _img_load_any(string $path)
{
  $data = @file_get_contents($path);
  if ($data === false) return false;
  $im = @imagecreatefromstring($data);
  return $im ?: false;
}

function _img_apply_watermark($dst, string $publicRoot): void
{
  $wmPath = rtrim($publicRoot, '/\\') . '/img/logo_header.png';
  if (!is_file($wmPath)) return; // watermark пока не задан — просто пропускаем

  $wm = @imagecreatefrompng($wmPath);
  if (!$wm) return;

  imagesavealpha($wm, true);

  $dstW = imagesx($dst);
  $dstH = imagesy($dst);

  $wmW = imagesx($wm);
  $wmH = imagesy($wm);

  // Масштаб watermark: ~18% ширины изображения (можно потом настроить)
  $targetW = $wmW;
  $targetH = $wmH;

  $wmRes = imagecreatetruecolor($targetW, $targetH);
  imagealphablending($wmRes, false);
  imagesavealpha($wmRes, true);
  $transparent = imagecolorallocatealpha($wmRes, 0, 0, 0, 127);
  imagefilledrectangle($wmRes, 0, 0, $targetW, $targetH, $transparent);

  imagecopyresampled($wmRes, $wm, 0,0,0,0, $targetW, $targetH, $wmW, $wmH);

  // Позиция: справа снизу с отступом
  $pad = (int) max(6, round($dstW * 0.015));
  $x = $dstW - $targetW - 30;
  $y = $dstH - $targetH - 30;

  imagealphablending($dst, true);
  imagecopy($dst, $wmRes, $x, $y, 0, 0, $targetW, $targetH);

  //imagedestroy($wmRes);
 // imagedestroy($wm);
}

/**
 * Создаёт изображения:
 * - base.jpg + base.webp
 * - base_preview.jpg + base_preview.webp
 * - base_gallery.jpg + base_gallery.webp
 *
 * $baseName — например: 'borsch' или 'borsch_g1'
 * Возвращает URL основного: /img/uploads/<baseName>.jpg
 */
function create_recipe_images(string $sourcePath, string $baseName): string
{
  $publicRoot = realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT'];
  $uploadDir  = rtrim($publicRoot, '/\\') . '/img/uploads/';

  if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0775, true);
  }

  $src = _img_load_any($sourcePath);
  if ($src === false) {
    return '';
  }

  $srcW = imagesx($src);
  $srcH = imagesy($src);

  // helper: ресайз с растяжением (как у вас сейчас)
  $make = function(int $w, int $h) use ($src, $srcW, $srcH) {
    $dst = imagecreatetruecolor($w, $h);
    imagealphablending($dst, true);
    imagecopyresampled($dst, $src, 0,0,0,0, $w,$h, $srcW,$srcH);
    return $dst;
  };

  // MAIN 1200x800
  $main = $make(1200, 800);
  _img_apply_watermark($main, $publicRoot);
  imagejpeg($main, $uploadDir . $baseName . '.jpg', 90);
  imagewebp($main, $uploadDir . $baseName . '.webp', 90);
  //imagedestroy($main);

  // PREVIEW 400x300
  $prev = $make(400, 300);
  _img_apply_watermark($prev, $publicRoot);
  imagejpeg($prev, $uploadDir . $baseName . '_preview.jpg', 90);
  imagewebp($prev, $uploadDir . $baseName . '_preview.webp', 90);
  //imagedestroy($prev);

  // GALLERY square 1200x1200
  $gal = $make(1200, 1200);
  _img_apply_watermark($gal, $publicRoot);
  imagejpeg($gal, $uploadDir . $baseName . '_gallery.jpg', 90);
  imagewebp($gal, $uploadDir . $baseName . '_gallery.webp', 90);
  //imagedestroy($gal);

  //imagedestroy($src);

  return '/img/uploads/' . $baseName . '.jpg';
}