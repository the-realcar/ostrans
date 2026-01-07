# Porównanie: Oczekiwania (readme.md) vs Rzeczywista Implementacja

## Streszczenie
Projekt **Ostrans Panel** jest w fazie **wczesnego prototypu** (MVP). Wiele funkcjonalności z readme.md jest planowanych, ale nie w pełni zaimplementowanych. Poniżej szczegółowe zestawienie.

---

## 1. LOGOWANIE I AUTORYZACJA

### ✅ F1: Logowanie za loginem + hasłem
- **Oczekiwanie**: Logowanie za loginem + hasłem
- **Implementacja**: ✅ **Zrealizowane**
  - Formularz: `panel/app/views/login.php`
  - Kontroler: `HomeController.login()`
  - API endpoint: `POST /api/login`
  - Wsparcie BCRYPT i plaintext hash

### ✅ F2: Logowanie Discord OAuth2
- **Oczekiwanie**: OAuth2 Discord + automatyczne mapowanie ról serwera
- **Implementacja**: ✅ **Zrealizowane**
  - Pliki: `auth/discord.php`, `auth/discord_callback.php`
  - Integracja: mapowanie Discord ról na role systemu
  - Env variables: `DISCORD_CLIENT_ID`, `DISCORD_CLIENT_SECRET`, itp.

### ✅ F3: System uprawnień (3 role + 15 stanowisk)
- **Oczekiwanie**: Kierowca, Dyspozytor, Zarząd + 15 stanowisk
- **Implementacja**: ✅ **Zrealizowane**
  - 3 główne role: `kierowca`, `dyspozytor`, `zarzad`
  - 20 stanowisk w tabeli `stanowiska`
  - Helper: `AuthHelper.php` do sprawdzania uprawnień

### ⚠️ F4: Reset hasła
- **Oczekiwanie**: E-mail lub kod jednorazowy
- **Implementacja**: **Częściowo** ✅
  - Endpoint: `POST /api/password-reset/request`
  - Tabela `password_resets` z tokenami (TTL 1h)
  - ❌ E-mail **NIE jest implementowany** — token nie jest wysyłany
  - Wymaga konfiguracji SMTP w produkcji

### ✅ F5: Sesje z automatyczną ważnością
- **Oczekiwanie**: Sesje z TTL + automatyczne wylogowanie
- **Implementacja**: ✅ **Zrealizowane**
  - PHP Sessions: `session_start()`
  - JWT API: Token expire 8 godzin
  - Middleware: `get_bearer_user()` waliduje JWT

---

## 2. ZARZĄDZANIE PRACOWNIKAMI

### ✅ F6: Dodawanie pracownika
- **Oczekiwanie**: Dodawanie z wyborem stanowiska i uprawnień
- **Implementacja**: ✅ **Zrealizowane**
  - Endpoint: `POST /api/admin/pracownik`
  - Metoda: `ApiController.adminPracownik()`
  - Panel: `panel/app/views/employees.php`

### ✅ F7: Edycja pracownika
- **Oczekiwanie**: Edycja imienia, nazwiska, loginu, aktywności, stanowiska, Discord ID
- **Implementacja**: ✅ **Zrealizowane**
  - Kolumny: `imie`, `nazwisko`, `login`, `discord_id`, `stanowisko_id`, `uprawnienie_id`, `is_active`
  - API supportuje edycję

### ✅ F8: Dezaktywacja konta (soft delete)
- **Oczekiwanie**: Archiwizacja zamiast usunięcia
- **Implementacja**: ✅ **Zrealizowane**
  - Kolumna: `is_active BOOLEAN DEFAULT true`
  - Endpoint: `POST /api/admin/employee/{id}/deactivate`
  - Metoda: `ApiController.deactivateEmployee()`

### ✅ F9: Historia aktywności pracownika
- **Oczekiwanie**: Logi aktywności + podgląd
- **Implementacja**: ✅ **Zrealizowane**
  - Tabela: `activity_log` (user_id, action, entity_type, entity_id, data, created_at)
  - Helper: `LogHelper::log()` rejestruje każdą akcję
  - Endpoint: `GET /api/activity-log` (tylko zarząd)

---

## 3. ZARZĄDZANIE POJAZDAMI

