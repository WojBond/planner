<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

if (!is_approval_enabled()) {
    http_response_code(400);
    echo json_encode(['error' => 'Moduł zatwierdzania urlopów jest wyłączony.']);
    exit;
}

$id = $_POST['id'] ?? '';
$action = $_POST['action'] ?? 'approve';

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT e.*, u.name AS user_name, u.email AS user_email FROM events e LEFT JOIN users u ON u.id = e.user_id WHERE e.id = ?');
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    echo json_encode(['error' => 'Nie znaleziono zdarzenia.']);
    exit;
}
if (!can_approve_group($user, $event['group_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak uprawnień do zatwierdzania urlopów w tym dziale.']);
    exit;
}

if ($action === 'unapprove') {
    $stmt = $pdo->prepare('UPDATE events SET status = ?, approved_by = NULL, approved_at = NULL WHERE id = ?');
    $stmt->execute(['wprowadzony', $id]);
} else {
    $stmt = $pdo->prepare('UPDATE events SET status = ?, approved_by = ?, approved_at = ? WHERE id = ?');
    $stmt->execute(['zatwierdzony', $user['id'], date('Y-m-d H:i:s'), $id]);

    if ($event['user_email']) {
        send_mail_to([$event['user_email']], 'Urlop zatwierdzony', implode("\n", [
            $user['name'] . ' zatwierdził(a) Twoj urlop.',
            '',
            'Typ: ' . event_type_label($event['type']),
            'Od: ' . $event['start_date'],
            'Do: ' . $event['end_date'],
            '',
            'Zobacz w systemie: ' . event_link($id),
        ]));
    }
}

echo json_encode(['ok' => true]);
