<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

/**
 * Typy urlopów - od wersji 1.9.0 definiowane przez administratora w bazie
 * (tabela event_types, zarządzana w admin_event_types.php), a nie zaszyte
 * na stałe w kodzie. $EVENT_TYPES zostaje jako globalna tablica [klucz =>
 * etykieta] dla wstecznej zgodności z miejscami, które po niej iterują
 * (np. filtr w kalendarzu, lista wyboru w formularzu zdarzenia).
 */
function load_event_types() {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT type_key, label FROM event_types ORDER BY sort_order, label');
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[$row['type_key']] = $row['label'];
    }
    return $out;
}

$EVENT_TYPES = load_event_types();

function event_type_label($value) {
    global $EVENT_TYPES;
    return $EVENT_TYPES[$value] ?? $value;
}

/** Lista kluczy typów urlopu, które obciążają roczny limit urlopowy (ustawiane przez admina). */
function leave_limit_type_keys() {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT type_key FROM event_types WHERE counts_toward_limit = 1');
    return array_column($stmt->fetchAll(), 'type_key');
}
