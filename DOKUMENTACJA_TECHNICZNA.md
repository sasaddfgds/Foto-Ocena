# Dokumentacja techniczna projektu Foto-Ocena

## Opis ogólny

Foto-Ocena - aplikacja internetowa do przesyłania, przeglądania i oceniania zdjęć przez użytkowników. Aplikacja pozwala użytkownikom na rejestrację, przesyłanie obrazów, głosowanie na nie (like/dislike) oraz przeglądanie profili innych użytkowników.

## Stos technologiczny

### Backend
- **PHP 7.4+** - główny język części serwerowej
- **Composer** - menedżer zależności
- **SQLite** - system zarządzania bazą danych (zamiast planowanej MariaDB)
- **PDO** - do pracy z bazą danych

### Frontend
- **Vanilla JavaScript ES6** - bez frameworków
- **HTML5** - znaczniki semantyczne z WCAG
- **CSS3** - style niestandardowe ze zmiennymi CSS i animacjami

### Zależności (composer.json)
- `vlucas/phpdotenv ^5.5` - ładowanie zmiennych środowiskowych z pliku .env
- `guzzlehttp/guzzle ^7.8` - klient HTTP (nieużywany w obecnej implementacji)

## Architektura projektu

### Struktura katalogów

```
Foto-grade-main/
├── cache/                    # Cache popularnych obrazów
├── config/
│   └── database.php          # Klasa Config do ładowania .env
├── database.db               # Baza danych SQLite (przeniesiona z public)
├── public/
│   ├── api/                  # API endpoints
│   │   ├── .htaccess         # Ochrona przed dostępem do plików .db
│   │   ├── events.php        # SSE do aktualizacji w czasie rzeczywistym
│   │   ├── images.php        # Pobieranie obrazów
│   │   ├── like.php          # Głosowanie
│   │   ├── login.php         # Autoryzacja
│   │   ├── logout.php        # Wylogowanie
│   │   ├── register.php      # Rejestracja
│   │   ├── settings.php      # Ustawienia profilu
│   │   ├── upload-avatar.php # Przesyłanie awatara
│   │   ├── upload.php        # Przesyłanie obrazów
│   │   └── user.php          # Aktualny użytkownik
│   ├── css/
│   │   └── style.css         # Style główne
│   ├── js/
│   │   ├── api-client.js     # Wspólny klient API (singleton)
│   │   ├── app.js            # Główna logika aplikacji
│   │   ├── auth.js           # Autoryzacja/rejestracja
│   │   ├── form-utils.js     # Narzędzia do formularzy
│   │   ├── settings.js       # Ustawienia
│   │   └── ui-utils.js       # Komponenty UI (Dropdown)
│   ├── uploads/              # Przesłane pliki
│   │   ├── .htaccess         # Ochrona przed wykonywaniem skryptów
│   │   ├── avatars/          # Awatary użytkowników
│   │   └── uploads/          # Obrazy
│   ├── index.php             # Strona główna
│   ├── login.php             # Strona logowania
│   ├── register.php          # Strona rejestracji
│   ├── profile.php           # Profil użytkownika
│   └── settings.php          # Strona ustawień
├── src/                      # Klasy PHP
│   ├── Auth.php              # Uwierzytelnianie
│   ├── Database.php          # Praca z bazą danych
│   ├── ImageHandler.php      # Przetwarzanie obrazów
│   ├── RateLimiter.php       # Rate limiting
│   └── Security.php          # Bezpieczeństwo
├── .env                      # Zmienne środowiskowe
├── composer.json             # Zależności PHP
├── README.md                 # Dokumentacja (po polsku)
└── projekt.md                # Plan projektu (po polsku)
```

## Architektura Backend

### Klasy w src/

#### Database.php
Klasa Singleton do pracy z SQLite przez PDO.

