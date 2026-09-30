<?php
require_once __DIR__ . '/bootstrap.php';
$currentAdmin = require_admin();

header('Content-Type: application/json; charset=utf-8');

$settings = get_mail_settings();

if ($settings['smtp_host'] === '' || $settings['smtp_host'] === null) {
    echo json_encode([
        'ok' => false,
        'message' => 'Serwer SMTP nie jest skonfigurowany — wiadomosc zostanie tylko zapisana do logs/mail.log, nie wyslana naprawde.',
    ]);
    send_mail_to(
        [$currentAdmin['email']],
        'Test konfiguracji poczty - Planer urlopow',
        'To jest testowa wiadomosc z Planera urlopow. Jesli ja widzisz w logs/mail.log, wysylka dziala poprawnie (w trybie logowania).'
    );
    exit;
}

$result = send_mail_via_smtp(
    [$currentAdmin['email']],
    'Test konfiguracji poczty - Planer urlopow',
    'To jest testowa wiadomosc z Planera urlopow. Jesli ja otrzymales, konfiguracja SMTP dziala poprawnie.',
    $settings
);

if ($result === true) {
    echo json_encode(['ok' => true, 'message' => 'Wyslano testowa wiadomosc na adres ' . $currentAdmin['email'] . '.']);
} else {
    echo json_encode(['ok' => false, 'message' => 'Blad wysylki: ' . $result]);
}
