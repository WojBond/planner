<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$pageTitle = 'Błąd - Planer urlopów';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Ups!</h1>
  <p><?= e($message ?? 'Wystąpił nieoczekiwany błąd.') ?></p>
  <a class="btn" href="calendar.php">Wróć do kalendarza</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
