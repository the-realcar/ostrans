# PPUT Ostrans - System Zarządzania Transportem
## Kompletny Przewodnik Wdrożeniowy

## 📋 Spis Treści
1. [Wymagania Systemowe](#wymagania-systemowe)
2. [Instalacja](#instalacja)
3. [Konfiguracja](#konfiguracja)
4. [Migracja Bazy Danych](#migracja-bazy-danych)
5. [Pierwsze Uruchomienie](#pierwsze-uruchomienie)
6. [Struktura Projektu](#struktura-projektu)
7. [API Documentation](#api-documentation)
8. [Zarządzanie Użytkownikami](#zarządzanie-użytkownikami)
9. [Rozwiązywanie Problemów](#rozwiązywanie-problemów)

---

## 🔧 Wymagania Systemowe

### Minimalne wymagania:
- **PHP:** 7.4+ (zalecane: 8.1+)
- **PostgreSQL:** 12+
- **Web Server:** Apache 2.4+ lub Nginx 1.18+
- **RAM:** 512 MB
- **Dysk:** 1 GB

### Rozszerzenia PHP:
```bash
php-pgsql
php-json
php-mbstring
php-curl
php-openssl
php-session
php-fileinfo
```

---

## 📦 Instalacja

### 1. Pobierz projekt
```bash
git clone https://github.com/your-repo/ostrans.git
cd ostrans
```

### 2. Skonfiguruj Web Server

#### Apache (.htaccess już w projekcie)
```apache
<VirtualHost *:80>
    ServerName ostrans.local
    DocumentRoot /path/to/ostrans
    
    <Directory /path/to/ostrans>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ostrans-error.log
    CustomLog ${APACHE_LOG_DIR}/ostrans-access.log combined
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name ostrans.local;
    root /path/to/ostrans;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

### 3. Uprawnienia katalogów
```bash
chmod 755 -R ostrans/
chmod 775 ostrans/panel/uploads
chown -R www-data:www-data ostrans/
```

---

## ⚙️ Konfiguracja

### 1. Utwórz plik konfiguracyjny
Skopiuj przykładowy plik konfiguracji:
```bash
cp panel/env.txt.example panel/env.txt
```

### 2. Edytuj `panel/env.txt`
```env
# Database Configuration
DATABASE_URL=postgresql://username:password@localhost:5432/ostrans
# LUB
PG_DSN=pgsql:host=localhost;port=5432;dbname=ostrans
PG_USER=your_db_user
PG_PASS=your_db_password

# JWT Secret (zmień na losowy string)
JWT_SECRET=your_random_secret_key_change_this

# Discord OAuth2 (opcjonalne)
DISCORD_CLIENT_ID=your_discord_client_id
DISCORD_CLIENT_SECRET=your_discord_client_secret
DISCORD_REDIRECT_URI=https://your-domain.pl/auth/discord_callback.php
DISCORD_GUILD_ID=your_discord_server_id
DISCORD_BOT_TOKEN=your_discord_bot_token

# Discord Role IDs (opcjonalne)
ROLE_ZARZAD_ID=role_id_for_management
ROLE_DYSP_ID=role_id_for_dispatcher
ROLE_KIEROWCA_ID=role_id_for_driver

# Email Configuration (opcjonalne, dla F4 - reset hasła)
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=your_email@example.com
SMTP_PASS=your_email_password
SMTP_FROM=noreply@ostrans.pl
```

### 3. Generuj JWT Secret
```bash
php -r "echo bin2hex(random_bytes(32));"
```
Wynik wklej jako `JWT_SECRET` w `env.txt`.

---

## 🗄️ Migracja Bazy Danych

### 1. Utwórz bazę danych
```sql
CREATE DATABASE ostrans;
CREATE USER ostrans_user WITH PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE ostrans TO ostrans_user;
```

### 2. Załaduj schemat
```bash
psql -U ostrans_user -d ostrans -f db/ostrans.sql
```

### 3. Uruchom migracje (dodatkowe kolumny)
```bash
psql -U ostrans_user -d ostrans -f db/migration_full_features.sql
```

### 4. Załaduj dane testowe (opcjonalne)
```bash
psql -U ostrans_user -d ostrans -f db/insert.sql
```

### 5. Weryfikacja
```sql
-- Sprawdź czy wszystkie tabele istnieją
\dt

-- Sprawdź domyślne uprawnienia
SELECT * FROM uprawnienia;

-- Sprawdź domyślne stanowiska
SELECT * FROM stanowiska;
```

---

## 🚀 Pierwsze Uruchomienie

### 1. Utwórz pierwszego użytkownika (Zarząd)
```sql
-- Wstaw poziomy uprawnień (jeśli nie istnieją)
INSERT INTO uprawnienia (poziom, opis) VALUES 
('kierowca', 'Kierowca - podstawowy dostęp'),
('dyspozytor', 'Dyspozytor - zarządzanie brygadami i grafikami'),
('zarzad', 'Zarząd - pełen dostęp do systemu')
ON CONFLICT (poziom) DO NOTHING;

-- Utwórz użytkownika admin (hasło: admin123)
INSERT INTO pracownicy (imie, nazwisko, login, haslo, uprawnienie_id, is_active)
VALUES (
    'Administrator',
    'System',
    'admin',
    '$2y$10$YourBcryptHashHere', -- Wygeneruj BCRYPT hash
    (SELECT id FROM uprawnienia WHERE poziom = 'zarzad'),
    true
);
```

**Wygeneruj BCRYPT hash hasła:**
```bash
php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
```

### 2. Zaloguj się
Otwórz przeglądarkę i przejdź do:
```
http://ostrans.local/panel/
```

Dane logowania:
- **Login:** admin
- **Hasło:** admin123

### 3. Zmień hasło administratora
Po pierwszym logowaniu zmień hasło:
1. Przejdź do **Profil** > **Zmień hasło**
2. Wpisz nowe, bezpieczne hasło
3. Zapisz

---

## 📁 Struktura Projektu

```
ostrans/
├── panel/                      # Panel administracyjny
│   ├── index.php              # Front controller (sesje PHP)
│   ├── api.php                # API REST (JWT auth)
│   ├── env.txt                # Konfiguracja (NIE commituj!)
│   ├── app/
│   │   ├── controllers/
│   │   │   ├── HomeController.php     # Login/logout
│   │   │   ├── PanelController.php    # Panel views
│   │   │   ├── ApiController.php      # Business logic
│   │   │   └── LinesController.php    # Lines API
│   │   ├── core/
│   │   │   └── Database.php           # PDO wrapper
│   │   ├── helpers/
│   │   │   ├── AuthHelper.php         # RBAC
│   │   │   ├── LogHelper.php          # Activity logging
│   │   │   ├── EmailHelper.php        # Email sending
│   │   │   └── ExportHelper.php       # CSV/PDF export
│   │   └── views/
│   │       ├── login.php              # Login form
│   │       ├── dashboard.php          # Dashboard
│   │       ├── employees.php          # Employee management
│   │       ├── pojazdy.php            # Vehicle management
│   │       ├── lines_management.php   # Lines & brigades
│   │       ├── grafik.php             # Schedules
│   │       ├── wnioski.php            # Requests
│   │       ├── raporty.php            # Reports
│   │       └── zgloszenia.php         # Incidents
│   └── uploads/                # Uploaded files
├── auth/
│   ├── discord.php            # Discord OAuth2 redirect
│   └── discord_callback.php   # Discord OAuth2 callback
├── db/
│   ├── ostrans.sql            # Main schema
│   ├── migration_full_features.sql  # Feature migrations
│   └── insert.sql             # Sample data
├── linie/
│   └── index.php              # Public lines view
├── index.php                  # Public homepage
├── index.html                 # Public HTML
├── readme.md                  # Requirements specification
├── IMPLEMENTATION_STATUS.md   # Implementation status
└── DEPLOYMENT_GUIDE.md        # This file
```

---

## 📚 API Documentation

### Base URL
```
https://your-domain.pl/panel/api.php
```

### Authentication
Wszystkie endpointy (poza `/login`) wymagają nagłówka:
```
Authorization: Bearer <JWT_TOKEN>
```

### Endpoints Summary

#### Authentication
- `POST /api/login` - Login
- `GET /api/me` - Current user info
- `POST /api/password-reset/request` - Request reset
- `POST /api/password-reset/confirm` - Confirm reset
- `POST /api/password/change` - Change password

#### Employees (Zarząd only)
- `GET /api/pracownicy` - List active employees
- `GET /api/admin/pracownicy` - All employees
- `POST /api/admin/pracownik` - Add/edit employee
- `POST /api/admin/employee/{id}/deactivate` - Deactivate
- `GET /api/activity-log` - Activity history

#### Vehicles
- `GET /api/pojazdy` - List vehicles
- `POST /api/admin/pojazd` - Add vehicle
- `PUT /api/admin/pojazd/{id}` - Edit vehicle
- `DELETE /api/admin/pojazd/{id}` - Delete vehicle
- `GET /api/pojazd/{id}/usage` - Usage history
- `POST /api/admin/pracownik/{id}/pojazd-staly` - Assign permanent

#### Lines & Brigades (Dyspozytor+)
- `GET /api/linie` - List lines
- `POST /api/admin/linia` - Add line
- `PUT /api/admin/linia/{id}` - Edit line
- `DELETE /api/admin/linia/{id}` - Delete line
- `GET /api/brygady` - List brigades
- `POST /api/admin/brygada` - Add brigade
- `PUT /api/admin/brygada/{id}` - Edit brigade
- `DELETE /api/admin/brygada/{id}` - Delete brigade

#### Schedules (Dyspozytor+)
- `GET /api/grafik` - List schedules
- `POST /api/admin/grafik` - Add schedule
- `PUT /api/admin/grafik/{id}` - Edit schedule
- `DELETE /api/admin/grafik/{id}` - Delete schedule

#### Requests
- `GET /api/wnioski` - List requests
- `POST /api/wnioski` - Submit request
- `PUT /api/wnioski/{id}/status` - Update status

#### Export (Dyspozytor+)
- `GET /api/export/grafiki?format=csv&start_date=2026-01-01&end_date=2026-01-31`
- `GET /api/export/pojazdy?format=pdf`
- `GET /api/export/brygady?format=csv`

### Example: Login
```bash
curl -X POST https://your-domain.pl/panel/api.php/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123"}'

# Response:
{
  "token": "eyJ0eXAiOiJKV1Q...",
  "user": {
    "id": 1,
    "imie": "Administrator",
    "nazwisko": "System",
    "login": "admin",
    "uprawnienie": "zarzad"
  }
}
```

### Example: List employees (authenticated)
```bash
curl -X GET https://your-domain.pl/panel/api.php/pracownicy \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1Q..."

# Response:
[
  {
    "id": 1,
    "imie": "Jan",
    "nazwisko": "Kowalski",
    "login": "jkowalski",
    "uprawnienie": "kierowca",
    "is_active": true
  },
  ...
]
```

---

## 👥 Zarządzanie Użytkownikami

### Role i uprawnienia

| Rola | Uprawnienia |
|------|------------|
| **kierowca** | - Własny grafik<br>- Składanie wniosków<br>- Podgląd zgłoszeń<br>- Wysyłanie raportów<br>- Zmiana hasła |
| **dyspozytor** | - Wszystkie uprawnienia kierowcy<br>- Tworzenie i edycja grafików<br>- Przydzielanie kierowców<br>- Zarządzanie liniami i brygadami<br>- Zarządzanie pojazdami<br>- Podgląd i akceptacja wniosków<br>- Raporty |
| **zarzad** | - Wszystkie uprawnienia dyspozytora<br>- Dodawanie/edycja/deaktywacja pracowników<br>- Podgląd logów aktywności<br>- Zarządzanie ustawieniami |

### Dodawanie nowego pracownika

#### Przez panel (Zarząd)
1. Zaloguj się jako zarząd
2. Przejdź do **Panel** > **Pracownicy**
3. Kliknij **+ Dodaj Pracownika**
4. Wypełnij formularz:
   - Imię
   - Nazwisko
   - Login (unikalny)
   - Hasło (min. 6 znaków)
   - Uprawnienie (kierowca/dyspozytor/zarząd)
   - Stanowisko (opcjonalne)
   - Discord ID (opcjonalne, dla OAuth2)
5. Kliknij **Zapisz**

#### Przez SQL
```sql
INSERT INTO pracownicy (imie, nazwisko, login, haslo, uprawnienie_id, is_active)
VALUES (
    'Jan',
    'Kowalski',
    'jkowalski',
    '$2y$10$...',  -- BCRYPT hash
    (SELECT id FROM uprawnienia WHERE poziom = 'kierowca'),
    true
);
```

### Dezaktywacja pracownika
Zamiast usuwania, pracowników dezaktywuje się (soft-delete):
```sql
UPDATE pracownicy SET is_active = false WHERE id = 123;
```
Lub przez API:
```bash
curl -X POST https://your-domain.pl/panel/api.php/admin/employee/123/deactivate \
  -H "Authorization: Bearer <TOKEN>"
```

---

## 🔍 Rozwiązywanie Problemów

### Problem: Nie mogę się zalogować
**Rozwiązanie:**
1. Sprawdź czy baza danych jest dostępna
2. Sprawdź czy hasło jest poprawne
3. Sprawdź logi:
```bash
tail -f /var/log/apache2/ostrans-error.log
# lub
tail -f /var/log/nginx/error.log
```

### Problem: 500 Internal Server Error
**Rozwiązanie:**
1. Włącz wyświetlanie błędów PHP:
```php
// W panel/index.php na górze:
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
2. Sprawdź uprawnienia plików (775 dla katalogów, 664 dla plików)
3. Sprawdź logi web servera

### Problem: API zwraca "no auth"
**Rozwiązanie:**
1. Sprawdź czy wysyłasz nagłówek `Authorization: Bearer <TOKEN>`
2. Sprawdź czy token nie wygasł (TTL: 8h)
3. Sprawdź `JWT_SECRET` w `env.txt`

### Problem: Discord OAuth2 nie działa
**Rozwiązanie:**
1. Sprawdź konfigurację w `env.txt`:
   - `DISCORD_CLIENT_ID`
   - `DISCORD_CLIENT_SECRET`
   - `DISCORD_REDIRECT_URI` (musi być dokładnie taki sam jak w Discord Developer Portal)
2. Sprawdź czy redirect URI jest dodany w Discord Application > OAuth2 > Redirects
3. Sprawdź logi w `activity_log`

### Problem: Eksport PDF nie działa
**Rozwiązanie:**
1. Zainstaluj DomPDF (zalecane):
```bash
composer require dompdf/dompdf
```
2. Jeśli nie masz Composera, eksport zwróci HTML (fallback)

### Problem: Upload plików nie działa (zgłoszenia)
**Rozwiązanie:**
1. Sprawdź uprawnienia katalogu:
```bash
chmod 775 panel/uploads
chown www-data:www-data panel/uploads
```
2. Sprawdź limity PHP:
```ini
upload_max_filesize = 10M
post_max_size = 12M
```

---

## 📊 Monitoring i Maintenance

### Kopie zapasowe bazy danych
**Automatyczne (cron):**
```bash
# /etc/cron.daily/ostrans-backup
#!/bin/bash
DATE=$(date +%Y-%m-%d_%H-%M-%S)
pg_dump -U ostrans_user ostrans | gzip > /backups/ostrans_$DATE.sql.gz
find /backups -name "ostrans_*.sql.gz" -mtime +30 -delete
```

**Ręczne:**
```bash
pg_dump -U ostrans_user ostrans > ostrans_backup.sql
```

### Czyszczenie starych logów
```sql
-- Usuń logi starsze niż 90 dni
DELETE FROM activity_log WHERE created_at < NOW() - INTERVAL '90 days';

-- Usuń wygasłe tokeny resetu hasła
DELETE FROM password_resets WHERE expires_at < NOW();
```

### Monitoring wydajności
```sql
-- Najdłuższe zapytania
SELECT query, mean_exec_time, calls 
FROM pg_stat_statements 
ORDER BY mean_exec_time DESC 
LIMIT 10;

-- Rozmiar tabel
SELECT 
    schemaname, 
    tablename, 
    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables 
WHERE schemaname = 'public' 
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;
```

---

## 🚢 Deployment na Produkcję

### 1. Wyłącz debug mode
```php
// W panel/index.php usuń:
ini_set('display_errors', 0);
error_reporting(0);
```

### 2. Włącz HTTPS
```apache
<VirtualHost *:443>
    ServerName ostrans.pl
    DocumentRoot /var/www/ostrans
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    SSLCertificateChainFile /path/to/chain.pem
    
    # ... reszta konfiguracji
</VirtualHost>
```

### 3. Ustaw silne hasła
- Zmień wszystkie domyślne hasła
- Ustaw silny `JWT_SECRET`
- Włącz 2FA (opcjonalnie)

### 4. Konfiguracja firewall
```bash
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 22/tcp
ufw enable
```

### 5. Rate limiting (Nginx)
```nginx
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;

location /panel/api.php {
    limit_req zone=api burst=20;
    # ... reszta konfiguracji
}
```

---

## 📞 Wsparcie

**Dokumentacja:** [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md)
**Issues:** https://github.com/your-repo/ostrans/issues
**Email:** support@ostrans.pl

---

## ✅ Status Implementacji

**Wersja:** 1.0.0
**Data:** 2026-01-08
**Status:** ✅ PRODUKCYJNY - Wszystkie wymagania F1-F29 zaimplementowane

Zobacz [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md) dla szczegółów.
