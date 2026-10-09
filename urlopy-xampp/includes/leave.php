<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

/**
 * Podsumowanie limitu urlopowego użytkownika dla danego roku kalendarzowego.
 * Liczy dni z typów urlopu oznaczonych w Konfiguracji jako "liczy się do
 * limitu" (leave_limit_type_keys(), domyślnie wypoczynkowy i na żądanie),
 * niezależnie od statusu zdarzenia (wprowadzony/zatwierdzony) - to po prostu
 * suma dni WPROWADZONYCH do kalendarza, obcinana do granic danego roku.
 */
function compute_leave_summary($user, $year = null) {
    $year = $year ?: (int) date('Y');
    $yearStart = sprintf('%04d-01-01', $year);
    $yearEnd = sprintf('%04d-12-31', $year);

    $pdo = get_pdo();
    $limitTypes = leave_limit_type_keys();
    $used = 0;

    if (!empty($limitTypes)) {
        $placeholders = implode(',', array_fill(0, count($limitTypes), '?'));
        $stmt = $pdo->prepare(
            "SELECT start_date, end_date FROM events
             WHERE user_id = ? AND type IN ($placeholders)
               AND start_date <= ? AND end_date >= ?"
        );
        $stmt->execute(array_merge([$user['id']], $limitTypes, [$yearEnd, $yearStart]));

        foreach ($stmt->fetchAll() as $row) {
            $s = max($row['start_date'], $yearStart);
            $e = min($row['end_date'], $yearEnd);
            $days = (strtotime($e) - strtotime($s)) / 86400 + 1;
            if ($days > 0) $used += $days;
        }
    }

    $limitCurrent = (float) $user['leave_current_days'];
    $limitBacklog = (float) $user['leave_backlog_days'];
    $limitTotal = $limitCurrent + $limitBacklog;

    return [
        'year' => $year,
        'limit_current' => $limitCurrent,
        'limit_backlog' => $limitBacklog,
        'limit_total' => $limitTotal,
        'used' => $used,
        'remaining' => $limitTotal - $used,
    ];
}

function format_days($n) {
    $n = (float) $n;
    return (abs($n - round($n)) < 0.001) ? (string) (int) round($n) : rtrim(rtrim(number_format($n, 1, ',', ''), '0'), ',');
}
