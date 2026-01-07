INSERT INTO stanowiska (stanowisko)
VALUES ('Nowy Kierowca'),
('Kierowca'),
('Doswiadczony Kierowca'),
('Blacharz'),
('Lakiernik'),
('Mechanik'),
('Inspektor biletów')
('Nowy Dyspozytor'),
('Dyspozytor'),
('Doswiadczony Dyspozytor'),
('Kierownik Zajezdni'),
('Zarząd'),
('Zastępca prezesa'),
('Prezes');

INSERT INTO marki (nazwa_marki)
VALUES ('Solaris'),
('MAN'),
('Mercedes-Benz'),
('Isuzu'),
('Jelcz'),
('SOR'),
('Volvo'),
('Škoda'),
('Setra'),
('Neoplan'),
('Autosan'),
('Karosa'),
('Ikarus'),
('ZiU'),
('Trolza'),
('Tatra'),
('Konstal'),
('Duewag'),
('Pesa'),
('Siemens'),
('Bombardier');

INSERT INTO modele (nazwa_modelu)
VALUES ('Urbino 12 IV'),
('Urbino 18 IV'),
('Urbino 12 III'),
('Urbino 15 III'),
('Urbino 18 III'),
('A21'),
('A23'),
('o530'),
('o530L'),
('o530G'),
('Citiport 12'),
('Citiport 18'),
('M121MB'),
('M181M/3')
('7700FL'),
('BN 8,5'),
('BN12'),
('NL283')
('NG313')
('A300'),
('AG300'),
('14Tr.D'),
('14Tr.E'),
('Lions City 18 EfficientHybrid'),
('Urbino 15 II'),
('Urbino 12 II'),
('Urbino 18 II'),
('K4016TD'),
('7000A.BEV'),
('7700'),
('Urbino 10,5 IV'),
('L090M'),
('H10-30'),
('B951'),
('M101/3'),
('14TrM'),
('15TrM'),
('260.04'),
('280.37'),
('14Tr.BET'),
('15Tr.BET'),
('27Tr III'),
('Trollino 12M III'),
('Trollino 12 IV'),
('Trollino 18 IV'),
('5625 Megalopolis'),
('9.BET'),
('T3SUSC'),
('105Na'),
('GT8N'),
('112N'),
('Twist 2015N'),
('16T'),
('40T'),
('NGT6'),
('Swing 120Na');

INSERT INTO grupy (grupa)
VALUES ('MINI'),
('MIDI'),
('MAXI'),
('MAXI+'),
('MEGA'),
('MEGA+');

INSERT INTO marki_modele (id_marki, id_modelu, id_grupy)
VALUES ('1','1','3'),
('1','2','5'),
('1','3','3'),
('1','4','4'),
('1','5','5'),
('2','6','3'),
('2','7','5'),
('3','8','3'),
('3','9','4'),
('3','10','5'),
('4','11','3'),
('4','12','5'),
('5','13','3'),
('5','14','5');

INSERT INTO rodzaje_przegladow (nazwa_rodzaju_przegladu)
VALUES ('O1'),
('OT'),
('OM'),
('OR'),
('OO'),
('OD'),
('OS'),
('OES');

INSERT INTO typy_zajezdni (nazwa_typu)
VALUES ('AUtobusowa'),
('Trolejbusowa'),
('Tramwajowa'),
('Autobusowo-trolejbusowa'),
('Inna');

INSERT INTO zajezdnie (nazwa_zajezdni, id_typu_zajezdni)
VALUES ('OKM', 4),
('OMC', 1),
('OKW', 3),
('OKA', 3),
('Zarząd', 5);

