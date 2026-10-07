<?php
require_once __DIR__ . '/bootstrap.php';
require_admin();

$pdo = get_pdo();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'create') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $error = 'Nazwa działu jest wymagana.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO groups_ (id, name) VALUES (?, ?)');
        $stmt->execute([uuidv4(), $name]);
        header('Location: admin_groups.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'delete') {
    $id = $_POST['id'] ?? '';
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE group_id = ?');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        $error = 'Nie można usunąć działu, który ma przypisanych użytkowników.';
    } else {
        $stmt = $pdo->prepare('DELETE FROM groups_ WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: admin_groups.php');
        exit;
    }
}

$groups = $pdo->query('SELECT * FROM groups_ ORDER BY name')->fetchAll();

$pageTitle = 'Działy - Planer urlopów';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Działy (grupy)</h1>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <table>
    <thead><tr><th>Nazwa</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($groups as $g): ?>
        <tr>
          <td><?= e($g['name']) ?></td>
          <td>
            <form method="POST" action="admin_groups.php" style="margin:0;"
                  onsubmit="return confirm('Usunąć dział &quot;<?= e($g['name']) ?>&quot;?');">
              <input type="hidden" name="form_action" value="delete" />
              <input type="hidden" name="id" value="<?= e($g['id']) ?>" />
              <button class="btn danger" type="submit">Usuń</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Dodaj dział</h2>
  <form method="POST" action="admin_groups.php">
    <input type="hidden" name="form_action" value="create" />
    <div class="form-row">
      <label for="name">Nazwa działu</label>
      <input type="text" id="name" name="name" required />
    </div>
    <button class="btn" type="submit">Dodaj</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
