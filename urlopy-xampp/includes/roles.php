<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

const ROLE_LABELS = [
    'user' => 'Użytkownik',
    'kierownik' => 'Kierownik',
    'admin' => 'Administrator',
];

const EVENT_STATUS_LABELS = [
    'wprowadzony' => 'Wprowadzony',
    'zatwierdzony' => 'Zatwierdzony',
];

// Typy zdarzeń, które obciążają roczny limit urlopowy (urlop wypoczynkowy
// i urlop na żądanie są czerpane z tej samej puli dni w polskim prawie pracy).
const LEAVE_LIMIT_TYPES = ['wypoczynkowy', 'na_zadanie'];

function role_label($role) {
    return ROLE_LABELS[$role] ?? $role;
}

function event_status_label($status) {
    return EVENT_STATUS_LABELS[$status] ?? $status;
}

/** Czy moduł zatwierdzania urlopów jest włączony w Konfiguracji. */
function is_approval_enabled() {
    return get_setting('approval_enabled', '0') === '1';
}

/** Lista id działów, którymi zarządza dany kierownik (puste dla innych rol). */
function manager_group_ids($user) {
    if ($user['role'] !== 'kierownik') return [];
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT group_id FROM manager_groups WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    return array_column($stmt->fetchAll(), 'group_id');
}

/** Czy zdarzenie jest zablokowane do edycji/usunięcia dla $user (zatwierdzone, moduł włączony, user nie jest adminem). */
function event_is_locked($user, $event) {
    return is_approval_enabled() && $event['status'] === 'zatwierdzony' && $user['role'] !== 'admin';
}

/**
 * Czy $user może zatwierdzać urlopy w dziale $groupId - kierownik może to
 * robić zarówno we własnym dziale (do którego należy), jak i w dodatkowych
 * działach przypisanych mu w manager_groups.
 */
function can_approve_group($user, $groupId) {
    if (!$user) return false;
    if ($user['role'] === 'admin') return true;
    if ($user['role'] !== 'kierownik') return false;
    if ($groupId && $user['group_id'] === $groupId) return true;
    return in_array($groupId, manager_group_ids($user), true);
}

/**
 * Czy $user może wydrukować wniosek urlopowy dla $event (wiersz z tabeli
 * events) - i tylko dla zdarzeń bieżących/przyszłych (koniec nie wcześniej
 * niż dzisiaj), żeby nie drukować już zakończonych urlopów.
 *
 * Admin oraz kierownik danego działu mogą drukować zawsze (niezależnie od
 * statusu). Zwykły użytkownik może wydrukować wyłącznie własny wniosek i
 * tylko przed jego zatwierdzeniem przez kierownika - po zatwierdzeniu
 * (gdy moduł zatwierdzania jest włączony) przycisk znika, bo druk służy
 * do uzyskania podpisu/zatwierdzenia, które w systemie już się dokonało.
 */
function can_print_event($user, $event) {
    if (!$user) return false;
    if ($event['end_date'] < date('Y-m-d')) return false;

    $isAdmin = $user['role'] === 'admin';
    if ($isAdmin || can_approve_group($user, $event['group_id'])) return true;

    if ($user['id'] !== $event['user_id']) return false;
    if (is_approval_enabled() && $event['status'] === 'zatwierdzony') return false;
    return true;
}

/**
 * Zbiory działów i użytkowników widocznych dla $user w kalendarzu:
 * - admin: wszystko,
 * - kierownik: swoj dział + wszystkie działy, którymi zarządza,
 * - user: tylko swoj dział.
 * Zwraca liste id działów.
 */
function visible_group_ids($user) {
    if ($user['role'] === 'admin') return null; // null = bez filtra (wszystkie)
    $ids = [];
    if ($user['group_id']) $ids[] = $user['group_id'];
    if ($user['role'] === 'kierownik') {
        $ids = array_merge($ids, manager_group_ids($user));
    }
    return array_values(array_unique($ids));
}