**Główne metody:**
- `getInstance()` - zwraca pojedynczą instancję
- `getConnection()` - zwraca połączenie PDO
- `query($sql, $params)` - wykonanie zapytania SQL
- `fetchAll($sql, $params)` - pobranie wszystkich wierszy
- `fetchOne($sql, $params)` - pobranie jednego wiersza
- `insert($table, $data)` - wstawienie danych
- `update($table, $data, $where, $whereParams)` - aktualizacja danych
- `delete($table, $where, $params)` - usunięcie danych

**Tworzone tabele:**
- `users` - użytkownicy
- `images` - obrazy
- `likes` - głosy
- `sessions` - sesje
- `rate_limits` - limity żądań

#### Auth.php
Klasa do uwierzytelniania i autoryzacji użytkowników.

**Główne metody:**
- `register($username, $password)` - rejestracja nowego użytkownika
- `login($username, $password)` - logowanie użytkownika
- `logout()` - wylogowanie z systemu
- `getCurrentUser()` - pobranie aktualnego użytkownika
- `requireAuth()` - sprawdzenie autoryzacji
- `changePassword($userId, $currentPassword, $newPassword)` - zmiana hasła
- `updateAvatar($userId, $avatarPath)` - aktualizacja awatara
- `updateProfile($userId, $username, $bio)` - aktualizacja profilu
- `updatePrivacy($userId, $isPrivate)` - aktualizacja ustawień prywatności
- `deleteAccount($userId, $password)` - usunięcie konta

**Cechy:**
- Haszowanie haseł za pomocą `password_hash()` (PASSWORD_BCRYPT, cost=12)
- Ochrona przed atakami timing przy sprawdzaniu haseł
- Cache użytkownika w sesji (5 minut TTL)
- Używanie tylko sesji (cookie user_id usunięte dla bezpieczeństwa)

#### ImageHandler.php
Klasa do przetwarzania i optymalizacji obrazów.

**Główne metody:**
- `upload($file, $userId)` - przesłanie obrazu
- `getPopularImages($limit, $offset)` - pobranie popularnych obrazów z paginacją i cache
- `getUserImages($userId, $currentUserId)` - pobranie obrazów użytkownika (z sprawdzeniem prywatności)
- `getImageById($imageId, $currentUserId)` - pobranie obrazu po ID (z sprawdzeniem prywatności)
- `getUserStats($userId)` - statystyki użytkownika

**Optymalizacja obrazów:**
- Konwersja do WebP (jakość 85%)
- Zmiana rozmiaru do maksymalnych wymiarów (1920x1080)
- Autokorekcja jasności przez imagefilter() (zoptymalizowane dla wydajności)
- Filtr sharpen
- Zachowanie przezroczystości dla PNG/GIF
- Cache popularnych obrazów (5 minut)

**Walidacja:**
- Sprawdzenie typu MIME (JPEG, PNG, GIF, WebP)
- Maksymalny rozmiar pliku: 5MB
- Sprawdzenie przez `is_uploaded_file()`

#### RateLimiter.php
Klasa do ograniczania częstotliwości żądań.

**Metody:**
- `check($userId, $action)` - sprawdzenie limitu

**Ustawienia:**
- Maksimum 10 przesłań na minutę na użytkownika
- Przechowywanie w bazie danych SQLite
- Automatyczne czyszczenie starych wpisów

#### Security.php
Klasa do bezpieczeństwa aplikacji.

**Metody:**
- `sanitizeOutput($data)` - escaping wyjścia (ochrona XSS)
- `sanitizeInput($data)` - czyszczenie danych wejściowych
- `generateCsrfToken()` - generowanie tokenu CSRF
- `validateCsrfToken($token)` - walidacja tokenu CSRF
- `setSecurityHeaders()` - ustawienie nagłówków bezpieczeństwa
- `validateJsonInput()` - walidacja JSON Content-Type
- `rateLimitCheck($identifier, $maxRequests, $window)` - sprawdzenie rate limit
- `checkAuthRateLimit($ip, $username)` - sprawdzenie rate limit dla autoryzacji (5 prób, blokada na 5 minut)