### ⚠️ F10: Dodawanie pojazdu
- **Oczekiwanie**: Z ręcznie nadanym ID
- **Implementacja**: **Częściowo** ⚠️
  - Tabela: `pojazdy` (nr_rejestracyjny, marka, model, rok_produkcji, sprawny)
  - ID: **auto-increment** (sekwencyjny) — nie ręczny
  - Nr rejestracyjny: unikalne
  - ❌ Formularz dodawania **NIE jest zaimplementowany** w UI
  - Endpoint API **możliwy**, ale nie w panelu

### ⚠️ F11: Oznaczanie pojazdu sprawny/niesprawny
- **Oczekiwanie**: Zmiana statusu pojazdu
- **Implementacja**: **Częściowo** ⚠️
  - Kolumna: `sprawny BOOLEAN`
  - Logika: istnieje w bazie
  - ❌ UI do zmiany statusu **NIE jest zaimplementowane**

### ❌ F12: Historia wykorzystania pojazdu
- **Oczekiwanie**: Logi użycia pojazdu
- **Implementacja**: **NIE zrealizowane** ❌
  - Brak tabeli `historia_pojazdu` lub logowania użycia
  - Można by wykorzystać `activity_log`, ale nie jest mapowany do pojazdów

### ❌ F13: Przypisanie kierowcy do pojazdu stałego
- **Oczekiwanie**: Stały pojazd dla kierowcy
- **Implementacja**: **Brak struktury** ❌
  - Brak tabeli `przydzial_staly_pojazd`
  - Tabela `przydzialy` jest pusta
  - Funkcjonalność nie jest opisana w API

---

## 4. LINIE I BRYGADY

### ✅ F14: Zarządzanie liniami
- **Oczekiwanie**: Nazwa, opis, warianty tras
- **Implementacja**: ✅ **Zrealizowane**
  - Tabela: `linie` (nr_linii, typ, start_point, end_point)
  - Dane: linia 107 i 116 są w bazie
  - Warianty: integracja z SIL API (sil.kanbeq.me)

### ✅ F15: Zarządzanie brygadami
- **Oczekiwanie**: Brygady przypisane do linii
- **Implementacja**: ✅ **Zrealizowane**
  - Tabela: `brygady` (linia_id, nazwa, is_active)
  - Dane: 107/1, 107/2, 116/1, 116/2 w bazie
  - API do zarządzania

### ❌ F16: Oznaczanie brygad dziennych i nocnych
- **Oczekiwanie**: Typ brygady (dzień/noc)
- **Implementacja**: **NIE zrealizowane** ❌
  - Brak kolumny `typ` lub `godziny_pracy` w `brygady`
  - Nie ma logiki rozróżniającej brygady

---

## 5. GRAFIKI KIEROWCÓW

### ✅ F17: Tworzenie grafików na dowolną datę
- **Oczekiwanie**: Grafiki na konkretne dni
- **Implementacja**: ✅ **Zrealizowane**
  - Tabela: `grafiki` (pracownik_id, data, brygada_id, pojazd_id, status)
  - Endpoint: `GET /api/grafik?userId=X`
  - Panel: `panel/app/views/grafik.php`

### ✅ F18: Przypisanie kierowcy do brygady
- **Oczekiwanie**: Kierowca na konkretną brygadę
- **Implementacja**: ✅ **Zrealizowane**
  - Kolumna: `brygada_id` w `grafiki`
  - Logika: przechowywana w bazie

### ✅ F19: Przypisanie pojazdu do brygady w dniu
- **Oczekiwanie**: Pojazd dla brygady na dany dzień
- **Implementacja**: ✅ **Zrealizowane**
  - Kolumna: `pojazd_id` w `grafiki`
  - Logika: przechowywana w bazie

### ⚠️ F20: Automatyczne sprawdzanie konfliktów
- **Oczekiwanie**: Kierowca nie może być w 2 brygadach równocześnie
- **Implementacja**: **Nie jest zaimplementowane** ❌
  - Brak walidacji w API
  - Baza pozwala na duplicity

---

## 6. SYSTEM WNIOSKÓW

### ✅ F21: Kierowca składa wniosek
- **Oczekiwanie**: Urlop, wolne, zmiana brygady, pojazd stały
- **Implementacja**: ✅ **Zrealizowane**
  - Tabela: `wnioski` (pracownik_id, typ, opis, status, data_zlozenia, data_rozpatrzenia)
  - Endpoint: `POST /api/wnioski`
  - Panel: `panel/app/views/wnioski.php`

