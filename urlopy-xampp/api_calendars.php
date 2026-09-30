<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = get_pdo();

if ($user['role'] === 'admin') {
    $stmt = $pdo->query('SELECT id, name, color FROM users ORDER BY name');
    $rows = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT id, name, color FROM users WHERE group_id = ? OR id = ? ORDER BY name');
    $stmt->execute([$user['group_id'], $user['id']]);
    $rows = $stmt->fetchAll();
}

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