**Security Headers:**
- Content-Security-Policy (bez unsafe-inline dla skryptów)
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- X-XSS-Protection: 1; mode=block
- Strict-Transport-Security (tylko przy HTTPS)
- Referrer-Policy: strict-origin-when-cross-origin
- Permissions-Policy: geolocation=(), microphone=(), camera=()

## API Endpoints

### Uwierzytelnianie

#### POST /api/login.php
Logowanie użytkownika.

**Request Body:**
```json
{
  "username": "string",
  "password": "string"
}
```

**Response:**
```json
{
  "message": "Zalogowano pomyślnie",
  "user": {
    "id": 1,
    "username": "string",
    "avatar": "string|null",
    "bio": "string",
    "is_private": 0
  }
}
```

#### POST /api/register.php
Rejestracja nowego użytkownika.

**Request Body:**
```json
{
  "username": "string (3-50 znaków)",
  "password": "string (minimum 6 znaków)"
}
```

**Response:**
```json
{
  "message": "Rejestracja zakończona pomyślnie",
  "user_id": 1
}
```

#### POST /api/logout.php
Wylogowanie z systemu.

**Response:**
```json
{
  "message": "Wylogowano pomyślnie"
}
```

#### GET /api/user.php
Pobranie aktualnego użytkownika.

**Response:**
```json
{
  "user": { ... } | null
}
```

### Obrazy

#### GET /api/images.php
Pobranie obrazów.

**Query Parameters:**
- `type` - typ żądania: `popular`, `user`, `single`
- `limit` - limit (dla type=popular, domyślnie 20)
- `user_id` - ID użytkownika (dla type=user)
- `id` - ID obrazu (dla type=single)

**Response (type=popular):**
```json
{
  "images": [
    {
      "id": 1,
      "user_id": 1,
      "filename": "string",
      "file_path": "uploads/string",
      "likes": 10,
      "dislikes": 2,
      "username": "string",
      "avatar": "string|null"
    }
  ]
}
```

#### POST /api/upload.php
Przesłanie obrazu.

**Request:** FormData z polem `image`

**Wymagania:**
- Zautoryzowany użytkownik
- Rate limit: 10 przesłań na minutę
- Maksymalny rozmiar: 5MB
- Formaty: JPEG, PNG, GIF, WebP

**Response:**
```json
{
  "message": "Zdjęcie przesłane pomyślnie",
  "image": {
    "image_id": 1,
    "filename": "string",
    "file_path": "uploads/string",
    "width": 1920,
    "height": 1080
  }
}
```

#### POST /api/upload-avatar.php
Przesłanie awatara.

**Request:** FormData z polem `avatar`

**Wymagania:**
- Zautoryzowany użytkownik
- Maksymalny rozmiar: 2MB
- Formaty: JPEG, PNG, GIF, WebP

**Response:**
```json
{
  "message": "Avatar zaktualizowany",
  "avatar": "uploads/avatars/string"
}
```

### Głosowanie

#### POST /api/like.php
Głosowanie na obraz.

**Request Body:**
```json
{
  "image_id": 1,
  "is_like": 1  // 1 = like, 0 = dislike
}
```

**Response:**
```json
{
  "message": "Głos dodany",
  "action": "added"  // "added", "changed", "removed"
}
```

**Logika:**
- Jeden użytkownik może głosować raz na obraz
- Powtórny głos z tą samą wartością usuwa głos
- Powtórny głos z inną wartością zmienia głos

### Ustawienia

#### GET /api/settings.php
Pobranie ustawień użytkownika.

**Response:**
```json
{
  "user": { ... }
}
```

#### POST /api/settings.php
Aktualizacja ustawień.

**Request Body:**
```json
{
  "action": "change_password|update_profile|update_privacy|delete_account",
  // ... dodatkowe pola w zależności od action
}
```

**Actions:**
- `change_password`: `current_password`, `new_password`
- `update_profile`: `username`, `bio`
- `update_privacy`: `is_private`
- `delete_account`: `password`

