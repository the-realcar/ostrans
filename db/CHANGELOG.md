# Dokumentacja zmian w plikach SQL - Ostrans Panel

## Podsumowanie
Wszystkie pliki SQL w katalogu `db/` zostały dostosowane do schematu bazy danych zdefiniowanego w `DATABASE_SCHEMA.sql`. Zmiany zapewniają poprawną integrację z panelem pracowniczym.

---

## 1. `db/uprawnienia.sql` ✅ [NOWY PLIK]

### Co zostało dodane
- Tabela `uprawnienia` zawiera trzy role:
  - `zarzad` - Zarząd z pełnym dostępem
  - `dyspozytor` - Dyspozytor zarządzający liniami i grafikikami
  - `kierowca` - Kierowca z dostępem do własnych danych

### Powiązanie z panelem
Kontroler `ApiController.php` sprawdza `uprawnienie` z tabeli `pracownicy`:
```php
if (($u['uprawnienie'] ?? '') !== 'zarzad') { ... }
```

---

## 2. `db/pracownicy.sql` ✅ [NAPRAWIONY]

### Zmiany:
```sql
-- PRZED:
INSERT INTO pracownicy (imie, nazwisko, login, haslo, stanowisko_id, uprawnienie_id)
VALUES ('Dawid','Volve','the_realcar','dpass', 20, 4),
       ('Hubert Jakub','Tryniecki','kustul','hpass', 19, 4);

-- TERAZ:
INSERT INTO pracownicy (imie, nazwisko, login, haslo, stanowisko_id, uprawnienie_id, email, is_active)
VALUES 
('Dawid','Volve','the_realcar','dpass', 2, 3, 'driver@ostrans.pl', true),
('Hubert Jakub','Tryniecki','kustul','hpass', 14, 2, 'dispatcher@ostrans.pl', true),
('Admin','User','admin1','$2y$10$...', 18, 1, 'admin@ostrans.pl', true);
```

### Szczegóły:
- **Stanowisko ID**: zmieniono z 19-20 na rzeczywiste ID ze schematu (2=Kierowca, 14=Dyspozytor, 18=Zarząd)
- **Uprawnienie ID**: zmieniono z 4 na rzeczywiste ID (1=zarzad, 2=dyspozytor, 3=kierowca)
- **Email**: dodano adresy email dla każdego pracownika
- **is_active**: dodano kolumnę dla soft delete
- **Trzeci pracownik**: dodano administratora `admin1` dla dostępu zarządu

---

## 3. `db/linie.sql` ✅ [NAPRAWIONY]

### Zmiany:
```sql
-- PRZED:
INSERT INTO linie (nazwa, opis) 
VALUES ('107','Salwator - Boernerowo'),
       ('116', 'Osiedle Zgody - Blachownia');

-- TERAZ:
INSERT INTO linie (nr_linii, typ, start_point, end_point) 
VALUES 
('107', 'bus', 'Salwator', 'Boernerowo'),
('116', 'bus', 'Osiedle Zgody', 'Blachownia');
```

### Szczegóły:
- Zmieniono `nazwa` na `nr_linii` (zgodnie ze schematem)
- Usunięto `opis` i zamiast niego użyto `typ`, `start_point`, `end_point`
- Dodano typ linii (`bus`)

---

## 4. `db/brygady.sql` ✅ [NAPRAWIONY]

### Zmiany:
```sql
-- PRZED:
INSERT INTO brygady (linia_id, nazwa) 
VALUES ( (SELECT id FROM linie WHERE nazwa='107' LIMIT 1), '107/1' ),
       ...

-- TERAZ:
INSERT INTO brygady (linia_id, nazwa, is_active) 
VALUES 
( (SELECT id FROM linie WHERE nr_linii='107' LIMIT 1), '107/1', true ),
...
```

### Szczegóły:
- Zmieniono `WHERE nazwa=` na `WHERE nr_linii=` (zgodnie ze zmianami w `linie.sql`)
- Dodano kolumnę `is_active` dla soft delete

---

## 5. `db/pojazdy.sql` ✅ [NAPRAWIONY - DUŻA ZMIANA]

### Zmiany:
```sql
-- PRZED:
INSERT INTO pojazdy (id, nr_taborowy, nr_rejestracyjny, marka, model, rok_produkcji, nazwa_zajezdni, naped, sprawny)
VALUES
(1, '79', 'WOS-1', 'Volvo', '7700FL', 2007, 'OKM', 5, TRUE),
...

-- TERAZ:
INSERT INTO pojazdy (nr_rejestracyjny, marka, model, rok_produkcji, sprawny)
VALUES
('WOS-1', 'Volvo', '7700FL', 2007, TRUE),
...
```

