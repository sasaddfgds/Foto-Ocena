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

$auth = new Auth();
$user = $auth->getCurrentUser();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Nieautoryzowany dostęp']);
    exit;
}

if (!isset($_FILES['avatar'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Brak pliku']);
    exit;
}

$file = $_FILES['avatar'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nieprawidłowy format pliku. Dozwolone: JPEG, PNG, GIF, WebP']);
    exit;
}

if ($file['size'] > 2097152) {
    http_response_code(400);
    echo json_encode(['error' => 'Plik jest za duży. Maksymalny rozmiar to 2MB']);
    exit;
}
$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $user['id'] . '_' . uniqid() . '.' . $extension;
$filepath = $uploadDir . $filename;
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd podczas zapisywania pliku']);
    exit;
}
if ($user['avatar']) {
    $oldAvatarPath = __DIR__ . '/../' . $user['avatar'];
    if (file_exists($oldAvatarPath)) {
        unlink($oldAvatarPath);
    }
}
$avatarPath = 'uploads/avatars/' . $filename;
$result = $auth->updateAvatar($user['id'], $avatarPath);

if ($result['success']) {
    echo json_encode([
        'message' => 'Avatar zaktualizowany',
        'avatar' => $avatarPath
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Błąd podczas aktualizacji avatara']);
}
