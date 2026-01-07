INSERT INTO grafiki (pracownik_id, data, brygada_id, pojazd_id)
VALUES (
  (SELECT id FROM pracownicy WHERE login='driver1' LIMIT 1),
  CURRENT_DATE,
  (SELECT id FROM brygady WHERE nazwa='Brygada A' LIMIT 1),
  1001
);