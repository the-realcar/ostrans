# 🚀 PPUT Ostrans - Quick Start Guide

## Szybki start w 5 krokach

### 1️⃣ Konfiguracja .env

```bash
# Skopiuj przykładowy plik
cp .env.example .env

# Edytuj wartości
nano .env  # lub notepad .env na Windows
```

**Minimalna konfiguracja wymagana:**
```env
DATABASE_URL=postgresql://user:password@localhost:5432/ostrans
JWT_SECRET=twoj_silny_losowy_sekret_minimum_32_znaki
ALLOW_ORIGIN=https://twoja-domena.pl
DISCORD_CLIENT_ID=twoj_discord_client_id
DISCORD_CLIENT_SECRET=twoj_discord_secret
DISCORD_REDIRECT_URI=https://twoja-domena.pl/auth/discord/callback
```

📖 **Szczegóły:** Zobacz [docs/ENV_CONFIGURATION.md](docs/ENV_CONFIGURATION.md)

---

### 2️⃣ Konfiguracja bazy danych

```bash
# Utwórz bazę danych
createdb ostrans

# Zaimportuj schemat
psql ostrans < DATABASE_SCHEMA.sql

# Opcjonalnie: dane testowe
psql ostrans < db/insert.sql
```

---

### 3️⃣ Konfiguracja Discord OAuth2

1. Przejdź do: https://discord.com/developers/applications
2. Utwórz nową aplikację
3. W zakładce OAuth2:
   - Skopiuj Client ID → `DISCORD_CLIENT_ID`
   - Skopiuj Client Secret → `DISCORD_CLIENT_SECRET`
   - Dodaj redirect: `https://twoja-domena.pl/auth/discord/callback`

📖 **Szczegóły:** Zobacz [docs/ENV_CONFIGURATION.md#discord-oauth2](docs/ENV_CONFIGURATION.md#discord-oauth2)

---

### 4️⃣ Konfiguracja serwera web

**Apache (.htaccess już skonfigurowany):**
```apache
<VirtualHost *:80>
    ServerName ostrans.twoja-domena.pl
    DocumentRoot /var/www/ostrans
    
    <Directory /var/www/ostrans>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name ostrans.twoja-domena.pl;
    root /var/www/ostrans;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

---

### 5️⃣ Konfiguracja automatycznych backupów

**Windows (PowerShell jako Administrator):**
```powershell
cd scripts
.\setup-backup-task.ps1
```

**Linux (cron):**
```bash
# Edytuj crontab
crontab -e

# Dodaj linię (backup o 2:00 AM)
0 2 * * * /path/to/ostrans/scripts/backup-database.sh
```

📖 **Szczegóły:** Zobacz [IMPROVEMENTS.md#automatyczne-backupy](IMPROVEMENTS.md#automatyczne-backupy)

---

## 🎯 Weryfikacja instalacji

### Test 1: Połączenie z bazą
```bash
psql $DATABASE_URL
```

### Test 2: Aplikacja działa
```bash
# Otwórz w przeglądarce
https://twoja-domena.pl/ostrans/
```

### Test 3: Discord OAuth
```bash
# Otwórz w przeglądarce
https://twoja-domena.pl/ostrans/auth/discord.php
```
Powinno przekierować do Discord i po autoryzacji wrócić z tokenem.

### Test 4: API
```bash
curl https://twoja-domena.pl/ostrans/panel/api.php/api/login \
  -H "Content-Type: application/json" \
  -d '{"login":"test","password":"test"}'
```

---

## 📚 Dokumentacja

- **[readme.md](readme.md)** - wymagania i specyfikacja
- **[VERIFICATION_REPORT.md](VERIFICATION_REPORT.md)** - raport zgodności z wymaganiami
- **[IMPLEMENTATION_REPORT.md](IMPLEMENTATION_REPORT.md)** - raport wdrożonych ulepszeń
- **[ENV_COMPLIANCE_REPORT.md](ENV_COMPLIANCE_REPORT.md)** - zgodność z .env
- **[IMPROVEMENTS.md](IMPROVEMENTS.md)** - changelog i instrukcje nowych funkcji
- **[docs/ENV_CONFIGURATION.md](docs/ENV_CONFIGURATION.md)** - przewodnik konfiguracji .env
- **[docs/PGBOUNCER_SETUP.md](docs/PGBOUNCER_SETUP.md)** - connection pooling

---

## 🔧 Narzędzia dodatkowe

### Load Testing
```powershell
.\scripts\load-test.ps1 -Users 80 -Duration 60
```

### Manual Backup
```powershell
.\scripts\backup-database.ps1
```

### Import CSV pracowników
```
https://twoja-domena.pl/ostrans/panel/index.php?route=import-pracownicy
```
(Wymagane: zalogowany jako zarząd)

---

## 🆘 Troubleshooting

### Problem: "DATABASE_URL not configured"
**Rozwiązanie:** Sprawdź czy plik `.env` istnieje i ma poprawną wartość `DATABASE_URL`

### Problem: "DISCORD_CLIENT_ID not configured"
**Rozwiązanie:** Dodaj credentials Discord w `.env`

### Problem: CORS errors
**Rozwiązanie:** Ustaw poprawny `ALLOW_ORIGIN` w `.env`

### Problem: 500 Internal Server Error
**Rozwiązanie:** Sprawdź logi PHP i uprawnienia do plików

---

## 📞 Wsparcie

- **Issues:** GitHub Issues (jeśli projekt jest na GitHub)
- **Email:** kontakt@twoja-domena.pl
- **Dokumentacja:** Zobacz pliki w katalogu `docs/`

---

## ✅ Checklist wdrożenia produkcyjnego

- [ ] `.env` skonfigurowany z bezpiecznymi wartościami
- [ ] `JWT_SECRET` ustawiony na silną losową wartość
- [ ] Baza danych utworzona i schemat zaimportowany
- [ ] Discord OAuth2 skonfigurowany
- [ ] HTTPS certyfikat zainstalowany
- [ ] `FORCE_HTTPS=true` w `.env`
- [ ] Automatyczne backupy skonfigurowane
- [ ] Load testing wykonany
- [ ] Uprawnienia plików ustawione (644/755)
- [ ] `.env` dodany do `.gitignore`
- [ ] Monitoring i logi skonfigurowane

---

**Wersja:** 1.1.0  
**Status:** Production Ready ✅  
**Data:** 2026-01-12
