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

    $auth = new Auth();
    $result = $auth->login($username, $password);

    if ($result['success']) {
        echo json_encode([
            'message' => 'Zalogowano pomyślnie',
            'user' => $result['user']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => $result['message']]);
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
}
