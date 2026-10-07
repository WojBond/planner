<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = get_pdo();

$groupIds = visible_group_ids($user); // null = admin, brak filtra

if ($groupIds === null) {
    $stmt = $pdo->query('
        SELECT e.*, u.name AS user_name, u.color AS user_color
        FROM events e
        LEFT JOIN users u ON u.id = e.user_id
        ORDER BY e.start_date
    ');
    $rows = $stmt->fetchAll();
} elseif (empty($groupIds)) {
    $stmt = $pdo->prepare('
        SELECT e.*, u.name AS user_name, u.color AS user_color
        FROM events e
        LEFT JOIN users u ON u.id = e.user_id
        WHERE e.user_id = ?
        ORDER BY e.start_date
    ');
    $stmt->execute([$user['id']]);
    $rows = $stmt->fetchAll();
} else {
    $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    $stmt = $pdo->prepare("
        SELECT e.*, u.name AS user_name, u.color AS user_color
        FROM events e
        LEFT JOIN users u ON u.id = e.user_id
        WHERE e.group_id IN ($placeholders) OR e.user_id = ?
        ORDER BY e.start_date
    ");
    $stmt->execute(array_merge($groupIds, [$user['id']]));
    $rows = $stmt->fetchAll();
}

function add_one_day($dateStr) {
    $d = new DateTime($dateStr);
    $d->modify('+1 day');
    return $d->format('Y-m-d');
}

$approvalEnabled = is_approval_enabled();

$out = [];
foreach ($rows as $r) {
    $label = event_type_label($r['type']);
    $statusLabel = event_status_label($r['status']);
    $title = ($r['user_name'] ?? '?') . ' - ' . $label . ($r['note'] ? ' (' . $r['note'] . ')' : '');
    $isOwner = $user['id'] === $r['user_id'];
    $isAdmin = $user['role'] === 'admin';
    $locked = event_is_locked($user, $r);

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
            'groupId' => $r['group_id'],
            'type' => $r['type'],
            'typeLabel' => $label,
            'note' => $r['note'] ?? '',
            'status' => $r['status'],
            'statusLabel' => $statusLabel,
            'canDelete' => ($isAdmin || $isOwner) && !$locked,
            'canEdit' => ($isAdmin || $isOwner) && !$locked,
            'canApprove' => $approvalEnabled && $r['status'] === 'wprowadzony' && can_approve_group($user, $r['group_id']),
            'canUnapprove' => $approvalEnabled && $r['status'] === 'zatwierdzony' && can_approve_group($user, $r['group_id']),
            'canPrint' => can_print_event($user, $r),
        ],
    ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
