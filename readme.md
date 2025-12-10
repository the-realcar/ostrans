1. Cel systemu

System ma umożliwiać obsługę pracowników przedsiębiorstwa transportowego, w tym zarządzanie grafikami, brygadami, pojazdami, liniami oraz przepływem wniosków, z uwzględnieniem trzech poziomów uprawnień:
kierowca, dyspozytor, zarząd.
Działanie systemu opiera się na bazie danych zawierającej informacje o pracownikach, pojazdach, liniach i brygadach.


---

2. Użytkownicy systemu

2.1 Kierowca

Dostęp do własnego grafiku pracy.

Podgląd przydzielonej brygady i pojazdu.

Składanie wniosków (urlop, kurs z wolnego, pojazd stały, prośba o zmianę grafiku itp.).

Podgląd statusu wcześniejszych wniosków.

Zmiana hasła, edycja danych kontaktowych.


2.2 Dyspozytor

Wszystko, co kierowca +:

Tworzenie i edycja grafików na dany dzień.

Przydzielanie kierowców do brygad.

Zarządzanie liniami i brygadami.

Zarządzanie pojazdami (stan, przypisanie, awarie).

Podgląd wszystkich wniosków kierowców i podejmowanie decyzji (akceptacja/odrzucenie).

Panel raportowy (obecności kierowców, wykorzystanie pojazdów).


2.3 Zarząd

Wszystko, co dyspozytor +:

Zarządzanie pracownikami (dodawanie, edytowanie, blokowanie kont).

Zarządzanie mapami, rejonami, zasięgiem obsługi komunikacji.

Wgląd we wszystkie dane statystyczne.

Podgląd i edycja ustawień systemowych.



---

3. Wymagania funkcjonalne

3.1 Logowanie i autoryzacja

F1. Logowanie za pomocą loginu + hasła.

F2. Logowanie przez Discord OAuth2 (opcjonalnie 2FA po integracji).

F3. System uprawnień (3 główne role + 15 stanowisk).

F4. Reset hasła (e-mail lub kod jednorazowy).

F5. Sesje użytkowników z automatyczną ważnością.


3.2 Zarządzanie pracownikami

F6. Dodawanie pracownika z wyborem stanowiska i poziomu uprawnień.

F7. Edycja danych: imię, nazwisko, login, aktywność, stanowisko, Discord ID.

F8. Dezaktywacja konta zamiast usuwania (archiwizacja).

F9. Podgląd historii aktywności pracownika (logi).


3.3 Zarządzanie pojazdami

F10. Dodawanie pojazdu (z ręcznie nadanym ID).

F11. Oznaczanie pojazdu jako sprawny/niesprawny.

F12. Podgląd historii wykorzystania pojazdu.

F13. Przypisanie kierowcy do pojazdu stałego.


3.4 Linie i brygady

F14. Zarządzanie liniami: nazwa, opis, warianty tras.

F15. Zarządzanie brygadami przypisanymi do linii.

F16. Oznaczanie brygad dziennych i nocnych.


3.5 Grafiki kierowców

F17. Tworzenie grafików na dowolną datę.

F18. Przypisanie kierowcy do brygady.

F19. Przypisanie pojazdu do danej brygady w dniu.

F20. Automatyczne sprawdzanie konfliktów (kierowca nie może być w 2 brygadach równocześnie).


3.6 System wniosków

F21. Kierowca składa wniosek (urlop, wolne, zmiana brygady, pojazd stały).

F22. Dyspozytor lub zarząd akceptuje/odrzuca wniosek.

F23. Każdy wniosek ma status: oczekujący, zatwierdzony, odrzucony, anulowany.

F24. Logowanie decyzji do tabeli logów.


3.7 Logi systemowe

F25. Zapisywanie kluczowych zdarzeń: logowanie, decyzje, zmiany danych.

F26. Automatyczna rejestracja IP i user-agent użytkownika (opcjonalne).


3.8 Import / eksport

F27. Eksport grafików do CSV/PDF.

F28. Możliwość importu list pracowników (CSV/SQL).

F29. Eksport listy pojazdów i brygad.



---

4. Wymagania niefunkcjonalne

4.1 Wydajność

System powinien obsługiwać jednocześnie min. 80 aktywnych użytkowników.

Wszystkie operacje bazodanowe muszą wykonywać się poniżej 0.5 sekundy.

Strona panelu musi ładować się w mniej niż 2 sekundy.


4.2 Bezpieczeństwo

Hasła muszą być haszowane (bcrypt / argon2).

Komunikacja szyfrowana HTTPS.

Ochrona przed SQL injection i XSS.

Role-based access control.

Ograniczenie dostępu do API tylko dla zalogowanych użytkowników.


4.3 Skalowalność

Możliwość podpięcia kolejnych modułów (np. system raportowy).

Możliwość pracy w chmurze (np. VPS, Docker).


4.4 Niezawodność

Automatyczne kopie zapasowe bazy danych.

Odporność na błędy dyspozytora (np. blokada powielenia przydziału kierowcy).


4.5 Użyteczność

Panel responsywny (telefon/tablet/komputer).

Interfejs intuicyjny, kolorystyczne oznaczenia statusów.

Tryb ciemny/jasny (opcjonalnie).



---

5. Wymagania techniczne

Backend: PHP 8.1+, rekomendowane: Laravel / Slim / własne MVC.

Frontend: HTML5, CSS3 (Bootstrap/Tailwind), JavaScript.

Baza danych: PostgreSQL (wg Twojego repo).

Integracje:

Discord OAuth2,

Możliwość rozszerzenia o API pojazdów lub lokalizacji.


Hostowanie:

Kompatybilność z home.pl, OVH, VPS, Docker.




---

6. Przykładowe moduły dodatkowe (opcjonalne)

System zgłaszania awarii pojazdów.

Tablica „kto dziś pracuje”.

Podgląd mapy tras i wariantów.

Harmonogram przeglądów pojazdów.

Powiadomienia e-mail/WebPush.
