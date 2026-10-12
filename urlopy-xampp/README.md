# Planer urlopów (wersja pod XAMPP: PHP + SQLite)

**Wersja: 1.9.1** (numer wersji trzymany w `config.php` jako `APP_VERSION`,
widoczny też w interfejsie obok nazwy aplikacji).

To jest wersja aplikacji "Planer urlopów" przystosowana do uruchomienia na
serwerze Apache z PHP, tak jak dostarcza je XAMPP. Funkcje są identyczne jak
w wersji Node.js:

1. Admin tworzy użytkowników i grupy użytkowników (działy).
2. Logowanie z rolami: **user** widzi swoje zdarzenia i zdarzenia swojego
   działu, **kierownik** dodatkowo widzi i zatwierdza urlopy w działach,
   którymi zarządza, **admin** widzi i zarządza wszystkim.
3. Każdy użytkownik wybiera własny kolor paska w kalendarzu.
4. Po dodaniu zdarzenia system wysyła e-mail do wszystkich osób w danym dziale.
5. W widoku Rok można przeciągać paski urlopów (zmiana terminu) i ciągnąć
   za ich brzegi (zmiana liczby dni).
6. Opcjonalny moduł zatwierdzania urlopów (włączany w Konfiguracji) oraz
   limity dni urlopowych (bieżący / zaległy) z automatycznym podliczaniem.

## Widoki kalendarza

Dostępne są trzy widoki, przełączane jednym przyciskiem w górnym pasku:

- **Rok** (domyślny) — układ w stylu Teamup: wszystkie miesiące roku jako
  wiersze, dni jako kolumny, zdarzenia jako kolorowe paski. Kliknięcie
  paska pokazuje dymek ze szczegółami (typ, zakres dat, osoba, notatka) i
  przyciskiem usuwania.
- **Miesiąc** — klasyczna siatka miesięczna (FullCalendar).
- **Lista** — chronologiczna lista zdarzeń w danym roku.

Panel boczny po lewej pozwala:
- ukrywać/pokazywać zdarzenia konkretnych osób (lista "Kalendarze"),
- filtrować według typu urlopu ("Filtruj wg typu"),
- przejść do zarządzania użytkownikami (admin) albo do własnego profilu
  (zwykły użytkownik) przez link "+ Dodaj / Edytuj".

Uwaga: filtrowanie w panelu bocznym działa we wszystkich trzech widokach,
natomiast nie ma odrębnych widoków Dzień/Tydzień/Oś czasu/Agenda/Tiles
znanych z Teamup — do planowania urlopów trzy powyższe widoki w zupełności
wystarczają, a ograniczenie zakresu upraszcza kod.

### Dodawanie i edycja kliknięciem

**Kliknięcie pustego dnia** (w widoku Rok albo Miesiąc) otwiera formularz
nowego zdarzenia z już wpisana data. **Przeciągnięcie po kilku dniach**
(w dowolnym kierunku, w obrębie tego samego wiersza/miesiąca w widoku Rok)
zaznacza całe od-do na raz i tak samo otwiera formularz z gotowym zakresem.
**Kliknięcie istniejącego zdarzenia**,
które możesz edytować (własne, albo dowolne dla admina, i nie jest jeszcze
zatwierdzone), otwiera ten sam formularz wypełniony aktualnymi danymi
(typ, zakres dat, notatka) — zmień co trzeba i kliknij "Zapisz". Z tego
samego okienka dostępne są też przyciski "Usuń" oraz (jeśli masz
uprawnienia) "Zatwierdź"/"Cofnij zatwierdzenie". Zdarzenia, których nie
możesz edytować (cudze, albo już zatwierdzone), po kliknięciu pokazują
zamiast tego krótki podgląd bez możliwości zmiany.

### Przeciąganie i zmiana długości urlopu (widok Rok)

W widoku **Rok** każdy edytowalny pasek urlopu można dodatkowo:
- **przeciągnąć** (chwytając środek paska) — przesuwa cały urlop o tyle dni,
  o ile przeciągnąłeś,
- **rozciągnąć za lewy albo prawy brzeg** — skraca albo wydłuża urlop od tej
  strony.

Zmiana zapisuje się automatycznie po puszczeniu przycisku myszy. Działa to
tylko jeśli zdarzenie mieści się całkowicie w obrębie jednego miesiąca —
urlopy przechodzące przez granice miesiąca nadal można edytować (typ,
notatkę, daty) przez kliknięcie i formularz, po prostu bez przeciągania
pikselowego. Przeciąganie działa wyłącznie w widoku Rok (nie w widokach
Miesiąc/Lista).

