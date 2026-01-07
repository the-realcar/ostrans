-- F12: Vehicle usage history tracking
-- Logs every vehicle usage (driver assignment to schedule)

CREATE TABLE IF NOT EXISTS vehicle_usage (
    id SERIAL PRIMARY KEY,
    pojazd_id INT NOT NULL REFERENCES pojazdy(id),
    pracownik_id INT REFERENCES pracownicy(id),
    grafik_id INT REFERENCES grafiki(id),
    data_start TIMESTAMP NOT NULL,
    data_end TIMESTAMP,
    km_start INT,
    km_end INT,
    uwagi TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_vehicle_usage_pojazd ON vehicle_usage(pojazd_id);
CREATE INDEX IF NOT EXISTS idx_vehicle_usage_pracownik ON vehicle_usage(pracownik_id);
CREATE INDEX IF NOT EXISTS idx_vehicle_usage_grafik ON vehicle_usage(grafik_id);
