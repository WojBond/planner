<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$pageTitle = 'Blad - Planer urlopow';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h1>Ups!</h1>
  <p><?= e($message ?? 'Wystapil nieoczekiwany blad.') ?></p>
  <a class="btn" href="calendar.php">Wroc do kalendarza</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