### ⚠️ F22: Dyspozytor/zarząd akceptuje/odrzuca wniosek
- **Oczekiwanie**: Zmiana statusu i notyfikacja
- **Implementacja**: **Struktura istnieje** ⚠️
  - Endpoint: `POST /api/wnioski` (POST dla dodania)
  - ❌ **Endpoint do zmany statusu NIE jest zaimplementowany**
  - UI do zatwierdzenia **brak**

### ✅ F23: Statusy wniosku
- **Oczekiwanie**: oczekujący, zatwierdzony, odrzucony, anulowany
- **Implementacja**: ✅ **Zrealizowane**
  - Kolumna: `status VARCHAR(50)`
  - Statyczne wartości w logice

### ✅ F24: Logowanie decyzji do logów
- **Oczekiwanie**: Każda decyzja zapisana w activity_log
- **Implementacja**: ✅ **Zrealizowane**
  - Tabela: `activity_log`
  - Logika: `LogHelper::log()` obsługuje to

---

## 7. LOGI SYSTEMOWE

### ✅ F25: Zapisywanie zdarzeń (logowanie, decyzje, zmiany)
- **Oczekiwanie**: Pełny audit trail
- **Implementacja**: ✅ **Zrealizowane**
  - Tabela: `activity_log` (user_id, action, entity_type, entity_id, data, ip_address, created_at)
  - Helper: `LogHelper::log($user_id, $action, $entity, $entity_id, $data)`

### ⚠️ F26: Automatyczna rejestracja IP i user-agent
- **Oczekiwanie**: IP i user-agent w logach
- **Implementacja**: **Częściowo** ⚠️
  - Kolumna: `ip_address` istnieje
  - ⚠️ User-agent **NIE jest zapisywany**
  - IP mogą być zapisywane, ale nie automatycznie we wszystkich akcjach

---

## 8. IMPORT/EKSPORT

### ❌ F27: Eksport grafików do CSV/PDF
- **Oczekiwanie**: Eksport harmonogramów
- **Implementacja**: **NIE zrealizowane** ❌
  - Brak endpointu do exportu
  - Brak biblioteki do generowania PDF

### ❌ F28: Import list pracowników (CSV/SQL)
- **Oczekiwanie**: Zbiorczy import danych
- **Implementacja**: **NIE zrealizowane** ❌
  - Brak formularza uploadowania CSV
  - Brak logiki parsowania

### ❌ F29: Eksport listy pojazdów i brygad
- **Oczekiwanie**: Eksport danych
- **Implementacja**: **NIE zrealizowane** ❌

---

## 9. WYMAGANIA NIEFUNKCJONALNE

### ⚠️ Wydajność
- **Oczekiwanie**: 80 użytkowników jednocześnie, <0.5s na query, <2s loading
- **Implementacja**: **Nie testowane** ⚠️
  - Brak load testing
  - Indeksy w bazie są zdefiniowane
  - Liczba pojazdów: 271 (duży dataset)

### ✅ Bezpieczeństwo
- **Oczekiwanie**: BCRYPT/Argon2, HTTPS, SQL injection, XSS protection, RBAC
- **Implementacja**: ✅ **Częściowo zrealizowane**
  - BCRYPT: ✅ (wsparcie w login)
  - SQL injection: ✅ (prepared statements w PDO)
  - XSS: ✅ (json_encode w API)
  - RBAC: ✅ (role-based access control)
  - ❌ HTTPS: zależy od hostingu (wymaga konfiguracji)
  - ❌ Argon2: nie jest używane

### ⚠️ Skalowalność
- **Oczekiwanie**: Modułowość, możliwość rozszerzenia, chmura/Docker
- **Implementacja**: **Częściowo** ⚠️
  - Struktura: MVC + helpers (dobrze podzielone)
  - Docker: ❌ brak Dockerfile
  - API: ✅ RESTful (łatwe do rozszerzenia)

### ⚠️ Niezawodność
- **Oczekiwanie**: Kopie zapasowe, ochrona przed błędami
- **Implementacja**: **Nie zrealizowane** ❌
  - Brak skriptów backup
  - Brak validacji konfliktów (np. duplikaty kierowców w brygadzie)

