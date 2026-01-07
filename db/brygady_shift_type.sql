-- F16: Shift type distinction (day/night brigades)
-- Alter table to add shift type column

ALTER TABLE brygady ADD COLUMN IF NOT EXISTS typ_brygady VARCHAR(20) DEFAULT 'dzień' CHECK(typ_brygady IN ('dzień', 'noc'));

-- Add index for shift type queries
CREATE INDEX IF NOT EXISTS idx_brygady_typ_brygady ON brygady(typ_brygady);

-- Example: Update existing brigades if needed
-- UPDATE brygady SET typ_brygady='dzień' WHERE id IN (1,2);
-- UPDATE brygady SET typ_brygady='noc' WHERE id IN (3,4);
