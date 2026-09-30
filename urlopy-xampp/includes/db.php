<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

require_once __DIR__ . '/uuid.php';

function get_pdo() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    switch (DB_DRIVER) {
        case 'mysql':
            $pdo = connect_mysql();
            break;
        case 'sqlsrv':
            $pdo = connect_sqlsrv();
            break;
        case 'sqlite':
        default:
            $pdo = connect_sqlite();
            break;
    }

    ensure_schema($pdo, DB_DRIVER);

    return $pdo;
}

function connect_sqlite() {
    $dir = dirname(SQLITE_PATH);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $pdo = new PDO(
        'sqlite:' . SQLITE_PATH,
        null,
        null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pdo->exec('PRAGMA foreign_keys = ON');
    return $pdo;
}

function connect_mysql() {
    // Polacz sie bez wskazanej bazy, zeby moc ja utworzyc, jesli nie istnieje.
    $root = new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $root->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET ' . DB_CHARSET);

    return new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

function connect_sqlsrv() {
    if (!extension_loaded('pdo_sqlsrv')) {
        throw new RuntimeException(
            'Rozszerzenie pdo_sqlsrv nie jest zaladowane w PHP. Zainstaluj "Microsoft ' .
            'Drivers for PHP for SQL Server" i wlacz extension=pdo_sqlsrv w php.ini, ' .
            'albo zmien DB_DRIVER na "sqlite" lub "mysql" w config.php.'
        );
    }
    $dsn = 'sqlsrv:Server=' . MSSQL_HOST . ';Database=' . MSSQL_DB;
    return new PDO(
        $dsn,
        MSSQL_USER,
        MSSQL_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
}

function ensure_schema(PDO $pdo, string $driver) {
    if ($driver === 'sqlite') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS groups_ (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('admin','user')),
            group_id TEXT,
            color TEXT NOT NULL DEFAULT '#2563eb',
            must_change_password INTEGER NOT NULL DEFAULT 1,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
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
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key TEXT PRIMARY KEY,
            value TEXT
        )");
    } elseif ($driver === 'mysql') {
        $pdo->exec("CREATE TABLE IF NOT EXISTS groups_ (
            id CHAR(36) NOT NULL PRIMARY KEY,
            name VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id CHAR(36) NOT NULL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin','user') NOT NULL DEFAULT 'user',
            group_id CHAR(36) NULL,
            color CHAR(7) NOT NULL DEFAULT '#2563eb',
            must_change_password TINYINT(1) NOT NULL DEFAULT 1,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS events (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
            value TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } elseif ($driver === 'sqlsrv') {
        $pdo->exec("IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'groups_')
        CREATE TABLE groups_ (
            id CHAR(36) NOT NULL PRIMARY KEY,
            name NVARCHAR(255) NOT NULL
        )");

        $pdo->exec("IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'users')
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
        )");

        $pdo->exec("IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'events')
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
        )");

        $pdo->exec("IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'settings')
        CREATE TABLE settings (
            setting_key NVARCHAR(100) NOT NULL PRIMARY KEY,
            value NVARCHAR(MAX)
        )");
    } else {
        throw new RuntimeException("Nieznany DB_DRIVER: $driver");
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
        $groupId = uuidv4();
        $stmt = $pdo->prepare('INSERT INTO groups_ (id, name) VALUES (?, ?)');
        $stmt->execute([$groupId, 'Dzial ogolny']);

        $adminId = uuidv4();
        $hash = password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (id, name, email, password_hash, role, group_id, color, must_change_password) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
        $stmt->execute([$adminId, 'Administrator', DEFAULT_ADMIN_EMAIL, $hash, 'admin', $groupId, '#2563eb']);
    }
}
