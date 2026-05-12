<?php
session_start();
require_once __DIR__ . '/../src/Auth.php';
$auth = new Auth();
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foto-Ocena - Oceniaj zdjęcia</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="navbar" role="navigation" aria-label="Główna nawigacja">
            <div class="nav-container">
                <a href="index.php" class="logo">Foto-Ocena</a>
                <ul class="nav-menu">
                    <?php if ($currentUser): ?>
                        <li><a href="index.php">Główna</a></li>
                        <li><a href="#" id="uploadBtn">Prześlij zdjęcie</a></li>
                        <li class="nav-user-menu">
                            <button class="nav-user-btn" id="navUserBtn" aria-label="Menu użytkownika" aria-expanded="false">
                                <?php if ($currentUser['avatar']): ?>
                                    <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="Avatar" class="nav-avatar">
                                <?php else: ?>
                                    <div class="nav-avatar-placeholder"><?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?></div>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($currentUser['username']); ?></span>
                                <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 12 12"><path d="M3 5l3 3 3-3" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
                            </button>
                            <ul class="nav-dropdown" id="navDropdown">
                                <li><a href="profile.php?id=<?php echo $currentUser['id']; ?>">Mój profil</a></li>
                                <li><a href="settings.php">Ustawienia</a></li>
                                <li><a href="#" id="logoutBtn">Wyloguj</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php">Zaloguj się</a></li>
                        <li><a href="register.php">Zarejestruj się</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <h1>Oceniaj najlepsze zdjęcia</h1>
            <p>Przeglądaj, oceniaj i dziel się swoimi zdjęciami z społecznością</p>
            <div class="hero-actions">
                <?php if ($currentUser): ?>
                    <button id="uploadBtn" class="btn btn-primary">Prześlij zdjęcie</button>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary">Zarejestruj się, aby przesłać zdjęcie</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="gallery" aria-label="Galeria zdjęć">
            <h2>Najpopularniejsze zdjęcia</h2>
            <div id="imageGrid" class="image-grid">
                <p class="loading">Ładowanie zdjęć...</p>
            </div>
        </section>
    </main>

    <div id="uploadModal" class="modal" role="dialog" aria-labelledby="uploadModalTitle" aria-hidden="true">
        <div class="modal-content">
            <span class="close" aria-label="Zamknij">&times;</span>
            <h2 id="uploadModalTitle">Prześlij zdjęcie</h2>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="imageFile">Wybierz zdjęcie:</label>
                    <input type="file" id="imageFile" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    <small>Maksymalny rozmiar: 5MB. Formaty: JPEG, PNG, GIF, WebP</small>
                </div>
                <button type="submit" class="btn btn-primary">Prześlij</button>
            </form>
            <div id="uploadError" class="error-message"></div>
        </div>
    </div>

    <div id="imageModal" class="modal" role="dialog" aria-labelledby="imageModalTitle" aria-hidden="true">
        <div class="modal-content modal-large">
            <span class="close" aria-label="Zamknij">&times;</span>
            <div id="imageModalContent"></div>
        </div>
    </div>

    <script>
        const userId = localStorage.getItem('user_id');
        const userData = localStorage.getItem('user_data');
        console.log('userId:', userId);
        console.log('userData:', userData);
        if (userId && userData) {
            const user = JSON.parse(userData);
            console.log('Parsed user:', user);
            const navMenu = document.querySelector('.nav-menu');
            const authLinks = navMenu.querySelectorAll('li');
            authLinks.forEach(link => link.remove());
            const userLi = document.createElement('li');
            userLi.className = 'nav-user-menu';
            userLi.innerHTML = `
                <button class="nav-user-btn" id="navUserBtn" aria-label="Menu użytkownika" aria-expanded="false">
                    ${user.avatar ? `<img src="${user.avatar}" alt="Avatar" class="nav-avatar">` : `<div class="nav-avatar-placeholder">${user.username.charAt(0).toUpperCase()}</div>`}
                    <span>${user.username}</span>
                    <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 12 12"><path d="M3 5l3 3 3-3" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>
                </button>
                <ul class="nav-dropdown" id="navDropdown">
                    <li><a href="index.php">Główna</a></li>
                    <li><a href="profile.php?id=${user.id || userId}">Mój profil</a></li>
                    <li><a href="settings.php">Ustawienia</a></li>
                    <li><a href="#" id="logoutBtn">Wyloguj</a></li>
                </ul>
            `;
            navMenu.appendChild(userLi);
            document.getElementById('logoutBtn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'index.php';
            });
            const uploadBtn = document.getElementById('uploadBtn');
            if (uploadBtn) {
                uploadBtn.addEventListener('click', () => {
                    document.getElementById('uploadModal').style.display = 'block';
                });
            }
            
            // Add close button handler
            const closeBtn = document.querySelector('#uploadModal .close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    document.getElementById('uploadModal').style.display = 'none';
                });
            }
        }
    </script>
    <script src="js/api-client.js"></script>
    <script src="js/ui-utils.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
