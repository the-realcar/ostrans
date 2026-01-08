# PPUT Ostrans - Quick Reference Card
## Najczęstsze Operacje

## 🔐 Logowanie

### Panel Web
```
URL: https://your-domain.pl/panel/
Login: admin
Hasło: (twoje hasło)
```

### API
```bash
curl -X POST https://your-domain.pl/panel/api.php/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"your_password"}'
```

---

## 👤 Zarządzanie Pracownikami

### Dodaj pracownika (API)
```bash
curl -X POST https://your-domain.pl/panel/api.php/admin/pracownik \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "imie": "Jan",
    "nazwisko": "Kowalski",
    "login": "jkowalski",
    "haslo": "haslo123",
    "uprawnienie_id": 1
  }'
```

### Dezaktywuj pracownika
```bash
curl -X POST https://your-domain.pl/panel/api.php/admin/employee/123/deactivate \
  -H "Authorization: Bearer <TOKEN>"
```

### Lista pracowników
```bash
curl -X GET https://your-domain.pl/panel/api.php/pracownicy \
  -H "Authorization: Bearer <TOKEN>"
```

---

## 🚗 Zarządzanie Pojazdami

### Dodaj pojazd
```bash
curl -X POST https://your-domain.pl/panel/api.php/admin/pojazd \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "nr_rejestracyjny": "GDA12345",
    "marka": "Solaris",
    "model": "Urbino 12",
    "rok_produkcji": 2020,
    "sprawny": true
  }'
```

### Oznacz pojazd jako niesprawny
```bash
curl -X PUT https://your-domain.pl/panel/api.php/admin/pojazd/123 \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"sprawny": false}'
```

### Przypisz pojazd stały kierowcy
```bash
curl -X POST https://your-domain.pl/panel/api.php/admin/pracownik/5/pojazd-staly \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"pojazd_id": 123}'
```

### Historia wykorzystania pojazdu
```bash
curl -X GET https://your-domain.pl/panel/api.php/pojazd/123/usage \
  -H "Authorization: Bearer <TOKEN>"
```

---

## 🚌 Zarządzanie Liniami i Brygadami

### Dodaj linię
```bash
curl -X POST https://your-domain.pl/panel/api.php/admin/linia \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "nr_linii": "15",
    "typ": "bus",
    "start_point": "Dworzec Główny",
    "end_point": "Oliwa"
  }'
```

### Dodaj brygadę
```bash
curl -X POST https://your-domain.pl/panel/api.php/admin/brygada \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "nazwa": "Brygada 1",
    "linia_id": 5,
    "typ_brygady": "dzienna"
  }'
```

### Lista linii
```bash
curl -X GET https://your-domain.pl/panel/api.php/linie \
  -H "Authorization: Bearer <TOKEN>"
```

### Lista brygad
```bash
curl -X GET https://your-domain.pl/panel/api.php/brygady \
  -H "Authorization: Bearer <TOKEN>"
```

---

## 📅 Zarządzanie Grafikami

### Dodaj wpis w grafiku
```bash
curl -X POST https://your-domain.pl/panel/api.php/admin/grafik \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "pracownik_id": 5,
    "data": "2026-01-15",
    "brygada_id": 3,
    "pojazd_id": 123
  }'
```

### Edytuj grafik
```bash
curl -X PUT https://your-domain.pl/panel/api.php/admin/grafik/456 \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "pojazd_id": 124,
    "status": "zaplanowany"
  }'
```

### Anuluj wpis w grafiku
```bash
curl -X DELETE https://your-domain.pl/panel/api.php/admin/grafik/456 \
  -H "Authorization: Bearer <TOKEN>"
```

### Pobierz grafik kierowcy
```bash
curl -X GET "https://your-domain.pl/panel/api.php/grafik?userId=5" \
  -H "Authorization: Bearer <TOKEN>"
```

---

## 📝 System Wniosków

### Złóż wniosek (kierowca)
```bash
curl -X POST https://your-domain.pl/panel/api.php/wnioski \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "typ": "urlop",
    "opis": "Proszę o urlop w dniach 15-20.01.2026",
    "data_od": "2026-01-15",
    "data_do": "2026-01-20"
  }'
```

### Lista wniosków
```bash
# Kierowca: tylko własne
# Dyspozytor/Zarząd: wszystkie
curl -X GET https://your-domain.pl/panel/api.php/wnioski \
  -H "Authorization: Bearer <TOKEN>"
```

