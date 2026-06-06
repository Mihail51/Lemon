<?php
require __DIR__ . '/../_init.php';

$channelsDir = __DIR__ . '/../content/channels/';
$files = glob($channelsDir . '*.php') ?: [];

$channels = [];

foreach ($files as $file) {
  $slug = basename($file, '.php');
  $channel = require $file;

  if (!is_array($channel)) continue;

  $channels[] = [
    'slug' => $slug,
    'title' => $channel['title'] ?? $slug,
    'handle' => $channel['handle'] ?? '',
    'url' => $channel['url'] ?? '',
    'subscribers' => $channel['subscribers'] ?? '',
    'category' => $channel['category'] ?? '',
    'comment' => $channel['comment'] ?? '',
    'origin' => $channel['origin'] ?? '',
  ];
}

usort($channels, function($a, $b) {
  return strcasecmp($a['title'], $b['title']);
});

function h($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Кулинарные источники</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="/css/header.css">
  <link rel="stylesheet" href="/css/footer.css">

  <style>
    body{
      font-family: Arial, sans-serif;
      background:#f8f8f8;
      margin:0;
    }

    .channels-page{
      max-width:1120px;
      margin:40px auto 80px;
      padding:0 20px;
    }

    .channels-title{
      text-align:center;
      font-size:42px;
      margin-bottom:10px;
    }

    .channels-subtitle{
      text-align:center;
      color:#666;
      margin-bottom:35px;
    }

    .channels-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));
      gap:20px;
    }

    .channel-card{
      background:#fff;
      border-radius:12px;
      box-shadow:0 0 12px rgba(0,0,0,0.08);
      padding:20px;
    }

    .channel-card h3{
      margin:0 0 8px;
      font-size:22px;
      color:#055555;
    }

    .channel-meta{
      color:#777;
      font-size:14px;
      margin-bottom:12px;
      line-height:1.4;
    }

    .channel-card a{
      color:#055555;
      text-decoration:none;
      font-weight:bold;
    }

    .channel-card a:hover{
      text-decoration:underline;
    }

    .channel-actions{
      margin-top:14px;
      display:flex;
      gap:10px;
      flex-wrap:wrap;
    }

    .channel-button{
      display:inline-block;
      padding:8px 12px;
      border:1px solid #ddd;
      border-radius:8px;
      text-decoration:none;
      color:#000;
      font-size:14px;
    }

    .channel-button:hover{
      background:#f3f3f3;
      text-decoration:none;
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/../partials/header.php'; ?>

<main class="channels-page">
  <h1 class="channels-title">Кулинарные источники</h1>

  <div class="channels-subtitle">
    YouTube-каналы из подписок Татьяны, сохранённые для кулинарной библиотеки Lemon.
  </div>

  <div class="channels-grid">
    <?php foreach ($channels as $channel): ?>
      <article class="channel-card">
        <h3><?= h($channel['title']) ?></h3>

        <div class="channel-meta">
          <?php if ($channel['handle']): ?>
            <?= h($channel['handle']) ?><br>
          <?php endif; ?>

          <?php if ($channel['subscribers']): ?>
            <?= h($channel['subscribers']) ?><br>
          <?php endif; ?>

          <?php if ($channel['origin']): ?>
            <?= h($channel['origin']) ?>
          <?php endif; ?>
        </div>

        <div class="channel-actions">
          <a class="channel-button" href="/html/channel.php?id=<?= urlencode($channel['slug']) ?>">
            Открыть
          </a>

          <?php if ($channel['url']): ?>
            <a class="channel-button" href="<?= h($channel['url']) ?>" target="_blank">
              YouTube
            </a>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</main>

<?php include __DIR__ . '/../partials/footer.php'; ?>

</body>
</html>