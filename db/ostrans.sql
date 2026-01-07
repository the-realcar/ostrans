-- SCHEMA ROZSZERZONE O MODUŁ PANELU PRACOWNIKÓW

-- ============================
-- TABELA: stanowiska (15 pozycji)
-- ============================
CREATE TABLE IF NOT EXISTS stanowiska (
    id SERIAL PRIMARY KEY,
    nazwa VARCHAR(100) NOT NULL UNIQUE
);

-- ============================
-- TABELA: uprawnienia (3 poziomy)
-- ============================
CREATE TABLE IF NOT EXISTS uprawnienia (
    id SERIAL PRIMARY KEY,
    poziom VARCHAR(50) NOT NULL UNIQUE  -- kierowca, dyspozytor, zarząd
);

-- ============================
-- TABELA: pracownicy (dodane logowanie + relacje)
-- ============================
CREATE TABLE IF NOT EXISTS pracownicy (
    id SERIAL PRIMARY KEY,
    imie VARCHAR(100) NOT NULL,
    nazwisko VARCHAR(100) NOT NULL,
    login VARCHAR(150) NOT NULL UNIQUE,
    haslo VARCHAR(255) NOT NULL,
    discord_id VARCHAR(50),
    stanowisko_id INT NOT NULL,
    uprawnienie_id INT NOT NULL,
    aktywny BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (stanowisko_id) REFERENCES stanowiska(id),
    FOREIGN KEY (uprawnienie_id) REFERENCES uprawnienia(id)
);

-- ============================
-- TABELA: wnioski kierowców
-- ============================
CREATE TABLE IF NOT EXISTS wnioski (
    id SERIAL PRIMARY KEY,
    pracownik_id INT NOT NULL,
    typ VARCHAR(100) NOT NULL, -- kurs z wolnego, urlop, przydział pojazdu stałego itp.
    opis TEXT,
    data_zlozenia TIMESTAMP DEFAULT NOW(),
    status VARCHAR(50) DEFAULT 'oczekujący',

    FOREIGN KEY (pracownik_id) REFERENCES pracownicy(id)
);

-- ============================
-- TABELA: pojazdy (wyjątek: brak SERIAL, numeracja własna)
-- ============================
CREATE TABLE IF NOT EXISTS pojazdy (
    id INT PRIMARY KEY,
    nr_rejestracyjny VARCHAR(20) NOT NULL UNIQUE,
    marka VARCHAR(50),
    model VARCHAR(50),
    rok_produkcji INT,
    nazwa_zajezdni VARCHAR(100),
    naped VARCHAR(50),
    sprawny BOOLEAN DEFAULT TRUE

    FOREIGN KEY (nazwa_zajezdni) REFERENCES zajezdnie(nazwa_zajezdni)
    FOREIGN KEY (naped) REFERENCES typy_napedow(nazwa_typu_napedu)
);

-- ============================
-- TABELA: linie
-- ============================
CREATE TABLE IF NOT EXISTS linie (
    id SERIAL PRIMARY KEY,
    nazwa VARCHAR(50) NOT NULL UNIQUE,
    opis TEXT
);

-- ============================
-- TABELA: brygady
-- ============================
CREATE TABLE IF NOT EXISTS brygady (
    id SERIAL PRIMARY KEY,
    linia_id INT NOT NULL,
    nazwa VARCHAR(50) NOT NULL,

    FOREIGN KEY (linia_id) REFERENCES linie(id)
);

-- ============================
-- TABELA: grafiki kierowców
-- ============================
CREATE TABLE IF NOT EXISTS grafiki (
    id SERIAL PRIMARY KEY,
    pracownik_id INT NOT NULL,
    data DATE NOT NULL,
    brygada_id INT,
    pojazd_id INT,

    FOREIGN KEY (pracownik_id) REFERENCES pracownicy(id),
    FOREIGN KEY (brygada_id) REFERENCES brygady(id),
    FOREIGN KEY (pojazd_id) REFERENCES pojazdy(id)
);

-- ============================
-- TABELA: logi systemowe (opcjonalne)
-- ============================
CREATE TABLE IF NOT EXISTS logi (
    id SERIAL PRIMARY KEY,
    pracownik_id INT,
    akcja VARCHAR(255),
    data TIMESTAMP DEFAULT NOW(),

    FOREIGN KEY (pracownik_id) REFERENCES pracownicy(id)
);

