<?php
require_once __DIR__ . '/bootstrap.php';
$currentAdmin = require_admin();

$pdo = get_pdo();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        $groupId = $_POST['groupId'] ?? '';
        $color = $_POST['color'] ?? '';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#2563eb';

        if ($name === '' || $email === '' || $password === '' || $groupId === '') {
            $error = 'Wypelnij wszystkie wymagane pola.';
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ((int)$stmt->fetchColumn() > 0) {
                $error = 'Uzytkownik z tym adresem e-mail juz istnieje.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (id, name, email, password_hash, role, group_id, color, must_change_password) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->execute([uuidv4(), $name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $groupId, $color]);
                header('Location: admin_users.php');
                exit;
            }
        }
    }

    if ($formAction === 'update') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
        $groupId = $_POST['groupId'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($password !== '') {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, role = ?, group_id = ?, password_hash = ?, must_change_password = 1 WHERE id = ?');
            $stmt->execute([$name, $role, $groupId, password_hash($password, PASSWORD_DEFAULT), $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, role = ?, group_id = ? WHERE id = ?');
            $stmt->execute([$name, $role, $groupId, $id]);
        }
        header('Location: admin_users.php');
        exit;
    }

    if ($formAction === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($id === $currentAdmin['id']) {
            $error = 'Nie mozna usunac wlasnego konta.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            header('Location: admin_users.php');
            exit;
        }
    }
}

$users = $pdo->query('
    SELECT u.*, g.name AS group_name
    FROM users u
    LEFT JOIN groups_ g ON g.id = u.group_id
    ORDER BY u.name
')->fetchAll();

$groups = $pdo->query('SELECT * FROM groups_ ORDER BY name')->fetchAll();

$pageTitle = 'Uzytkownicy - Planer urlopow';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Uzytkownicy</h1>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <table>
    <thead>
      <tr><th>Kolor</th><th>Imie i nazwisko</th><th>E-mail</th><th>Dzial</th><th>Rola</th><th>Akcje</th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><span class="color-dot" style="background: <?= e($u['color']) ?>"></span></td>
          <td><?= e($u['name']) ?></td>
          <td><?= e($u['email']) ?></td>
          <td><?= e($u['group_name'] ?? '-') ?></td>
          <td><?= $u['role'] === 'admin' ? 'Administrator' : 'Uzytkownik' ?></td>
          <td>
            <details>
              <summary class="muted" style="cursor:pointer;">Edytuj</summary>
              <form method="POST" action="admin_users.php" style="margin-top:8px;">
                <input type="hidden" name="form_action" value="update" />
                <input type="hidden" name="id" value="<?= e($u['id']) ?>" />
                <div class="form-row">
                  <label>Imie i nazwisko</label>
                  <input type="text" name="name" value="<?= e($u['name']) ?>" />
                </div>
                <div class="form-row">
                  <label>Dzial</label>
                  <select name="groupId">
                    <?php foreach ($groups as $g): ?>
                      <option value="<?= e($g['id']) ?>" <?= $g['id'] === $u['group_id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-row">
                  <label>Rola</label>
                  <select name="role">
                    <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>Uzytkownik</option>
                    <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                  </select>
                </div>
                <div class="form-row">
                  <label>Nowe haslo (opcjonalnie)</label>
                  <input type="password" name="password" placeholder="pozostaw puste, aby nie zmieniac" />
                </div>
                <button class="btn" type="submit">Zapisz</button>
              </form>
            </details>
            <form method="POST" action="admin_users.php" style="margin-top:8px;"
                  onsubmit="return confirm('Usunac uzytkownika <?= e($u['name']) ?>?');">
              <input type="hidden" name="form_action" value="delete" />
              <input type="hidden" name="id" value="<?= e($u['id']) ?>" />
              <button class="btn danger" type="submit">Usun</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Dodaj uzytkownika</h2>
  <form method="POST" action="admin_users.php">
    <input type="hidden" name="form_action" value="create" />
    <div class="form-row">
      <label for="name">Imie i nazwisko</label>
      <input type="text" id="name" name="name" required />
    </div>
    <div class="form-row">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" required />
    </div>
    <div class="form-row">
      <label for="password">Haslo startowe</label>
      <input type="password" id="password" name="password" required minlength="6" />
    </div>
    <div class="form-row">
      <label for="groupId">Dzial</label>
      <select id="groupId" name="groupId" required>
        <?php foreach ($groups as $g): ?>
          <option value="<?= e($g['id']) ?>"><?= e($g['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <label for="role">Rola</label>
      <select id="role" name="role">
        <option value="user">Uzytkownik</option>
        <option value="admin">Administrator</option>
      </select>
    </div>
    <div class="form-row">
      <label for="color">Kolor paska</label>
      <input type="color" id="color" name="color" value="#2563eb" />
    </div>
    <button class="btn" type="submit">Dodaj uzytkownika</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
