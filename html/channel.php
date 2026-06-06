<?php
require __DIR__ . '/../_init.php';

function h($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$id = trim($_GET['id'] ?? '');
$id = strtolower($id);
$id = preg_replace('~[^a-z0-9\-]~', '', $id);

$file = __DIR__ . '/../content/channels/' . $id . '.php';

if ($id === '' || !is_file($file)) {
  http_response_code(404);
  echo 'Channel not found';
  exit;
}

$channel = require $file;

$title = $channel['title'] ?? $id;
$handle = $channel['handle'] ?? '';
$url = $channel['url'] ?? '';
$subscribers = $channel['subscribers'] ?? '';
$category = $channel['category'] ?? '';
$comment = $channel['comment'] ?? '';
$origin = $channel['origin'] ?? '';
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title><?= h($title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="/css/header.css">
  <link rel="stylesheet" href="/css/footer.css">

  <style>
    body{
      font-family: Arial, sans-serif;
      background:#f8f8f8;
      margin:0;
    }

    .channel-page{
      max-width:900px;
      margin:40px auto 80px;
      padding:0 20px;
    }

    .channel-box{
      background:#fff;
      border-radius:14px;
      box-shadow:0 0 12px rgba(0,0,0,0.08);
      padding:30px;
    }

    .channel-box h1{
      margin:0 0 10px;
      font-size:40px;
      color:#055555;
    }

    .channel-meta{
      color:#666;
      line-height:1.6;
      margin-bottom:24px;
    }

    .channel-actions{
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      margin-top:24px;
    }

    .channel-button{
      display:inline-block;
      padding:10px 14px;
      border:1px solid #ddd;
      border-radius:8px;
      text-decoration:none;
      color:#000;
      background:#fff;
    }

    .channel-button:hover{
      background:#f3f3f3;
    }

    .channel-note{
      margin-top:26px;
      padding:16px;
      background:#f6f6f6;
      border-radius:10px;
      color:#555;
      line-height:1.6;
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="channel-page">
  <div class="channel-box">
    <h1><?= h($title) ?></h1>

    <div class="channel-meta">
      <?php if ($handle): ?>
        <div><b>Канал:</b> <?= h($handle) ?></div>
      <?php endif; ?>

      <?php if ($subscribers): ?>
        <div><b>Подписчики:</b> <?= h($subscribers) ?></div>
      <?php endif; ?>

      <?php if ($category): ?>
        <div><b>Категория:</b> <?= h($category) ?></div>
      <?php endif; ?>

      <?php if ($origin): ?>
        <div><b>Источник:</b> <?= h($origin) ?></div>
      <?php endif; ?>
    </div>

    <?php if ($comment): ?>
      <div class="channel-note">
        <?= nl2br(h($comment)) ?>
      </div>
    <?php endif; ?>

    <div class="channel-actions">
      <a class="channel-button" href="/html/channels.php">← Все источники</a>

      <?php if ($url): ?>
        <a class="channel-button" href="<?= h($url) ?>" target="_blank">
          Открыть на YouTube
        </a>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>