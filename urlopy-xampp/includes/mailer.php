<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

require_once __DIR__ . '/../libs/PHPMailer/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/SMTP.php';
require_once __DIR__ . '/settings.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Wysyła maila do listy adresów, używając konfiguracji ustawionej w panelu
 * Admin -> Poczta (tabela settings), z fallbackiem na stale w config.php.
 * Jeśli serwer SMTP nie jest skonfigurowany, treść jest tylko dopisywana do
 * logs/mail.log (przydatne do testów lokalnych).
 */
function send_mail_to(array $recipients, string $subject, string $text) {
    $recipients = array_values(array_filter($recipients));
    if (empty($recipients)) return;

    $settings = get_mail_settings();

    if ($settings['smtp_host'] === '' || $settings['smtp_host'] === null) {
        $line = "--- [" . date('Y-m-d H:i:s') . "] ---\n";
        $line .= "Od: " . $settings['from_name'] . ' <' . $settings['from_email'] . ">\n";
        $line .= "Do: " . implode(', ', $recipients) . "\n";
        $line .= "Temat: $subject\n\n$text\n";
        $line .= "--------------------------------------\n\n";
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) mkdir($logDir, 0777, true);
        file_put_contents($logDir . '/mail.log', $line, FILE_APPEND);
        return;
    }

    send_mail_via_smtp($recipients, $subject, $text, $settings);
}

function send_mail_via_smtp(array $recipients, string $subject, string $text, array $settings) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $settings['smtp_host'];
        $mail->Port = (int) $settings['smtp_port'];
        if ($settings['smtp_secure'] !== '') $mail->SMTPSecure = $settings['smtp_secure'];
        if ($settings['smtp_user'] !== '') {
            $mail->SMTPAuth = true;
            $mail->Username = $settings['smtp_user'];
            $mail->Password = $settings['smtp_pass'];
        }
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($settings['from_email'], $settings['from_name']);
        foreach ($recipients as $r) $mail->addAddress($r);
        $mail->Subject = $subject;
        $mail->Body = $text;
        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('Błąd wysyłki maila: ' . $mail->ErrorInfo);
        return $mail->ErrorInfo;
    }
}
