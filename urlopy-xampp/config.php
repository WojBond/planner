<?php
// Konfiguracja aplikacji "Planer urlopów" pod XAMPP.

define('APP_VERSION', '1.10.1');
define('APP_CHANGELOG', [
    '1.10.1' => [
        'date' => '2026-10-27',
        'changes' => [
            'Widok Rok: bieżący dzień jest teraz podświetlony (niebieska',
            'obwódka wokół komórki dnia) - ułatwia szybkie odnalezienie',
            'dzisiejszej daty. Widoki Miesiąc i Lista już wcześniej',
            'podświetlały dziś (wbudowana funkcja FullCalendar).',
        ],
    ],
    '1.10.0' => [
        'date' => '2026-10-13',
        'changes' => [
            'Aplikację można teraz "zainstalować" na telefonie (Android i iOS)',
            'jako PWA (Progressive Web App) - bez Google Play/App Store, przez',
            '"Dodaj do ekranu głównego" w przeglądarce. Dodano manifest.json,',
            'service worker (cache statycznych plików, bez cache\'owania',
            'dynamicznych/zależnych od sesji stron) oraz ikony aplikacji.',
        ],
    ],
    '1.9.5' => [
        'date' => '2026-10-13',
        'changes' => [
            'Użytkownicy: formularz edycji miał brakujące pole "Kolor paska"',
            '(dostępne dotąd tylko przy dodawaniu nowego użytkownika) - dodano',
            'je przy pozostałych polach edycji, przed przyciskami Zapisz/',
            'Anuluj/Usuń.',
        ],
    ],
    '1.9.4' => [
        'date' => '2026-10-12',
        'changes' => [
            'Użytkownicy: przyciski "Zapisz"/"Anuluj"/"Usuń" przeniesione na',
            'własny wspólny rząd pod polami edycyjnymi (zamiast mieszania się',
            'z polami w tym samym rzędzie). Pola edycyjne (i pola formularza',
            'dodawania użytkownika) rozmieszczone proporcjonalnie do ich',
            'zawartości - np. "Imie i nazwisko" szersze, "Limit" i "Kolor',
            'paska" węższe.',
        ],
    ],
    '1.9.3' => [
        'date' => '2026-10-12',
        'changes' => [
            'Użytkownicy: przy edycji dodano przycisk "Anuluj" - zamyka pola',
            'edycji bez zapisywania zmian i przywraca niezapisane wartości.',
            'Formularz dodawania nowego użytkownika jest teraz skompaktowany',
            '- pola ułożone poziomo obok siebie zamiast w osobnych liniach.',
        ],
    ],
    '1.9.2' => [
        'date' => '2026-10-12',
        'changes' => [
            'Użytkownicy: po kliknięciu wiersza pola edycji oraz przyciski',
            '"Zapisz"/"Usuń" pokazują się teraz w jednym, wspólnym poziomym',
            'rzędzie (usunięto oddzielny krok "Edytuj" - edycja otwiera się',
            'od razu), lepiej wykorzystując dostępną szerokość ekranu.',
        ],
    ],
    '1.9.1' => [
        'date' => '2026-10-12',
        'changes' => [
            'Użytkownicy (Konfiguracja -> Użytkownicy): akcje "Edytuj"/"Usuń"',
            'są teraz ukryte, dopóki nie klikniesz danego użytkownika w',
            'tabeli. Formularz edycji otwiera się pod użytkownikiem w',
            'poziomym układzie pól, zamiast wąskiej kolumny jak dotychczas.',
        ],
    ],
    '1.9.0' => [
        'date' => '2026-10-09',
        'changes' => [
            'Nowa strona "Typy urlopów" (Konfiguracja -> Typy urlopów, tylko',
            'admin): typy zdarzeń (wcześniej zaszyte na stałe w kodzie) można',
            'teraz dowolnie dodawać, przemianowywać i usuwać, z osobnym',
            'przełącznikiem "liczy się do rocznego limitu urlopowego" dla',
            'każdego typu. Nie można usunąć typu używanego w istniejących',
            'zdarzeniach, ani typów "Wypoczynkowy"/"Na żądanie" (wymaganych',
            'przez wydruk wniosku).',
            'Przyciski w górnym menu i przycisk "+ Dodaj / Edytuj" pod listą',
            'kalendarzy mają teraz widoczną ramkę.',
        ],
    ],
    '1.8.5' => [
        'date' => '2026-10-09',
        'changes' => [
            'Wydruk wniosku urlopowego: wartości pobrane z terminarza (imię i',
            'nazwisko, dział, daty, liczba dni) są teraz wyśrodkowane w polach.',
            'Drukuje się od teraz na zwykłej kartce A4 w pionie - treść',
            '(w rozmiarze A5 poziomo) jest umieszczona u góry strony, dolna',
            'połowa kartki zostaje pusta. Nie trzeba już ustawiać w drukarce',
            'papieru A5.',
        ],
    ],
    '1.8.4' => [
        'date' => '2026-10-08',
        'changes' => [
            'Wydruk wniosku urlopowego: zmieniono format z A6 na A5 poziomo',
            '(więcej miejsca na czytelny układ) i dodano logo firmy',
            '(assets/img/logo-portos.png) w prawym górnym rogu, tak jak na',
            'oryginalnym papierowym wzorze. Usunięto automatyczne otwieranie',
            'okna drukowania przy wejściu na stronę - teraz trzeba kliknąć',
            'przycisk "Drukuj" na podglądzie (łatwiej najpierw sprawdzić',
            'wygląd wydruku).',
        ],
    ],
    '1.8.3' => [
        'date' => '2026-10-07',
        'changes' => [
            'Doprecyzowano zasady drukowania wniosku urlopowego: zwykły',
            'użytkownik może drukować wyłącznie własny wniosek i tylko przed',
            'jego zatwierdzeniem przez kierownika. Kierownik danego działu i',
            'administrator mogą drukować zawsze, niezależnie od statusu.',
        ],
    ],
    '1.8.2' => [
        'date' => '2026-10-07',
        'changes' => [
            'Poprawka: biblioteka FullCalendar (assets/vendor/fullcalendar.min.js),',
            'wymagana przez widoki Miesiąc i Lista, nigdy nie trafiła do',
            'repozytorium ani do paczek wydania - przez co te dwa widoki nie',
            'działały na świeżo sklonowanej/rozpakowanej instalacji. Dodano plik',
            'na stałe do repozytorium.',
        ],
    ],
    '1.8.1' => [
        'date' => '2026-10-07',
        'changes' => [
            'Poprawka: kierownik może teraz zatwierdzać urlopy również we',
            'własnym dziale (do którego należy), nie tylko w dodatkowych',
            'działach przypisanych mu w "Działy zarządzane".',
        ],
    ],
    '1.8.0' => [
        'date' => '2026-10-07',
        'changes' => [
            'Nowy przycisk "Drukuj wniosek" przy zdarzeniu (popover i formularz',
            'edycji) - generuje gotowy do wydruku wniosek urlopowy w formacie A6',
            'poziomo, wypełniony danymi pracownika, działu i wybranego urlopu.',
            'Dostępny tylko dla bieżących i przyszłych urlopów (nie dla już',
            'zakończonych) i tylko dla właściciela, kierownika danego działu lub',
            'administratora. Nazwę firmy i miejscowość na wydruku można ustawić',
            'w Konfiguracji.',
        ],
    ],
    '1.7.1' => [
        'date' => '2026-10-06',
        'changes' => [
            'Poprawiono brakujące polskie znaki diakrytyczne (ogonki, kreski)',
            'w całym interfejsie, komunikatach, mailach i dokumentacji. Bez zmian',
            'w działaniu aplikacji.',
        ],
    ],
    '1.7.0' => [
        'date' => '2026-10-06',
        'changes' => [
            'Powiadomienia e-mail o zdarzeniach urlopowych zawierają teraz link',
            '"Zobacz w systemie" prowadzący wprost do danego zgłoszenia.',
            'Adres aplikacji (IP albo domena) do budowy linku jest wykrywany',
            'automatycznie z bieżącego żądania HTTP; można go też ustawić na',
            'sztywno w Poczta, jeśli bywa niestabilny.',
            'Link po zalogowaniu poprawnie wraca do strony, z której użytkownik',
            'został przekierowany (np. wprost do zgłoszenia z maila).',
        ],
    ],
    '1.6.0' => [
        'date' => '2026-10-05',
        'changes' => [
            'Zaznaczanie kilku dni przeciągnięciem po pustym miejscu w kalendarzu',
            '(widok Rok i Miesiąc) automatycznie wypełnia zakres dat "od-do" w',
            'formularzu nowego zdarzenia. Zwykle kliknięcie pojedynczego dnia',
            'działa jak dotychczas.',
        ],
    ],
    '1.5.0' => [
        'date' => '2026-10-05',
        'changes' => [
            'Kliknięcie pustego dnia w kalendarzu (widok Rok i Miesiąc) otwiera',
            'formularz nowego zdarzenia z gotowa data.',
            'Kliknięcie istniejącego, edytowalnego zdarzenia otwiera ten sam',
            'formularz wypełniony danymi (typ, daty, notatka) do edycji - zamiast',
            'tylko podglądu. Usuwanie i zatwierdzanie/cofanie zatwierdzenia dostępne',
            'teraz też bezpośrednio z tego formularza.',
        ],
    ],
    '1.4.0' => [
        'date' => '2026-10-05',
        'changes' => [
            'Widok Rok: przeciąganie pasków zmienia termin urlopu, ciągnięcie za',
            'brzegi zmienia liczbę dni (tylko własne/wszystkie wpisy nie-zatwierdzone).',
            'Nowy opcjonalny moduł zatwierdzania urlopów (Konfiguracja): rola',
            '"kierownik" przypisywana do wielu działów, statusy Wprowadzony/Zatwierdzony,',
            'zatwierdzać może tylko kierownik danego działu lub administrator.',
            'Limity urlopowe (bieżący/zaległy) na użytkownika, z automatycznym',
            'podliczaniem wykorzystanych dni na podstawie wpisów w kalendarzu.',
        ],
    ],
    '1.3.0' => [
        'date' => '2026-09-29',
        'changes' => [
            'Nowy ekran administracyjny "Poczta": adres/nazwa nadawcy oraz dane',
            'serwera SMTP można teraz skonfigurować z poziomu aplikacji (baza danych),',
            'zamiast ręcznie edytować config.php. Dodano też przycisk testowej wysyłki.',
        ],
    ],
    '1.2.0' => [
        'date' => '2026-09-29',
        'changes' => [
            'Nowy widok "Rok" w stylu Teamup: wszystkie miesiące jako wiersze,',
            'zdarzenia jako kolorowe paski, dymek szczegółów po kliknięciu.',
            'Panel boczny: lista kalendarzy (osób) z checkboxami do ukrywania',
            'oraz filtr według typu urlopu. Dodano przełącznik Miesiąc / Rok / Lista.',
        ],
    ],
    '1.1.0' => [
        'date' => '2026-09-29',
        'changes' => [
            'Domyślna baza to teraz SQLite (zero konfiguracji, bez potrzeby uruchamiania MySQL).',
            'Dodano opcjonalne konektory do MySQL i MS SQL Server (przełączane przez DB_DRIVER).',
            'Zabezpieczono katalogi data/, logs/, includes/ i libs/ przed bezpośrednim dostepem przez HTTP.',
        ],
    ],
    '1.0.0' => [
        'date' => '2026-09-29',
        'changes' => [
            'Pierwsza wersja: logowanie i role (user/admin), tworzenie użytkowników i działów,',
            'wybór koloru paska, powiadomienia e-mail o nowych zdarzeniach dla działu.',
        ],
    ],
]);

