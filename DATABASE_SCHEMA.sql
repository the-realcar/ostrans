-- PPUT Ostrans - Rekomendowany schemat bazy danych
-- Implementacja wymagań z readme.md

-- ============================================================================
-- TABELE PODSTAWOWE
-- ============================================================================

-- Uprawnienia (role)
CREATE TABLE IF NOT EXISTS uprawnienia (
    id SERIAL PRIMARY KEY,
    poziom VARCHAR(50) UNIQUE NOT NULL, -- 'kierowca', 'dyspozytor', 'zarzad'
    opis TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Stanowiska (opcjonalne, dla bardziej szczegółowego zarządzania)
CREATE TABLE IF NOT EXISTS stanowiska (
    id SERIAL PRIMARY KEY,
    nazwa VARCHAR(100) NOT NULL,
    opis TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Pracownicy
CREATE TABLE IF NOT EXISTS pracownicy (
    id SERIAL PRIMARY KEY,
    imie VARCHAR(50) NOT NULL,
    nazwisko VARCHAR(50) NOT NULL,
    login VARCHAR(50) UNIQUE NOT NULL,
    haslo VARCHAR(255) NOT NULL,           -- BCRYPT hash lub plaintext (legacy)
    email VARCHAR(100),
    discord_id VARCHAR(50),                -- Dla OAuth
    stanowisko_id INT REFERENCES stanowiska(id),
    uprawnienie_id INT REFERENCES uprawnienia(id),
    is_active BOOLEAN DEFAULT true,        -- F8: Soft delete zamiast usuwania
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================================
-- TABELE DLA FUNKCJONALNOŚCI TRANSPORTU
-- ============================================================================

-- Pojazdy
CREATE TABLE IF NOT EXISTS pojazdy (
    id SERIAL PRIMARY KEY,
    nr_rejestracyjny VARCHAR(20) UNIQUE NOT NULL,
    marka VARCHAR(50),
    model VARCHAR(50),
    rok_produkcji INT,
    sprawny BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Linie (tabela statyczna, głównie z SIL API)
CREATE TABLE IF NOT EXISTS linie (
    id SERIAL PRIMARY KEY,
    nr_linii VARCHAR(20) NOT NULL,
    typ VARCHAR(20),                      -- 'bus', 'tram', 'trol'
    start_point VARCHAR(100),
    end_point VARCHAR(100),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Brygady (zespoły kierowców na linii)
CREATE TABLE IF NOT EXISTS brygady (
    id SERIAL PRIMARY KEY,
    linia_id INT REFERENCES linie(id),
    nazwa VARCHAR(100),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Grafiki
CREATE TABLE IF NOT EXISTS grafiki (
    id SERIAL PRIMARY KEY,
    pracownik_id INT REFERENCES pracownicy(id),
    data DATE NOT NULL,
    brygada_id INT REFERENCES brygady(id),
    pojazd_id INT REFERENCES pojazdy(id),
    status VARCHAR(50) DEFAULT 'zaplanowany', -- 'zaplanowany', 'wykonany', 'anulowana'
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================================
-- TABELE DLA WNIOSKÓW I ZGŁOSZEŃ
-- ============================================================================

-- Wnioski (urlop, zmiana stałego pojazdu, itd.)
CREATE TABLE IF NOT EXISTS wnioski (
    id SERIAL PRIMARY KEY,
    pracownik_id INT REFERENCES pracownicy(id),
    typ VARCHAR(100),                     -- 'urlop', 'KZW', 'zmiana_etatu', itd.
    opis TEXT,
    status VARCHAR(50) DEFAULT 'nowy',    -- 'nowy', 'zatwierdzony', 'odrzucony'
    data_zlozenia TIMESTAMP DEFAULT NOW(),
    data_rozpatrzenia TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Metadata wniosków (szczegółowe pola)
CREATE TABLE IF NOT EXISTS wnioski_meta (
    id SERIAL PRIMARY KEY,
    wniosek_id INT REFERENCES wnioski(id),
    meta JSONB,                           -- Przechowuje dodatkowe pola w formacie JSON
    created_at TIMESTAMP DEFAULT NOW()
);

-- Zgłoszenia (wypadki, awarie)
CREATE TABLE IF NOT EXISTS zgloszenia (
    id SERIAL PRIMARY KEY,
    pracownik_id INT REFERENCES pracownicy(id),
    pojazd_id INT REFERENCES pojazdy(id),
    data_zdarzenia TIMESTAMP NOT NULL,
    opis TEXT NOT NULL,
    wyjasnienie TEXT,
    uwagi TEXT,
    files JSONB,                          -- Lista ścieżek do uploadów
    status VARCHAR(50) DEFAULT 'nowe',    -- 'nowe', 'potwierdzone', 'rozpatrzone'
    created_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================================
-- TABELE DLA RAPORTÓW
-- ============================================================================

-- Raporty
CREATE TABLE IF NOT EXISTS raporty (
    id SERIAL PRIMARY KEY,
    grafik_id INT REFERENCES grafiki(id),
    pracownik_id INT REFERENCES pracownicy(id),
    data_raportu TIMESTAMP NOT NULL,
    opis TEXT,
    status VARCHAR(50) DEFAULT 'wysłany',  -- 'wysłany', 'odczytany'
    created_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================================
-- TABELE DLA LOGOWANIA I BEZPIECZEŃSTWA
-- ============================================================================

-- Dziennik aktywności (F9: Historia działań użytkowników)
CREATE TABLE IF NOT EXISTS activity_log (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES pracownicy(id),
    action VARCHAR(255),                  -- 'login', 'edit_employee', 'add_schedule', etc.
    entity_type VARCHAR(100),             -- 'pracownik', 'pojazd', 'grafik', etc.
    entity_id INT,                        -- ID encji, do której akcja się odnosiła
    data JSONB,                           -- Dodatkowe dane (stare wartości, nowe wartości, etc.)
    ip_address VARCHAR(50),               -- IP użytkownika
    created_at TIMESTAMP DEFAULT NOW(),
    INDEX (user_id, created_at),
    INDEX (action, created_at),
    INDEX (entity_type, entity_id)
);

-- Reset hasła (F4: Tokeny do resetowania hasła)
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES pracownicy(id),
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Rejestracje (legacy: opcjonalne dla specjalnych rejestracji)
CREATE TABLE IF NOT EXISTS rejestracje (
    id SERIAL PRIMARY KEY,
    login VARCHAR(50),
    token VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW()
);

-- ============================================================================
-- INDEKSY DLA WYDAJNOŚCI
-- ============================================================================

CREATE INDEX IF NOT EXISTS idx_pracownicy_login ON pracownicy(login);
CREATE INDEX IF NOT EXISTS idx_pracownicy_is_active ON pracownicy(is_active);
CREATE INDEX IF NOT EXISTS idx_pracownicy_uprawnienie_id ON pracownicy(uprawnienie_id);
CREATE INDEX IF NOT EXISTS idx_grafiki_pracownik_id ON grafiki(pracownik_id);
CREATE INDEX IF NOT EXISTS idx_grafiki_data ON grafiki(data);
CREATE INDEX IF NOT EXISTS idx_wnioski_pracownik_id ON wnioski(pracownik_id);
CREATE INDEX IF NOT EXISTS idx_wnioski_status ON wnioski(status);
CREATE INDEX IF NOT EXISTS idx_zgloszenia_pracownik_id ON zgloszenia(pracownik_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_user_id ON activity_log(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_created_at ON activity_log(created_at);
CREATE INDEX IF NOT EXISTS idx_password_resets_token ON password_resets(token);

-- ============================================================================
-- INIT DATA (Przykład)
-- ============================================================================

-- Insert uprawnienia
INSERT INTO uprawnienia (poziom, opis) VALUES
    ('zarzad', 'Zarząd — pełny dostęp'),
    ('dyspozytor', 'Dyspozytor — zarządzanie liniami i graficikami'),
    ('kierowca', 'Kierowca — dostęp do własnych danych')
ON CONFLICT DO NOTHING;

-- Insert stanowiska (opcjonalne)
INSERT INTO stanowiska (nazwa) VALUES
    ('Kierowca bus'),
    ('Kierowca tramwaj'),
    ('Kierowca trolejbus'),
    ('Dyspozytor'),
    ('Manager')
ON CONFLICT DO NOTHING;

-- Insert demo pracownika (zarząd)
-- Hasło: admin1 (plaintext dla demo; w produkcji powinno być haszowane)
INSERT INTO pracownicy (imie, nazwisko, login, haslo, uprawnienie_id)
    SELECT 'Admin', 'User', 'admin1', '$2y$10$XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', id
    FROM uprawnienia WHERE poziom = 'zarzad'
ON CONFLICT (login) DO NOTHING;

-- ============================================================================
-- MIGRACJE I WERSJONOWANIE (dla produkcji)
-- ============================================================================

-- Jeśli potrzebujesz dodać kolumnę do istniejącej tabeli:
-- ALTER TABLE pracownicy ADD COLUMN is_active BOOLEAN DEFAULT true;
-- ALTER TABLE pracownicy ADD COLUMN deleted_at TIMESTAMP;

-- Jeśli potrzebujesz zmienić typ kolumny:
-- ALTER TABLE password_resets ALTER COLUMN expires_at TYPE TIMESTAMP;

-- ============================================================================
-- NOTATKI
-- ============================================================================

/*
 * 1. SOFT DELETE (F8)
 *    - is_active = false zamiast DELETE
 *    - Wszystkie SELECT'y powinny filtrować: WHERE is_active = true
 *    - Funkcja deactivateEmployee() ustawia is_active = false
 *
 * 2. LOGGING (F9)
 *    - activity_log rejestruje każdą akcję
 *    - LogHelper::log() w ApiController
 *    - Endpoint: GET /api/activity-log (tylko zarząd)
 *
 * 3. RESET HASŁA (F4)
 *    - password_resets przechowuje tokeny (TTL 1h)
 *    - Endpoint: POST /api/password-reset/request
 *    - Endpoint: POST /api/password-reset/confirm
 *
 * 4. OAUTH DISCORD (F2)
 *    - discord_id mapuje pracownika do konta Discord
 *    - Rola Discord mapowana do uprawnienie_id
 *
 * 5. WNIOSKI I ZGŁOSZENIA
 *    - wnioski: formalne prośby (urlop, zmiana etatu, itd.)
 *    - zgloszenia: problemy (wypadki, awarie)
 *    - Pola metadanych przechowywane w JSONB
 */
