<?php
require_once __DIR__ . '/bootstrap.php';
require_admin();

$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enabled = isset($_POST['approval_enabled']) ? '1' : '0';
    set_setting('approval_enabled', $enabled);
    set_setting('print_company_name', trim($_POST['print_company_name'] ?? ''));
    set_setting('print_city', trim($_POST['print_city'] ?? ''));
    $saved = true;
}

$approvalEnabled = is_approval_enabled();
$printCompanyName = get_setting('print_company_name', '');
$printCity = get_setting('print_city', '');

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
    <div class="form-row">
      <label for="print_company_name">Nazwa firmy na wydruku wniosku (opcjonalnie)</label>
      <input type="text" id="print_company_name" name="print_company_name" value="<?= e($printCompanyName) ?>" placeholder="np. Firma Sp. z o.o." />
    </div>
    <div class="form-row">
      <label for="print_city">Miejscowość na wydruku wniosku (opcjonalnie)</label>
      <input type="text" id="print_city" name="print_city" value="<?= e($printCity) ?>" placeholder="np. Kalisz" />
      <p class="muted" style="margin:6px 0 0;">
        Używane na wydrukowanym wniosku urlopowym (przycisk "Drukuj wniosek"
        przy zdarzeniu w kalendarzu, dostępny tylko dla bieżących i
        przyszłych urlopów) w nagłówku "Miejscowość, dnia ...". Zostaw puste,
        aby nie drukować nazwy miejscowości.
      </p>
    </div>
    <button class="btn" type="submit">Zapisz</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
