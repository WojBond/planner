<?php
require_once __DIR__ . '/bootstrap.php';
require_admin();

$pdo = get_pdo();
$error = null;

// Klucze strukturalnie powiązane z drukiem wniosku urlopowego
// (print_leave.php sprawdza je bezpośrednio po kluczu) - nie można ich usunąć,
// ale można dowolnie zmienić ich nazwę wyświetlaną.
const PROTECTED_TYPE_KEYS = ['wypoczynkowy', 'na_zadanie'];

function slugify_type_key($label) {
    $map = ['ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z'];
    $label = mb_strtolower($label, 'UTF-8');
    $label = strtr($label, $map);
    $label = preg_replace('/[^a-z0-9]+/', '_', $label);
    return trim($label, '_');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'create') {
        $label = trim($_POST['label'] ?? '');
        $countsTowardLimit = isset($_POST['counts_toward_limit']) ? 1 : 0;
        if ($label === '') {
            $error = 'Nazwa typu urlopu jest wymagana.';
        } else {
            $key = slugify_type_key($label);
            if ($key === '') {
                $error = 'Nie udało się wygenerować poprawnego klucza z podanej nazwy - użyj liter lub cyfr.';
            } else {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM event_types WHERE type_key = ?');
                $stmt->execute([$key]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $base = $key;
                    $i = 2;
                    do {
                        $key = $base . '_' . $i;
                        $stmt->execute([$key]);
                        $i++;
                    } while ((int) $stmt->fetchColumn() > 0);
                }
                $maxOrder = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM event_types')->fetchColumn();
                $stmt = $pdo->prepare('INSERT INTO event_types (type_key, label, counts_toward_limit, sort_order) VALUES (?, ?, ?, ?)');
                $stmt->execute([$key, $label, $countsTowardLimit, $maxOrder + 1]);
                header('Location: admin_event_types.php');
                exit;
            }
        }
    }

    if ($formAction === 'update') {
        $key = $_POST['type_key'] ?? '';
        $label = trim($_POST['label'] ?? '');
        $countsTowardLimit = isset($_POST['counts_toward_limit']) ? 1 : 0;
        if ($label === '') {
            $error = 'Nazwa typu urlopu jest wymagana.';
        } else {
            $stmt = $pdo->prepare('UPDATE event_types SET label = ?, counts_toward_limit = ? WHERE type_key = ?');
            $stmt->execute([$label, $countsTowardLimit, $key]);
            header('Location: admin_event_types.php');
            exit;
        }
    }

    if ($formAction === 'delete') {
        $key = $_POST['type_key'] ?? '';
        if (in_array($key, PROTECTED_TYPE_KEYS, true)) {
            $error = 'Tego typu urlopu nie można usunąć - jest wymagany przez wydruk wniosku urlopowego.';
        } else {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM events WHERE type = ?');
            $stmt->execute([$key]);
            if ((int) $stmt->fetchColumn() > 0) {
                $error = 'Nie można usunąć typu urlopu, który jest używany w istniejących zdarzeniach.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM event_types WHERE type_key = ?');
                $stmt->execute([$key]);
                header('Location: admin_event_types.php');
                exit;
            }
        }
    }
}

$types = $pdo->query('SELECT * FROM event_types ORDER BY sort_order, label')->fetchAll();
$usageCounts = [];
foreach ($pdo->query('SELECT type, COUNT(*) AS c FROM events GROUP BY type') as $row) {
    $usageCounts[$row['type']] = (int) $row['c'];
}

$pageTitle = 'Typy urlopów - Planer urlopów';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Typy urlopów</h1>
  <p class="muted">
    Typy widoczne w formularzu dodawania/edycji zdarzenia i w filtrze kalendarza.
    "Liczy się do limitu" oznacza, że dni tego typu są wliczane do rocznego
    limitu urlopowego użytkownika (<a href="admin_users.php">Użytkownicy</a>).
  </p>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <table>
    <thead><tr><th>Nazwa</th><th>Klucz</th><th>Liczy się do limitu</th><th>Użyć w kalendarzu</th><th>Akcje</th></tr></thead>
    <tbody>
      <?php foreach ($types as $t): ?>
        <tr>
          <td><?= e($t['label']) ?></td>
          <td><code><?= e($t['type_key']) ?></code></td>
          <td><?= $t['counts_toward_limit'] ? 'Tak' : 'Nie' ?></td>
          <td><?= e((string) ($usageCounts[$t['type_key']] ?? 0)) ?></td>
          <td>
            <details>
              <summary class="muted" style="cursor:pointer;">Edytuj</summary>
              <form method="POST" action="admin_event_types.php" style="margin-top:8px;">
                <input type="hidden" name="form_action" value="update" />
                <input type="hidden" name="type_key" value="<?= e($t['type_key']) ?>" />
                <div class="form-row">
                  <label>Nazwa</label>
                  <input type="text" name="label" value="<?= e($t['label']) ?>" required />
                </div>
                <div class="form-row">
                  <label style="display:flex; align-items:center; gap:8px; font-weight:400;">
                    <input type="checkbox" name="counts_toward_limit" value="1" style="width:auto;" <?= $t['counts_toward_limit'] ? 'checked' : '' ?> />
                    Liczy się do rocznego limitu urlopowego
                  </label>
                </div>
                <button class="btn" type="submit">Zapisz</button>
              </form>
            </details>
            <?php if (!in_array($t['type_key'], PROTECTED_TYPE_KEYS, true) && empty($usageCounts[$t['type_key']])): ?>
              <form method="POST" action="admin_event_types.php" style="margin-top:8px;"
                    onsubmit="return confirm('Usunąć typ urlopu &quot;<?= e($t['label']) ?>&quot;?');">
                <input type="hidden" name="form_action" value="delete" />
                <input type="hidden" name="type_key" value="<?= e($t['type_key']) ?>" />
                <button class="btn danger" type="submit">Usuń</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Dodaj typ urlopu</h2>
  <form method="POST" action="admin_event_types.php">
    <input type="hidden" name="form_action" value="create" />
    <div class="form-row">
      <label for="label">Nazwa</label>
      <input type="text" id="label" name="label" required placeholder="np. Urlop ojcowski" />
    </div>
    <div class="form-row">
      <label style="display:flex; align-items:center; gap:8px; font-weight:400;">
        <input type="checkbox" name="counts_toward_limit" value="1" style="width:auto;" />
        Liczy się do rocznego limitu urlopowego
      </label>
    </div>
    <button class="btn" type="submit">Dodaj</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
