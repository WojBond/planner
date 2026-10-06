# Planer urlopow (wersja pod XAMPP: PHP + SQLite)

**Wersja: 1.7.0** (numer wersji trzymany w `config.php` jako `APP_VERSION`,
widoczny tez w interfejsie obok nazwy aplikacji).

To jest wersja aplikacji "Planer urlopow" przystosowana do uruchomienia na
serwerze Apache z PHP, tak jak dostarcza je XAMPP. Funkcje sa identyczne jak
w wersji Node.js:

1. Admin tworzy uzytkownikow i grupy uzytkownikow (dzialy).
2. Logowanie z rolami: **user** widzi swoje zdarzenia i zdarzenia swojego
   dzialu, **kierownik** dodatkowo widzi i zatwierdza urlopy w dzialach,
   ktorymi zarzadza, **admin** widzi i zarzadza wszystkim.
3. Kazdy uzytkownik wybiera wlasny kolor paska w kalendarzu.
4. Po dodaniu zdarzenia system wysyla e-mail do wszystkich osob w danym dziale.
5. W widoku Rok mozna przeciagac paski urlopow (zmiana terminu) i ciagnac
   za ich brzegi (zmiana liczby dni).
6. Opcjonalny modul zatwierdzania urlopow (wlaczany w Konfiguracji) oraz
   limity dni urlopowych (biezacy / zalegly) z automatycznym podliczaniem.

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

### Dodawanie i edycja klikniemem

**Klikniecie pustego dnia** (w widoku Rok albo Miesiac) otwiera formularz
nowego zdarzenia z juz wpisana data. **Przeciagniecie po kilku dniach**
(w dowolnym kierunku, w obrebie tego samego wiersza/miesiaca w widoku Rok)
zaznacza cale od-do na raz i tak samo otwiera formularz z gotowym zakresem.
**Klikniecie istniejacego zdarzenia**,
ktore mozesz edytowac (wlasne, albo dowolne dla admina, i nie jest jeszcze
zatwierdzone), otwiera ten sam formularz wypelniony aktualnymi danymi
(typ, zakres dat, notatka) — zmien co trzeba i kliknij "Zapisz". Z tego
samego okienka dostepne sa tez przyciski "Usun" oraz (jesli masz
uprawnienia) "Zatwierdz"/"Cofnij zatwierdzenie". Zdarzenia, ktorych nie
mozesz edytowac (cudze, albo juz zatwierdzone), po kliknieciu pokazuja
zamiast tego krotki podglad bez mozliwosci zmiany.

### Przeciaganie i zmiana dlugosci urlopu (widok Rok)

W widoku **Rok** kazdy edytowalny pasek urlopu mozna dodatkowo:
- **przeciagnac** (chwytajac srodek paska) — przesuwa caly urlop o tyle dni,
  o ile przeciagnales,
- **rozciagnac za lewy albo prawy brzeg** — skraca albo wydluza urlop od tej
  strony.

Zmiana zapisuje sie automatycznie po puszczeniu przycisku myszy. Dziala to
tylko jesli zdarzenie miesci sie calkowicie w obrebie jednego miesiaca —
urlopy przechodzace przez granice miesiaca nadal mozna edytowac (typ,
notatke, daty) przez klikniecie i formularz, po prostu bez przeciagania
pikselowego. Przeciaganie dziala wylacznie w widoku Rok (nie w widokach
Miesiac/Lista).

## Modul zatwierdzania urlopow (opcjonalny)

Wlacza sie go w **Konfiguracja -> Modul zatwierdzania urlopow**
(dostepne tylko dla administratora, [admin_settings.php](admin_settings.php)).
Domyslnie jest **wylaczony**.

Po wlaczeniu:
- kazde nowe zdarzenie ma status **Wprowadzony** i nie jest jeszcze
  ostateczne,
- zatwierdzic moze je **kierownik** dzialu, w ktorym powstalo, albo
  **administrator** — status zmienia sie wtedy na **Zatwierdzony**,
  a wlasciciel urlopu dostaje o tym maila,
- zdarzenia zatwierdzone sa zablokowane przed przesuwaniem, zmiana dlugosci
  i usunieciem przez zwyklego uzytkownika (admin moze zawsze); zatwierdzenie
  mozna cofnac przyciskiem w dymku szczegolow,
- w widoku Rok wprowadzone-ale-niezatwierdzone urlopy maja dodatkowy,
  biały "szrafowany" obrys (legenda widoczna w panelu bocznym).

Po wylaczeniu modulu nowe zdarzenia sa od razu zatwierdzone — bez
dodatkowego kroku, dokladnie jak przed wersja 1.4.0.

### Rola "kierownik"

Kierownika tworzy/edytuje admin w **Uzytkownicy** ([admin_users.php](admin_users.php)):
wybierz rolę "Kierownik", a nastepnie w polu **"Dzialy zarzadzane"**
zaznacz jeden lub wiecej dzialow (przytrzymaj Ctrl/Cmd, zeby zaznaczyc kilka).
Kierownik ma tez wlasny dzial "macierzysty" (jak kazdy uzytkownik) — widzi w
kalendarzu swoj dzial oraz wszystkie dzialy, ktorymi zarzadza, i moze
zatwierdzac urlopy wylacznie w tych ostatnich.

## Limity urlopowe

Kazdy uzytkownik ma dwa pola limitu w **Uzytkownicy**: **Limit biezacy**
i **Limit zalegly** (w dniach, mozna wpisac np. "0.5" dla pol-dnia). Suma
obu to limit do wykorzystania w danym roku kalendarzowym.

Aplikacja automatycznie liczy, ile dni urlopu **wypoczynkowego** i **na
zadanie** (te dwa typy sa czerpane z tej samej puli w polskim prawie pracy)
dany uzytkownik wprowadzil do kalendarza w biezacym roku — niezaleznie od
statusu zatwierdzenia. Podsumowanie (Limit / Wykorzystano / Pozostalo)
widac przy kazdym uzytkowniku w tabeli w **Uzytkownicy**, a kazdy uzytkownik
widzi wlasny bilans w **Moj profil**.

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

### Link do zgloszenia w mailu

Kazde powiadomienie o zdarzeniu urlopowym (nowy wpis, prosba o
zatwierdzenie, informacja o zatwierdzeniu) zawiera link "Zobacz w
systemie", ktory po kliknieciu otwiera dany wpis bezposrednio w
kalendarzu (po ewentualnym zalogowaniu — aplikacja pamieta, dokad
wrocic). Adres (IP albo domena), z jakiego zbudowany jest ten link,
**wykrywany jest automatycznie** z biezacego zadania HTTP — nie trzeba
nic konfigurowac. Jesli ten adres bywa niestabilny (np. zmienne IP w
sieci lokalnej), mozna go ustawic na sztywno w **Poczta** -> "Adres
aplikacji".

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
  calendar.php            - widok kalendarza (Rok/Miesiac/Lista, drag&drop)
  api_events.php          - zwraca zdarzenia jako JSON (filtrowane wg roli/dzialow)
  api_calendars.php       - lista kalendarzy (osob) + ustawienia dla UI
  events_create.php, events_update.php, events_delete.php, events_approve.php
  profile.php, profile_color.php, profile_password.php
  admin_users.php, admin_groups.php, admin_mail.php, admin_mail_test.php
  admin_settings.php      - Konfiguracja: wlacznik modulu zatwierdzania urlopow
  includes/               - polaczenie z baza (wielosilnikowe + migracje),
                             autoryzacja, role/uprawnienia, limity urlopowe,
                             adres aplikacji do linkow w mailach, layout
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