### Szczegóły:
- **Usunięto kolumny**: `id` (auto-increment), `nr_taborowy` (zbędne), `nazwa_zajezdni`, `naped`
- Zachowano: `nr_rejestracyjny` (UNIQUE), `marka`, `model`, `rok_produkcji`, `sprawny`
- Wszystkie 271 pojazdów dostosowało do nowego formatu
- `nr_rejestracyjny` zmieniono na unikalne wartości WOS-1 do WOS-271 (zamiast ID sekwencyjnego)

---

## 6. `db/insert.sql` ✅ [PRZECZYSZCZONY]

### Zmiany:
- **Usunięto**: duplikaty `marki`, `modele`, `grupy`, `marki_modele` (tabele pomocnicze)
- **Usunięto**: błędne inserty do `pojazdy`, `pracownicy`, `przydzialy` itd. (już obsłużone w dedykowanych plikach)
- **Usunięto**: SELECT queries (nie powinny być w SQL insert)

### Co zostało:
```sql
-- Stanowiska
INSERT INTO stanowiska (nazwa)
VALUES ('Nowy Kierowca'), ... ('Prezes');

-- Rodzaje przeglądów
INSERT INTO rodzaje_przegladow (nazwa_rodzaju_przegladu)
VALUES ('O1'), ('OT'), ... ('OES');

-- Typy zajezdni
INSERT INTO typy_zajezdni (nazwa_typu)
VALUES ('Autobusowa'), ... ('Inna');
```

---

## 7. `db/stanowisko.sql` ⚠️ [DUPLIKAT - USUNĄĆ]

**Uwaga**: Plik `stanowisko.sql` zawiera identyczne dane co `insert.sql`. Rekomendacja: usunąć `stanowisko.sql` i używać `insert.sql`.

---

## 8. `db/grafiki.sql` ✅ [NAPRAWIONY]

### Zmiany:
```sql
-- PRZED:
INSERT INTO grafiki (pracownik_id, data, brygada_id, pojazd_id)
VALUES ((SELECT id FROM pracownicy WHERE login='driver1' LIMIT 1), ...);

-- TERAZ:
INSERT INTO grafiki (pracownik_id, data, brygada_id, pojazd_id, status)
VALUES ((SELECT id FROM pracownicy WHERE login='the_realcar' LIMIT 1), 
        CURRENT_DATE,
        (SELECT id FROM brygady WHERE nazwa='107/1' LIMIT 1),
        1,
        'zaplanowany');
```

### Szczegóły:
- Zmieniono login z `driver1` na `the_realcar` (istniejący pracownik)
- Zmieniono brygadę z `Brygada A` na rzeczywistą `107/1`
- Zmieniono pojazd z `1001` na `1` (rzeczywisty ID)
- Dodano kolumnę `status` z wartością `zaplanowany`

---

## 9. `db/wnioski.sql` ✅ [NAPRAWIONY]

### Zmiany:
```sql
-- PRZED:
INSERT INTO wnioski (pracownik_id, typ, opis, status)
VALUES ((SELECT id FROM pracownicy WHERE login='driver1' LIMIT 1),
        'kurs_z_wolnego',
        'Prośba o przydzielenie kursu z dnia 2025-06-01',
        'oczekujący');

-- TERAZ:
INSERT INTO wnioski (pracownik_id, typ, opis, status)
VALUES ((SELECT id FROM pracownicy WHERE login='the_realcar' LIMIT 1),
        'urlop',
        'Prośba o urlop w dniach 2025-06-01 do 2025-06-15',
        'nowy');
```

### Szczegóły:
- Zmieniono login z `driver1` na `the_realcar`
- Typ zmieniany na `urlop` (valid type)
- Status zmieniony z `oczekujący` na `nowy` (zgodnie ze schematem)

---

## 10. Pliki niezmieniane (już poprawne)

### `db/marki.sql`, `db/modele.sql`, `db/grupy.sql`, `db/marki_modele.sql`
✅ Poprawne - zawierają dane katalogowe dla pojazdów

### `db/napedy.sql`
✅ Poprawne - zawiera typy napędów

### `db/zajezdnie.sql`
✅ Poprawne - zawiera siedziby transportu

### `db/rodzaje_przegladow.sql`
✅ Poprawne - zawiera rodzaje przeglądów

### `db/linie.sql` (stary, teraz znowelizowany)
✅ Zaktualizowany

