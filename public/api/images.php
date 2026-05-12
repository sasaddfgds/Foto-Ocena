<?php
session_start();
ob_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda nie dozwolona']);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../src/Database.php';
    require_once __DIR__ . '/../../src/Auth.php';
    require_once __DIR__ . '/../../src/ImageHandler.php';

    $imageHandler = new ImageHandler();
    $type = $_GET['type'] ?? 'popular';
    $userId = $_GET['user_id'] ?? null;

    switch ($type) {
        case 'popular':
            $limit = (int)($_GET['limit'] ?? 20);
            $images = $imageHandler->getPopularImages($limit);
            ob_end_clean();
            echo json_encode(['images' => $images]);
            break;

        case 'user':
            if (!$userId) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Brak ID użytkownika']);
                exit;
            }
            $auth = new Auth();
            $currentUser = $auth->getCurrentUser();
            $images = $imageHandler->getUserImages($userId, $currentUser['id'] ?? null);
            ob_end_clean();
            echo json_encode(['images' => $images]);
            break;

        case 'single':
            $imageId = $_GET['id'] ?? null;
            if (!$imageId) {
                ob_end_clean();
                http_response_code(400);
                echo json_encode(['error' => 'Brak ID zdjęcia']);
                exit;
            }
            $auth = new Auth();
            $currentUser = $auth->getCurrentUser();
            $image = $imageHandler->getImageById($imageId, $currentUser['id'] ?? null);
            if (!$image) {
                ob_end_clean();
                http_response_code(404);
                echo json_encode(['error' => 'Zdjęcie nie znalezione']);
                exit;
            }
            ob_end_clean();
            echo json_encode(['image' => $image]);
            break;

        default:
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowy typ']);
            exit;
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Błąd serwera']);
}
