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

$pdo = get_pdo();
$stmt = $pdo->prepare('INSERT INTO events (id, user_id, group_id, start_date, end_date, type, note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$id, $user['id'], $user['group_id'], $startDate, $endDate, $type, $note !== '' ? $note : null, date('Y-m-d H:i:s')]);

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
]));

send_mail_to($recipients, 'Nowe zdarzenie urlopowe: ' . $user['name'], $text);

echo json_encode(['ok' => true]);
