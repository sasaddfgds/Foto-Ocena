<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function register($username, $password) {
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ?",
            [$username]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Użytkownik o tej nazwie już istnieje'];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $userId = $this->db->insert('users', [
            'username' => $username,
            'password_hash' => $passwordHash
        ]);
        $this->createSession($userId);

        return [
            'success' => true,
            'user_id' => $userId
        ];
    }

    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE username = ?",
            [$username]
        );

        if (!$user) {
            // Use hash_equals to prevent timing attacks even when user doesn't exist
            password_verify('dummy', '$2y$12$dummy.hash.for.timing');
            return ['success' => false, 'message' => 'Nieprawidłowa nazwa użytkownika lub hasło'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Nieprawidłowa nazwa użytkownika lub hasło'];
        }

        $sessionId = $this->createSession($user['id']);
        
        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => $user['avatar'],
                'bio' => $user['bio'] ?? '',
                'is_private' => $user['is_private'] ?? 0
            ]
        ];
    }

    private function createSession($userId) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        // Remove user_id cookie for security - use session only
        // setcookie('user_id', $userId, time() + 86400, '/', '', false, true);
        
        return true;
    }

    public function logout() {
        unset($_SESSION['user_id'], $_SESSION['logged_in'], $_SESSION['login_time'], $_SESSION['cached_user'], $_SESSION['cached_user_time']);
        session_destroy();
        return ['success' => true];
    }

    public function getCurrentUser() {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            return null;
        }

        $now = time();
        $cacheTtl = 300;
        if (isset($_SESSION['cached_user']) && isset($_SESSION['cached_user_time']) && ($now - $_SESSION['cached_user_time']) < $cacheTtl) {
            return $_SESSION['cached_user'];
        }

        $user = $this->db->fetchOne(
            "SELECT id, username, avatar, bio, is_private FROM users WHERE id = ?",
            [$userId]
        );

        if ($user) {
            $_SESSION['cached_user'] = $user;
            $_SESSION['cached_user_time'] = $now;
        }

        return $user;
    }

    public function requireAuth() {
        $user = $this->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Nieautoryzowany dostęp']);
            exit;
        }
        return $user;
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetchOne(
            "SELECT password_hash FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Nieprawidłowe obecne hasło'];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->update('users', ['password_hash' => $newHash], 'id = ?', [$userId]);

        return ['success' => true, 'message' => 'Hasło zmienione pomyślnie'];
    }

    public function updateAvatar($userId, $avatarPath) {
        $this->db->update('users', ['avatar' => $avatarPath], 'id = ?', [$userId]);
        if (isset($_SESSION['cached_user'])) {
            $_SESSION['cached_user']['avatar'] = $avatarPath;
        }

        return ['success' => true, 'message' => 'Avatar zaktualizowany'];
    }

    public function updateProfile($userId, $username, $bio) {
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ? AND id != ?",
            [$username, $userId]
        );

        if ($existing) {
            return ['success' => false, 'message' => 'Ta nazwa użytkownika jest już zajęta'];
        }

        $this->db->update('users', ['username' => $username, 'bio' => $bio], 'id = ?', [$userId]);
        if (isset($_SESSION['cached_user'])) {
            $_SESSION['cached_user']['username'] = $username;
            $_SESSION['cached_user']['bio'] = $bio;
        }

        return ['success' => true, 'message' => 'Profil zaktualizowany'];
    }

    public function updatePrivacy($userId, $isPrivate) {
        $this->db->update('users', ['is_private' => $isPrivate ? 1 : 0], 'id = ?', [$userId]);
        if (isset($_SESSION['cached_user'])) {
            $_SESSION['cached_user']['is_private'] = $isPrivate ? 1 : 0;
        }

        return ['success' => true, 'message' => 'Ustawienia prywatności zaktualizowane'];
    }

    public function deleteAccount($userId, $password) {
        $user = $this->db->fetchOne(
            "SELECT password_hash FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Nieprawidłowe hasło'];
        }
        $this->db->delete('users', 'id = ?', [$userId]);
        $this->logout();

        return ['success' => true, 'message' => 'Konto usunięte'];
    }

}