### Zaakceptuj wniosek
```bash
curl -X PUT https://your-domain.pl/panel/api.php/wnioski/789/status \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "zaakceptowany"
  }'
```

### Odrzuć wniosek
```bash
curl -X PUT https://your-domain.pl/panel/api.php/wnioski/789/status \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "odrzucony",
    "reason": "Brak pokrycia w brygadzie"
  }'
```

---

## 📊 Eksport Danych

### Eksport grafików (CSV)
```bash
curl -X GET "https://your-domain.pl/panel/api.php/export/grafiki?format=csv&start_date=2026-01-01&end_date=2026-01-31" \
  -H "Authorization: Bearer <TOKEN>" \
  -o grafiki.csv
```

### Eksport grafików (PDF)
```bash
curl -X GET "https://your-domain.pl/panel/api.php/export/grafiki?format=pdf&start_date=2026-01-01&end_date=2026-01-31" \
  -H "Authorization: Bearer <TOKEN>" \
  -o grafiki.pdf
```

### Eksport pojazdów
```bash
curl -X GET "https://your-domain.pl/panel/api.php/export/pojazdy?format=csv" \
  -H "Authorization: Bearer <TOKEN>" \
  -o pojazdy.csv
```

### Eksport brygad
```bash
curl -X GET "https://your-domain.pl/panel/api.php/export/brygady?format=csv" \
  -H "Authorization: Bearer <TOKEN>" \
  -o brygady.csv
```

---

## 🔑 Zarządzanie Hasłami

### Zmień hasło (zalogowany użytkownik)
```bash
curl -X POST https://your-domain.pl/panel/api.php/password/change \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "oldPassword": "stare_haslo",
    "newPassword": "nowe_haslo123"
  }'
```

### Reset hasła (krok 1: prośba o reset)
```bash
curl -X POST https://your-domain.pl/panel/api.php/password-reset/request \
  -H "Content-Type: application/json" \
  -d '{
    "login": "jkowalski"
  }'
```

### Reset hasła (krok 2: potwierdzenie z tokenem)
```bash
curl -X POST https://your-domain.pl/panel/api.php/password-reset/confirm \
  -H "Content-Type: application/json" \
  -d '{
    "token": "abc123def456...",
    "newPassword": "nowe_bezpieczne_haslo"
  }'
```

---

## 📋 Logi Aktywności

### Pobierz logi użytkownika
```bash
curl -X GET "https://your-domain.pl/panel/api.php/activity-log?user_id=5" \
  -H "Authorization: Bearer <TOKEN>"
```

### Pobierz logi dla typu encji
```bash
curl -X GET "https://your-domain.pl/panel/api.php/activity-log?entity_type=pojazdy" \
  -H "Authorization: Bearer <TOKEN>"
```

### Pobierz logi dla konkretnej encji
```bash
curl -X GET "https://your-domain.pl/panel/api.php/activity-log?entity_type=pojazdy&entity_id=123" \
  -H "Authorization: Bearer <TOKEN>"
```

---

## 🗄️ Zarządzanie Bazą Danych

### Backup
```bash
pg_dump -U ostrans_user ostrans > ostrans_backup_$(date +%Y%m%d).sql
```

### Restore
```bash
psql -U ostrans_user ostrans < ostrans_backup_20260108.sql
```

### Czyszczenie starych logów
```sql
DELETE FROM activity_log WHERE created_at < NOW() - INTERVAL '90 days';
DELETE FROM password_resets WHERE expires_at < NOW();
```

### Sprawdź rozmiar tabel
```sql
SELECT 
    tablename, 
    pg_size_pretty(pg_total_relation_size(tablename::text)) AS size
FROM pg_tables 
WHERE schemaname = 'public' 
ORDER BY pg_total_relation_size(tablename::text) DESC;
```

---

## 🛠️ Troubleshooting

### Sprawdź logi błędów
```bash
# Apache
tail -f /var/log/apache2/ostrans-error.log

# Nginx
tail -f /var/log/nginx/error.log

# PostgreSQL
tail -f /var/log/postgresql/postgresql-14-main.log
```

### Sprawdź połączenie z bazą danych
```bash
psql -U ostrans_user -d ostrans -c "SELECT version();"
```

### Sprawdź status web servera
```bash
# Apache
systemctl status apache2

# Nginx
systemctl status nginx

# PHP-FPM
systemctl status php8.1-fpm
```

