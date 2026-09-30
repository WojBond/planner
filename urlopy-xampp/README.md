# Planer urlopow (wersja pod XAMPP: PHP + SQLite)

**Wersja: 1.3.0** (numer wersji trzymany w `config.php` jako `APP_VERSION`,
widoczny tez w interfejsie obok nazwy aplikacji).

To jest wersja aplikacji "Planer urlopow" przystosowana do uruchomienia na
serwerze Apache z PHP, tak jak dostarcza je XAMPP. Funkcje sa identyczne jak
w wersji Node.js:

1. Admin tworzy uzytkownikow i grupy uzytkownikow (dzialy).
2. Logowanie z rolami: **user** widzi swoje zdarzenia i zdarzenia swojego
   dzialu, **admin** widzi wszystkie zdarzenia.
3. Kazdy uzytkownik wybiera wlasny kolor paska w kalendarzu.
4. Po dodaniu zdarzenia system wysyla e-mail do wszystkich osob w danym dziale.

## Widoki kalendarza

Dostepne sa trzy widoki, przelaczane jednym przyciskiem w gornym pasku:

- **Rok** (domyslny) — uklad w stylu Teamup: wszystkie miesiace roku jako
  wiersze, dni jako kolumny, zdarzenia jako kolorowe paski. Klikniecie
  paska pokazuje dymek ze szczegolami (typ, zakres dat, osoba, notatka) i
  przyciskiem usuwania.
- **Miesiac** — klasyczna siatka miesieczna (FullCalendar).
- **Lista** — chronologiczna lista zdarzen w danym roku.

Panel boczny po lewej pozwala:
- ukrywac/pokazywac zdarzenia konkretnych osob (lista "Kalendarze"),
- filtrowac wedlug typu urlopu ("Filtruj wg typu"),
- przejsc do zarzadzania uzytkownikami (admin) albo do wlasnego profilu
  (zwykly uzytkownik) przez link "+ Dodaj / Edytuj".

Uwaga: filtrowanie w panelu bocznym dziala we wszystkich trzech widokach,
natomiast nie ma odrebnych widokow Dzien/Tydzien/Oś czasu/Agenda/Tiles
znanych z Teamup — do planowania urlopow trzy powyzsze widoki w zupelnosci
wystarczaja, a ograniczenie zakresu upraszcza kod.

## Baza danych

Domyslnie aplikacja uzywa **SQLite** — jednego pliku (`data/urlopy.sqlite`),
tworzonego automatycznie przy pierwszym uruchomieniu. Nie trzeba w ogole
uruchamiac MySQL w XAMPP — wystarczy sam Apache.

Opcjonalnie mozna przelaczyc sie na **MySQL/MariaDB** albo
**Microsoft SQL Server**, zmieniajac jedna linijke w [`config.php`](config.php):

```php
define('DB_DRIVER', 'sqlite'); // 'sqlite' (domyslnie) | 'mysql' | 'sqlsrv'
```

W kazdym przypadku tabele i domyslne konto administratora tworza sie
**automatycznie** przy pierwszym uzyciu — nie trzeba nic importowac recznie.
Pliki `schema.sqlite.sql`, `schema.mysql.sql`, `schema.mssql.sql` sa
dolaczone wylacznie jako dokumentacja struktury / do recznego podejrzenia.

### Konektor MySQL / MariaDB

Ustaw `DB_DRIVER` na `'mysql'` i uzupelnij dane polaczenia (domyslne pasuja
do standardowej instalacji XAMPP: `root` bez hasla):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'urlopy');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Pamietaj, zeby uruchomic **MySQL** w panelu XAMPP Control Panel, gdy
korzystasz z tego trybu.

### Konektor Microsoft SQL Server

Ustaw `DB_DRIVER` na `'sqlsrv'` i uzupelnij:

```php
define('MSSQL_HOST', 'localhost');
define('MSSQL_DB', 'urlopy');
define('MSSQL_USER', 'sa');
define('MSSQL_PASS', '');
```

