# Planer urlopow

**Wersja: 1.0.0** (numer wersji trzymany w `src/version.js` jako `APP_VERSION`,
widoczny tez w interfejsie obok nazwy aplikacji).

Lokalna aplikacja webowa do planowania urlopow, zainspirowana kalendarzem Teamup.
Dziala calkowicie lokalnie (Node.js + Express), dane sa zapisywane w pliku `data/db.json`.

## Funkcje

1. Admin tworzy uzytkownikow i grupy uzytkownikow (dzialy).
2. Logowanie z rolami:
   - **user** - widzi swoje zdarzenia oraz zdarzenia swojego dzialu,
   - **admin** - widzi wszystkie zdarzenia w systemie.
3. Kazdy uzytkownik moze wybrac wlasny kolor paska w kalendarzu (w profilu).
4. Po dodaniu zdarzenia system wysyla e-mail do wszystkich osob w danym dziale.

## Instalacja i uruchomienie

```bash
cd urlopy-app
npm install
npm start
```

Aplikacja wystartuje na `http://localhost:3000`.

Domyslne konto administratora tworzone przy pierwszym starcie:

- e-mail: `admin@firma.local`
- haslo: `admin123`

**Zmien to haslo od razu po pierwszym zalogowaniu** (Moj profil -> Zmiana hasla).

## Konfiguracja wysylki e-mail

Bez konfiguracji SMTP aplikacja dziala normalnie, ale tresc maili jest tylko
wypisywana w konsoli serwera (przydatne do testow).

Aby wysylac prawdziwe maile:

1. Skopiuj `.env.example` do `.env`.
2. Wpisz dane swojego serwera SMTP (np. firmowej skrzynki, Gmail z hasiem
   aplikacji, SendGrid, Mailtrap itp.) w polach `SMTP_HOST`, `SMTP_PORT`,
   `SMTP_USER`, `SMTP_PASS`.
3. Zrestartuj serwer (`npm start`).

## Typy zdarzen

Urlop wypoczynkowy, Urlop na zadanie, Urlop z nadgodzin, Wizyta lekarska, Inne.

## Struktura danych

Wszystko trzymane jest w jednym pliku `data/db.json` (tworzony automatycznie):
uzytkownicy, dzialy (grupy) i zdarzenia urlopowe. Do backupu wystarczy
skopiowac ten plik.

## Uwagi bezpieczenstwa

- Hasla sa haszowane (bcrypt), nigdy nie sa przechowywane w postaci czystego tekstu.
- Sesja logowania oparta na `express-session` (cookie httpOnly).
- Przed wystawieniem aplikacji poza siec lokalna zmien `SESSION_SECRET` w `.env`
  i rozwaz uzycie HTTPS (np. przez reverse proxy).
