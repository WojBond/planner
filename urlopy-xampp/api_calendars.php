<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = get_pdo();

$groupIds = visible_group_ids($user); // null = admin, brak filtra

if ($groupIds === null) {
    $rows = $pdo->query('SELECT id, name, color FROM users ORDER BY name')->fetchAll();
} elseif (empty($groupIds)) {
    $stmt = $pdo->prepare('SELECT id, name, color FROM users WHERE id = ? ORDER BY name');
    $stmt->execute([$user['id']]);
    $rows = $stmt->fetchAll();
} else {
    $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    $stmt = $pdo->prepare("SELECT id, name, color FROM users WHERE group_id IN ($placeholders) OR id = ? ORDER BY name");
    $stmt->execute(array_merge($groupIds, [$user['id']]));
    $rows = $stmt->fetchAll();
}

echo json_encode([
    'calendars' => $rows,
    'meta' => [
        'approvalEnabled' => is_approval_enabled(),
        'role' => $user['role'],
    ],
], JSON_UNESCAPED_UNICODE);