Wymaga to doinstalowania w PHP rozszerzenia `pdo_sqlsrv` z pakietu
[Microsoft Drivers for PHP for SQL Server](https://learn.microsoft.com/pl-pl/sql/connect/php/microsoft-php-driver-for-sql-server)
— nie jest ono czescia standardowego XAMPP. Baza `urlopy` musi juz istniec
na serwerze SQL Server (aplikacja tworzy tylko tabele, nie sama baze).

## Instalacja (krok po kroku, tryb domyslny — SQLite)

1. Zainstaluj [XAMPP](https://www.apachefriends.org/) (Apache + PHP, wersja
   PHP 8.x). MySQL nie jest wymagany w trybie domyslnym.
2. Uruchom **Apache** w panelu XAMPP Control Panel.
3. Skopiuj caly folder `urlopy-xampp` do katalogu `htdocs` XAMPP, np.:
   - Windows: `C:\xampp\htdocs\urlopy\`
   - (czyli zawartosc tego folderu, nie sam folder `urlopy-xampp` w srodku
     kolejnego podfolderu — pliki `index.php`, `login.php` itd. maja lezec
     bezposrednio w `C:\xampp\htdocs\urlopy\`)
4. Otworz w przegladarce: `http://localhost/urlopy/`
5. Plik bazy `data/urlopy.sqlite` oraz wszystkie tabele **tworza sie
   automatycznie** przy pierwszym wejsciu na strone. Tworzy sie tez
   domyslne konto administratora.

Domyslne konto administratora:

- e-mail: `admin@firma.local`
- haslo: `admin123`

**Zmien to haslo od razu po pierwszym zalogowaniu** (Moj profil -> Zmiana hasla).

## Konfiguracja wysylki e-mail

Kazde nowe zdarzenie urlopowe generuje powiadomienie e-mail do wszystkich
czlonkow dzialu osoby, ktora je dodala (poza nia samą).

Adres/nazwe nadawcy oraz dane serwera SMTP konfiguruje sie wygodnie z
poziomu aplikacji: zaloguj sie jako **administrator** i wejdz w
**Poczta** w gornym menu ([admin_mail.php](admin_mail.php)). Ustawienia
zapisywane sa w bazie danych (tabela `settings`) i maja pierwszenstwo przed
stalymi w `config.php`. Na tym ekranie jest tez przycisk do wyslania
testowej wiadomosci na adres zalogowanego administratora.

Bez skonfigurowanego serwera SMTP (pole "Serwer SMTP" puste) aplikacja
dziala normalnie, ale tresc maili jest tylko zapisywana do pliku
`logs/mail.log` zamiast byc faktycznie wyslana (przydatne do testow).

Stale w `config.php` (`SMTP_HOST`, `MAIL_FROM_EMAIL` itd.) sluza jedynie
jako wartosci startowe/awaryjne, dopoki administrator nic nie zapisze w
panelu "Poczta":

```php
define('SMTP_HOST', 'smtp.twojafirma.pl');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // albo 'ssl'
define('SMTP_USER', 'twoj_login_smtp');
define('SMTP_PASS', 'twoje_haslo_smtp');
define('MAIL_FROM_EMAIL', 'urlopy@twojafirma.pl');
define('MAIL_FROM_NAME', 'Planer urlopow');
```

Wysylka realizowana jest przez dolaczona biblioteke PHPMailer
(`libs/PHPMailer/`), bez potrzeby instalowania Composera.

## Typy zdarzen

Urlop wypoczynkowy, Urlop na zadanie, Urlop z nadgodzin, Wizyta lekarska, Inne.

## Struktura projektu

```
urlopy-xampp/
  config.php             - wybor bazy danych (DB_DRIVER), dane polaczen, SMTP
  schema.sqlite.sql       - dokumentacja struktury (SQLite, domyslny silnik)
  schema.mysql.sql        - dokumentacja struktury (opcjonalny konektor MySQL)
  schema.mssql.sql        - dokumentacja struktury (opcjonalny konektor MS SQL)
  bootstrap.php           - wspolny start kazdej strony (sesja, polaczenia)
  index.php, login.php, logout.php
  calendar.php            - widok kalendarza (FullCalendar)
  api_events.php          - zwraca zdarzenia jako JSON (filtrowane wg roli)
  events_create.php, events_delete.php
  profile.php, profile_color.php, profile_password.php
  admin_users.php, admin_groups.php, admin_mail.php, admin_mail_test.php
  includes/               - polaczenie z baza (wielosilnikowe), autoryzacja, layout
  libs/PHPMailer/          - biblioteka do wysylki e-mail (SMTP)
  assets/                  - CSS i biblioteka kalendarza (FullCalendar)
  data/urlopy.sqlite       - plik bazy SQLite (tryb domyslny, tworzony automatycznie)
  logs/mail.log            - log wyslanych/probowanych maili
```

## Uwagi bezpieczenstwa

- Hasla sa haszowane (`password_hash`/`password_verify`), nigdy nie sa
  przechowywane w postaci czystego tekstu.
- Sesja logowania oparta na natywnych sesjach PHP.
- Katalogi `data/`, `logs/`, `includes/` i `libs/` maja wlasne pliki
  `.htaccess` blokujace bezposredni dostep przez przegladarke — to wazne
  zwlaszcza dla `data/`, bo w trybie SQLite caly plik bazy danych lezy w
  katalogu serwowanym przez Apache. Jesli Twoj serwer nie uzywa Apache
  (`.htaccess` dziala tylko z nim), przenies katalog `data/` poza
  document root i zaktualizuj `SQLITE_PATH` w `config.php`.
- Przed wystawieniem aplikacji poza siec lokalna: w trybie MySQL ustaw
  haslo do konta bazy danych (nie zostawiaj `root` bez hasla), rozwaz HTTPS
  oraz wylacz wyswietlanie bledow PHP na produkcji
  (`display_errors = Off` w `php.ini`).
