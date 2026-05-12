<?php
session_start();
ob_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../src/Auth.php';
    require_once __DIR__ . '/../../src/ImageHandler.php';
    require_once __DIR__ . '/../../src/RateLimiter.php';
    require_once __DIR__ . '/../../src/Security.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(['error' => 'Metoda nie dozwolona']);
        exit;
    }

    $auth = new Auth();
    $user = $auth->getCurrentUser();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Nieautoryzowany dostęp']);
        exit;
    }

    $rateLimiter = new RateLimiter();
    $check = $rateLimiter->check($user['id'], 'upload');

    if (!$check['allowed']) {
        ob_end_clean();
        http_response_code(429);
        echo json_encode([
            'error' => 'Przekroczono limit przesłań. Maksymalnie 10 zdjęć na minutę.',
            'reset_time' => $check['reset_time']
        ]);
        exit;
    }

    if (!isset($_FILES['image'])) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Brak pliku']);
        exit;
    }

    $imageHandler = new ImageHandler();
    $result = $imageHandler->upload($_FILES['image'], $user['id']);

    if ($result['success']) {
        ob_end_clean();
        echo json_encode([
            'message' => 'Zdjęcie przesłane pomyślnie',
            'image' => $result
        ]);
    } else {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['error' => $result['message']]);
    }
} catch (Exception $e) {
    ob_end_clean();
    error_log("Upload error");
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera']);
}
