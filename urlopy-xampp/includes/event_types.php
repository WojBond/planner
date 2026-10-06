<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

$EVENT_TYPES = [
    'wypoczynkowy' => 'Urlop wypoczynkowy',
    'na_zadanie'   => 'Urlop na żądanie',
    'z_nadgodzin'  => 'Urlop z nadgodzin',
    'lekarz'       => 'Wizyta lekarska',
    'inne'         => 'Inne',
];

function event_type_label($value) {
    global $EVENT_TYPES;
    return $EVENT_TYPES[$value] ?? $value;
}
