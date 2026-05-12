<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda nie dozwolona']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak wymaganych pól']);
        exit;
    }

    $username = trim($data['username']);
    $password = $data['password'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Check auth rate limit
    $rateLimitCheck = Security::checkAuthRateLimit($ip, $username);
    if (!$rateLimitCheck['allowed']) {
        http_response_code(429);
        $waitTime = ceil(($rateLimitCheck['blocked_until'] - time()) / 60);
        echo json_encode(['error' => "Zbyt wiele prób. Spróbuj ponownie za {$waitTime} minut."]);
        exit;
    }

    if (strlen($username) < 3 || strlen($username) > 50) {
        http_response_code(400);
        echo json_encode(['error' => 'Nazwa użytkownika musi mieć od 3 do 50 znaków']);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Hasło musi mieć minimum 6 znaków']);
        exit;
    }

    $auth = new Auth();
    $result = $auth->register($username, $password);

    if ($result['success']) {
        $user = $auth->getCurrentUser();
        http_response_code(201);
        echo json_encode([
            'message' => 'Rejestracja zakończona pomyślnie',
            'user_id' => $result['user_id'],
            'user' => $user
        ]);
    } else {
        http_response_code(409);
        echo json_encode(['error' => $result['message']]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
}
