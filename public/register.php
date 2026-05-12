<?php
session_start();
require_once __DIR__ . '/../src/Auth.php';
$auth = new Auth();
$currentUser = $auth->getCurrentUser();

if ($currentUser) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja - Foto-Ocena</title>
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
                </ul>
            </div>
        </nav>
    </header>

    <main>
        <section class="auth-container">
            <div class="auth-box">
                <h1>Zarejestruj się</h1>
                <form id="registerForm" autocomplete="off">
                    <div class="form-group">
                        <label for="username">Nazwa użytkownika:</label>
                        <input type="text" id="username" name="username" required autocomplete="username" minlength="3" maxlength="50">
                        <small>Od 3 do 50 znaków</small>
                    </div>
                    <div class="form-group">
                        <label for="password">Hasło:</label>
                        <input type="password" id="password" name="password" required autocomplete="new-password" minlength="6">
                        <small>Minimum 6 znaków</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Zarejestruj się</button>
                </form>
                <div id="registerError" class="error-message"></div>
                <p class="auth-link">Masz już konto? <a href="login.php">Zaloguj się</a></p>
            </div>
        </section>
    </main>

    <script src="js/api-client.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>