### Puste pliki (do rozwinięcia)
- `db/naprawy.sql` - dane o naprawach pojazdów
- `db/przeglady.sql` - dane o przeglądach pojazdów
- `db/przetargi.sql` - dane o przetargach
- `db/przydzialy.sql` - przydzielenie kierowców do pojazdów
- `db/transakcje.sql` - transakcje finansowe
- `db/wypozyczenia.sql` - wypożyczenia pojazdów

---

## Kolejność ładowania SQL

Aby prawidłowo załadować dane do bazy, Execute w tej kolejności:

```sql
-- 1. Schemat bazy (tworzy tabele)
\i DATABASE_SCHEMA.sql

-- 2. Dane referencyjne (role, stanowiska, typy)
\i db/uprawnienia.sql
\i db/stanowisko.sql      -- lub insert.sql (zawiera stanowiska)
\i insert.sql             -- stanowiska, rodzaje_przegladow, typy_zajezdni
\i db/napedy.sql          -- typy napędów
\i db/marki.sql           -- marki pojazdów
\i db/modele.sql          -- modele pojazdów
\i db/grupy.sql           -- grupy wielkościowe
\i db/marki_modele.sql    -- mapowanie marek do modeli i grup

-- 3. Dane geograficzne/organizacyjne
\i db/zajezdnie.sql       -- siedziby
\i db/linie.sql           -- linie autobusów
\i db/brygady.sql         -- brygady na liniach

-- 4. Pracownicy i pojazdy
\i db/pracownicy.sql      -- pracownicy z uprawnieniami
\i db/pojazdy.sql         -- pojazdy

-- 5. Dane operacyjne
\i db/grafiki.sql         -- grafiki pracy
\i db/wnioski.sql         -- wnioski pracowników
\i db/rodzaje_przegladow.sql -- już w insert.sql

-- 6. Pozostałe dane (gdy będą dostępne)
-- \i db/naprawy.sql
-- \i db/przeglady.sql
-- \i db/przydzialy.sql
-- \i db/przetargi.sql
-- \i db/transakcje.sql
-- \i db/wypozyczenia.sql
```

---

## Weryfikacja po załadowaniu

```sql
-- Sprawdź pracowników
SELECT id, login, imie, nazwisko, uprawnienie_id FROM pracownicy;

-- Sprawdź role
SELECT id, poziom FROM uprawnienia;

-- Sprawdź pojazdy
SELECT COUNT(*) as liczba_pojazdow FROM pojazdy;

-- Sprawdź linie i brygady
SELECT b.id, b.nazwa, l.nr_linii FROM brygady b 
JOIN linie l ON b.linia_id = l.id;

-- Sprawdź grafiki
SELECT g.id, p.login, g.data, b.nazwa FROM grafiki g
JOIN pracownicy p ON g.pracownik_id = p.id
JOIN brygady b ON g.brygada_id = b.id;
```

---

## Potencjalne problemy i rozwiązania

### Problem: `uprawnienia` jeszcze nie istnieją
**Rozwiązanie**: Załaduj `uprawnienia.sql` PRZED `pracownicy.sql`

### Problem: `linie` o nazwie `107` nie istnieją
**Rozwiązanie**: Załaduj `linie.sql` i `brygady.sql` PRZED `grafiki.sql`

### Problem: Kierowca `driver1` nie istnieje
**Rozwiązanie**: Używaj rzeczywistych loginów z `pracownicy.sql` (np. `the_realcar`, `kustul`)

### Problem: Pojazd ID `1001` nie istnieje
**Rozwiązanie**: Pojazdy mają teraz ID auto-increment, zaczynając od 1

---

## Podsumowanie zmian

| Plik | Status | Zmiana |
|------|--------|--------|
| `uprawnienia.sql` | ✅ Nowy | Tabela ról do panelu |
| `pracownicy.sql` | ✅ Naprawiony | ID stanowisk i uprawnień, Email, is_active |
| `linie.sql` | ✅ Naprawiony | Struktura kolumn (nr_linii, typ, start_point, end_point) |
| `brygady.sql` | ✅ Naprawiony | Referencja do nr_linii, is_active |
| `pojazdy.sql` | ✅ Naprawiony | Usunięto zbędne kolumny, 271 pojazdów |
| `insert.sql` | ✅ Przeczyszczony | Stanowiska, rodzaje przeglądów, typy zajezdni |
| `stanowisko.sql` | ⚠️ Duplikat | Usunąć (duplikat insert.sql) |
| `grafiki.sql` | ✅ Naprawiony | Rzeczywiste login i ID brygad |
| `wnioski.sql` | ✅ Naprawiony | Rzeczywisty login, typ i status |

---

**Ostatnia aktualizacja**: 7 stycznia 2026  
**Status**: ✅ Gotowe do wdrożenia

