<?php
require_once __DIR__ . '/bootstrap.php';
$currentAdmin = require_admin();

$pdo = get_pdo();
$error = null;

function valid_role($value) {
    return in_array($value, ['user', 'kierownik', 'admin'], true) ? $value : 'user';
}

function parse_days($value) {
    $value = str_replace(',', '.', trim((string) $value));
    return is_numeric($value) && (float) $value >= 0 ? (float) $value : 0;
}

function save_manager_groups(PDO $pdo, string $userId, string $role, array $groupIds) {
    $stmt = $pdo->prepare('DELETE FROM manager_groups WHERE user_id = ?');
    $stmt->execute([$userId]);
    if ($role !== 'kierownik' || empty($groupIds)) return;
    $stmt = $pdo->prepare('INSERT INTO manager_groups (user_id, group_id) VALUES (?, ?)');
    foreach (array_unique($groupIds) as $gid) {
        if ($gid === '') continue;
        $stmt->execute([$userId, $gid]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = valid_role($_POST['role'] ?? 'user');
        $groupId = $_POST['groupId'] ?? '';
        $color = $_POST['color'] ?? '';
        $managedGroups = $_POST['managed_groups'] ?? [];
        $leaveCurrent = parse_days($_POST['leave_current_days'] ?? 0);
        $leaveBacklog = parse_days($_POST['leave_backlog_days'] ?? 0);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#2563eb';

        if ($name === '' || $email === '' || $password === '' || $groupId === '') {
            $error = 'Wypełnij wszystkie wymagane pola.';
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ((int) $stmt->fetchColumn() > 0) {
                $error = 'Użytkownik z tym adresem e-mail już istnieje.';
            } else {
                $newId = uuidv4();
                $stmt = $pdo->prepare('INSERT INTO users (id, name, email, password_hash, role, group_id, color, must_change_password, leave_current_days, leave_backlog_days) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?)');
                $stmt->execute([$newId, $name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $groupId, $color, $leaveCurrent, $leaveBacklog]);
                save_manager_groups($pdo, $newId, $role, $managedGroups);
                header('Location: admin_users.php');
                exit;
            }
        }
    }

    if ($formAction === 'update') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $role = valid_role($_POST['role'] ?? 'user');
        $groupId = $_POST['groupId'] ?? '';
        $password = $_POST['password'] ?? '';
        $managedGroups = $_POST['managed_groups'] ?? [];
        $leaveCurrent = parse_days($_POST['leave_current_days'] ?? 0);
        $leaveBacklog = parse_days($_POST['leave_backlog_days'] ?? 0);
        $color = $_POST['color'] ?? '';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = null;

        if ($password !== '') {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, role = ?, group_id = ?, password_hash = ?, must_change_password = 1, leave_current_days = ?, leave_backlog_days = ?' . ($color !== null ? ', color = ?' : '') . ' WHERE id = ?');
            $params = [$name, $role, $groupId, password_hash($password, PASSWORD_DEFAULT), $leaveCurrent, $leaveBacklog];
            if ($color !== null) $params[] = $color;
            $params[] = $id;
            $stmt->execute($params);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET name = ?, role = ?, group_id = ?, leave_current_days = ?, leave_backlog_days = ?' . ($color !== null ? ', color = ?' : '') . ' WHERE id = ?');
            $params = [$name, $role, $groupId, $leaveCurrent, $leaveBacklog];
            if ($color !== null) $params[] = $color;
            $params[] = $id;
            $stmt->execute($params);
        }
        save_manager_groups($pdo, $id, $role, $managedGroups);
        header('Location: admin_users.php');
        exit;
    }

    if ($formAction === 'delete') {
        $id = $_POST['id'] ?? '';
        if ($id === $currentAdmin['id']) {
            $error = 'Nie można usunąć własnego konta.';
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

$managedGroupsByUser = [];
foreach ($pdo->query('SELECT mg.user_id, g.name FROM manager_groups mg JOIN groups_ g ON g.id = mg.group_id') as $row) {
    $managedGroupsByUser[$row['user_id']][] = $row['name'];
}
$managedGroupIdsByUser = [];
foreach ($pdo->query('SELECT user_id, group_id FROM manager_groups') as $row) {
    $managedGroupIdsByUser[$row['user_id']][] = $row['group_id'];
}

$pageTitle = 'Użytkownicy - Planer urlopów';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Użytkownicy</h1>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <table>
    <thead>
      <tr>
        <th>Kolor</th><th>Imie i nazwisko</th><th>E-mail</th><th>Dział</th><th>Rola</th>
        <th>Limit (biez./zaleg.)</th><th>Wykorzystano</th><th>Pozostało</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): $summary = compute_leave_summary($u); ?>
        <tr class="user-row" data-target="user-detail-<?= e($u['id']) ?>">
          <td><span class="color-dot" style="background: <?= e($u['color']) ?>"></span></td>
          <td>
            <?= e($u['name']) ?>
            <?php if ($u['role'] === 'kierownik' && !empty($managedGroupsByUser[$u['id']])): ?>
              <div class="muted" style="font-size:0.78rem;">zarządza: <?= e(implode(', ', $managedGroupsByUser[$u['id']])) ?></div>
            <?php endif; ?>
          </td>
          <td><?= e($u['email']) ?></td>
          <td><?= e($u['group_name'] ?? '-') ?></td>
          <td><?= e(role_label($u['role'])) ?></td>
          <td><?= e(format_days($summary['limit_current'])) ?> / <?= e(format_days($summary['limit_backlog'])) ?></td>
          <td><?= e(format_days($summary['used'])) ?></td>
          <td><?= e(format_days($summary['remaining'])) ?></td>
        </tr>
        <tr class="user-detail-row" id="user-detail-<?= e($u['id']) ?>" style="display:none;">
          <td colspan="8">
            <form id="edit-form-<?= e($u['id']) ?>" method="POST" action="admin_users.php" class="user-edit-form">
              <input type="hidden" name="form_action" value="update" />
              <input type="hidden" name="id" value="<?= e($u['id']) ?>" />
              <div class="form-horizontal">
                <div class="form-row field-wide">
                  <label>Imie i nazwisko</label>
                  <input type="text" name="name" value="<?= e($u['name']) ?>" />
                </div>
                <div class="form-row field-medium">
                  <label>Dział (własny)</label>
                  <select name="groupId">
                    <?php foreach ($groups as $g): ?>
                      <option value="<?= e($g['id']) ?>" <?= $g['id'] === $u['group_id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-row field-narrow">
                  <label>Rola</label>
                  <select name="role" class="role-select">
                    <?php foreach (ROLE_LABELS as $value => $label): ?>
                      <option value="<?= e($value) ?>" <?= $u['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-row managed-groups-row field-wide" style="<?= $u['role'] === 'kierownik' ? '' : 'display:none;' ?>">
                  <label>Dodatkowe działy zarządzane (poza własnym)</label>
                  <select name="managed_groups[]" multiple size="<?= max(3, count($groups)) ?>">
                    <?php foreach ($groups as $g): ?>
                      <option value="<?= e($g['id']) ?>" <?= in_array($g['id'], $managedGroupIdsByUser[$u['id']] ?? [], true) ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-row field-tiny">
                  <label>Limit bieżący (dni)</label>
                  <input type="text" name="leave_current_days" value="<?= e(format_days($u['leave_current_days'])) ?>" />
                </div>
                <div class="form-row field-tiny">
                  <label>Limit zaległy (dni)</label>
                  <input type="text" name="leave_backlog_days" value="<?= e(format_days($u['leave_backlog_days'])) ?>" />
                </div>
                <div class="form-row field-medium">
                  <label>Nowe hasło (opcjonalnie)</label>
                  <input type="password" name="password" placeholder="pozostaw puste, aby nie zmieniać" autocomplete="new-password" />
                </div>
                <div class="form-row field-tiny">
                  <label>Kolor paska</label>
                  <input type="color" name="color" value="<?= e($u['color']) ?>" />
                </div>
              </div>
            </form>
            <form id="delete-form-<?= e($u['id']) ?>" method="POST" action="admin_users.php" style="display:none;"
                  onsubmit="return confirm('Usunąć użytkownika <?= e($u['name']) ?>?');">
              <input type="hidden" name="form_action" value="delete" />
              <input type="hidden" name="id" value="<?= e($u['id']) ?>" />
            </form>
            <div class="form-actions-row">
              <button class="btn" type="submit" form="edit-form-<?= e($u['id']) ?>">Zapisz</button>
              <button type="button" class="btn secondary btn-cancel-edit">Anuluj</button>
              <button class="btn danger" type="submit" form="delete-form-<?= e($u['id']) ?>">Usuń</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Dodaj użytkownika</h2>
  <form method="POST" action="admin_users.php" class="user-edit-form">
    <input type="hidden" name="form_action" value="create" />
    <div class="form-horizontal">
      <div class="form-row field-wide">
        <label for="name">Imie i nazwisko</label>
        <input type="text" id="name" name="name" required />
      </div>
      <div class="form-row field-wide">
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required autocomplete="off" />
      </div>
      <div class="form-row field-medium">
        <label for="password">Hasło startowe</label>
        <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password" />
      </div>
      <div class="form-row field-medium">
        <label for="groupId">Dział (własny)</label>
        <select id="groupId" name="groupId" required>
          <?php foreach ($groups as $g): ?>
            <option value="<?= e($g['id']) ?>"><?= e($g['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row field-narrow">
        <label for="role">Rola</label>
        <select id="role" name="role" class="role-select">
          <?php foreach (ROLE_LABELS as $value => $label): ?>
            <option value="<?= e($value) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row managed-groups-row field-wide" style="display:none;">
        <label>Dodatkowe działy zarządzane (poza własnym)</label>
        <select name="managed_groups[]" multiple size="<?= max(3, count($groups)) ?>">
          <?php foreach ($groups as $g): ?>
            <option value="<?= e($g['id']) ?>"><?= e($g['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row field-tiny">
        <label for="leave_current_days">Limit bieżący (dni)</label>
        <input type="text" id="leave_current_days" name="leave_current_days" value="0" />
      </div>
      <div class="form-row field-tiny">
        <label for="leave_backlog_days">Limit zaległy (dni)</label>
        <input type="text" id="leave_backlog_days" name="leave_backlog_days" value="0" />
      </div>
      <div class="form-row field-tiny">
        <label for="color">Kolor paska</label>
        <input type="color" id="color" name="color" value="#2563eb" />
      </div>
    </div>
    <div class="form-actions-row">
      <button class="btn" type="submit">Dodaj użytkownika</button>
    </div>
  </form>
</div>

<script>
document.querySelectorAll('.user-edit-form').forEach(function (form) {
  var roleSelect = form.querySelector('.role-select');
  var groupsRow = form.querySelector('.managed-groups-row');
  if (!roleSelect || !groupsRow) return;
  var toggle = function () { groupsRow.style.display = roleSelect.value === 'kierownik' ? '' : 'none'; };
  roleSelect.addEventListener('change', toggle);
  toggle();
});

// Wiersz użytkownika: kliknięcie pokazuje/ukrywa akcje (Edytuj/Usuń) pod nim.
document.querySelectorAll('.user-row').forEach(function (row) {
  row.addEventListener('click', function () {
    var detail = document.getElementById(row.dataset.target);
    var wasOpen = detail.style.display !== 'none';
    document.querySelectorAll('.user-detail-row').forEach(function (d) { d.style.display = 'none'; });
    document.querySelectorAll('.user-row').forEach(function (r) { r.classList.remove('active'); });
    if (!wasOpen) {
      detail.style.display = '';
      row.classList.add('active');
    }
  });
});

// "Anuluj" przy edycji: cofa niezapisane zmiany w polach i chowa wiersz z powrotem.
document.querySelectorAll('.btn-cancel-edit').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var detail = btn.closest('.user-detail-row');
    var form = detail.querySelector('.user-edit-form');
    if (form) form.reset();
    detail.style.display = 'none';
    var row = document.querySelector('.user-row[data-target="' + detail.id + '"]');
    if (row) row.classList.remove('active');
  });
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
