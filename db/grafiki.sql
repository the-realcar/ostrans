-- Przykładowe grafiki dla kierowcy
INSERT INTO grafiki (pracownik_id, data, brygada_id, pojazd_id, status)
VALUES (
  (SELECT id FROM pracownicy WHERE login='the_realcar' LIMIT 1),
  CURRENT_DATE,
  (SELECT id FROM brygady WHERE nazwa='107/1' LIMIT 1),
  1,
  'zaplanowany'
INSERT INTO grafiki (pracownik_id, data, brygada_id, pojazd_id)
VALUES (
  (SELECT id FROM pracownicy WHERE login='driver1' LIMIT 1),
  CURRENT_DATE,
  (SELECT id FROM brygady WHERE nazwa='Brygada A' LIMIT 1),
  1001
);