# PPUT Ostrans — Implementacja Wymagań

## Spis Treści

1. [Logowanie i Autoryzacja (F1–F5)](#logowanie-i-autoryzacja)
2. [Zarządzanie Pracownikami (F6–F9)](#zarządzanie-pracownikami)
3. [Zarządzanie Pojazdami](#zarządzanie-pojazdami)
4. [Struktury Ról i Uprawnień](#struktury-ról-i-uprawnień)
5. [Architektura Techniczna](#architektura-techniczna)
6. [API Endpoints](#api-endpoints)

---

## Logowanie i Autoryzacja

### F1: Logowanie za pomocą loginu + hasła

**Implementacja:**
- Forma logowania: [panel/app/views/login.php](panel/app/views/login.php)
- Kontroler: [HomeController.login()](panel/app/controllers/HomeController.php)
- Endpoint API: `POST /api/login` → [panel/api.php](panel/api.php)
- Baza danych: Tabela `pracownicy` (kolumny: `login`, `haslo`)

**Flow:**
1. Użytkownik wprowadza login i hasło na formie logowania
2. HomeController.login() waliduje dane, sprawdza hasło (wsparcie BCRYPT i plaintext)
3. Sesja PHP ustawiająca `$_SESSION['user']`
4. Redirect na dashboard lub ponowne pokazanie formy z błędem

### F2: Logowanie przez Discord OAuth2

**Implementacja:**
- Endpoint: [auth/discord.php](auth/discord.php) — redirect do Discord
- Callback: [auth/discord_callback.php](auth/discord_callback.php) — przetworzenie kodu
- Integracja: Automatyczne mapowanie ról z roliami na serwerze Discord
- Zmienne env: `DISCORD_CLIENT_ID`, `DISCORD_CLIENT_SECRET`, `DISCORD_REDIRECT_URI`, `DISCORD_GUILD_ID`, `DISCORD_BOT_TOKEN`, `ROLE_ZARZAD_ID`, `ROLE_DYSP_ID`, `ROLE_KIEROWCA_ID`

**Zmienne env (env.txt):**
```
DISCORD_CLIENT_ID=<twój_client_id>
DISCORD_CLIENT_SECRET=<twój_secret>
DISCORD_REDIRECT_URI=https://twoja-domena.pl/auth/discord/callback
DISCORD_GUILD_ID=<id_serwera>
DISCORD_BOT_TOKEN=<bot_token>
ROLE_ZARZAD_ID=<role_id>
ROLE_DYSP_ID=<role_id>
ROLE_KIEROWCA_ID=<role_id>
```

### F3: System Uprawnień (3 główne role + stanowiska)

**Główne Role (poziom):
1. **kierowca** — dostęp tylko do własnych danych
2. **dyspozytor** — zarządzanie brygadami, liniami, pojazami + wszystkie uprawnienia kierowcy
3. **zarzad** (zarząd) — pełny dostęp do systemu, zarządzanie pracownikami

**Stanowiska (opcjonalne, tabela `stanowiska`):**
- Mogą być definiowane jako życzenie, domyślnie system pracuje z rolami

**Implementacja:**
- Tabela `uprawnienia` mapuje role na poziomy (`id`, `poziom`)
- Każdy pracownik ma `uprawnienie_id` → `uprawnienia.poziom`
- Helper: [panel/app/helpers/AuthHelper.php](panel/app/helpers/AuthHelper.php)

```php
AuthHelper::isDriver($user);       // Check if kierowca
AuthHelper::isDispatcher($user);   // Check if dyspozytor or zarzad
AuthHelper::isManagement($user);   // Check if zarzad
```

### F4: Reset Hasła

**Implementacja:**
- Endpoint: `POST /api/password-reset/request` — poproszenie o reset (login)
- Endpoint: `POST /api/password-reset/confirm` — potwierdzenie resetem tokenu
- Metody w ApiController: `requestPasswordReset()`, `resetPassword()`
- Tabela: `password_resets` — przechowuje tokeny (TTL 1h)

**Tok:**
1. Użytkownik podaje login
2. System generuje losowy token i wysyła (w produkcji via email)
3. Użytkownik używa tokenu do ustawienia nowego hasła

### F5: Sesje Użytkowników z Automatyczną Ważnością

**Implementacja:**
- PHP Sessions: `session_start()` w [panel/index.php](panel/index.php)
- JWT API: Token expire w ApiController.login() — 8 godzin
- Middleware: `get_bearer_user()` w [panel/api.php](panel/api.php) waliduje JWT

```php
// JWT exp time: 8 hours
$token = $this->signJwt([...], $jwtSecret, $expiresIn = 8 * 3600);

// Verify JWT on each API call
$payload = verify_jwt($token, $secret);
if (isset($payload['exp']) && time() > $payload['exp']) return null; // Token expired
```

---

## Zarządzanie Pracownikami

### F6: Dodawanie Pracownika

**Panel Admin:**
- Strona: [panel/app/views/employees.php](panel/app/views/employees.php) (dostęp tylko zarząd)
- Route: `?route=employees` → PanelController.employees()
- Formularz wysyła: `POST /api/admin/pracownik`

**Pola:**
- Imię, Nazwisko, Login, Hasło, Uprawnienia (select), Discord ID (opcjonalnie)
- Hasło jest haszowane BCRYPT przy zapisie

### F7: Edycja Danych

**Implementacja:**
- Metoda: ApiController.adminPracownik() — wspiera UPDATE oraz INSERT
- Parametr: `id` — jeśli obecne, edycja; inaczej, tworzenie
- Pola edytowalne: imie, nazwisko, haslo, uprawnienie_id, is_active

### F8: Dezaktywacja Konta Zamiast Usuwania

**Implementacja:**
- Kolumna `is_active` (BOOLEAN, domyślnie true) w tabeli `pracownicy`
- Metoda: ApiController.deactivateEmployee($id)
- Endpoint: `POST /api/admin/employee/{id}/deactivate`
- Logika: Ustawienie `is_active = false` zamiast DELETE
- Filtry zapytań: Wszystkie SELECT'y zawierają `WHERE ... AND is_active = true`

```sql
-- Logowanie pracownika: tylko aktywni mogą się logować
SELECT ... FROM pracownicy WHERE login = ? AND is_active = true

-- Lista pracowników: domyślnie tylko aktywni
SELECT ... FROM pracownicy WHERE is_active = true
```

### F9: Historia Aktywności Pracownika

**Implementacja:**
- Helper: [panel/app/helpers/LogHelper.php](panel/app/helpers/LogHelper.php)
- Tabela: `activity_log` (auto-create jeśli nie istnieje)
- Struktury logów:
  - `user_id`, `action`, `entity_type`, `entity_id`, `data` (JSON), `ip_address`, `created_at`

**Zdarzenia Logowane:**
- `login` — Logowanie użytkownika
- `change_password` — Zmiana hasła
- `reset_password` — Reset hasła
- `create_employee` — Dodanie pracownika
- `edit_employee` — Edycja pracownika
- `deactivate_employee` — Deaktywacja pracownika

**Endpoint:**
- `GET /api/activity-log?user_id=X&entity_type=pracownik`
- Dostęp: Tylko zarząd
- Wyświetlenie: Panel Admin (employees.php)

---

## Zarządzanie Pojazdami

**Tabela: `pojazdy`**
- `id`, `nr_rejestracyjny`, `marka`, `model`, `rok_produkcji`, `sprawny`

**Endpoint:**
- `POST /api/admin/pojazd` — dodanie/edycja pojazdu (zarząd)
- `GET /api/pojazdy` — lista pojazdów (wszystkie role)

---

## Struktury Ról i Uprawnień

### Rola: Kierowca (kierowca)

**Dostęp:**
- ✓ Własny grafik (`?route=grafik`)
- ✓ Składanie wniosków (`?route=wnioski`)
- ✓ Podgląd zgłoszeń (`?route=zgloszenia`)
- ✓ Wysyłanie raportów (`?route=wyslij-raport`)
- ✓ Zmiana hasła (`/api/password/change`)
- ✗ Zarządzanie innymi pracownikami
- ✗ Zarządzanie pojazdami

**Implementacja:**
```php
$user['uprawnienie'] === 'kierowca'
```

### Rola: Dyspozytor (dyspozytor)

**Dostęp:**
- ✓ Wszystko co kierowca
- ✓ Tworzenie grafików (`?route=grafik` — widok edycji)
- ✓ Przydzielanie kierowców (`/api/grafik` POST)
- ✓ Zarządzanie liniami i brygadami
- ✓ Zarządzanie pojazdami (widok)
- ✓ Podgląd wniosków (`/api/wnioski` — wszystkie)
- ✓ Raporty (`?route=raporty`)
- ✗ Zarządzanie pracownikami (dodawanie, usuwanie)
- ✗ Panel Admin

**Implementacja:**
```php
in_array($user['uprawnienie'], ['dyspozytor', 'zarzad'])
```

### Rola: Zarząd (zarzad)

**Dostęp:**
- ✓ Wszystko co dyspozytor
- ✓ Panel Admin (`?route=admin` or `?route=employees`)
- ✓ Dodawanie/edycja pracowników (`/api/admin/pracownik`)
- ✓ Deaktywacja pracowników
- ✓ Zarządzanie pojazdami (`/api/admin/pojazd`)
- ✓ Widok dziennika aktywności (`/api/activity-log`)
- ✓ Zarządzanie ustawieniami systemowymi

**Implementacja:**
```php
$user['uprawnienie'] === 'zarzad'
```

---

## Architektura Techniczna

### Stack Technologiczny
- **Backend:** PHP 7.4+ (MVC pattern)
- **Frontend:** HTML/CSS/JavaScript (vanilla)
- **Baza Danych:** PostgreSQL
- **Autentykacja:** Sesje PHP + JWT (API)
- **Auth Społeczna:** Discord OAuth2

### Struktura Katalogów

```
panel/
├── index.php                 # Front-controller sesji
├── api.php                   # Front-controller API (JWT)
├── panel.js                  # Wspólny JS dla frontend'u
├── app/
│   ├── controllers/
│   │   ├── HomeController.php      # Logowanie, logout
│   │   ├── PanelController.php     # Panel pages
│   │   └── ApiController.php       # Logika biznesowa
│   ├── helpers/
│   │   ├── AuthHelper.php          # Walidacja ról
│   │   └── LogHelper.php           # Logowanie zdarzeń
│   ├── views/
│   │   ├── login.php               # Forma logowania
│   │   ├── dashboard.php           # Panel główny
│   │   ├── grafik.php, wnioski.php, ... # Panel pages
│   │   ├── employees.php           # Admin — zarządzanie pracownikami
│   │   └── admin.php               # Admin — inne
│   └── core/
│       └── Database.php            # PDO wrapper

auth/
├── discord.php             # Redirect do Discord
└── discord_callback.php    # Callback OAuth

linie/
├── index.php               # Detail page handler
└── /linie.php              # Lista linii (SIL API)
```

### Flow Logowania

**Login z login+password:**
```
HTML Form → POST /?route=login
  ↓
HomeController.login()
  ↓
Check DB (pracownicy WHERE login=X AND is_active=true)
  ↓
Verify password (BCRYPT or plaintext)
  ↓
$_SESSION['user'] = [id, login, uprawnienie]
  ↓
LogHelper::log() → activity_log
  ↓
Redirect /panel/?route=dashboard
```

**Login z Discord:**
```
/auth/discord
  ↓
Redirect do Discord API
  ↓
Discord → callback /auth/discord_callback?code=X
  ↓
Exchange code → access token
  ↓
Fetch user info + guild roles
  ↓
Find/create pracownik
  ↓
Sign JWT
  ↓
Redirect /panel/?token=XXX (frontend stores in localStorage)
```

---

## API Endpoints

### Autentykacja

| Metoda | Endpoint | Opis | Dostęp |
|--------|----------|------|--------|
| POST | `/api/login` | Logowanie (login + password) | Publiczny |
| GET | `/api/me` | Info zalogowanego użytkownika | JWT |
| POST | `/api/password-reset/request` | Poproszenie o reset hasła | Publiczny |
| POST | `/api/password-reset/confirm` | Resetowanie hasła z tokenem | Publiczny |
| POST | `/api/password/change` | Zmiana hasła (stare + nowe) | JWT |

### Wnioski

| Metoda | Endpoint | Opis | Dostęp |
|--------|----------|------|--------|
| GET | `/api/wnioski` | Lista wniosków | JWT |
| POST | `/api/wnioski` | Dodanie wniosku | JWT (kierowca) |

### Zgłoszenia (Wypadki/Awarie)

| Metoda | Endpoint | Opis | Dostęp |
|--------|----------|------|--------|
| POST | `/api/zgloszenia` | Dodanie zgłoszenia z uploadem | JWT |

### Grafiki

| Metoda | Endpoint | Opis | Dostęp |
|--------|----------|------|--------|
| GET | `/api/grafik` | Grafik użytkownika | JWT |
| GET | `/api/grafik?userId=X` | Grafik konkretnego pracownika | JWT (dyspozytor+) |
| POST | `/api/admin/grafik` | Dodanie grafiku | JWT (zarząd) |

### Raporty

| Metoda | Endpoint | Opis | Dostęp |
|--------|----------|------|--------|
| GET | `/api/raporty/pending` | Raporty do wykonania | JWT |
| GET | `/api/raporty/sent` | Wysłane raporty | JWT |
| GET | `/api/raporty/cancelled` | Anulowane grafiki | JWT |

### Pracownicy

| Metoda | Endpoint | Opis | Dostęp |
|--------|----------|------|--------|
| GET | `/api/pracownicy` | Lista aktywnych pracowników | JWT (zarząd) |
| GET | `/api/admin/pracownicy` | Lista wszystkich (+ nieaktywni) | JWT (zarząd) |
| POST | `/api/admin/pracownik` | Dodaj/edytuj pracownika | JWT (zarząd) |
| POST | `/api/admin/employee/{id}/deactivate` | Deaktywuj pracownika | JWT (zarząd) |

### Pojazdy

| Metoda | Endpoint | Opis | Dostęp |
|--------|----------|------|--------|
| GET | `/api/pojazdy` | Lista pojazdów | JWT |
| POST | `/api/admin/pojazd` | Dodaj/edytuj pojazd | JWT (zarząd) |

### Dziennik Aktywności

| Metoda | Endpoint | Opis | Dostęp |
|--------|----------|------|--------|
| GET | `/api/activity-log` | Historia aktywności | JWT (zarząd) |

---

## Checklist Wymagań

- [x] F1: Logowanie login+hasło
- [x] F2: Discord OAuth2
- [x] F3: System ról (kierowca, dyspozytor, zarząd)
- [x] F4: Reset hasła
- [x] F5: Sesje PHP + JWT
- [x] F6: Dodawanie pracownika
- [x] F7: Edycja danych pracownika
- [x] F8: Deaktywacja zamiast usuwania
- [x] F9: Dziennik aktywności
- [x] Żadne panele bez dostępu do roli

---

## Uruchomienie

### Wymagania
- PHP 7.4+
- PostgreSQL 12+
- Apache (mod_rewrite)

### Setup

1. **Skopiuj .env lub ustaw env.txt:**
```
DATABASE_URL=postgresql://user:pass@localhost/ostrans
DISCORD_CLIENT_ID=...
DISCORD_CLIENT_SECRET=...
DISCORD_GUILD_ID=...
DISCORD_BOT_TOKEN=...
JWT_SECRET=changeme
```

2. **Utwórz bazę danych:**
```sql
CREATE DATABASE ostrans;
```

3. **Uruchom serwer:**
```bash
php -S localhost:8000 -t .
```

4. **Dostęp:**
- Strona główna: http://localhost:8000/
- Panel: http://localhost:8000/panel/
- Admin: http://localhost:8000/panel/?route=admin

---

**Dokument ostatnio zaktualizowany:** Styczeń 2026
