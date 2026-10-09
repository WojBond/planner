-- Schemat bazy danych "Planer urlopów" - MySQL / MariaDB (opcjonalny konektor).
-- Aktywuj ustawiając w config.php: define('DB_DRIVER', 'mysql');
-- Aplikacja tworzy i migruje baze/tabele automatycznie przy każdym uruchomieniu
-- (patrz includes/db.php: ensure_schema() / run_migrations()) - ręcznego
-- importu NIE trzeba robić. Zostawiony wyłącznie jako dokumentacja aktualnej
-- struktury (wersja schematu 3) / do ręcznego importu przez phpMyAdmin.

CREATE DATABASE IF NOT EXISTS urlopy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE urlopy;

CREATE TABLE IF NOT EXISTS groups_ (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- role: 'user' | 'kierownik' | 'admin' (VARCHAR zamiast ENUM, żeby łatwo
-- bylo dokładać kolejne role bez przebudowy kolumny).
CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    group_id CHAR(36) NULL,
    color CHAR(7) NOT NULL DEFAULT '#2563eb',
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    leave_current_days DECIMAL(5,1) NOT NULL DEFAULT 0,
    leave_backlog_days DECIMAL(5,1) NOT NULL DEFAULT 0,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- status: 'wprowadzony' | 'zatwierdzony'
CREATE TABLE IF NOT EXISTS events (
    id CHAR(36) NOT NULL PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    group_id CHAR(36) NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    type VARCHAR(50) NOT NULL,
    note VARCHAR(500) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'wprowadzony',
    approved_by CHAR(36) NULL,
    approved_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Działy, którymi zarządza dany kierownik (relacja wiele-do-wielu).
CREATE TABLE IF NOT EXISTS manager_groups (
    user_id CHAR(36) NOT NULL,
    group_id CHAR(36) NOT NULL,
    PRIMARY KEY (user_id, group_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Typy urlopów definiowane przez administratora (Konfiguracja -> Typy
-- urlopów). counts_toward_limit: czy dany typ obciąża roczny limit
-- urlopowy użytkownika (patrz includes/leave.php).
CREATE TABLE IF NOT EXISTS event_types (
    type_key VARCHAR(50) NOT NULL PRIMARY KEY,
    label VARCHAR(255) NOT NULL,
    counts_toward_limit TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
