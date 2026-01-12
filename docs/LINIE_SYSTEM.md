# System Stron Linii - Dokumentacja

## Przegląd

System stron linii PPUT Ostrans zapewnia szczegółowe informacje o każdej linii transportu publicznego, wzorowany na profesjonalnym designie GZM (Górnośląsko-Zagłębiowska Metropolia).

## Struktura

### Pliki

- **`/linie/index.php`** - Dynamiczny routing dla stron linii (format: `/linie/XXX-YY.php`)
- **`/linie/styles.css`** - Dedykowany CSS z profesjonalnym designem
- **`/linie/404.php`** - Strona błędu dla nieznalezionych linii
- **`/linie.php`** - Lista wszystkich linii (widok główny)

### Endpointy API

#### `/api/public/lines` (GET)
Zwraca listę wszystkich linii z cache SIL API (5 min).

**Odpowiedź:**
```json
{
  "lines": [
    {
      "line": "1",
      "variant": "01",
      "from": "Górka Narodowa",
      "to": "Ostrołęka Lotnisko",
      "type": "tram",
      "route": "..."
    }
  ]
}
```

#### `/api/public/lines/{line}/{variant}/stops` (GET)
Zwraca listę przystanków dla konkretnego wariantu linii.

**Parametry:**
- `{line}` - numer linii (np. "1", "A", "107")
- `{variant}` - ID wariantu (np. "01", "02")

**Odpowiedź:**
```json
{
  "stops": [
    "Górka Narodowa",
    "Al. Jana Pawła II",
    "Dworzec Główny",
    ...
  ]
}
```

## Format URL

### Strony linii
```
/linie/{line}-{variant}.php
```

**Przykłady:**
- `/linie/1-01.php` - Tramwaj 1, wariant 01
- `/linie/A-02.php` - Trolejbus A, wariant 02
- `/linie/107-01.php` - Autobus 107, wariant 01

### Automatyczne przekierowania
- Jeśli wariant nie zostanie podany, system użyje pierwszego dostępnego wariantu
- Jeśli linia nie istnieje, wyświetlana jest strona 404

## Funkcje

### 1. Widok szczegółów linii
- **Numer linii** - wyświetlany w kolorowej odznace według typu (tramwaj/trolejbus/autobus)
- **Trasa** - kierunek od przystanku początkowego do końcowego
- **Typ linii** - tramwaj, trolejbus, autobus, metro

### 2. Przełączanie wariantów
- Lista wszystkich dostępnych wariantów dla danej linii
- Aktywny wariant jest podświetlony
- Kliknięcie zmienia widok na wybrany wariant

### 3. Lista przystanków
- Chronologiczna lista wszystkich przystanków
- Wizualna linia czasu (timeline) łącząca przystanki
- Kolorowe markery dla pierwszego i ostatniego przystanku
- Numeracja przystanków

### 4. Karty informacyjne
- Liczba przystanków
- Pierwszy przystanek
- Ostatni przystanek

### 5. Tryb ciemny/jasny
- Przełącznik w nagłówku
- Automatyczne wykrywanie preferencji systemowych
- Zapisywanie wyboru w localStorage

### 6. Responsywny design
- Optymalizacja dla urządzeń mobilnych
- Elastyczny layout dostosowujący się do rozmiaru ekranu
- Touch-friendly elementy interfejsu

## Design System

### Kolory według typu linii

| Typ | Kolor | CSS Variable |
|-----|-------|--------------|
| Tramwaj | #d32f2f (czerwony) | `--color-tram` |
| Trolejbus | #1976d2 (niebieski) | `--color-trol` |
| Autobus | #388e3c (zielony) | `--color-bus` |
| Metro | #7b1fa2 (fioletowy) | `--color-metro` |

### Typografia

- **Primary Font**: Quicksand (tekst główny)
- **Heading Font**: Oswald (nagłówki)
- **Mono Font**: Doto (numery, warianty)

### Spacing

- `--spacing-xs`: 4px
- `--spacing-sm`: 8px
- `--spacing-md`: 16px
- `--spacing-lg`: 24px
- `--spacing-xl`: 32px

### Border Radius

