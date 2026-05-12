<?php
session_start();
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/ImageHandler.php';
require_once __DIR__ . '/../src/Database.php';

$auth = new Auth();
$currentUser = $auth->getCurrentUser();

$userId = $_GET['id'] ?? null;
$imageHandler = new ImageHandler();
$userImages = [];
$userStats = ['total_images' => 0, 'total_likes' => 0, 'total_dislikes' => 0];
$profileUser = null;

if ($userId) {
    $userImages = $imageHandler->getUserImages($userId);
    $userStats = $imageHandler->getUserStats($userId);
    
    $db = Database::getInstance();
    $profileUser = $db->fetchOne(
        "SELECT id, username, avatar, bio, is_private FROM users WHERE id = ?",
        [$userId]
    );
    
    error_log("Profile user ID: " . $userId);
    error_log("Profile user from DB: " . print_r($profileUser, true));
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php echo $profileUser ? htmlspecialchars($profileUser['username']) : 'Nie znaleziono'; ?> - Foto-Ocena</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="navbar" role="navigation" aria-label="Główna nawigacja">
            <div class="nav-container">
                <a href="index.php" class="logo">Foto-Ocena</a>
                <ul class="nav-menu">
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
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <?php if (!$profileUser): ?>
            <section class="hero">
                <h1>Profil nie znaleziony</h1>
                <p>Ten użytkownik nie istnieje.</p>
                <a href="index.php" class="btn btn-primary">Powrót do strony głównej</a>
            </section>
        <?php else: ?>
        <section class="profile-header">
            <div class="profile-avatar">
                <?php if ($profileUser['avatar']): ?>
                    <img src="<?php echo htmlspecialchars($profileUser['avatar']); ?>" alt="Avatar <?php echo htmlspecialchars($profileUser['username']); ?>">
                <?php else: ?>
                    <div class="avatar-placeholder"><?php echo strtoupper(substr($profileUser['username'], 0, 1)); ?></div>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($profileUser['username']); ?></h1>
                <?php if ($profileUser['bio']): ?>
                    <p class="profile-bio"><?php echo htmlspecialchars($profileUser['bio']); ?></p>
                <?php endif; ?>
                <?php if ($profileUser['is_private']): ?>
                    <span class="private-badge">Prywatny profil</span>
                <?php endif; ?>
                <?php if ($currentUser && $currentUser['id'] == $profileUser['id']): ?>
                    <a href="settings.php" class="btn btn-secondary">Ustawienia</a>
                <?php endif; ?>
                <div class="profile-stats">
                    <div class="stat">
                        <span class="stat-value"><?php echo $userStats['total_images'] ?? 0; ?></span>
                        <span class="stat-label">Zdjęć</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value"><?php echo $userStats['total_likes'] ?? 0; ?></span>
                        <span class="stat-label">Polubień</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value"><?php echo $userStats['total_dislikes'] ?? 0; ?></span>
                        <span class="stat-label">Niepolubień</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="gallery" aria-label="Galeria zdjęć użytkownika">
            <h2>Zdjęcia użytkownika</h2>
            <div id="imageGrid" class="image-grid">
                <?php if (empty($userImages)): ?>
                    <p class="no-images">Ten użytkownik nie przesłał jeszcze żadnych zdjęć.</p>
                <?php else: ?>
                    <?php foreach ($userImages as $image): ?>
                        <div class="image-card" data-image-id="<?php echo $image['id']; ?>">
                            <img src="<?php echo htmlspecialchars($image['file_path']); ?>" alt="Zdjęcie od <?php echo htmlspecialchars($image['username']); ?>" loading="lazy">
                            <div class="image-overlay">
                                <div class="image-actions">
                                    <button class="like-btn" data-image-id="<?php echo $image['id']; ?>" aria-label="Polub">
                                        <span class="icon">👍</span>
                                        <span class="count"><?php echo $image['likes']; ?></span>
                                    </button>
                                    <button class="dislike-btn" data-image-id="<?php echo $image['id']; ?>" aria-label="Nie lub">
                                        <span class="icon">👎</span>
                                        <span class="count"><?php echo $image['dislikes']; ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
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

    <script>
        const userId = localStorage.getItem('user_id');
        const userData = localStorage.getItem('user_data');
        console.log('Profile - userId:', userId);
        console.log('Profile - userData:', userData);
        console.log('Profile - URL id:', <?php echo json_encode($userId); ?>);
        if (userId && userData) {
            const user = JSON.parse(userData);
            console.log('Profile - Parsed user:', user);
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
                    <li><a href="settings.php">Ustawienia</a></li>
                    <li><a href="#" id="logoutBtn">Wyloguj</a></li>
                </ul>
            `;
            navMenu.appendChild(userLi);
            document.getElementById('logoutBtn').addEventListener('click', function(e) {
                e.preventDefault();
                localStorage.removeItem('user_id');
                localStorage.removeItem('user_data');
                window.location.href = 'index.php';
            });
        }
    </script>
    <script src="js/app.js"></script>
</body>
</html>