### Real-time

#### GET /api/events.php
Server-Sent Events do aktualizacji w czasie rzeczywistym.

**Events:**
- `likes_update` - aktualizacja lajków
- `new_images` - nowe obrazy

**Uwaga:** SSE zaimplementowany na serwerze, ale wyłączony w kodzie klienta (return w setupSSE w app.js)

## Architektura Frontend

### Moduły JavaScript

#### api-client.js
Wspólny klient HTTP do żądań API (singleton).

**Klasy:**
- `ApiClient` - wspólny klient HTTP dla wszystkich modułów

**Metody:**
- `request(url, options)` - podstawowa metoda żądań
- `get(url)` - żądania GET
- `post(url, data)` - żądania POST z JSON
- `upload(url, formData)` - przesyłanie plików z timeout
- `postFormData(url, formData)` - żądania POST z FormData

#### app.js
Główny moduł aplikacji.

**Klasy:**
- `ImageGallery` - zarządzanie galerią obrazów
- `ScrollAnimations` - animacje przy przewijaniu

**Funkcjonalność:**
- Pobieranie popularnych obrazów
- Wyświetlanie galerii z animacjami
- Obsługa głosowania
- Okna modalne do przesyłania i przeglądania obrazów
- SSE (wyłączone)
- Efekt ripple na przyciskach

#### auth.js
Moduł autoryzacji.

**Klasy:**
- `AuthHandler` - obsługa formularzy autoryzacji

**Funkcjonalność:**
- Logowanie użytkownika
- Rejestracja
- Używa tylko sesji (bez localStorage)

#### settings.js
Moduł ustawień.

**Klasy:**
- `SettingsHandler` - obsługa ustawień

**Funkcjonalność:**
- Przesyłanie awatara
- Aktualizacja profilu
- Zmiana hasła
- Ustawienia prywatności
- Usunięcie konta
- Używa tylko sesji (bez localStorage)

#### ui-utils.js
Komponenty UI i narzędzia.

**Klasy:**
- `Dropdown` - komponent menu dropdown

**Funkcjonalność:**
- Zarządzanie menu dropdown
- Obsługa kliknięć poza menu
- Obsługa klawisza Escape

#### form-utils.js
Narzędzia do pracy z formularzami.

**Klasy:**
- `FormUtils` - metody statyczne dla formularzy

**Metody:**
- `showError(formId, message)` - wyświetlanie błędów
- `showSuccess(formId, message)` - wyświetlanie komunikatów sukcesu
- `clearMessages(formId)` - czyszczenie komunikatów

### Cechy CSS

**Motywy:**
- Ciemny motyw domyślny
- Zmienne CSS dla kolorów
- Obsługa motywów (forest, ocean, sunset) - przygotowania w CSS

**Animacje:**
- Animacje scroll reveal
- Efekty hover na kartach
- Efekty neon glow
- Efekt ripple na przyciskach
- Skeleton loaders

**Responsywność:**
- Podejście mobile-first
- Media queries dla tabletów i telefonów
- Layouts Flexbox i Grid

**Dostępność:**
- Stany focus
- Atrybuty ARIA
- Skip link
- Obsługa prefers-reduced-motion

## Schemat bazy danych

### Tabela users
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    avatar TEXT DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    is_private INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
```

### Tabela images
```sql
CREATE TABLE images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    original_filename TEXT NOT NULL,
    file_path TEXT NOT NULL,
    width INTEGER NOT NULL,
    height INTEGER NOT NULL,
    file_size INTEGER NOT NULL,
    likes_count INTEGER DEFAULT 0,
    dislikes_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

### Tabela likes
```sql
CREATE TABLE likes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    image_id INTEGER NOT NULL,
    is_like INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    UNIQUE(user_id, image_id)
)
```

