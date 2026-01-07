-- F13: Permanent vehicle assignments to drivers
-- Assign a dedicated/permanent vehicle to a driver

CREATE TABLE IF NOT EXISTS pracownik_pojazd_staly (
    id SERIAL PRIMARY KEY,
    pracownik_id INT NOT NULL REFERENCES pracownicy(id) UNIQUE,
    pojazd_id INT NOT NULL REFERENCES pojazdy(id),
    data_przypisania DATE DEFAULT CURRENT_DATE,
    data_zakonczenia DATE,
    uwagi TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_pracownik_pojazd_staly_pracownik ON pracownik_pojazd_staly(pracownik_id);
CREATE INDEX IF NOT EXISTS idx_pracownik_pojazd_staly_pojazd ON pracownik_pojazd_staly(pojazd_id);