// --- Wybór bazy danych -----------------------------------------------------
// 'sqlite' (domyślnie, zalecane) - jeden plik, zero konfiguracji, działa bez
//          uruchamiania MySQL. Wymaga tylko rozszerzenia pdo_sqlite (wlaczone
//          domyślnie w PHP dostarczanym z XAMPP).
// 'mysql'  - opcjonalny konektor do MySQL/MariaDB (np. wbudowanego w XAMPP).
// 'sqlsrv' - opcjonalny konektor do Microsoft SQL Server. Wymaga osobno
//            zainstalowanego rozszerzenia pdo_sqlsrv (Microsoft Drivers for
//            PHP for SQL Server) - nie jest częścią standardowego XAMPP.
define('DB_DRIVER', 'sqlite');

// Połączenie SQLite (używane, gdy DB_DRIVER = 'sqlite')
define('SQLITE_PATH', __DIR__ . '/data/urlopy.sqlite');

// Połączenie MySQL / MariaDB (używane, gdy DB_DRIVER = 'mysql')
// Domyślne wartości pasują do domyślnej instalacji XAMPP (root bez hasła).
define('DB_HOST', 'localhost');
define('DB_NAME', 'urlopy');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Połączenie MS SQL Server (używane, gdy DB_DRIVER = 'sqlsrv')
define('MSSQL_HOST', 'localhost');
define('MSSQL_DB', 'urlopy');
define('MSSQL_USER', 'sa');
define('MSSQL_PASS', '');

// Konfiguracja SMTP do wysyłki prawdziwych maili.
// Są to wyłącznie wartości POCZATKOWE / awaryjne - docelowo skonfiguruj
// adres nadawcy i dane SMTP wygodniej z poziomu aplikacji: zaloguj się jako
// administrator i wejdz w "Poczta" w menu. To, co zapiszesz tam, ma
// pierwszeństwo przed stałymi poniżej.
// Jeśli SMTP_HOST jest puste, treść maili jest tylko zapisywana w logs/mail.log
// (przydatne do testów bez konfigurowania serwera pocztowego).
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls', 'ssl' albo ''
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('MAIL_FROM_EMAIL', 'urlopy@firma.local');
define('MAIL_FROM_NAME', 'Planer urlopów');

define('SESSION_NAME', 'urlopy_session');

// Domyślne hasło konta admina tworzonego przy pierwszym uruchomieniu.
define('DEFAULT_ADMIN_EMAIL', 'admin@firma.local');
define('DEFAULT_ADMIN_PASSWORD', 'admin123');
