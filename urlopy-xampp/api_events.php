<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = get_pdo();

if ($user['role'] === 'admin') {
    $stmt = $pdo->query('
        SELECT e.*, u.name AS user_name, u.color AS user_color
        FROM events e
        LEFT JOIN users u ON u.id = e.user_id
        ORDER BY e.start_date
    ');
} else {
    $stmt = $pdo->prepare('
        SELECT e.*, u.name AS user_name, u.color AS user_color
        FROM events e
        LEFT JOIN users u ON u.id = e.user_id
        WHERE e.group_id = ? OR e.user_id = ?
        ORDER BY e.start_date
    ');
    $stmt->execute([$user['group_id'], $user['id']]);
}
$rows = $stmt->fetchAll();

function add_one_day($dateStr) {
    $d = new DateTime($dateStr);
    $d->modify('+1 day');
    return $d->format('Y-m-d');
}

$out = [];
foreach ($rows as $r) {
    $label = event_type_label($r['type']);
    $title = ($r['user_name'] ?? '?') . ' - ' . $label . ($r['note'] ? ' (' . $r['note'] . ')' : '');
    $out[] = [
        'id' => $r['id'],
        'title' => $title,
        'start' => $r['start_date'],
        'end' => add_one_day($r['end_date']),
        'color' => $r['user_color'] ?? '#64748b',
        'allDay' => true,
        'extendedProps' => [
            'userId' => $r['user_id'],
            'userName' => $r['user_name'] ?? '?',
            'type' => $r['type'],
            'typeLabel' => $label,
            'note' => $r['note'] ?? '',
            'canDelete' => $user['role'] === 'admin' || $user['id'] === $r['user_id'],
        ],
    ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