INSERT INTO firmy (nazwa_firmy, opis_firmy)
VALUES ('PPUT Ostrans', 'Prywatne Przedsiębiorstwo Usług Transportowych Ostrans'),
('MZK Ostrołęka', 'Miejskie Zakłady Komunikacyjne w Ostrołęce'),
('DPO', 'Dopravní podnik Ostrava a.s.'),
('PMDP', 'Plzeňské městské dopravní podniky'),
('Osmuni', 'Ostromunikacja'),
('Kujatrans', 'Kujawsko-Pomorskie Przedsiębiorstwo Transportowe'),
('PKM Gliwice', 'Przedsiębiorstwo Komunikacji Miejskiej w Gliwicach'),
('Transgór', 'Przedsiębiorstwo Transportowe Transgór'),
('Kłosok', 'Przedsiębiorstwo Transportowe Kłosok'),
('PKM Tychy', 'Przedsiębiorstwo Komunikacji Miejskiej w Tychach'),
('PKM Katowice', 'Przedsiębiorstwo Komunikacji Miejskiej w Katowicach'),
('MZK Jaworzno', 'Miejskie Zakłady Komunikacyjne w Jaworznie'),
('MZK Bydgoszcz', 'Miejskie Zakłady Komunikacyjne w Bydgoszczy'),
('MZK Toruń', 'Miejskie Zakłady Komunikacyjne w Toruniu'),
('MZK Lublin', 'Miejskie Zakłady Komunikacyjne w Lublinie'),
('MPK Szczecin', 'Miejskie Przedsiębiorstwo Komunikacyjne w Szczecinie'),
('MPK Kraków', 'Miejskie Przedsiębiorstwo Komunikacyjne w Krakowie'),
('MPK Wrocław', 'Miejskie Przedsiębiorstwo Komunikacyjne we Wrocławiu'),
('ZTM Warszawa', 'Zarząd Transportu Miejskiego w Warszawie'),
('ZTM Gdańsk', 'Zarząd Transportu Miejskiego w Gdańsku'),
('ZTM Poznań', 'Zarząd Transportu Miejskiego w Poznaniu')
('GAiT', 'Gdańskie Autobusy i Tramwaje'),
('Solaris','Solaris Bus & Coach sp. z o.o.'),
('MAN','MAN Truck & Bus sp. z o.o.'),
('Mercedes-Benz','Daimler Buses Polska Sp. z o.o.'),
('SOR','SOR Libchavy spol. s r. o.'),
('Volvo','AB Volvo'),
('Škoda','ŠKODA TRANSPORTATION a.s.'),
('MPK Włocławek', 'Miejskie Przedsiębiorstwo Komunikacyjne we Włocławku'),
('MPK Słupsk', 'Miejskie Przedsiębiorstwo Komunikacyjne w Słupsku'),
('MPK Bielsko-Biała', 'Miejskie Przedsiębiorstwo Komunikacyjne w Bielsku-Białej'),
('MPK Wałbrzych', 'Miejskie Przedsiębiorstwo Komunikacyjne w Wałbrzychu'),
('MPK Opoczno', 'Miejskie Przedsiębiorstwo Komunikacyjne w Opocznie'),
('MPK Jaworzno', 'Miejskie Przedsiębiorstwo Komunikacyjne w Jaworznie'),
('MPK Tarnów', 'Miejskie Przedsiębiorstwo Komunikacyjne w Tarnowie'),
('MPK Koszalin', 'Miejskie Przedsiębiorstwo Komunikacyjne w Koszalinie'),
('MPK Zielona Góra', 'Miejskie Przedsiębiorstwo Komunikacyjne w Zielonej Górze'),
('MPK Opole', 'Miejskie Przedsiębiorstwo Komunikacyjne w Opolu'),
('MPK Gdynia', 'Miejskie Przedsiębiorstwo Komunikacyjne w Gdyni'),
('MPK Poznań', 'Miejskie Przedsiębiorstwo Komunikacyjne w Poznaniu'),
('MPK Bydgoszcz', 'Miejskie Przedsiębiorstwo Komunikacyjne w Bydgoszczy'),
('MPK Lublin', 'Miejskie Przedsiębiorstwo Komunikacyjne w Lublinie'),
('MPK Rzeszów', 'Miejskie Przedsiębiorstwo Komunikacyjne w Rzeszowie'),
('MPK Łódź', 'Miejskie Przedsiębiorstwo Komunikacyjne w Łodzi'),
('MPK Częstochowa', 'Miejskie Przedsiębiorstwo Komunikacyjne w Częstochowie'),
('PKS Gdańsk', 'Przedsiębiorstwo Komunikacji Samochodowej w Gdańsku'),
('PKS Słupsk', 'Przedsiębiorstwo Komunikacji Samochodowej w Słupsku'),
('PKS Koszalin', 'Przedsiębiorstwo Komunikacji Samochodowej w Koszalinie'),
('PKS Elbląg', 'Przedsiębiorstwo Komunikacji Samochodowej w Elblągu'),
('PKS Olsztyn', 'Przedsiębiorstwo Komunikacji Samochodowej w Olsztynie'),
('PKS Białystok', 'Przedsiębiorstwo Komunikacji Samochodowej w Białymstoku'),
('PKS Lublin', 'Przedsiębiorstwo Komunikacji Samochodowej w Lublinie'),
('PKS Radom', 'Przedsiębiorstwo Komunikacji Samochodowej w Radomiu'),
('PKS Kielce', 'Przedsiębiorstwo Komunikacji Samochodowej w Kielcach'),
('PKS Częstochowa', 'Przedsiębiorstwo Komunikacji Samochodowej w Częstochowie'),
('PKS Katowice', 'Przedsiębiorstwo Komunikacji Samochodowej w Katowicach'),
('PKS Bielsko-Biała', 'Przedsiębiorstwo Komunikacji Samochodowej w Bielsku-Białej'),
('PKS Rzeszów', 'Przedsiębiorstwo Komunikacji Samochodowej w Rzeszowie'),
('PKS Przemyśl', 'Przedsiębiorstwo Komunikacji Samochodowej w Przemyślu'),
('PKS Tarnów', 'Przedsiębiorstwo Komunikacji Samochodowej w Tarnowie'),
('PKS Nowy Sącz', 'Przedsiębiorstwo Komunikacji Samochodowej w Nowym Sączu'),
('PKS Zakopane', 'Przedsiębiorstwo Komunikacji Samochodowej w Zakopanem');

