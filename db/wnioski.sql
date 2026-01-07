-- Przykładowy wniosek kierowcy
INSERT INTO wnioski (pracownik_id, typ, opis, status)
VALUES (
  (SELECT id FROM pracownicy WHERE login='the_realcar' LIMIT 1),
  'urlop',
  'Prośba o urlop w dniach 2025-06-01 do 2025-06-15',
  'nowy'
);