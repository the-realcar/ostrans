# PPUT Ostrans - Przewodnik konfiguracji .env

## 📋 Spis treści
1. [Podstawowa konfiguracja](#podstawowa-konfiguracja)
2. [Zmienne środowiskowe](#zmienne-środowiskowe)
3. [Discord OAuth2](#discord-oauth2)
4. [Bezpieczeństwo](#bezpieczeństwo)
5. [Troubleshooting](#troubleshooting)

---

## Podstawowa konfiguracja

### 1. Utwórz plik .env

```bash
# Skopiuj przykładowy plik
cp .env.example .env
```

### 2. Edytuj wartości

Otwórz plik `.env` i uzupełnij wszystkie wymagane wartości.

---

## Zmienne środowiskowe

### ✅ WYMAGANE

#### DATABASE_URL
Format połączenia z bazą PostgreSQL:
```env
DATABASE_URL=postgresql://username:password@hostname:port/database
```

**Przykłady:**
```env
# Lokalna baza
DATABASE_URL=postgresql://postgres:admin@localhost:5432/ostrans

# Zdalna baza
DATABASE_URL=postgresql://dbuser:securepass@db.example.com:5432/ostrans

# PgBouncer (connection pooling)
DATABASE_URL=postgresql://ostrans_user:password@localhost:6432/ostrans
```

#### JWT_SECRET
Tajny klucz do podpisywania tokenów JWT. **MUSI być silny i losowy w produkcji!**

**Generowanie bezpiecznego klucza:**
```bash
# Linux/Mac/WSL
openssl rand -base64 32

# PowerShell
$bytes = New-Object byte[] 32
[Security.Cryptography.RNGCryptoServiceProvider]::Create().GetBytes($bytes)
[Convert]::ToBase64String($bytes)
```

**Przykład:**
```env
JWT_SECRET=xK3mP9vQ7wR2nF8tL5gH4jB6cD1aE0sZ
```

⚠️ **NIGDY nie commituj prawdziwego JWT_SECRET do repozytorium!**

#### ALLOW_ORIGIN
Domena frontendu dla CORS:
```env
# Produkcja
ALLOW_ORIGIN=https://ostrans.famisska.pl

# Development (localhost)
ALLOW_ORIGIN=http://localhost:3000

# Zezwól wszystkim (tylko development!)
ALLOW_ORIGIN=*
```

#### MAIN_SITE
Główny URL aplikacji:
```env
MAIN_SITE=https://ostrans.famisska.pl
```

### 🔑 Discord OAuth2 (WYMAGANE dla logowania przez Discord)

#### Uzyskanie credentials z Discord:

1. Przejdź do: https://discord.com/developers/applications
2. Kliknij "New Application"
3. Wpisz nazwę: "PPUT Ostrans"
4. Przejdź do zakładki "OAuth2"

#### DISCORD_CLIENT_ID
Skopiuj "CLIENT ID" z panelu Discord Developer:
```env
DISCORD_CLIENT_ID=1448272608108875988
```

#### DISCORD_CLIENT_SECRET
Skopiuj "CLIENT SECRET" (kliknij "Reset Secret" jeśli potrzeba):
```env
DISCORD_CLIENT_SECRET=VZH_wK--pWDHBG_OIPdcAbNnAQwQxN0E
```

⚠️ **NIGDY nie udostępniaj CLIENT SECRET publicznie!**

#### DISCORD_REDIRECT_URI
URL callback'u OAuth2 - MUSI być dodany w "Redirects" w panelu Discord:
```env
DISCORD_REDIRECT_URI=https://ostrans.famisska.pl/auth/discord/callback
```

**Dodanie redirect URI w Discord:**
1. W panelu aplikacji Discord → OAuth2 → Redirects
2. Kliknij "Add Redirect"
3. Wpisz: `https://yourdomain.com/auth/discord/callback`
4. Kliknij "Save Changes"

### 🎭 Discord Role Mapping (OPCJONALNE)

Mapowanie ról Discord na uprawnienia w systemie.

#### DISCORD_GUILD_ID
ID serwera Discord (prawy klik na serwerze → Copy Server ID):
```env
DISCORD_GUILD_ID=1031155622013567086
```

**Włączenie Developer Mode w Discord:**
1. User Settings → Advanced
2. Włącz "Developer Mode"

#### DISCORD_BOT_TOKEN
Token bota Discord (wymagany do odczytu ról użytkowników):

**Utworzenie bota:**
1. W panelu aplikacji Discord → Bot
2. Kliknij "Add Bot"
3. Skopiuj token (kliknij "Reset Token" jeśli potrzeba)
```env
DISCORD_BOT_TOKEN=MTQ0ODI3MjYwODEwODg3NTk4OA.GqBXug.g4SZXiSeusKs_nJM016vHHDgNbv1mgRQSVUKWQ
```

**Dodanie bota na serwer:**
1. OAuth2 → URL Generator
2. Zaznacz scope: `bot`
3. Zaznacz permissions: `Read Messages/View Channels`, `View Server Members`
4. Skopiuj wygenerowany URL i otwórz w przeglądarce
5. Wybierz serwer i zatwierdź

#### Role IDs
ID ról Discord dla mapowania uprawnień:

```env
# Zarząd (najwyższe uprawnienia)
ROLE_ZARZAD_ID=1448280738494550076

# Dyspozytor (średnie uprawnienia)
ROLE_DYSP_ID=1448280770048163893

# Kierowca (podstawowe uprawnienia)
ROLE_KIEROWCA_ID=1448280800738152529
```

**Uzyskanie Role ID:**
1. Server Settings → Roles
2. Prawy klik na rolę → Copy Role ID

### ⚙️ OPCJONALNE

#### PORT
Port dla serwera Node.js (jeśli używasz `server.js`):
```env
PORT=3000
```

**Uwaga:** Przy używaniu PHP nie jest wymagane.

#### FORCE_HTTPS
Wymuszenie przekierowania HTTP → HTTPS:
```env
# Produkcja
FORCE_HTTPS=true

# Development (localhost)
FORCE_HTTPS=false
```

---

## Bezpieczeństwo

### ⚠️ NIGDY nie commituj .env do repozytorium!

Dodaj do `.gitignore`:
```
.env
.env.local
.env.*.local
```

### ✅ Dobre praktyki:

1. **Różne .env dla różnych środowisk:**
   - `.env.local` - development
   - `.env.production` - produkcja
   - `.env.test` - testy

2. **Silne hasła:**
   - JWT_SECRET min. 32 znaki
   - DB_PASSWORD min. 16 znaków
   - Używaj generatorów haseł

3. **Rotacja secrets:**
   - Regularnie zmieniaj JWT_SECRET
   - Zmieniaj DISCORD_CLIENT_SECRET po wykryciu wycieku

4. **Backup .env:**
   - Przechowuj kopię w bezpiecznym miejscu (np. password manager)
   - Nie wysyłaj przez email/Slack

5. **Uprawnienia plików:**
   ```bash
   # Linux/Mac
   chmod 600 .env
   ```

---

## Przykładowe konfiguracje

### Development (localhost)

```env
DATABASE_URL=postgresql://postgres:admin@localhost:5432/ostrans
JWT_SECRET=dev_secret_change_in_production
ALLOW_ORIGIN=http://localhost:3000
MAIN_SITE=http://localhost
FORCE_HTTPS=false
DISCORD_CLIENT_ID=your_dev_client_id
DISCORD_CLIENT_SECRET=your_dev_client_secret
DISCORD_REDIRECT_URI=http://localhost/auth/discord/callback
```

### Production

```env
DATABASE_URL=postgresql://ostrans_user:VerySecurePassword123!@localhost:6432/ostrans
JWT_SECRET=xK3mP9vQ7wR2nF8tL5gH4jB6cD1aE0sZpM8nL7kJ6hG5f
ALLOW_ORIGIN=https://ostrans.famisska.pl
MAIN_SITE=https://ostrans.famisska.pl
FORCE_HTTPS=true
DISCORD_CLIENT_ID=1448272608108875988
DISCORD_CLIENT_SECRET=VZH_wK--pWDHBG_OIPdcAbNnAQwQxN0E
DISCORD_REDIRECT_URI=https://ostrans.famisska.pl/auth/discord/callback
DISCORD_GUILD_ID=1031155622013567086
DISCORD_BOT_TOKEN=MTQ0ODI3MjYwODEwODg3NTk4OA.GqBXug.g4SZXiSeusKs_nJM016vHHDgNbv1mgRQSVUKWQ
ROLE_ZARZAD_ID=1448280738494550076
ROLE_DYSP_ID=1448280770048163893
ROLE_KIEROWCA_ID=1448280800738152529
```

---

## Troubleshooting

### Problem: "DATABASE_URL not configured"
**Rozwiązanie:**
- Sprawdź czy plik `.env` istnieje w katalogu głównym projektu
- Upewnij się że `DATABASE_URL` jest poprawnie ustawione
- Sprawdź uprawnienia do pliku `.env`

### Problem: "DISCORD_CLIENT_ID not configured"
**Rozwiązanie:**
- Sprawdź czy Discord credentials są ustawione w `.env`
- Zweryfikuj czy wartości są bez cudzysłowów
- Sprawdź czy aplikacja Discord jest aktywna

### Problem: "Token exchange failed"
**Rozwiązanie:**
- Sprawdź czy `DISCORD_REDIRECT_URI` w `.env` pasuje do tego w panelu Discord
- Upewnij się że `DISCORD_CLIENT_SECRET` jest poprawny
- Sprawdź czy aplikacja Discord nie jest zablokowana

### Problem: "Connection refused" do bazy
**Rozwiązanie:**
- Sprawdź czy PostgreSQL działa: `pg_isready`
- Zweryfikuj host i port w `DATABASE_URL`
- Sprawdź czy użytkownik ma dostęp do bazy
- Jeśli używasz PgBouncer, sprawdź czy działa na porcie 6432

### Problem: Role mapping nie działa
**Rozwiązanie:**
- Upewnij się że `DISCORD_BOT_TOKEN` jest poprawny
- Sprawdź czy bot jest na serwerze i ma uprawnienia
- Zweryfikuj `DISCORD_GUILD_ID`
- Sprawdź czy `ROLE_*_ID` są poprawne

### Problem: CORS errors
**Rozwiązanie:**
- Ustaw poprawną wartość `ALLOW_ORIGIN`
- W development możesz użyć `*` (niezalecane w produkcji)
- Sprawdź czy domena frontendu jest poprawna

---

## Weryfikacja konfiguracji

### Test połączenia z bazą:

```bash
psql postgresql://username:password@host:port/database
```

### Test Discord OAuth2:

1. Otwórz: `https://yourdomain.com/auth/discord.php`
2. Powinno przekierować do Discord OAuth
3. Po autoryzacji powinno wrócić z tokenem

### Test API:

```bash
curl -X POST http://localhost/ostrans/panel/api.php/api/login \
  -H "Content-Type: application/json" \
  -d '{"login":"test","password":"test"}'
```

---

## Wsparcie

- **Dokumentacja:** README.md, VERIFICATION_REPORT.md
- **Discord API:** https://discord.com/developers/docs
- **PostgreSQL:** https://www.postgresql.org/docs/

---

**Ostatnia aktualizacja:** 2026-01-12  
**Wersja:** 1.0
