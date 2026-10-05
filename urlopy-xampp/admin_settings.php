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

$pageTitle = 'Konfiguracja - Planer urlopow';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Konfiguracja</h1>
  <?php if ($saved): ?><div class="alert success">Zapisano ustawienia.</div><?php endif; ?>

  <form method="POST" action="admin_settings.php">
    <div class="form-row">
      <label style="display:flex; align-items:center; gap:10px; font-weight:600;">
        <input type="checkbox" name="approval_enabled" value="1" style="width:auto;" <?= $approvalEnabled ? 'checked' : '' ?> />
        Modul zatwierdzania urlopow
      </label>
      <p class="muted" style="margin:6px 0 0;">
        Po wlaczeniu: nowe zdarzenia urlopowe maja status "Wprowadzony" i
        wymagaja zatwierdzenia przez kierownika danego dzialu (lub admina),
        zanim zostana oznaczone jako "Zatwierdzony". Kierownikow i dzialy,
        ktorymi zarzadzaja, przypisuje sie w
        <a href="admin_users.php">Uzytkownicy</a>.
        Po wylaczeniu nowe zdarzenia sa od razu zatwierdzone - bez dodatkowego kroku.
      </p>
    </div>
    <button class="btn" type="submit">Zapisz</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