- `--radius-sm`: 6px
- `--radius-md`: 10px
- `--radius-lg`: 16px

## Integracja z SIL API

System używa API sil.kanbeq.me/ostrans jako źródła danych:

### Cache
- Dane linii są cache'owane na 5 minut w `sys_get_temp_dir()`
- Przystanki są cache'owane osobno dla każdego wariantu
- Zmniejsza obciążenie zewnętrznego API

### Obsługa błędów
- Jeśli API jest niedostępne, wyświetlany jest komunikat
- Graceful fallback dla brakujących danych
- Strona 404 dla nieznalezionych linii

## Dostępność (Accessibility)

### ARIA
- `aria-label` dla logo i przycisków
- `aria-pressed` dla przełącznika motywu
- `aria-current="page"` dla aktywnego wariantu
- `role="img"` dla dekoracyjnych elementów

### Nawigacja klawiaturą
- Focus styles dla wszystkich interaktywnych elementów
- Tab order zgodny z wizualną kolejnością
- Skip links w breadcrumb

### Semantyczny HTML
- Odpowiednie nagłówki (h1, h2)
- Strukturalne znaczniki (header, main, footer, nav, section)
- Lista dla przystanków (`<ul>` z `role="list"`)

### Kontrast
- Wszystkie kolory spełniają WCAG AA
- High contrast mode support
- Prefers-reduced-motion support

## SEO

### Meta tags
- `<title>` dynamiczny z nazwą linii i trasą
- `<meta name="description">` z szczegółami linii
- Semantyczne URL (`/linie/XXX-YY.php`)

### Structured data
- Breadcrumb navigation
- Hierarchia nagłówków (H1 → H2)

## Performance

### Optymalizacje
- Preconnect do Google Fonts
- CSS inline dla krytycznych stylów
- Lazy loading obrazów (gdzie stosowane)
- Minimalna liczba zapytań HTTP

### Caching
- 5-minutowy cache dla danych API
- localStorage dla preferencji motywu
- Statyczne CSS z długim cache

## Wsparcie przeglądarek

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Opera 76+
- Mobile browsers (iOS Safari, Chrome Android)

## Przykłady użycia

### Dodanie nowej linii
Linie są automatycznie pobierane z SIL API. Nie wymaga zmian w kodzie.

### Niestandardowe trasy
Jeśli linia ma specjalne właściwości (np. okrężna, sezonowa), można je dodać w `route` lub `description`.

### Integracja z mapą
Przygotowane do przyszłej integracji z OpenStreetMap lub Google Maps dla wizualizacji trasy.

## Troubleshooting

### Problem: Linia się nie wyświetla
**Rozwiązanie:** 
1. Sprawdź czy linia istnieje w SIL API
2. Wyczyść cache (`sys_get_temp_dir()/ostrans_*.json`)
3. Sprawdź logi serwera

### Problem: Brak przystanków
**Rozwiązanie:**
1. Sprawdź endpoint `/api/public/lines/{line}/{variant}/stops`
2. Zweryfikuj format odpowiedzi SIL API
3. Sprawdź case sensitivity (1 vs "1")

### Problem: 404 dla istniejącej linii
**Rozwiązanie:**
1. Sprawdź routing w `.htaccess` lub `web.config`
2. Zweryfikuj format URL (musi być `/linie/XXX-YY.php`)
3. Upewnij się, że `index.php` obsługuje dynamiczny routing

## Przyszłe rozszerzenia

- 🗺️ Mapa trasy (OpenStreetMap)
- 📅 Harmonogram kursów (rozkład jazdy)
- ⏱️ Odjazdy w czasie rzeczywistym
- 📊 Statystyki popularności linii
- 🚏 Szczegóły przystanków (zdjęcia, udogodnienia)
- 🔔 Powiadomienia o zmianach tras
- 📱 Progressive Web App (PWA)

## Kontakt i wsparcie

W razie problemów lub pytań, skontaktuj się z zespołem technicznym PPUT Ostrans.

---

**Ostatnia aktualizacja:** <?= date('Y-m-d') ?>  
**Wersja:** 1.0.0  
**Autor:** GitHub Copilot dla PPUT Ostrans
