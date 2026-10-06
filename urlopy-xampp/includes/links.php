<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

/**
 * Adres bazowy aplikacji (np. "http://192.168.1.50/urlopy" albo
 * "https://urlopy.firma.pl"), używany do budowania linków w mailach.
 *
 * Jeśli administrator ustawił "Adres aplikacji" w Poczta -> używamy go
 * (stabilny, przydatny gdy maszyna bywa dostępna pod różnymi adresami).
 * W przeciwnym razie wykrywamy go automatycznie z bieżącego żądania HTTP
 * (działa poprawnie, bo mail zawsze wysyłany jest w trakcie obsługi
 * żądania - admin/użytkownik klika cos w przeglądarce pod jakims adresem,
 * i ten sam adres trafia do linka).
 */
function app_base_url() {
    $configured = trim((string) get_setting('app_base_url', ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    return detect_base_url();
}

/** Zawsze wykrywa adres z bieżącego żądania HTTP, ignorując ręcznie ustawiony adres. */
function detect_base_url() {
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    $host = explode(',', $host)[0]; // pierwszy host, gdy nagromadzone przez kilka proxy

    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if ($forwardedProto !== '') {
        $scheme = explode(',', $forwardedProto)[0];
    } else {
        $isHttps = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        $scheme = $isHttps ? 'https' : 'http';
    }

    $path = '';
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        if ($path === '/' || $path === '.') $path = '';
    }

    return $scheme . '://' . $host . $path;
}

/** Link do konkretnego zdarzenia w kalendarzu (otwiera je automatycznie po wejściu). */
function event_link($eventId) {
    return app_base_url() . '/calendar.php?event=' . urlencode($eventId);
}