INSERT INTO napedy (nazwa_napedu)
VALUES ('<euro 0'),
('euro 1'),
('euro 2'),
('euro 3'),
('euro 4'),
('euro 5'),
('EEV'),
('euro 6'),
('euro 4 CNG'),
('EEV CNG'),
('euro 6 CNG'),
('EEV Hybrid'),
('euro 6 Hybrid'),
('euro 6 Mild Hybrid'),
('elektryczny'),
('elektryczny plug-in');

INSERT INTO pojazdy (id_pojazdu, id_marki_modelu, rok_prod, nr_VIN)
VALUES ();

INSERT INTO rejestracje (id_pojazdu, rejestracja)
VALUES ();

INSERT INTO pracownicy (imie, nazwisko, nazwa, id_stanowiska, id_zajezdni)
VALUES ('Dawid','Volve','the_realcar','14','5'), ('Hubert Jakub','Tryniecki','kustul','13','5');

INSERT INTO przydzialy (id_pojazdu, id_pracownka, data_od, data_do)
VALUES ();

INSERT INTO przeglady ()
VALUES ();

INSERT INTO naprawy ()
VALUES ();

INSERT INTO przetargi ()
VALUES ();

INSERT INTO wypozyczenia ()
VALUES ();

INSERT INTO transakcje ()
VALUES ();

SELECT * FROM public.stanowiska;

SELECT * FROM public.marki;

SELECT * FROM public.modele;

SELECT * FROM public.grupy;

SELECT * FROM public.marki_modele;

SELECT * FROM public.rodzaje_przegladow;

SELECT * FROM public.typy_zajezdni;

SELECT * FROM public.zajezdnie;

SELECT * FROM public.firmy;