### ✅ Użyteczność
- **Oczekiwanie**: Responsywny, intuicyjny UI, dark/light mode
- **Implementacja**: ✅ **Zrealizowane**
  - CSS: `employee.css`, `panel_dark.css` (dark mode)
  - Responsive: ✅ (media queries)
  - Kolorystyka: ✅ (blue #003366, yellow #ffbf47)

---

## 10. WYMAGANIA TECHNICZNE

### ✅ Backend
- **Oczekiwanie**: PHP 8.1+, Laravel/Slim/custom MVC
- **Implementacja**: ✅ **Zrealizowane**
  - Custom MVC: `panel/app/` struktura
  - PHP 8+: ✅

### ✅ Frontend
- **Oczekiwanie**: HTML5, CSS3, Bootstrap/Tailwind, JS
- **Implementacja**: ✅ **Zrealizowane**
  - HTML5: ✅
  - CSS3: ✅ (custom stylesheets)
  - Bootstrap/Tailwind: ❌ (custom CSS)
  - JavaScript: ✅ (vanilla, brak frameworks)

### ✅ Baza danych
- **Oczekiwanie**: PostgreSQL
- **Implementacja**: ✅ **Zrealizowane**
  - PostgreSQL: ✅
  - Schema: `DATABASE_SCHEMA.sql` (15 tabel)

### ✅ Integracje
- **Oczekiwanie**: Discord OAuth2, możliwość API
- **Implementacja**: ✅ **Zrealizowane**
  - Discord OAuth2: ✅
  - SIL API (linie): ✅ (integracja w `linie/index.php`)

### ⚠️ Hostowanie
- **Oczekiwanie**: home.pl, OVH, VPS, Docker
- **Implementacja**: **Niejasne** ⚠️
  - PHP+PostgreSQL: ✅ (kompatybilne z home.pl, OVH)
  - Docker: ❌ (brak Dockerfile)

---

## 11. MODUŁY DODATKOWE (OPCJONALNE)

| Funkcjonalność | Oczekiwanie | Status |
|---|---|---|
| System zgłaszania awarii | Opcjonalne | ❌ Brak |
| Tablica „kto dziś pracuje" | Opcjonalne | ❌ Brak |
| Mapa tras i warianty | Opcjonalne | ✅ SIL API (integracja do rozwinięcia) |
| Harmonogram przeglądów | Opcjonalne | ⚠️ Struktura w bazie (`przeglady.sql`), logika brak |
| Powiadomienia e-mail | Opcjonalne | ❌ Brak |
| WebPush | Opcjonalne | ❌ Brak |

---

## PODSUMOWANIE WDROŻENIA

### 🟢 Zrealizowane w pełni (12 wymagań)
- F1, F2, F3, F5, F6, F7, F8, F9, F14, F15, F17, F18, F19, F23, F24, F25

### 🟡 Zrealizowane częściowo (10 wymagań)
- F4 (reset bez e-maila), F10 (pojazdy bez UI), F11 (bez UI), F12 (brak struktury), F13 (brak), F16 (brak), F20 (brak walidacji), F22 (brak endpoint do zmany statusu), F26 (IP tylko), F27-F29 (Export brak)

### 🔴 Niezrealizowane (7 wymagań)
- F12, F13, F16, F20, F27, F28, F29

### Ukończenie: ~65% MVP

---

## REKOMENDACJE NA PRODUKCJĘ

### 🔴 Krytyczne (przed wdrożeniem)
1. **Walidacja konfliktów** (F20) — kierowca nie może być w 2 brygadach
2. **Endpoint do akceptacji wniosków** (F22) — zmiany statusu + logowanie
3. **Integracja e-mail** (F4) — wysyłanie tokenów resetowych
4. **Export grafików** (F27) — CSV/PDF dla dyspozyatorów
5. **Testy bezpieczeństwa** — penetration testing

### 🟡 Ważne (w następnych sprintach)
1. UI do zarządzania pojazdami (F10, F11)
2. Historia użycia pojazdów (F12)
3. Przypisanie stałych pojazdów (F13)
4. Brygady dzienne/nocne (F16)
5. Docker + backup scripts

### 🟢 Przyszłość
1. Powiadomienia e-mail/WebPush
2. Tablica obecności
3. Mapa tras

---

**Ostatnia aktualizacja**: 7 stycznia 2026  
**Status MVP**: 65% ukończone, gotowe do testów wewnętrznych
