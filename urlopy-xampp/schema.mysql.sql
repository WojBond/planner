-- Schemat bazy danych "Planer urlopow" - MySQL / MariaDB (opcjonalny konektor).
-- Aktywuj ustawiajac w config.php: define('DB_DRIVER', 'mysql');
-- Aplikacja tworzy baze i tabele automatycznie przy pierwszym uruchomieniu
-- (patrz includes/db.php) - recznego importu NIE trzeba robic.
-- Zostawiony wylacznie jako dokumentacja struktury / do recznego importu
-- przez phpMyAdmin, jesli wolisz.

CREATE DATABASE IF NOT EXISTS urlopy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE urlopy;

CREATE TABLE IF NOT EXISTS groups_ (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    group_id CHAR(36) NULL,
    color CHAR(7) NOT NULL DEFAULT '#2563eb',
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS events (
    id CHAR(36) NOT NULL PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    group_id CHAR(36) NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    type VARCHAR(50) NOT NULL,
    note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
