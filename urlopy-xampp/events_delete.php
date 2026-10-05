<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

$id = $_POST['id'] ?? '';

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
    echo json_encode(['error' => 'Brak uprawnien do usuniecia tego zdarzenia.']);
    exit;
}
if (event_is_locked($user, $event)) {
    http_response_code(403);
    echo json_encode(['error' => 'Zdarzenie jest juz zatwierdzone i nie mozna go usunac. Poproś kierownika o cofniecie zatwierdzenia.']);
    exit;
}

$stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
$stmt->execute([$id]);

echo json_encode(['ok' => true]);
