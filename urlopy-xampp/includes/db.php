<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

require_once __DIR__ . '/uuid.php';

// Numer wersji schematu. Podnies przy każdej zmianie struktury i dopisz
// odpowiedni blok w run_migrations().
const SCHEMA_VERSION = 2;

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
    run_migrations($pdo, DB_DRIVER);

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
    // Bez tego SQLite rzuca "database is locked" natychmiast przy
    // jakiejkolwiek kolizji (np. dwóch równoczesnych zapisów) zamiast
    // chwile poczekac i spróbować ponownie.
    $pdo->exec('PRAGMA busy_timeout = 5000');
    return $pdo;
}

function connect_mysql() {
    // Połącz się bez wskazanej bazy, żeby móc ją utworzyć, jeśli nie istnieje.
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
            'Rozszerzenie pdo_sqlsrv nie jest załadowane w PHP. Zainstaluj "Microsoft ' .
            'Drivers for PHP for SQL Server" i włącz extension=pdo_sqlsrv w php.ini, ' .
            'albo zmień DB_DRIVER na "sqlite" lub "mysql" w config.php.'
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

/**
 * Tworzy tabele od zera (fresh install). Schemat tutaj to zawsze AKTUALNA
 * (najnowsza) wersja struktury - dla już istniejących baz zmiany dokładane
 * są przez run_migrations().
 */
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
            role TEXT NOT NULL DEFAULT 'user',
            group_id TEXT,
            color TEXT NOT NULL DEFAULT '#2563eb',
            must_change_password INTEGER NOT NULL DEFAULT 1,
            leave_current_days REAL NOT NULL DEFAULT 0,
            leave_backlog_days REAL NOT NULL DEFAULT 0,
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
            status TEXT NOT NULL DEFAULT 'wprowadzony',
            approved_by TEXT,
            approved_at TEXT,
            created_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS manager_groups (
            user_id TEXT NOT NULL,
            group_id TEXT NOT NULL,
            PRIMARY KEY (user_id, group_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE CASCADE
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
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            group_id CHAR(36) NULL,
            color CHAR(7) NOT NULL DEFAULT '#2563eb',
            must_change_password TINYINT(1) NOT NULL DEFAULT 1,
            leave_current_days DECIMAL(5,1) NOT NULL DEFAULT 0,
            leave_backlog_days DECIMAL(5,1) NOT NULL DEFAULT 0,
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
            status VARCHAR(20) NOT NULL DEFAULT 'wprowadzony',
            approved_by CHAR(36) NULL,
            approved_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS manager_groups (
            user_id CHAR(36) NOT NULL,
            group_id CHAR(36) NOT NULL,
            PRIMARY KEY (user_id, group_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE CASCADE
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
            role NVARCHAR(20) NOT NULL DEFAULT 'user',
            group_id CHAR(36) NULL,
            color CHAR(7) NOT NULL DEFAULT '#2563eb',
            must_change_password BIT NOT NULL DEFAULT 1,
            leave_current_days DECIMAL(5,1) NOT NULL DEFAULT 0,
            leave_backlog_days DECIMAL(5,1) NOT NULL DEFAULT 0,
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
            status NVARCHAR(20) NOT NULL DEFAULT 'wprowadzony',
            approved_by CHAR(36) NULL,
            approved_at DATETIME2 NULL,
            created_at DATETIME2 NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE SET NULL
        )");

        $pdo->exec("IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'manager_groups')
        CREATE TABLE manager_groups (
            user_id CHAR(36) NOT NULL,
            group_id CHAR(36) NOT NULL,
            PRIMARY KEY (user_id, group_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE CASCADE
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
        $stmt->execute([$groupId, 'Dział ogólny']);

        $adminId = uuidv4();
        $hash = password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (id, name, email, password_hash, role, group_id, color, must_change_password) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
        $stmt->execute([$adminId, 'Administrator', DEFAULT_ADMIN_EMAIL, $hash, 'admin', $groupId, '#2563eb']);
    }
}

/**
 * Dokleja zmiany struktury do już istniejących baz (założonych przez
 * wcześniejsza wersje aplikacji). ensure_schema() powyżej opisuje docelowy
 * kształt dla NOWYCH instalacji - ta funkcja dogania do niego STARE.
 */
function run_migrations(PDO $pdo, string $driver) {
    $current = (int) (get_schema_version($pdo, $driver) ?? 1);
    if ($current >= SCHEMA_VERSION) return;

    if ($current < 2) {
        migrate_to_v2($pdo, $driver);
    }

    set_schema_version($pdo, $driver, SCHEMA_VERSION);
}

function get_schema_version(PDO $pdo, string $driver) {
    try {
        $stmt = $pdo->prepare('SELECT value FROM settings WHERE setting_key = ?');
        $stmt->execute(['schema_version']);
        $row = $stmt->fetch();
        $stmt->closeCursor();
        return $row ? (int) $row['value'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

function set_schema_version(PDO $pdo, string $driver, int $version) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE setting_key = ?');
    $stmt->execute(['schema_version']);
    $count = (int) $stmt->fetchColumn();
    $stmt->closeCursor();
    if ($count > 0) {
        $stmt = $pdo->prepare('UPDATE settings SET value = ? WHERE setting_key = ?');
        $stmt->execute([(string) $version, 'schema_version']);
    } else {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, value) VALUES (?, ?)');
        $stmt->execute(['schema_version', (string) $version]);
    }
}

/**
 * v1 -> v2: rola "kierownik", limity urlopowe, statusy/zatwierdzanie zdarzeń,
 * tabela przypisań kierownik-dział. Bezpieczna do wielokrotnego uruchomienia
 * (każdy krok ignoruje błąd "już istnieje").
 */
function migrate_to_v2(PDO $pdo, string $driver) {
    $exec_safe = function (string $sql) use ($pdo) {
        try { $pdo->exec($sql); } catch (PDOException $e) { /* kolumna/tabela już istnieje - ok */ }
    };

    if ($driver === 'sqlite') {
        // Role miało CHECK(role IN ('admin','user')) - SQLite nie pozwala
        // zmienić CHECK bez przebudowy tabeli.
        $hasOldCheck = false;
        $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
        $row = $stmt->fetch();
        $stmt->closeCursor();
        $stmt = null;
        if ($row && strpos($row['sql'], "CHECK") !== false) {
            $hasOldCheck = true;
        }

        if ($hasOldCheck) {
            $pdo->exec('PRAGMA foreign_keys = OFF');
            // Bez tego RENAME TO przepisuje też odwołania FOREIGN KEY w innych
            // tabelach (np. events.user_id zaczęłoby wskazywać na users_old_v1).
            $pdo->exec('PRAGMA legacy_alter_table = ON');
            $pdo->beginTransaction();
            $pdo->exec("ALTER TABLE users RENAME TO users_old_v1");
            $pdo->exec("CREATE TABLE users (
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
            )");
            $pdo->exec("INSERT INTO users (id, name, email, password_hash, role, group_id, color, must_change_password)
                SELECT id, name, email, password_hash, role, group_id, color, must_change_password FROM users_old_v1");
            $pdo->exec("DROP TABLE users_old_v1");
            $pdo->commit();
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA legacy_alter_table = OFF');
        } else {
            $exec_safe("ALTER TABLE users ADD COLUMN leave_current_days REAL NOT NULL DEFAULT 0");
            $exec_safe("ALTER TABLE users ADD COLUMN leave_backlog_days REAL NOT NULL DEFAULT 0");
        }

        $exec_safe("ALTER TABLE events ADD COLUMN status TEXT NOT NULL DEFAULT 'wprowadzony'");
        $exec_safe("ALTER TABLE events ADD COLUMN approved_by TEXT");
        $exec_safe("ALTER TABLE events ADD COLUMN approved_at TEXT");

        $exec_safe("CREATE TABLE IF NOT EXISTS manager_groups (
            user_id TEXT NOT NULL,
            group_id TEXT NOT NULL,
            PRIMARY KEY (user_id, group_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE CASCADE
        )");
    } elseif ($driver === 'mysql') {
        $exec_safe("ALTER TABLE users MODIFY COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");
        $exec_safe("ALTER TABLE users ADD COLUMN leave_current_days DECIMAL(5,1) NOT NULL DEFAULT 0");
        $exec_safe("ALTER TABLE users ADD COLUMN leave_backlog_days DECIMAL(5,1) NOT NULL DEFAULT 0");
        $exec_safe("ALTER TABLE events ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'wprowadzony'");
        $exec_safe("ALTER TABLE events ADD COLUMN approved_by CHAR(36) NULL");
        $exec_safe("ALTER TABLE events ADD COLUMN approved_at DATETIME NULL");
        $exec_safe("CREATE TABLE IF NOT EXISTS manager_groups (
            user_id CHAR(36) NOT NULL,
            group_id CHAR(36) NOT NULL,
            PRIMARY KEY (user_id, group_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } elseif ($driver === 'sqlsrv') {
        // Zdejmij CHECK na role (nazwa jest generowana automatycznie przez SQL Server).
        try {
            $pdo->exec("
                DECLARE @c NVARCHAR(200);
                SELECT @c = cc.name
                FROM sys.check_constraints cc
                JOIN sys.columns col ON cc.parent_object_id = col.object_id AND cc.parent_column_id = col.column_id
                WHERE cc.parent_object_id = OBJECT_ID('users') AND col.name = 'role';
                IF @c IS NOT NULL EXEC('ALTER TABLE users DROP CONSTRAINT ' + @c);
            ");
        } catch (PDOException $e) { /* brak ograniczenia - ok */ }

        $exec_safe("ALTER TABLE users ADD leave_current_days DECIMAL(5,1) NOT NULL DEFAULT 0");
        $exec_safe("ALTER TABLE users ADD leave_backlog_days DECIMAL(5,1) NOT NULL DEFAULT 0");
        $exec_safe("ALTER TABLE events ADD status NVARCHAR(20) NOT NULL DEFAULT 'wprowadzony'");
        $exec_safe("ALTER TABLE events ADD approved_by CHAR(36) NULL");
        $exec_safe("ALTER TABLE events ADD approved_at DATETIME2 NULL");
        $exec_safe("IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'manager_groups')
        CREATE TABLE manager_groups (
            user_id CHAR(36) NOT NULL,
            group_id CHAR(36) NOT NULL,
            PRIMARY KEY (user_id, group_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES groups_(id) ON DELETE CASCADE
        )");
    }
}
