<?php
// admin/_auth.php
// Общая защита для админки (session + пароль)

$ADMIN_PASSWORD = 'Gmb1151Gtv0951'; // <-- ЗАМЕНИ на свой пароль

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (isset($_POST['logout'])) {
  unset($_SESSION['admin_ok']);
  header('Location: ' . ($_SERVER['PHP_SELF'] ?? '/admin/recipe-new.php'));
  exit;
}

if (!isset($_SESSION['admin_ok'])) {
  if (isset($_POST['password']) && $_POST['password'] === $ADMIN_PASSWORD) {
    $_SESSION['admin_ok'] = true;
    header('Location: ' . ($_SERVER['PHP_SELF'] ?? '/admin/recipe-new.php'));
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
      .row{display:flex;gap:10px}
      .hint{font-size:13px;color:#666;margin-top:10px}
    </style>
  </head>
  <body>
    <h2>Вход в админку</h2>
    <form method="post">
      <div class="row">
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
      </div>
      <div class="hint">После входа можно открывать любые страницы в /admin/</div>
    </form>
  </body>
  </html>
  <?php
  exit;
}