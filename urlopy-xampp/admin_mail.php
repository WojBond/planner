<?php
require_once __DIR__ . '/bootstrap.php';
$currentAdmin = require_admin();

$error = null;
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromEmail = trim($_POST['from_email'] ?? '');
    $fromName = trim($_POST['from_name'] ?? '');
    $smtpHost = trim($_POST['smtp_host'] ?? '');
    $smtpPort = trim($_POST['smtp_port'] ?? '');
    $smtpSecure = $_POST['smtp_secure'] ?? '';
    $smtpUser = trim($_POST['smtp_user'] ?? '');
    $smtpPass = $_POST['smtp_pass'] ?? '';

    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Podaj poprawny adres e-mail nadawcy.';
    } elseif ($smtpHost !== '' && (!ctype_digit($smtpPort) || (int) $smtpPort < 1 || (int) $smtpPort > 65535)) {
        $error = 'Port SMTP musi byc liczba od 1 do 65535.';
    } elseif (!in_array($smtpSecure, ['', 'tls', 'ssl'], true)) {
        $error = 'Nieprawidlowa wartosc szyfrowania SMTP.';
    } else {
        set_setting('mail_from_email', $fromEmail);
        set_setting('mail_from_name', $fromName !== '' ? $fromName : 'Planer urlopow');
        set_setting('smtp_host', $smtpHost);
        set_setting('smtp_port', $smtpPort !== '' ? $smtpPort : '587');
        set_setting('smtp_secure', $smtpSecure);
        set_setting('smtp_user', $smtpUser);
        if ($smtpPass !== '') {
            set_setting('smtp_pass', $smtpPass);
        }
        $saved = true;
    }
}

$settings = get_mail_settings();

$pageTitle = 'Poczta - Planer urlopow';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Ustawienia poczty</h1>
  <p class="muted">
    Adres i serwer, z ktorego wysylane sa powiadomienia o nowych zdarzeniach
    urlopowych do czlonkow danego dzialu. Jesli pole "Serwer SMTP" zostanie
    puste, wiadomosci nie beda faktycznie wysylane — ich tresc trafi tylko
    do pliku <code>logs/mail.log</code> (przydatne do testow).
  </p>
  <?php if ($saved): ?><div class="alert success">Zapisano ustawienia poczty.</div><?php endif; ?>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

  <form method="POST" action="admin_mail.php">
    <div class="form-row">
      <label for="from_email">Adres nadawcy (od kogo przychodza powiadomienia)</label>
      <input type="email" id="from_email" name="from_email" value="<?= e($settings['from_email']) ?>" required />
    </div>
    <div class="form-row">
      <label for="from_name">Nazwa nadawcy</label>
      <input type="text" id="from_name" name="from_name" value="<?= e($settings['from_name']) ?>" />
    </div>
    <div class="form-row">
      <label for="smtp_host">Serwer SMTP (puste = tylko log, bez realnej wysylki)</label>
      <input type="text" id="smtp_host" name="smtp_host" value="<?= e($settings['smtp_host']) ?>" placeholder="np. smtp.twojafirma.pl" />
    </div>
    <div class="form-row">
      <label for="smtp_port">Port SMTP</label>
      <input type="text" id="smtp_port" name="smtp_port" value="<?= e($settings['smtp_port']) ?>" />
    </div>
    <div class="form-row">
      <label for="smtp_secure">Szyfrowanie</label>
      <select id="smtp_secure" name="smtp_secure">
        <option value="" <?= $settings['smtp_secure'] === '' ? 'selected' : '' ?>>Brak</option>
        <option value="tls" <?= $settings['smtp_secure'] === 'tls' ? 'selected' : '' ?>>TLS</option>
        <option value="ssl" <?= $settings['smtp_secure'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
      </select>
    </div>
    <div class="form-row">
      <label for="smtp_user">Uzytkownik SMTP</label>
      <input type="text" id="smtp_user" name="smtp_user" value="<?= e($settings['smtp_user']) ?>" />
    </div>
    <div class="form-row">
      <label for="smtp_pass">Haslo SMTP</label>
      <input type="password" id="smtp_pass" name="smtp_pass" placeholder="pozostaw puste, aby nie zmieniac" />
    </div>
    <button class="btn" type="submit">Zapisz ustawienia</button>
  </form>
</div>

<div class="card">
  <h2>Test wysylki</h2>
  <p class="muted">Wysle testowa wiadomosc na Twoj adres (<?= e($currentAdmin['email']) ?>), z aktualnie zapisanymi ustawieniami.</p>
  <div id="testResult"></div>
  <button class="btn secondary" id="btnTestMail">Wyslij testowa wiadomosc</button>
</div>

<script>
document.getElementById('btnTestMail').addEventListener('click', async () => {
  const box = document.getElementById('testResult');
  box.textContent = '';
  const pending = document.createElement('p');
  pending.className = 'muted';
  pending.textContent = 'Wysylanie...';
  box.appendChild(pending);

  const res = await fetch('admin_mail_test.php', { method: 'POST' });
  const body = await res.json();

  box.textContent = '';
  const alertBox = document.createElement('div');
  alertBox.className = 'alert ' + (body.ok ? 'success' : 'error');
  alertBox.textContent = body.message;
  box.appendChild(alertBox);
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