### Tabela sessions
```sql
CREATE TABLE sessions (
    id TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

### Tabela rate_limits
```sql
CREATE TABLE rate_limits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    request_count INTEGER DEFAULT 1,
    window_start DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(user_id, action, window_start)
)
```

## Konfiguracja (.env)

```env
# Ścieżka do bazy danych SQLite
DB_PATH=C:\Shit\htdocs\Foto-grade-main\database.db

SESSION_LIFETIME=3600
UPLOAD_DIR=uploads/
MAX_UPLOAD_SIZE=5242880
MAX_IMAGE_WIDTH=1920
MAX_IMAGE_HEIGHT=1080
RATE_LIMIT_UPLOADS=10
RATE_LIMIT_WINDOW=60
```

## Bezpieczeństwo

### Zaimplementowane środki

1. **Ochrona XSS:**
   - `htmlspecialchars()` dla wyjścia
   - `strip_tags()` dla danych wejściowych
   - Nagłówek Content-Security-Policy (bez unsafe-inline dla skryptów)

2. **Ochrona CSRF:**
   - Generowanie tokenów w sesji
   - Walidacja tokenów przy żądaniach POST
   - `hash_equals()` do porównania

3. **Ochrona przed SQL Injection:**
   - Prepared statements przez PDO
   - Zapytania parametryzowane
   - Whitelist dla nazw tabel w Database.php

4. **Uwierzytelnianie:**
   - Haszowanie haseł (PASSWORD_BCRYPT, cost=12)
   - Ochrona przed atakami timing przy sprawdzaniu haseł
   - Sesje z lifetime
   - Usunięcie niebezpiecznego cookie user_id (używane tylko sesje)

5. **Rate Limiting:**
   - 10 przesłań na minutę na użytkownika
   - 5 prób autoryzacji na IP+username, blokada na 5 minut
   - Przechowywanie w bazie danych i sesjach
   - Automatyczne czyszczenie

6. **Bezpieczeństwo przesyłania plików:**
   - Sprawdzenie `is_uploaded_file()`
   - Walidacja typu MIME przez finfo
   - Ograniczenie rozmiaru pliku
   - Generowanie unikalnych nazw plików
   - Ochrona .htaccess w uploads/ przed wykonywaniem skryptów

7. **Security Headers:**
   - CSP (bez unsafe-inline dla skryptów)
   - X-Frame-Options: DENY
   - X-XSS-Protection: 1; mode=block
   - HSTS (przy HTTPS)
   - Referrer-Policy: strict-origin-when-cross-origin
   - Permissions-Policy: geolocation=(), microphone=(), camera=()

8. **Ochrona bazy danych:**
   - Baza danych przeniesiona z katalogu publicznego do głównego katalogu projektu
   - Ochrona .htaccess w public/api/ przed dostępem do plików .db
   - Bezpieczne logowanie (bez danych wrażliwych)

9. **Bezpieczne logowanie:**
   - Usunięto logowanie błędów SQL ze szczegółami
   - Usunięto logowanie szczegółów plików przy przesyłaniu
   - Usunięto logowanie sesji i danych użytkowników

10. **Sprawdzanie prywatności:**
    - getUserImages() i getImageById() sprawdzają is_private użytkownika
    - Prywatne profile są ukryte przed innymi użytkownikami
    - Właściciel widzi swoje zdjęcia nawet w trybie prywatnym

11. **Optymalizacja wydajności:**
    - Autokorekcja jasności przez imagefilter() zamiast iteracji po pikselach
    - Cache popularnych obrazów (5 minut)
    - Paginacja dla popularnych obrazów

12. **Usunięcie duplikatów kodu:**
    - Wspólny ApiClient (singleton) zamiast 3 kopii
    - Metody CSRF tylko w Security.php
    - Wspólny komponent Dropdown
    - Wspólne narzędzia do formularzy

13. **Bezpieczeństwo danych:**
    - Pełne rezygnacja z localStorage na rzecz sesji
    - Dane użytkownika przechowywane tylko na serwerze

## Niezgodności między planem a implementacją

### 1. Baza danych
**Plan (projekt.md):** MariaDB 10.3+ z phpMyAdmin
**Implementacja:** SQLite

**Wpływ:** SQLite jest prostszy w konfiguracji, ale mniej skalowalny. Nie ma potrzeby osobnego serwera bazy danych.

### 2. Przechowywanie obrazów
**Plan (projekt.md):** Obrazy przechowywane w repozytorium GitHub, backend ma token do API
**Implementacja:** Obrazy przechowywane lokalnie w folderze `uploads/`

**Wpływ:**
- Brak integracji z GitHub API
- Backend nie ma tokena do GitHub
- Frontend nie pobiera obrazów bezpośrednio z GitHub
- Obrazy przechowywane na lokalnym serwerze

### 3. Aktualizacje w czasie rzeczywistym
**Plan (projekt.md):** SSE do aktualizacji w czasie rzeczywistym
**Implementacja:** SSE zaimplementowany na serwerze (events.php), ale wyłączony w kodzie klienta (return w setupSSE w app.js linia 319)

**Wpływ:** Aktualizacje w czasie rzeczywistym nie działają, galeria aktualizuje się tylko przy przeładowaniu lub jawnym żądaniu.

### 4. Zależności
**Plan (projekt.md):** JS vanilla ES6 (może z Vite dla Node.js)
**Implementacja:** Vanilla JS bez bundlerów

**Wpływ:** Prostsze w rozwoju, ale brak optymalizacji i modułowości na poziomie budowania.

## Potencjalne ulepszenia

1. **Zaimplementować integrację z GitHub:**
   - Dodać przesyłanie obrazów do repozytorium GitHub
   - Użyć GitHub API do przechowywania metadanych
   - Pobierać obrazy bezpośrednio z GitHub

2. **Aktywować SSE:**
   - Usunąć return w setupSSE
   - Dodać obsługę zdarzeń do aktualizacji w czasie rzeczywistym

3. **Przejść na MariaDB/MySQL:**
   - Zmienić Database.php do pracy z MySQL
   - Zaktualizować konfigurację .env
   - Migrować dane z SQLite

4. **Dodać tokeny CSRF do formularzy:**
   - Dołączyć tokeny CSRF do formularzy HTML
   - Walidować tokeny przy wszystkich żądaniach POST

5. **Ulepszyć walidację:**
   - Dodać bardziej ścisłą walidację username
   - Dodać walidację bio
   - Dodać sprawdzanie złożoności hasła

6. **Dodać testy:**
   - Testy jednostkowe dla klas PHP
   - Testy integracyjne dla API
   - Testy E2E dla frontend

7. **Optymalizacja:**
   - Dodać cache obrazów
   - Zoptymalizować rozmiar CSS
   - Dodać lazy loading dla obrazów

8. **Dokumentacja:**
   - Dodać dokumentację Swagger/OpenAPI dla API
   - Dodać komentarze do kodu
   - Utworzyć przewodnik instalacji

## Wymagania środowiska

- PHP 7.4 lub nowsze
- Rozszerzenia PHP: pdo_sqlite, gd, fileinfo
- Serwer WWW (Apache/Nginx) lub wbudowany serwer PHP
- Composer (do instalacji zależności)

## Instalacja

1. Sklonuj repozytorium
2. Zainstaluj zależności: `composer install`
3. Skonfiguruj plik .env
4. Ustaw prawa zapisu dla folderu uploads/
5. Uruchom serwer: `php -S localhost:8000 -t public`

## Podsumowanie
Foto-Ocena - funkcjonalna aplikacja internetowa do oceny zdjęć z dobrą architekturą i środkami bezpieczeństwa. Główne niezgodności z planem dotyczą przechowywania obrazów (lokalnie zamiast GitHub) i bazy danych (SQLite zamiast MariaDB). SSE zaimplementowany na serwerze, ale wyłączony na kliencie. Aplikacja jest gotowa do użycia, ale aby zgodzić się z pierwotnym planem, wymaga dopracowania integracji z GitHub i aktywacji aktualizacji w czasie rzeczywistym.
