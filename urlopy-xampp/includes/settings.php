<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

function get_setting($key, $default = null) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}

function set_setting($key, $value) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    if ((int) $stmt->fetchColumn() > 0) {
        $stmt = $pdo->prepare('UPDATE settings SET value = ? WHERE setting_key = ?');
        $stmt->execute([$value, $key]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, value) VALUES (?, ?)');
        $stmt->execute([$key, $value]);
    }
}

/**
 * Ustawienia poczty wychodzacej. Wartosci z bazy (ustawione w panelu
 * Admin -> Poczta) maja pierwszenstwo, a stale z config.php sluza jako
 * wartosci startowe / awaryjne, dopoki admin nic nie zapisze w panelu.
 */
function get_mail_settings() {
    return [
        'from_email'  => get_setting('mail_from_email', MAIL_FROM_EMAIL),
        'from_name'   => get_setting('mail_from_name', MAIL_FROM_NAME),
        'smtp_host'   => get_setting('smtp_host', SMTP_HOST),
        'smtp_port'   => get_setting('smtp_port', (string) SMTP_PORT),
        'smtp_secure' => get_setting('smtp_secure', SMTP_SECURE),
        'smtp_user'   => get_setting('smtp_user', SMTP_USER),
        'smtp_pass'   => get_setting('smtp_pass', SMTP_PASS),
    ];
}
