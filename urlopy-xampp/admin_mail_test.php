<?php
require_once __DIR__ . '/bootstrap.php';
$currentAdmin = require_admin();

header('Content-Type: application/json; charset=utf-8');

$settings = get_mail_settings();

if ($settings['smtp_host'] === '' || $settings['smtp_host'] === null) {
    echo json_encode([
        'ok' => false,
        'message' => 'Serwer SMTP nie jest skonfigurowany — wiadomość zostanie tylko zapisana do logs/mail.log, nie wysłana naprawdę.',
    ]);
    send_mail_to(
        [$currentAdmin['email']],
        'Test konfiguracji poczty - Planer urlopów',
        'To jest testowa wiadomość z Planera urlopów. Jeśli ja widzisz w logs/mail.log, wysyłka działa poprawnie (w trybie logowania).'
    );
    exit;
}

$result = send_mail_via_smtp(
    [$currentAdmin['email']],
    'Test konfiguracji poczty - Planer urlopów',
    'To jest testowa wiadomość z Planera urlopów. Jeśli ja otrzymałeś, konfiguracja SMTP działa poprawnie.',
    $settings
);

if ($result === true) {
    echo json_encode(['ok' => true, 'message' => 'Wysłano testowa wiadomość na adres ' . $currentAdmin['email'] . '.']);
} else {
    echo json_encode(['ok' => false, 'message' => 'Błąd wysyłki: ' . $result]);
}
