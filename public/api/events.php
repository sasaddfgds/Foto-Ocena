<?php
session_start();
ob_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

$auth = new Auth();
$user = $auth->getCurrentUser();

if (!$user) {
    ob_end_clean();
    echo "data: " . json_encode(['error' => 'Unauthorized']) . "\n\n";
    exit;
}

$db = Database::getInstance();
$lastCheck = time();
$maxRuntime = 300;
$startTime = time();

while (true) {
    if (time() - $startTime > $maxRuntime) {
        break;
    }
    $newLikes = $db->fetchAll("
        SELECT
            l.image_id,
            i.user_id as image_owner,
            l.is_like,
            i.likes_count as total_likes,
            i.dislikes_count as total_dislikes
        FROM likes l
        JOIN images i ON l.image_id = i.id
        WHERE l.created_at > datetime('now', '-5 seconds')
        ORDER BY l.created_at DESC
        LIMIT 10
    ");

    if (!empty($newLikes)) {
        ob_end_clean();
        echo "data: " . json_encode(['type' => 'likes_update', 'data' => $newLikes]) . "\n\n";
        ob_flush();
        flush();
        ob_start();
    }
    $newImages = $db->fetchAll("
        SELECT i.*, u.username
        FROM images i
        JOIN users u ON i.user_id = u.id
        WHERE i.created_at > datetime('now', '-5 seconds')
        ORDER BY i.created_at DESC
        LIMIT 5
    ");

    if (!empty($newImages)) {
        ob_end_clean();
        echo "data: " . json_encode(['type' => 'new_images', 'data' => $newImages]) . "\n\n";
        ob_flush();
        flush();
        ob_start();
    }
    if (time() - $lastCheck > 15) {
        ob_end_clean();
        echo ": keepalive\n\n";
        ob_flush();
        flush();
        ob_start();
        $lastCheck = time();
    }
    if (connection_aborted()) {
        break;
    }

    sleep(2);
}

ob_end_clean();
