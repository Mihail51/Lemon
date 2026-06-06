<?php
session_start();

$codesFile = __DIR__ . '/content/users/_codes.php';
$codes = is_file($codesFile) ? require $codesFile : [];

function save_codes($file, $data) {
  $content = "<?php\nreturn " . var_export($data, true) . ";\n";
  file_put_contents($file, $content, LOCK_EX);
}

$step = 'email';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Шаг 1 — ввод email
  if (isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if ($email === '') {
      $error = 'Введите email или телефон.';
    } else {
      $code = random_int(100000, 999999);

      $codes[$email] = [
        'code' => $code,
        'created' => time()
      ];

      save_codes($codesFile, $codes);

      $step = 'code';
      $message = "Ваш код подтверждения: <b>$code</b>";
    }
  }

  // Шаг 2 — ввод кода
  if (isset($_POST['confirm_code'])) {
    $email = trim($_POST['confirm_email']);
    $entered = trim($_POST['confirm_code']);

    if (!isset($codes[$email])) {
      $error = 'Код не найден.';
    } elseif ($codes[$email]['code'] != $entered) {
      $error = 'Неверный код.';
    } else {
      $_SESSION['user_email'] = $email;
      unset($codes[$email]);
      save_codes($codesFile, $codes);
      header('Location: /');
      exit;
    }

    $step = 'code';
  }
}

?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<title>Вход</title>
<style>
body{font-family:Arial;max-width:420px;margin:60px auto}
input,button{width:100%;padding:10px;margin:8px 0;font-size:16px}
.note{color:#666;font-size:14px}
.error{color:red}
.message{color:green}
</style>
</head>
<body>

<h2>Вход для редактирования</h2>

<?php if ($error): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($message): ?>
  <div class="message"><?= $message ?></div>
<?php endif; ?>

<?php if ($step === 'email'): ?>

<form method="post">
  <input name="email" placeholder="Введите email или телефон">
  <button type="submit">Получить код</button>
</form>

<div class="note">
Код подтверждения сейчас отображается на экране.  
Позже будет отправляться на email.
</div>

<?php else: ?>

<form method="post">
  <input type="hidden" name="confirm_email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
  <input name="confirm_code" placeholder="Введите код">
  <button type="submit">Подтвердить</button>
</form>

<?php endif; ?>

</body>
</html>