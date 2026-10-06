<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

$id = $_POST['id'] ?? '';
$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';
// Typ i notatka są opcjonalne: przeciąganie paska w widoku Rok wysyła tylko
// daty, formularz edycji wysyła komplet pol.
$type = array_key_exists('type', $_POST) ? trim($_POST['type']) : null;
$note = array_key_exists('note', $_POST) ? trim($_POST['note']) : null;

if ($id === '' || $startDate === '' || $endDate === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Brak wymaganych danych.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidłowy format daty.']);
    exit;
}
if ($endDate < $startDate) {
    http_response_code(400);
    echo json_encode(['error' => 'Data zakończenia nie może być wcześniejsza niz data rozpoczęcia.']);
    exit;
}
if ($type !== null && $type === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Wybierz typ zdarzenia.']);
    exit;
}

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    echo json_encode(['error' => 'Nie znaleziono zdarzenia.']);
    exit;
}
if ($user['role'] !== 'admin' && $event['user_id'] !== $user['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Brak uprawnień do edycji tego zdarzenia.']);
    exit;
}
if (event_is_locked($user, $event)) {
    http_response_code(403);
    echo json_encode(['error' => 'Zdarzenie jest już zatwierdzone - nie można go edytować.']);
    exit;
}

$newType = $type !== null ? $type : $event['type'];
$newNote = $note !== null ? mb_substr($note, 0, 500) : ($event['note'] ?? '');

$stmt = $pdo->prepare('UPDATE events SET start_date = ?, end_date = ?, type = ?, note = ? WHERE id = ?');
$stmt->execute([$startDate, $endDate, $newType, $newNote !== '' ? $newNote : null, $id]);

echo json_encode(['ok' => true]);
