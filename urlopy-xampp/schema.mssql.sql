-- Schemat bazy danych "Planer urlopow" - Microsoft SQL Server (opcjonalny konektor).
-- Aktywuj ustawiajac w config.php: define('DB_DRIVER', 'sqlsrv');
-- Wymaga rozszerzenia pdo_sqlsrv w PHP (Microsoft Drivers for PHP for SQL
-- Server) - nie jest czescia standardowego XAMPP, trzeba doinstalowac osobno.
-- Aplikacja tworzy tabele automatycznie przy pierwszym uruchomieniu
-- (patrz includes/db.php) - recznego importu NIE trzeba robic, o ile baza
-- 'urlopy' juz istnieje na serwerze.
-- Zostawiony wylacznie jako dokumentacja struktury / do recznego importu.

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'groups_')
CREATE TABLE groups_ (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name NVARCHAR(255) NOT NULL
);

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'users')
CREATE TABLE users (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name NVARCHAR(255) NOT NULL,
    email NVARCHAR(255) NOT NULL UNIQUE,
    password_hash NVARCHAR(255) NOT NULL,
    role NVARCHAR(10) NOT NULL DEFAULT 'user' CHECK (role IN ('admin','user')),
    group_id CHAR(36) NULL,
    color CHAR(7) NOT NULL DEFAULT '#2563eb',
    must_change_password BIT NOT NULL DEFAULT 1,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
);

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'events')
CREATE TABLE events (
    id CHAR(36) NOT NULL PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    group_id CHAR(36) NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    type NVARCHAR(50) NOT NULL,
    note NVARCHAR(500) NULL,
    created_at DATETIME2 NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
);

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'settings')
CREATE TABLE settings (
    setting_key NVARCHAR(100) NOT NULL PRIMARY KEY,
    value NVARCHAR(MAX)
);
