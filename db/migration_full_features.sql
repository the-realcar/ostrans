-- Migration script to add missing columns and ensure database is up to date
-- Run this script to update existing database to support all features

-- Add is_active column to various tables (soft delete)
ALTER TABLE linie ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true;
ALTER TABLE brygady ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true;
ALTER TABLE pojazdy ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true;
ALTER TABLE grafiki ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT true;

-- Add typ_brygady column for day/night shift marking (F16)
ALTER TABLE brygady ADD COLUMN IF NOT EXISTS typ_brygady VARCHAR(20) DEFAULT 'dzienna';

-- Add opis column to linie for detailed description
ALTER TABLE linie ADD COLUMN IF NOT EXISTS opis TEXT;

-- Add updated_at timestamps
ALTER TABLE pracownicy ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT NOW();
ALTER TABLE pojazdy ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT NOW();
ALTER TABLE grafiki ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT NOW();

-- Ensure email column exists for password reset (F4)
ALTER TABLE pracownicy ADD COLUMN IF NOT EXISTS email VARCHAR(100);

-- Create activity_log table if not exists (F25-F26)
CREATE TABLE IF NOT EXISTS activity_log (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES pracownicy(id),
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    data JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Create password_resets table if not exists (F4)
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES pracownicy(id),
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Create vehicle_usage table if not exists (F12)
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

-- Create pracownik_pojazd_staly table if not exists (F13)
CREATE TABLE IF NOT EXISTS pracownik_pojazd_staly (
    id SERIAL PRIMARY KEY,
    pracownik_id INT NOT NULL REFERENCES pracownicy(id),
    pojazd_id INT NOT NULL REFERENCES pojazdy(id),
    data_przypisania DATE DEFAULT CURRENT_DATE,
    data_zakonczenia DATE,
    uwagi TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    CONSTRAINT unique_active_pojazd_per_pracownik UNIQUE(pracownik_id, is_active)
);

-- Create wnioski_meta table if not exists (for additional request data)
CREATE TABLE IF NOT EXISTS wnioski_meta (
    id SERIAL PRIMARY KEY,
    wniosek_id INT REFERENCES wnioski(id) ON DELETE CASCADE,
    meta JSONB,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Create zgloszenia table if not exists (incident reports)
CREATE TABLE IF NOT EXISTS zgloszenia (
    id SERIAL PRIMARY KEY,
    pracownik_id INT REFERENCES pracownicy(id),
    pojazd_id INT REFERENCES pojazdy(id),
    data_zdarzenia TIMESTAMP NOT NULL,
    opis TEXT NOT NULL,
    wyjasnienie TEXT,
    uwagi TEXT,
    files JSONB,
    status VARCHAR(50) DEFAULT 'nowe',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Create raporty table if not exists
CREATE TABLE IF NOT EXISTS raporty (
    id SERIAL PRIMARY KEY,
    grafik_id INT REFERENCES grafiki(id),
    pracownik_id INT REFERENCES pracownicy(id),
    typ VARCHAR(50),
    tresc TEXT,
    files JSONB,
    status VARCHAR(50) DEFAULT 'wyslany',
    created_at TIMESTAMP DEFAULT NOW()
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_activity_log_user_id ON activity_log(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_entity ON activity_log(entity_type, entity_id);
CREATE INDEX IF NOT EXISTS idx_activity_log_created ON activity_log(created_at DESC);

CREATE INDEX IF NOT EXISTS idx_vehicle_usage_pojazd ON vehicle_usage(pojazd_id);
CREATE INDEX IF NOT EXISTS idx_vehicle_usage_pracownik ON vehicle_usage(pracownik_id);
CREATE INDEX IF NOT EXISTS idx_vehicle_usage_grafik ON vehicle_usage(grafik_id);

CREATE INDEX IF NOT EXISTS idx_grafiki_data ON grafiki(data);
CREATE INDEX IF NOT EXISTS idx_grafiki_pracownik ON grafiki(pracownik_id);
CREATE INDEX IF NOT EXISTS idx_grafiki_brygada ON grafiki(brygada_id);

CREATE INDEX IF NOT EXISTS idx_wnioski_pracownik ON wnioski(pracownik_id);
CREATE INDEX IF NOT EXISTS idx_wnioski_status ON wnioski(status);

CREATE INDEX IF NOT EXISTS idx_brygady_linia ON brygady(linia_id);

CREATE INDEX IF NOT EXISTS idx_pracownicy_login ON pracownicy(login);
CREATE INDEX IF NOT EXISTS idx_pracownicy_discord ON pracownicy(discord_id);
CREATE INDEX IF NOT EXISTS idx_pracownicy_active ON pracownicy(is_active);

-- Update existing data: set default values for new columns
UPDATE linie SET is_active = true WHERE is_active IS NULL;
UPDATE brygady SET is_active = true WHERE is_active IS NULL;
UPDATE brygady SET typ_brygady = 'dzienna' WHERE typ_brygady IS NULL;
UPDATE pojazdy SET is_active = true WHERE is_active IS NULL;
UPDATE grafiki SET is_active = true WHERE is_active IS NULL;
UPDATE pracownicy SET is_active = true WHERE is_active IS NULL;

-- Add comments to tables for documentation
COMMENT ON TABLE activity_log IS 'F25-F26: System activity logging with IP and user agent tracking';
COMMENT ON TABLE password_resets IS 'F4: Password reset tokens with 1 hour expiry';
COMMENT ON TABLE vehicle_usage IS 'F12: Vehicle usage history tracking';
COMMENT ON TABLE pracownik_pojazd_staly IS 'F13: Permanent vehicle assignment for drivers';
COMMENT ON TABLE wnioski IS 'F21-F24: Employee request system with approval workflow';
COMMENT ON TABLE grafiki IS 'F17-F20: Driver schedules with conflict validation';
COMMENT ON TABLE linie IS 'F14: Line management';
COMMENT ON TABLE brygady IS 'F15-F16: Brigade management with day/night shift marking';

-- Migration complete
-- All features F1-F29 are now supported by the database schema