### Drukowanie wniosku urlopowego

Przy zdarzeniu (popover po kliknięciu paska w widoku Rok, albo formularz
edycji we wszystkich widokach) dostępny jest przycisk **"Drukuj wniosek"**.
Otwiera on w nowej karcie gotowy do wydruku wniosek urlopowy w formacie A5
poziomo (z logo firmy, jeśli plik `assets/img/logo-portos.png` jest obecny),
wypełniony danymi pracownika, działu, typu i zakresu dat urlopu (wartości
wyśrodkowane w polach) — pole "Zastępstwo pełni" oraz podpisy zostają puste
do ręcznego uzupełnienia. Drukuje się na zwykłej kartce **A4 w pionie**,
z treścią (w rozmiarze A5 poziomo) umieszczoną u góry strony — dolna połowa
kartki zostaje pusta, więc nie trzeba ładować do drukarki osobnego papieru
A5.

Przycisk jest widoczny tylko dla **bieżących i przyszłych** urlopów (nie dla
już zakończonych). Kierownik danego działu i administrator mogą drukować
zawsze, niezależnie od statusu. Zwykły użytkownik może wydrukować wyłącznie
**własny** wniosek i tylko **przed jego zatwierdzeniem** przez kierownika —
po zatwierdzeniu (gdy moduł zatwierdzania jest włączony) przycisk znika,
bo druk służy do uzyskania zatwierdzenia, które w systemie już się dokonało.
Nazwę firmy i miejscowość widoczne na wydruku ("Miejscowość, dnia ...")
można ustawić w **Konfiguracja**.

## Moduł zatwierdzania urlopów (opcjonalny)

Włącza się go w **Konfiguracja -> Moduł zatwierdzania urlopów**
(dostępne tylko dla administratora, [admin_settings.php](admin_settings.php)).
Domyślnie jest **wyłączony**.

Po włączeniu:
- każde nowe zdarzenie ma status **Wprowadzony** i nie jest jeszcze
  ostateczne,
- zatwierdzić może je **kierownik** działu, w którym powstało, albo
  **administrator** — status zmienia się wtedy na **Zatwierdzony**,
  a właściciel urlopu dostaje o tym maila,
- zdarzenia zatwierdzone są zablokowane przed przesuwaniem, zmiana długości
  i usunięciem przez zwykłego użytkownika (admin może zawsze); zatwierdzenie
  można cofnąć przyciskiem w dymku szczegółów,
- w widoku Rok wprowadzone-ale-niezatwierdzone urlopy mają dodatkowy,
  biały "szrafowany" obrys (legenda widoczna w panelu bocznym).

Po wyłączeniu modułu nowe zdarzenia są od razu zatwierdzone — bez
dodatkowego kroku, dokładnie jak przed wersja 1.4.0.

### Rola "kierownik"

Kierownika tworzy/edytuje admin w **Użytkownicy** ([admin_users.php](admin_users.php)):
wybierz rolę "Kierownik", a opcjonalnie w polu **"Działy zarządzane"**
zaznacz dodatkowe działy spoza swojego (przytrzymaj Ctrl/Cmd, żeby zaznaczyć
kilka). Kierownik ma też własny dział "macierzysty" (jak każdy użytkownik) —
widzi w kalendarzu swoj dział oraz wszystkie działy, którymi zarządza, i może
zatwierdzać urlopy zarówno we własnym dziale, jak i w dodatkowych działach
zarządzanych.

## Limity urlopowe

Każdy użytkownik ma dwa pola limitu w **Użytkownicy**: **Limit bieżący**
i **Limit zaległy** (w dniach, można wpisać np. "0.5" dla pol-dnia). Suma
obu to limit do wykorzystania w danym roku kalendarzowym.

Aplikacja automatycznie liczy, ile dni urlopu **wypoczynkowego** i **na
żądanie** (te dwa typy są czerpane z tej samej puli w polskim prawie pracy)
dany użytkownik wprowadził do kalendarza w bieżącym roku — niezależnie od
statusu zatwierdzenia. Podsumowanie (Limit / Wykorzystano / Pozostało)
widać przy każdym użytkowniku w tabeli w **Użytkownicy**, a każdy użytkownik
widzi własny bilans w **Mój profil**.

## Baza danych

Domyślnie aplikacja używa **SQLite** — jednego pliku (`data/urlopy.sqlite`),
tworzonego automatycznie przy pierwszym uruchomieniu. Nie trzeba w ogóle
uruchamiać MySQL w XAMPP — wystarczy sam Apache.

