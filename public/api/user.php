<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda nie dozwolona']);
    exit;
}

$auth = new Auth();
$user = $auth->getCurrentUser();

if ($user) {
    echo json_encode(['user' => $user]);
} else {
    echo json_encode(['user' => null]);
}
