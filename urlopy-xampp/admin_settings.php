<?php
require_once __DIR__ . '/bootstrap.php';
require_admin();

$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled = isset($_POST['approval_enabled']) ? '1' : '0';
    set_setting('approval_enabled', $enabled);
    $saved = true;
}

$approvalEnabled = is_approval_enabled();

$pageTitle = 'Konfiguracja - Planer urlopów';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Konfiguracja</h1>
  <?php if ($saved): ?><div class="alert success">Zapisano ustawienia.</div><?php endif; ?>

  <form method="POST" action="admin_settings.php">
    <div class="form-row">
      <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
        <input type="checkbox" name="approval_enabled" value="1" style="width:auto;" <?= $approvalEnabled ? 'checked' : '' ?> />
        Moduł zatwierdzania urlopów
      </label>
      <p class="muted" style="margin:6px 0 0;">
        Po włączeniu: nowe zdarzenia urlopowe mają status "Wprowadzony" i
        wymagają zatwierdzenia przez kierownika danego działu (lub admina),
        zanim zostaną oznaczone jako "Zatwierdzony". Kierowników i działy,
        którymi zarządzają, przypisuje się w
        <a href="admin_users.php">Użytkownicy</a>.
        Po wyłączeniu nowe zdarzenia są od razu zatwierdzone - bez dodatkowego kroku.
      </p>
    </div>
    <button class="btn" type="submit">Zapisz</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