Opcjonalnie można przełączyć się na **MySQL/MariaDB** albo
**Microsoft SQL Server**, zmieniając jedna linijkę w [`config.php`](config.php):

```php
define('DB_DRIVER', 'sqlite'); // 'sqlite' (domyślnie) | 'mysql' | 'sqlsrv'
```

W każdym przypadku tabele i domyślne konto administratora tworzą się
**automatycznie** przy pierwszym użyciu — nie trzeba nic importować ręcznie.
Pliki `schema.sqlite.sql`, `schema.mysql.sql`, `schema.mssql.sql` są
dołączone wyłącznie jako dokumentacja struktury / do ręcznego podejrzenia.

### Konektor MySQL / MariaDB

Ustaw `DB_DRIVER` na `'mysql'` i uzupełnij dane połączenia (domyślne pasują
do standardowej instalacji XAMPP: `root` bez hasła):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'urlopy');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Pamiętaj, żeby uruchomić **MySQL** w panelu XAMPP Control Panel, gdy
korzystasz z tego trybu.

### Konektor Microsoft SQL Server

Ustaw `DB_DRIVER` na `'sqlsrv'` i uzupełnij:

```php
define('MSSQL_HOST', 'localhost');
define('MSSQL_DB', 'urlopy');
define('MSSQL_USER', 'sa');
define('MSSQL_PASS', '');
```

