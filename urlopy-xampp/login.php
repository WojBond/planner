<?php
require_once __DIR__ . '/bootstrap.php';

if (current_user()) {
    header('Location: calendar.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([strtolower($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        $error = 'Nieprawidlowy e-mail lub haslo.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        header('Location: calendar.php');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Logowanie - Planer urlopow</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<div class="login-wrap">
  <div class="card login-card">
    <h1>Planer urlopow</h1>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" action="login.php">
      <div class="form-row">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required autofocus />
      </div>
      <div class="form-row">
        <label for="password">Haslo</label>
        <input type="password" id="password" name="password" required />
      </div>
      <button class="btn" type="submit" style="width:100%;">Zaloguj</button>
    </form>
    <p class="muted" style="margin-top:14px;">
      Domyslne konto administratora: <code><?= e(DEFAULT_ADMIN_EMAIL) ?></code> / <code><?= e(DEFAULT_ADMIN_PASSWORD) ?></code>
    </p>
    <p class="muted" style="margin-top:6px; font-size:0.75rem;">wersja <?= e(APP_VERSION) ?></p>
  </div>
</div>
</body>
</html>
