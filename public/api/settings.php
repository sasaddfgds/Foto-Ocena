<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['user' => $user]);
} else {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'change_password':
                $result = $auth->changePassword(
                    $user['id'],
                    $data['current_password'],
                    $data['new_password']
                );
                break;

            case 'update_profile':
                $result = $auth->updateProfile(
                    $user['id'],
                    $data['username'],
                    $data['bio'] ?? ''
                );
                break;

            case 'update_privacy':
                $result = $auth->updatePrivacy(
                    $user['id'],
                    $data['is_private']
                );
                break;

            case 'delete_account':
                $result = $auth->deleteAccount(
                    $user['id'],
                    $data['password']
                );
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Nieprawidłowa akcja']);
                exit;
        }

        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['message']]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
    }
}
