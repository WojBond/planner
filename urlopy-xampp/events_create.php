<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';
$type = $_POST['type'] ?? '';
$note = trim($_POST['note'] ?? '');

if ($startDate === '' || $endDate === '' || $type === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Wypelnij wymagane pola.']);
    exit;
}
if ($endDate < $startDate) {
    http_response_code(400);
    echo json_encode(['error' => 'Data zakonczenia nie moze byc wczesniejsza niz data rozpoczecia.']);
    exit;
}

$note = mb_substr($note, 0, 500);
$id = uuidv4();
$approvalEnabled = is_approval_enabled();
$status = $approvalEnabled ? 'wprowadzony' : 'zatwierdzony';

$pdo = get_pdo();
$stmt = $pdo->prepare('INSERT INTO events (id, user_id, group_id, start_date, end_date, type, note, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$id, $user['id'], $user['group_id'], $startDate, $endDate, $type, $note !== '' ? $note : null, $status, date('Y-m-d H:i:s')]);

$stmt = $pdo->prepare('SELECT email FROM users WHERE group_id = ? AND id <> ?');
$stmt->execute([$user['group_id'], $user['id']]);
$recipients = array_column($stmt->fetchAll(), 'email');

$text = implode("\n", array_filter([
    $user['name'] . ' dodal(a) nowy wpis w kalendarzu urlopow.',
    '',
    'Typ: ' . event_type_label($type),
    'Od: ' . $startDate,
    'Do: ' . $endDate,
    $note !== '' ? 'Notatka: ' . $note : '',
    $approvalEnabled ? 'Status: oczekuje na zatwierdzenie przez kierownika.' : '',
]));

send_mail_to($recipients, 'Nowe zdarzenie urlopowe: ' . $user['name'], $text);

if ($approvalEnabled && $user['group_id']) {
    $stmt = $pdo->prepare('SELECT u.email FROM manager_groups mg JOIN users u ON u.id = mg.user_id WHERE mg.group_id = ?');
    $stmt->execute([$user['group_id']]);
    $managerEmails = array_column($stmt->fetchAll(), 'email');
    if (!empty($managerEmails)) {
        send_mail_to($managerEmails, 'Urlop do zatwierdzenia: ' . $user['name'], implode("\n", array_filter([
            $user['name'] . ' zglosil(a) urlop oczekujacy na Twoje zatwierdzenie.',
            '',
            'Typ: ' . event_type_label($type),
            'Od: ' . $startDate,
            'Do: ' . $endDate,
        ])));
    }
}

echo json_encode(['ok' => true]);
