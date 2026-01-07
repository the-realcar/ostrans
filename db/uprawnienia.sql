-- Uprawnienia (role) dla panelu
INSERT INTO uprawnienia (poziom, opis)
VALUES 
('zarzad', 'Zarząd — pełny dostęp do systemu'),
('dyspozytor', 'Dyspozytor — zarządzanie liniami, brygadami, graficikami i wnioskami kierowców'),
('kierowca', 'Kierowca — dostęp do własnych danych i możliwość składania wniosków');
