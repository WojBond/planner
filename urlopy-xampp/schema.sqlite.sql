-- Schemat bazy danych "Planer urlopow" - SQLite (domyslny silnik).
-- Uwaga: aplikacja tworzy te tabele automatycznie przy pierwszym uruchomieniu
-- (patrz includes/db.php), wiec recznego importu tego pliku NIE trzeba robic.
-- Zostawiony wylacznie jako dokumentacja struktury.

CREATE TABLE IF NOT EXISTS groups_ (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('admin','user')),
    group_id TEXT,
    color TEXT NOT NULL DEFAULT '#2563eb',
    must_change_password INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS events (
    id TEXT PRIMARY KEY,
    user_id TEXT NOT NULL,
    group_id TEXT,
    start_date TEXT NOT NULL,
    end_date TEXT NOT NULL,
    type TEXT NOT NULL,
    note TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key TEXT PRIMARY KEY,
    value TEXT
);
