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
    $appBaseUrl = trim($_POST['app_base_url'] ?? '');

    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Podaj poprawny adres e-mail nadawcy.';
    } elseif ($smtpHost !== '' && (!ctype_digit($smtpPort) || (int) $smtpPort < 1 || (int) $smtpPort > 65535)) {
        $error = 'Port SMTP musi być liczba od 1 do 65535.';
    } elseif (!in_array($smtpSecure, ['', 'tls', 'ssl'], true)) {
        $error = 'Nieprawidłowa wartość szyfrowania SMTP.';
    } elseif ($appBaseUrl !== '' && !filter_var($appBaseUrl, FILTER_VALIDATE_URL)) {
        $error = 'Adres aplikacji musi być poprawnym adresem URL (np. http://192.168.1.50/urlopy).';
    } else {
        set_setting('mail_from_email', $fromEmail);
        set_setting('mail_from_name', $fromName !== '' ? $fromName : 'Planer urlopów');
        set_setting('smtp_host', $smtpHost);
        set_setting('smtp_port', $smtpPort !== '' ? $smtpPort : '587');
        set_setting('smtp_secure', $smtpSecure);
        set_setting('smtp_user', $smtpUser);
        if ($smtpPass !== '') {
            set_setting('smtp_pass', $smtpPass);
        }
        set_setting('app_base_url', rtrim($appBaseUrl, '/'));
        $saved = true;
    }
}

$settings = get_mail_settings();
$appBaseUrlSetting = get_setting('app_base_url', '');
$detectedBaseUrl = detect_base_url();

$pageTitle = 'Poczta - Planer urlopów';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Ustawienia poczty</h1>
  <p class="muted">
    Adres i serwer, z którego wysyłane są powiadomienia o nowych zdarzeniach
    urlopowych do członków danego działu. Jeśli pole "Serwer SMTP" zostanie
    puste, wiadomości nie będą faktycznie wysyłane — ich treść trafi tylko
    do pliku <code>logs/mail.log</code> (przydatne do testów).
  </p>
  <?php if ($saved): ?><div class="alert success">Zapisano ustawienia poczty.</div><?php endif; ?>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

  <form method="POST" action="admin_mail.php">
    <div class="form-row">
      <label for="from_email">Adres nadawcy (od kogo przychodzą powiadomienia)</label>
      <input type="email" id="from_email" name="from_email" value="<?= e($settings['from_email']) ?>" required />
    </div>
    <div class="form-row">
      <label for="from_name">Nazwa nadawcy</label>
      <input type="text" id="from_name" name="from_name" value="<?= e($settings['from_name']) ?>" />
    </div>
    <div class="form-row">
      <label for="smtp_host">Serwer SMTP (puste = tylko log, bez realnej wysyłki)</label>
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
      <label for="smtp_user">Użytkownik SMTP</label>
      <input type="text" id="smtp_user" name="smtp_user" value="<?= e($settings['smtp_user']) ?>" />
    </div>
    <div class="form-row">
      <label for="smtp_pass">Hasło SMTP</label>
      <input type="password" id="smtp_pass" name="smtp_pass" placeholder="pozostaw puste, aby nie zmieniać" />
    </div>
    <div class="form-row">
      <label for="app_base_url">Adres aplikacji (link w powiadomieniach)</label>
      <input type="text" id="app_base_url" name="app_base_url" value="<?= e($appBaseUrlSetting) ?>" placeholder="<?= e($detectedBaseUrl) ?>" />
      <p class="muted" style="margin:6px 0 0;">
        Ten adres służy do budowania linku "Zobacz w systemie" w mailach
        (np. do konkretnego zgłoszenia urlopowego). Zostaw puste, aby
        aplikacja sama wykrywała adres (IP albo domene) z bieżącego
        żądania — obecnie wykryty adres to:
        <code><?= e($detectedBaseUrl) ?></code>.
        Wypełnij tylko, jeśli ten adres bywa niestabilny (np. zmienne IP w
        sieci lokalnej) i wolisz zawsze ustawiony na sztywno adres/domene.
      </p>
    </div>
    <button class="btn" type="submit">Zapisz ustawienia</button>
  </form>
</div>

<div class="card">
  <h2>Test wysyłki</h2>
  <p class="muted">Wyśle testowa wiadomość na Twoj adres (<?= e($currentAdmin['email']) ?>), z aktualnie zapisanymi ustawieniami.</p>
  <div id="testResult"></div>
  <button class="btn secondary" id="btnTestMail">Wyślij testowa wiadomość</button>
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