### Restart serwisów
```bash
systemctl restart apache2  # lub nginx
systemctl restart php8.1-fpm
systemctl restart postgresql
```

---

## 📱 Szybki Dostęp (Panel Web)

### Dla Kierowcy
- **Grafik:** `/panel/index.php?route=grafik`
- **Wnioski:** `/panel/index.php?route=wnioski`
- **Zgłoszenia:** `/panel/index.php?route=zgloszenia`
- **Raporty:** `/panel/index.php?route=wyslij-raport`

### Dla Dyspozytora
- Wszystkie powyższe +
- **Linie i Brygady:** `/panel/index.php?route=lines-management`
- **Grafik (zarządzanie):** `/panel/index.php?route=grafik`
- **Raporty:** `/panel/index.php?route=raporty`

### Dla Zarządu
- Wszystkie powyższe +
- **Pracownicy:** `/panel/index.php?route=employees`
- **Pojazdy:** `/panel/index.php?route=pojazdy`
- **Logi:** `/panel/index.php?route=admin`

---

## 🎯 Najczęstsze Scenariusze

### Scenariusz 1: Dodaj nowego kierowcę i przydziel grafik
```bash
# 1. Dodaj pracownika
curl -X POST https://your-domain.pl/panel/api.php/admin/pracownik \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"imie":"Jan","nazwisko":"Kowalski","login":"jkowalski","haslo":"haslo123","uprawnienie_id":1}'

# Odpowiedź: {"ok":true,"pracownik":{"id":10,...}}

# 2. Przydziel pojazd stały (opcjonalnie)
curl -X POST https://your-domain.pl/panel/api.php/admin/pracownik/10/pojazd-staly \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"pojazd_id":5}'

# 3. Dodaj do grafiku
curl -X POST https://your-domain.pl/panel/api.php/admin/grafik \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"pracownik_id":10,"data":"2026-01-15","brygada_id":3,"pojazd_id":5}'
```

### Scenariusz 2: Obsługa wniosku o urlop
```bash
# 1. Kierowca składa wniosek
curl -X POST https://your-domain.pl/panel/api.php/wnioski \
  -H "Authorization: Bearer <DRIVER_TOKEN>" \
  -d '{"typ":"urlop","opis":"Proszę o urlop 15-20.01.2026"}'

# 2. Dyspozytor sprawdza wnioski
curl -X GET https://your-domain.pl/panel/api.php/wnioski \
  -H "Authorization: Bearer <DISPATCHER_TOKEN>"

# 3. Dyspozytor akceptuje
curl -X PUT https://your-domain.pl/panel/api.php/wnioski/123/status \
  -H "Authorization: Bearer <DISPATCHER_TOKEN>" \
  -d '{"status":"zaakceptowany"}'
```

### Scenariusz 3: Pojazd uległ awarii
```bash
# 1. Oznacz pojazd jako niesprawny
curl -X PUT https://your-domain.pl/panel/api.php/admin/pojazd/5 \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"sprawny":false}'

# 2. Usuń przyszłe wpisy z grafiku dla tego pojazdu (opcjonalnie)
# Można to zrobić przez panel web lub API batch update

# 3. Przydziel zastępczy pojazd do brygad
curl -X PUT https://your-domain.pl/panel/api.php/admin/grafik/456 \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{"pojazd_id":6}'
```

---

## 💡 Tips & Tricks

### Generuj silny JWT Secret
```bash
openssl rand -hex 32
```

### Sprawdź ważność JWT tokenu
```bash
# Rozkoduj payload (bez weryfikacji)
TOKEN="eyJ0eXAiOiJKV1Q..."
echo $TOKEN | cut -d'.' -f2 | base64 -d 2>/dev/null | jq .
```

### Szybki backup przed zmianami
```bash
pg_dump ostrans | gzip > backup_before_changes_$(date +%s).sql.gz
```

### Testuj API z plikiem
```bash
# Zapisz token do pliku
echo "Bearer eyJ0eXAiOi..." > token.txt

# Użyj w requescie
curl -X GET https://your-domain.pl/panel/api.php/pracownicy \
  -H @token.txt
```

---

## 📞 Kontakt i Wsparcie

**Dokumentacja:** [README.md](readme.md), [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md)
**Deployment Guide:** [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
**Issues:** https://github.com/your-repo/ostrans/issues

---

**Wersja:** 1.0.0 | **Data:** 2026-01-08
