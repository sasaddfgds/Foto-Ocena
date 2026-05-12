Projekt zdjecia z ocenianiem



Stuck technologiczny:

PHP (Koniecznie z composer ale może na początku w php script)

JS vanila ES6 (może z vite dla node.js)

html css dla wyglądu (Semantyczny z WCAG)

Architektura



backend jest proxy tylko do zapoisu a nie do odczytu plików z repo github i on ma do niego token

Frontend dzieki meta danym od backendu przechowywanych w bazie danych mariaDB phpMyadmin pobiera bezpośrednio zdjecia z repo github

Obrazy tylko na repo github (Ostatecznie temp w razie chęci wprowadzenia moderacji)

Logika biznesowa



Użytkownik musi się zarejestrować lub zalogować

Wyświetla się na stronie głównej 20 obrazów z najwiekszą liczbą polubień

User może polubić lub nie polubić zdejcie tylko raz (unikamy od jednegoi usera 40 dislikeów)

przy obrazie mamy avatar usera gdzie jak klikniemy na niego to mamy jego profil z tylko jego zdjeciami które możemy ocenić like/dislike (bonus na profilu mamy sumę likleów i dislików za zdjecia danegoi usera)

User może wysłać obraz, backend zapisze meta dane i dzieki temu że ma token to prześle i zapisze zdjecie w repo github oraz w tabeli bazy danych potrzebne metadane

+++

Bosuy optymalizacji i bezpieczeństwa



optymalizacja obrazu do formatu .webp i max rozmiar 1920x1080

rate limiter na requesty uploadu np.: 10 zjedjęć na minutę dla danego usera.

XXS CRFS HASH haseł

sse 😎 (emoji win + .) albo tunbelowanie albop subskrybcja ale chyba SSE