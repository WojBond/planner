<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

$id = $_POST['id'] ?? '';
$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';

if ($id === '' || $startDate === '' || $endDate === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Brak wymaganych danych.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidlowy format daty.']);
    exit;
}
if ($endDate < $startDate) {
    http_response_code(400);
    echo json_encode(['error' => 'Data zakonczenia nie moze byc wczesniejsza niz data rozpoczecia.']);
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
    echo json_encode(['error' => 'Brak uprawnien do edycji tego zdarzenia.']);
    exit;
}
if (event_is_locked($user, $event)) {
    http_response_code(403);
    echo json_encode(['error' => 'Zdarzenie jest juz zatwierdzone - nie mozna go przesunac ani zmienic jego dlugosci.']);
    exit;
}

$stmt = $pdo->prepare('UPDATE events SET start_date = ?, end_date = ? WHERE id = ?');
$stmt->execute([$startDate, $endDate, $id]);

echo json_encode(['ok' => true]);
