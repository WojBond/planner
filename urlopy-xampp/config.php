<?php
// Konfiguracja aplikacji "Planer urlopow" pod XAMPP.

define('APP_VERSION', '1.4.0');
define('APP_CHANGELOG', [
    '1.4.0' => [
        'date' => '2026-10-05',
        'changes' => [
            'Widok Rok: przeciaganie pasków zmienia termin urlopu, ciagniecie za',
            'brzegi zmienia liczbe dni (tylko wlasne/wszystkie wpisy nie-zatwierdzone).',
            'Nowy opcjonalny modul zatwierdzania urlopow (Konfiguracja): rola',
            '"kierownik" przypisywana do wielu dzialow, statusy Wprowadzony/Zatwierdzony,',
            'zatwierdzac moze tylko kierownik danego dzialu lub administrator.',
            'Limity urlopowe (biezacy/zalegly) na uzytkownika, z automatycznym',
            'podliczaniem wykorzystanych dni na podstawie wpisow w kalendarzu.',
        ],
    ],
    '1.3.0' => [
        'date' => '2026-09-29',
        'changes' => [
            'Nowy ekran administracyjny "Poczta": adres/nazwa nadawcy oraz dane',
            'serwera SMTP mozna teraz skonfigurowac z poziomu aplikacji (baza danych),',
            'zamiast recznie edytowac config.php. Dodano tez przycisk testowej wysylki.',
        ],
    ],
    '1.2.0' => [
        'date' => '2026-09-29',
        'changes' => [
            'Nowy widok "Rok" w stylu Teamup: wszystkie miesiace jako wiersze,',
            'zdarzenia jako kolorowe paski, dymek szczegolow po kliknieciu.',
            'Panel boczny: lista kalendarzy (osob) z checkboxami do ukrywania',
            'oraz filtr wedlug typu urlopu. Dodano przelacznik Miesiac / Rok / Lista.',
        ],
    ],
    '1.1.0' => [
        'date' => '2026-09-29',
        'changes' => [
            'Domyslna baza to teraz SQLite (zero konfiguracji, bez potrzeby uruchamiania MySQL).',
            'Dodano opcjonalne konektory do MySQL i MS SQL Server (przelaczane przez DB_DRIVER).',
            'Zabezpieczono katalogi data/, logs/, includes/ i libs/ przed bezposrednim dostepem przez HTTP.',
        ],
    ],
    '1.0.0' => [
        'date' => '2026-09-29',
        'changes' => [
            'Pierwsza wersja: logowanie i role (user/admin), tworzenie uzytkownikow i dzialow,',
            'wybor koloru paska, powiadomienia e-mail o nowych zdarzeniach dla dzialu.',
        ],
    ],
]);

// --- Wybor bazy danych -----------------------------------------------------
// 'sqlite' (domyslnie, zalecane) - jeden plik, zero konfiguracji, dziala bez
//          uruchamiania MySQL. Wymaga tylko rozszerzenia pdo_sqlite (wlaczone
//          domyslnie w PHP dostarczanym z XAMPP).
// 'mysql'  - opcjonalny konektor do MySQL/MariaDB (np. wbudowanego w XAMPP).
// 'sqlsrv' - opcjonalny konektor do Microsoft SQL Server. Wymaga osobno
//            zainstalowanego rozszerzenia pdo_sqlsrv (Microsoft Drivers for
//            PHP for SQL Server) - nie jest czescia standardowego XAMPP.
define('DB_DRIVER', 'sqlite');

// Polaczenie SQLite (uzywane, gdy DB_DRIVER = 'sqlite')
define('SQLITE_PATH', __DIR__ . '/data/urlopy.sqlite');

// Polaczenie MySQL / MariaDB (uzywane, gdy DB_DRIVER = 'mysql')
// Domyslne wartosci pasuja do domyslnej instalacji XAMPP (root bez hasla).
define('DB_HOST', 'localhost');
define('DB_NAME', 'urlopy');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Polaczenie MS SQL Server (uzywane, gdy DB_DRIVER = 'sqlsrv')
define('MSSQL_HOST', 'localhost');
define('MSSQL_DB', 'urlopy');
define('MSSQL_USER', 'sa');
define('MSSQL_PASS', '');

// Konfiguracja SMTP do wysylki prawdziwych maili.
// Sa to wylacznie wartosci POCZATKOWE / awaryjne - docelowo skonfiguruj
// adres nadawcy i dane SMTP wygodniej z poziomu aplikacji: zaloguj sie jako
// administrator i wejdz w "Poczta" w menu. To, co zapiszesz tam, ma
// pierwszenstwo przed stalymi ponizej.
// Jesli SMTP_HOST jest puste, tresc maili jest tylko zapisywana w logs/mail.log
// (przydatne do testow bez konfigurowania serwera pocztowego).
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls', 'ssl' albo ''
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('MAIL_FROM_EMAIL', 'urlopy@firma.local');
define('MAIL_FROM_NAME', 'Planer urlopow');

define('SESSION_NAME', 'urlopy_session');

// Domyslne haslo konta admina tworzonego przy pierwszym uruchomieniu.
define('DEFAULT_ADMIN_EMAIL', 'admin@firma.local');
define('DEFAULT_ADMIN_PASSWORD', 'admin123');
