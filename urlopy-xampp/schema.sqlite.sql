-- Schemat bazy danych "Planer urlopów" - SQLite (domyślny silnik).
-- Uwaga: aplikacja tworzy i migruje te tabele automatycznie przy każdym
-- uruchomieniu (patrz includes/db.php: ensure_schema() / run_migrations()),
-- więc ręcznego importu tego pliku NIE trzeba robić. Zostawiony wyłącznie
-- jako dokumentacja aktualnej struktury (wersja schematu 3).

CREATE TABLE IF NOT EXISTS groups_ (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL
);

-- role: 'user' | 'kierownik' | 'admin' (bez CHECK - walidacja w PHP,
-- żeby łatwo bylo dokładać kolejne role w przyszłości).
CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user',
    group_id TEXT,
    color TEXT NOT NULL DEFAULT '#2563eb',
    must_change_password INTEGER NOT NULL DEFAULT 1,
    leave_current_days REAL NOT NULL DEFAULT 0,
    leave_backlog_days REAL NOT NULL DEFAULT 0,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
);

-- status: 'wprowadzony' | 'zatwierdzony'
CREATE TABLE IF NOT EXISTS events (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    group_id TEXT,
    start_date TEXT NOT NULL,
    end_date TEXT NOT NULL,
    type TEXT NOT NULL,
    note TEXT,
    status TEXT NOT NULL DEFAULT 'wprowadzony',
    approved_by TEXT,
    approved_at TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
);

-- Działy, którymi zarządza dany kierownik (relacja wiele-do-wielu).
CREATE TABLE IF NOT EXISTS manager_groups (
    user_id TEXT NOT NULL,
    group_id TEXT NOT NULL,
    PRIMARY KEY (user_id, group_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key TEXT PRIMARY KEY,
    value TEXT
);

-- Typy urlopów definiowane przez administratora (Konfiguracja -> Typy
-- urlopów). counts_toward_limit: czy dany typ obciąża roczny limit
-- urlopowy użytkownika (patrz includes/leave.php).
CREATE TABLE IF NOT EXISTS event_types (
    type_key TEXT PRIMARY KEY,
    label TEXT NOT NULL,
    counts_toward_limit INTEGER NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0
);
