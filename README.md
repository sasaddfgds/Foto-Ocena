# Foto-Ocena

Aplikacja do oceniania zdjęć z możliwością rejestracji, logowania i głosowania.

## Funkcje

- **Rejestracja i logowanie użytkowników** - Bezpieczna autoryzacja z haszowaniem haseł
- **Przesyłanie zdjęć** - Automatyczna optymalizacja do formatu WebP (max 1920x1080)
- **System ocen** - Każdy użytkownik może raz zagłosować (like/dislike) na zdjęcie
- **Galeria popularnych** - Wyświetlanie 20 najpopularniejszych zdjęć
- **Profile użytkowników** - Przeglądanie zdjęć i statystyk użytkowników
- **Aktualizacje w czasie rzeczywistym** - SSE dla natychmiastowych aktualizacji
- **Rate limiting** - Ograniczenie 10 przesłań na minutę dla użytkownika
- **Bezpieczeństwo** - Ochrona XSS, CSRF, bezpieczne nagłówki HTTP

## Wymagania

- PHP 7.4 lub wyższy
- MariaDB 10.3 lub wyższy
- Rozszerzenia PHP: pdo_mysql, gd, fileinfo
- Serwer WWW (Apache/Nginx) lub wbudowany serwer PHP
## Wsparcie

W razie problemów lub pytań, proszę o otwarcie issue na GitHub.
