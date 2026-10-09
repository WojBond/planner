-- Schemat bazy danych "Planer urlopów" - Microsoft SQL Server (opcjonalny konektor).
-- Aktywuj ustawiając w config.php: define('DB_DRIVER', 'sqlsrv');
-- Wymaga rozszerzenia pdo_sqlsrv w PHP (Microsoft Drivers for PHP for SQL
-- Server) - nie jest częścią standardowego XAMPP, trzeba doinstalować osobno.
-- Aplikacja tworzy i migruje tabele automatycznie przy każdym uruchomieniu
-- (patrz includes/db.php: ensure_schema() / run_migrations()) - ręcznego
-- importu NIE trzeba robić, o ile baza 'urlopy' już istnieje na serwerze.
-- Zostawiony wyłącznie jako dokumentacja aktualnej struktury (wersja schematu 3).

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'groups_')
CREATE TABLE groups_ (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name NVARCHAR(255) NOT NULL
);

-- role: 'user' | 'kierownik' | 'admin' (bez CHECK - walidacja w PHP,
-- żeby łatwo bylo dokładać kolejne role w przyszłości).
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'users')
CREATE TABLE users (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    email NVARCHAR(255) NOT NULL UNIQUE,
    password_hash NVARCHAR(255) NOT NULL,
    role NVARCHAR(20) NOT NULL DEFAULT 'user',
    group_id CHAR(36) NULL,
    color CHAR(7) NOT NULL DEFAULT '#2563eb',
    must_change_password BIT NOT NULL DEFAULT 1,
    leave_current_days DECIMAL(5,1) NOT NULL DEFAULT 0,
    leave_backlog_days DECIMAL(5,1) NOT NULL DEFAULT 0,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
);

-- status: 'wprowadzony' | 'zatwierdzony'
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'events')
CREATE TABLE events (
    id CHAR(36) NOT NULL PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    group_id CHAR(36) NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    type NVARCHAR(50) NOT NULL,
    note NVARCHAR(500) NULL,
    status NVARCHAR(20) NOT NULL DEFAULT 'wprowadzony',
    approved_by CHAR(36) NULL,
    approved_at DATETIME2 NULL,
    created_at DATETIME2 NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
);

-- Działy, którymi zarządza dany kierownik (relacja wiele-do-wielu).
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'manager_groups')
CREATE TABLE manager_groups (
    user_id CHAR(36) NOT NULL,
    group_id CHAR(36) NOT NULL,
    PRIMARY KEY (user_id, group_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE CASCADE
);

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'settings')
CREATE TABLE settings (
    setting_key NVARCHAR(100) NOT NULL PRIMARY KEY,
    value NVARCHAR(MAX)
);

-- Typy urlopów definiowane przez administratora (Konfiguracja -> Typy
-- urlopów). counts_toward_limit: czy dany typ obciąża roczny limit
-- urlopowy użytkownika (patrz includes/leave.php).
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'event_types')
CREATE TABLE event_types (
    type_key NVARCHAR(50) NOT NULL PRIMARY KEY,
    label NVARCHAR(255) NOT NULL,
    counts_toward_limit BIT NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0
);
