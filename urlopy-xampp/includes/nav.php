<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$navUser = current_user();
?>
<div class="topbar">
  <div class="brand">Planer urlopów <span class="muted" style="font-weight:400; font-size:0.75rem;">v<?= e(APP_VERSION) ?></span></div>
  <nav>
    <a href="calendar.php">Kalendarz</a>
    <a href="profile.php">Mój profil</a>
    <?php if ($navUser && $navUser['role'] === 'admin'): ?>
      <a href="admin_users.php">Użytkownicy</a>
      <a href="admin_groups.php">Działy</a>
      <a href="admin_mail.php">Poczta</a>
      <a href="admin_settings.php">Konfiguracja</a>
    <?php endif; ?>
    <?php if ($navUser): ?>
      <span class="user-badge">
        <span class="color-dot" style="background: <?= e($navUser['color']) ?>"></span>
        <?= e($navUser['name']) ?> (<?= e(mb_strtolower(role_label($navUser['role']))) ?>)
      </span>
      <form method="POST" action="logout.php" style="margin:0;">
        <button class="btn secondary" type="submit">Wyloguj</button>
      </form>
    <?php endif; ?>
  </nav>
</div>
