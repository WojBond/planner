<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

$pdo = get_pdo();
$group = null;
if ($user['group_id']) {
    $stmt = $pdo->prepare('SELECT * FROM groups_ WHERE id = ?');
    $stmt->execute([$user['group_id']]);
    $group = $stmt->fetch() ?: null;
}

$saved = isset($_GET['saved']);
$error = $_GET['error'] ?? null;
$leave = compute_leave_summary($user);

$pageTitle = 'Moj profil - Planer urlopow';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Moj profil</h1>
  <?php if ($saved): ?><div class="alert success">Zapisano zmiany.</div><?php endif; ?>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <p><strong>Imie i nazwisko:</strong> <?= e($user['name']) ?></p>
  <p><strong>E-mail:</strong> <?= e($user['email']) ?></p>
  <p><strong>Dzial:</strong> <?= e($group ? $group['name'] : '-') ?></p>
  <p><strong>Rola:</strong> <?= e(role_label($user['role'])) ?></p>
</div>

<div class="card">
  <h2>Limit urlopowy <?= e($leave['year']) ?></h2>
  <table>
    <tbody>
      <tr><td>Limit biezacy</td><td><?= e(format_days($leave['limit_current'])) ?> dni</td></tr>
      <tr><td>Limit zalegly</td><td><?= e(format_days($leave['limit_backlog'])) ?> dni</td></tr>
      <tr><td>Razem do wykorzystania</td><td><?= e(format_days($leave['limit_total'])) ?> dni</td></tr>
      <tr><td>Wykorzystano (wg kalendarza)</td><td><?= e(format_days($leave['used'])) ?> dni</td></tr>
      <tr><td><strong>Pozostalo</strong></td><td><strong><?= e(format_days($leave['remaining'])) ?> dni</strong></td></tr>
    </tbody>
  </table>
  <p class="muted" style="margin-top:10px;">
    Liczone sa dni urlopu wypoczynkowego i urlopu na zadanie wprowadzone do
    kalendarza w tym roku (niezaleznie od statusu zatwierdzenia).
  </p>
</div>

<div class="card">
  <h2>Kolor mojego paska w kalendarzu</h2>
  <form method="POST" action="profile_color.php" style="display:flex; align-items:center; gap:14px;">
    <input type="color" name="color" value="<?= e($user['color']) ?>" />
    <button class="btn" type="submit">Zapisz kolor</button>
  </form>
</div>

<div class="card">
  <h2>Zmiana hasla</h2>
  <form method="POST" action="profile_password.php">
    <div class="form-row">
      <label for="currentPassword">Aktualne haslo</label>
      <input type="password" id="currentPassword" name="currentPassword" required />
    </div>
    <div class="form-row">
      <label for="newPassword">Nowe haslo (min. 6 znakow)</label>
      <input type="password" id="newPassword" name="newPassword" required minlength="6" />
    </div>
    <button class="btn" type="submit">Zmien haslo</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
