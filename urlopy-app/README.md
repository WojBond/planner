# Planer urlopów

**Wersja: 1.0.0** (numer wersji trzymany w `src/version.js` jako `APP_VERSION`,
widoczny też w interfejsie obok nazwy aplikacji).

Lokalna aplikacja webowa do planowania urlopów, zainspirowana kalendarzem Teamup.
Działa całkowicie lokalnie (Node.js + Express), dane są zapisywane w pliku `data/db.json`.

## Funkcje

1. Admin tworzy użytkowników i grupy użytkowników (działy).
2. Logowanie z rolami:
   - **user** - widzi swoje zdarzenia oraz zdarzenia swojego działu,
   - **admin** - widzi wszystkie zdarzenia w systemie.
3. Każdy użytkownik może wybrac własny kolor paska w kalendarzu (w profilu).
4. Po dodaniu zdarzenia system wysyła e-mail do wszystkich osób w danym dziale.

## Instalacja i uruchomienie

```bash
cd urlopy-app
npm install
npm start
```

Aplikacja wystartuje na `http://localhost:3000`.

Domyślne konto administratora tworzone przy pierwszym starcie:

- e-mail: `admin@firma.local`
- hasło: `admin123`

**Zmień to hasło od razu po pierwszym zalogowaniu** (Mój profil -> Zmiana hasła).

## Konfiguracja wysyłki e-mail

Bez konfiguracji SMTP aplikacja działa normalnie, ale treść maili jest tylko
wypisywana w konsoli serwera (przydatne do testów).

Aby wysyłać prawdziwe maile:

1. Skopiuj `.env.example` do `.env`.
2. Wpisz dane swojego serwera SMTP (np. firmowej skrzynki, Gmail z hasiem
   aplikacji, SendGrid, Mailtrap itp.) w polach `SMTP_HOST`, `SMTP_PORT`,
   `SMTP_USER`, `SMTP_PASS`.
3. Zrestartuj serwer (`npm start`).

## Typy zdarzeń

Urlop wypoczynkowy, Urlop na żądanie, Urlop z nadgodzin, Wizyta lekarska, Inne.

## Struktura danych

Wszystko trzymane jest w jednym pliku `data/db.json` (tworzony automatycznie):
użytkownicy, działy (grupy) i zdarzenia urlopowe. Do backupu wystarczy
skopiowac ten plik.

## Uwagi bezpieczeństwa

- Hasła są haszowane (bcrypt), nigdy nie są przechowywane w postaci czystego tekstu.
- Sesja logowania oparta na `express-session` (cookie httpOnly).
- Przed wystawieniem aplikacji poza siec lokalna zmień `SESSION_SECRET` w `.env`
  i rozważ uzycie HTTPS (np. przez reverse proxy).
