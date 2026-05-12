<?php
session_start();
require_once __DIR__ . '/../src/Auth.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    $currentUser = ['id' => '', 'username' => '', 'avatar' => null, 'bio' => '', 'is_private' => 0];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia - Foto-Ocena</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="navbar" role="navigation" aria-label="Główna nawigacja">
            <div class="nav-container">
                <a href="index.php" class="logo">Foto-Ocena</a>
                <ul class="nav-menu">
                    <li><a href="index.php">Główna</a></li>
                    <li><a href="login.php">Zaloguj się</a></li>
                    <li><a href="register.php">Zarejestruj się</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="settings-container">
            <h1>Ustawienia</h1>

            <div class="settings-section">
                <h2>Avatar</h2>
                <div class="avatar-section">
                    <div class="current-avatar">
                        <?php if ($currentUser['avatar']): ?>
                            <img src="<?php echo htmlspecialchars($currentUser['avatar']); ?>" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-placeholder"><?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <form id="avatarForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="avatarFile">Zmień avatar:</label>
                            <input type="file" id="avatarFile" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small>Maksymalny rozmiar: 2MB. Formaty: JPEG, PNG, GIF, WebP</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Zapisz avatar</button>
                    </form>
                    <div id="avatarError" class="error-message"></div>
                    <div id="avatarSuccess" class="success-message"></div>
                </div>
            </div>

            <div class="settings-section">
                <h2>Profil</h2>
                <form id="profileForm">
                    <div class="form-group">
                        <label for="username">Nazwa użytkownika:</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required minlength="3" maxlength="50">
                        <small>Od 3 do 50 znaków</small>
                    </div>
                    <div class="form-group">
                        <label for="bio">O mnie:</label>
                        <textarea id="bio" name="bio" rows="4" maxlength="500"><?php echo htmlspecialchars($currentUser['bio'] ?? ''); ?></textarea>
                        <small>Maksymalnie 500 znaków</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Zapisz profil</button>
                </form>
                <div id="profileError" class="error-message"></div>
                <div id="profileSuccess" class="success-message"></div>
            </div>

            <div class="settings-section">
                <h2>Hasło</h2>
                <form id="passwordForm">
                    <div class="form-group">
                        <label for="currentPassword">Obecne hasło:</label>
                        <input type="password" id="currentPassword" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">Nowe hasło:</label>
                        <input type="password" id="newPassword" name="new_password" required minlength="6">
                        <small>Minimum 6 znaków</small>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Potwierdź nowe hasło:</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary">Zmień hasło</button>
                </form>
                <div id="passwordError" class="error-message"></div>
                <div id="passwordSuccess" class="success-message"></div>
            </div>

            <div class="settings-section">
                <h2>Prywatność</h2>
                <form id="privacyForm">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="isPrivate" name="is_private" <?php echo ($currentUser['is_private'] ?? 0) ? 'checked' : ''; ?>>
                            Prywatny profil
                        </label>
                        <small>Gdy profil jest prywatny, tylko ty widzisz swoje zdjęcia</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Zapisz ustawienia prywatności</button>
                </form>
                <div id="privacyError" class="error-message"></div>
                <div id="privacySuccess" class="success-message"></div>
            </div>

            <div class="settings-section danger-zone">
                <h2>Strefa niebezpieczeństwa</h2>
                <p>Tej akcji nie można cofnąć. Bądź pewien, że chcesz to zrobić.</p>
                <form id="deleteAccountForm">
                    <div class="form-group">
                        <label for="deletePassword">Hasło:</label>
                        <input type="password" id="deletePassword" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-danger">Usuń konto</button>
                </form>
                <div id="deleteError" class="error-message"></div>
            </div>
        </section>
    </main>

    <script>
        const userId = localStorage.getItem('user_id');
        const userData = localStorage.getItem('user_data');
        if (!userId) {
            window.location.href = 'login.php';
        }
        if (userId && userData) {
            const user = JSON.parse(userData);
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
                    <li><a href="profile.php?id=${user.id}">Mój profil</a></li>
                    <li><a href="#" id="logoutBtn">Wyloguj</a></li>
                </ul>
            `;
            navMenu.appendChild(userLi);
            document.getElementById('logoutBtn').addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = 'index.php';
            });
        }
    </script>
    <script src="js/api-client.js"></script>
    <script src="js/ui-utils.js"></script>
    <script src="js/form-utils.js"></script>
    <script src="js/settings.js"></script>
</body>
</html>
