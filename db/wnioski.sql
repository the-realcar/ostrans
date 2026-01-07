INSERT INTO wnioski (pracownik_id, typ, opis, status)
VALUES (
  (SELECT id FROM pracownicy WHERE login='driver1' LIMIT 1),
  'kurs_z_wolnego',
  'Prośba o przydzielenie kursu z dnia 2025-06-01',
  'oczekujący'
);