Wymaga to doinstalowania w PHP rozszerzenia `pdo_sqlsrv` z pakietu
[Microsoft Drivers for PHP for SQL Server](https://learn.microsoft.com/pl-pl/sql/connect/php/microsoft-php-driver-for-sql-server)
— nie jest ono częścią standardowego XAMPP. Baza `urlopy` musi już istnieć
na serwerze SQL Server (aplikacja tworzy tylko tabele, nie sama baze).

## Instalacja (krok po kroku, tryb domyślny — SQLite)

1. Zainstaluj [XAMPP](https://www.apachefriends.org/) (Apache + PHP, wersja
   PHP 8.x). MySQL nie jest wymagany w trybie domyślnym.
2. Uruchom **Apache** w panelu XAMPP Control Panel.
3. Skopiuj cały folder `urlopy-xampp` do katalogu `htdocs` XAMPP, np.:
   - Windows: `C:\xampp\htdocs\urlopy\`
   - (czyli zawartość tego folderu, nie sam folder `urlopy-xampp` w środku
     kolejnego podfolderu — pliki `index.php`, `login.php` itd. mają leżeć
     bezpośrednio w `C:\xampp\htdocs\urlopy\`)
4. Otwórz w przeglądarce: `http://localhost/urlopy/`
5. Plik bazy `data/urlopy.sqlite` oraz wszystkie tabele **tworzą się
   automatycznie** przy pierwszym wejściu na stronę. Tworzy się też
   domyślne konto administratora.

Domyślne konto administratora:

- e-mail: `admin@firma.local`
- hasło: `admin123`

**Zmień to hasło od razu po pierwszym zalogowaniu** (Mój profil -> Zmiana hasła).

## Konfiguracja wysyłki e-mail

Każde nowe zdarzenie urlopowe generuje powiadomienie e-mail do wszystkich
członków działu osoby, która je dodala (poza nia samą).

Adres/nazwę nadawcy oraz dane serwera SMTP konfiguruje się wygodnie z
poziomu aplikacji: zaloguj się jako **administrator** i wejdz w
**Poczta** w górnym menu ([admin_mail.php](admin_mail.php)). Ustawienia
zapisywane są w bazie danych (tabela `settings`) i mają pierwszeństwo przed
stałymi w `config.php`. Na tym ekranie jest też przycisk do wysłania
testowej wiadomości na adres zalogowanego administratora.

Bez skonfigurowanego serwera SMTP (pole "Serwer SMTP" puste) aplikacja
działa normalnie, ale treść maili jest tylko zapisywana do pliku
`logs/mail.log` zamiast być faktycznie wysłana (przydatne do testów).

Stale w `config.php` (`SMTP_HOST`, `MAIL_FROM_EMAIL` itd.) służą jedynie
jako wartości startowe/awaryjne, dopóki administrator nic nie zapisze w
panelu "Poczta":

```php
define('SMTP_HOST', 'smtp.twojafirma.pl');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // albo 'ssl'
define('SMTP_USER', 'twoj_login_smtp');
define('SMTP_PASS', 'twoje_haslo_smtp');
define('MAIL_FROM_EMAIL', 'urlopy@twojafirma.pl');
define('MAIL_FROM_NAME', 'Planer urlopów');
```

Wysyłka realizowana jest przez dołączona biblioteke PHPMailer
(`libs/PHPMailer/`), bez potrzeby instalowania Composera.

### Link do zgłoszenia w mailu

Każde powiadomienie o zdarzeniu urlopowym (nowy wpis, prośba o
zatwierdzenie, informacja o zatwierdzeniu) zawiera link "Zobacz w
systemie", który po kliknięciu otwiera dany wpis bezpośrednio w
kalendarzu (po ewentualnym zalogowaniu — aplikacja pamięta, dokąd
wrócić). Adres (IP albo domena), z jakiego zbudowany jest ten link,
**wykrywany jest automatycznie** z bieżącego żądania HTTP — nie trzeba
nic konfigurować. Jeśli ten adres bywa niestabilny (np. zmienne IP w
sieci lokalnej), można go ustawić na sztywno w **Poczta** -> "Adres
aplikacji".

## Typy zdarzeń

Domyślnie: Urlop wypoczynkowy, Urlop na żądanie, Urlop z nadgodzin, Wizyta
lekarska, Inne. Administrator może dodawać własne typy, zmieniać ich nazwy
oraz usuwać nieużywane w **Konfiguracja -> Typy urlopów**
([admin_event_types.php](admin_event_types.php)) - dla każdego typu można
ustawić, czy jego dni liczą się do rocznego limitu urlopowego użytkownika.
Typy "Urlop wypoczynkowy" i "Urlop na żądanie" można dowolnie przemianować,
ale nie można ich usunąć - są strukturalnie powiązane z wydrukiem wniosku
urlopowego (checkboxy "Wypoczynkowego" / "Na żądanie" na formularzu A5).
Typ używany w istniejących zdarzeniach też nie może zostać usunięty, dopóki
żadne zdarzenie go nie używa.

## Struktura projektu

```
urlopy-xampp/
  config.php             - wybór bazy danych (DB_DRIVER), dane połączeń, SMTP
  schema.sqlite.sql       - dokumentacja struktury (SQLite, domyślny silnik)
  schema.mysql.sql        - dokumentacja struktury (opcjonalny konektor MySQL)
  schema.mssql.sql        - dokumentacja struktury (opcjonalny konektor MS SQL)
  bootstrap.php           - wspólny start każdej strony (sesja, połączenia)
  index.php, login.php, logout.php
  calendar.php            - widok kalendarza (Rok/Miesiąc/Lista, drag&drop)
  api_events.php          - zwraca zdarzenia jako JSON (filtrowane wg roli/działów)
  api_calendars.php       - lista kalendarzy (osób) + ustawienia dla UI
  events_create.php, events_update.php, events_delete.php, events_approve.php
  profile.php, profile_color.php, profile_password.php
  admin_users.php, admin_groups.php, admin_mail.php, admin_mail_test.php
  admin_event_types.php   - Konfiguracja: typy urlopów definiowane przez admina
  admin_settings.php      - Konfiguracja: włącznik modułu zatwierdzania urlopów
  print_leave.php         - wydruk wniosku urlopowego (A5 na kartce A4)
  includes/               - połączenie z baza (wielosilnikowe + migracje),
                             autoryzacja, role/uprawnienia, limity urlopowe,
                             adres aplikacji do linków w mailach, layout
  libs/PHPMailer/          - biblioteka do wysyłki e-mail (SMTP)
  assets/                  - CSS i biblioteka kalendarza (FullCalendar)
  data/urlopy.sqlite       - plik bazy SQLite (tryb domyślny, tworzony automatycznie)
  logs/mail.log            - log wysłanych/próbowanych maili
```

## Uwagi bezpieczeństwa

- Hasła są haszowane (`password_hash`/`password_verify`), nigdy nie są
  przechowywane w postaci czystego tekstu.
- Sesja logowania oparta na natywnych sesjach PHP.
- Katalogi `data/`, `logs/`, `includes/` i `libs/` mają własne pliki
  `.htaccess` blokujace bezpośredni dostęp przez przeglądarkę — to ważne
  zwłaszcza dla `data/`, bo w trybie SQLite cały plik bazy danych leży w
  katalogu serwowanym przez Apache. Jeśli Twoj serwer nie używa Apache
  (`.htaccess` działa tylko z nim), przenieś katalog `data/` poza
  document root i zaktualizuj `SQLITE_PATH` w `config.php`.
- Przed wystawieniem aplikacji poza siec lokalna: w trybie MySQL ustaw
  hasło do konta bazy danych (nie zostawiaj `root` bez hasła), rozważ HTTPS
  oraz wyłącz wyświetlanie błędów PHP na produkcji
  (`display_errors = Off` w `php.ini`).
