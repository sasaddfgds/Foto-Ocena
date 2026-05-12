<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/database.php';

class ImageHandler {
    private $db;
    private $uploadDir;
    private $maxWidth;
    private $maxHeight;
    private $maxFileSize;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->uploadDir = __DIR__ . '/../' . Config::get('UPLOAD_DIR', 'uploads/');
        $this->maxWidth = (int)Config::get('MAX_IMAGE_WIDTH', 1920);
        $this->maxHeight = (int)Config::get('MAX_IMAGE_HEIGHT', 1080);
        $this->maxFileSize = (int)Config::get('MAX_UPLOAD_SIZE', 5242880);

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function upload($file, $userId) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Nieprawidłowy plik'];
        }

        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'message' => 'Plik jest za duży. Maksymalny rozmiar to 5MB'];
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Nieprawidłowy format pliku. Dozwolone: JPEG, PNG, GIF, WebP'];
        }

        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            return ['success' => false, 'message' => 'Nie można odczytać obrazu'];
        }

        $optimizedImage = $this->optimizeImage($file['tmp_name'], $imageInfo);

        if (!$optimizedImage) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('img_', true) . '.' . $extension;
            $filepath = $this->uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return ['success' => false, 'message' => 'Błąd podczas zapisywania pliku'];
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $fileSize = filesize($filepath);
        } else {
            $filename = uniqid('img_', true) . '.webp';
            $filepath = $this->uploadDir . $filename;
            imagewebp($optimizedImage, $filepath, 85);
            imagedestroy($optimizedImage);

            $width = imagesx($optimizedImage);
            $height = imagesy($optimizedImage);
            $fileSize = filesize($filepath);
        }

        $imageId = $this->db->insert('images', [
            'user_id' => $userId,
            'filename' => $filename,
            'original_filename' => basename($file['name']),
            'file_path' => 'uploads/' . $filename,
            'width' => $width,
            'height' => $height,
            'file_size' => $fileSize
        ]);

        // Clear cache after upload
        $this->clearPopularImagesCache();

        return [
            'success' => true,
            'image_id' => $imageId,
            'filename' => $filename,
            'file_path' => 'uploads/' . $filename,
            'width' => $width,
            'height' => $height
        ];
    }

    private function clearPopularImagesCache() {
        $cacheDir = __DIR__ . '/../cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/popular_images_*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    private function optimizeImage($filepath, $imageInfo) {
        $image = null;

        switch ($imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($filepath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($filepath);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($filepath);
                break;
            default:
                return null;
        }

        if (!$image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $this->autoCorrect($image);
        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);

            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            if ($imageInfo[2] == IMAGETYPE_PNG || $imageInfo[2] == IMAGETYPE_GIF) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $this->sharpenImage($newImage);

            imagedestroy($image);
            $image = $newImage;
        }

        return $image;
    }

    private function sharpenImage($image) {
        $width = imagesx($image);
        $height = imagesy($image);
        $sharpenMatrix = [
            [-1, -1, -1],
            [-1, 16, -1],
            [-1, -1, -1]
        ];

        $divisor = 8;
        $offset = 0;

        imageconvolution($image, $sharpenMatrix, $divisor, $offset);
    }

    private function autoCorrect($image) {
        $width = imagesx($image);
        $height = imagesy($image);
        $totalBrightness = 0;
        $pixelCount = 0;

        // Sample pixels to calculate average brightness (much faster than full scan)
        for ($x = 0; $x < $width; $x += 20) {
            for ($y = 0; $y < $height; $y += 20) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $brightness = ($r + $g + $b) / 3;
                $totalBrightness += $brightness;
                $pixelCount++;
            }
        }

        if ($pixelCount > 0) {
            $avgBrightness = $totalBrightness / $pixelCount;
            $targetBrightness = 128;
            $adjustment = ($targetBrightness - $avgBrightness) * 0.3;
            if (abs($adjustment) > 10) {
                // Use GD's built-in filter for brightness (much faster than pixel iteration)
                imagefilter($image, IMG_FILTER_BRIGHTNESS, (int)$adjustment);
            }
        }
    }

    public function getPopularImages($limit = 20, $offset = 0) {
        $cacheKey = "popular_images_{$limit}_{$offset}";
        $cacheFile = __DIR__ . '/../cache/' . $cacheKey . '.json';
        $cacheTime = 300; // 5 minutes

        // Check if cache exists and is valid
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
            $cached = file_get_contents($cacheFile);
            return json_decode($cached, true);
        }

        $sql = "
            SELECT i.*, u.username, u.avatar,
                   i.likes_count as likes,
                   i.dislikes_count as dislikes
            FROM images i
            JOIN users u ON i.user_id = u.id
            ORDER BY i.likes_count DESC, i.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $result = $this->db->fetchAll($sql, [$limit, $offset]);

        // Create cache directory if it doesn't exist
        $cacheDir = __DIR__ . '/../cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Save to cache
        file_put_contents($cacheFile, json_encode($result));

        return $result;
    }

    public function getUserImages($userId, $currentUserId = null) {
        // Check if user is private
        $user = $this->db->fetchOne("SELECT is_private FROM users WHERE id = ?", [$userId]);

        // If user is private and not the owner, return empty array
        if ($user && $user['is_private'] && $currentUserId != $userId) {
            return [];
        }

        $sql = "
            SELECT i.*, u.username, u.avatar,
                   i.likes_count as likes,
                   i.dislikes_count as dislikes
            FROM images i
            JOIN users u ON i.user_id = u.id
            WHERE i.user_id = ?
            ORDER BY i.created_at DESC
        ";
        return $this->db->fetchAll($sql, [$userId]);
    }

    public function getImageById($imageId, $currentUserId = null) {
        $sql = "
            SELECT i.*, u.username, u.avatar, u.is_private,
                   i.likes_count as likes,
                   i.dislikes_count as dislikes
            FROM images i
            JOIN users u ON i.user_id = u.id
            WHERE i.id = ?
        ";
        $image = $this->db->fetchOne($sql, [$imageId]);

        // If user is private and not the owner, return null
        if ($image && $image['is_private'] && $currentUserId != $image['user_id']) {
            return null;
        }

        return $image;
    }

    public function getUserStats($userId) {
        $sql = "
            SELECT 
                COUNT(*) as total_images,
                SUM(likes_count) as total_likes,
                SUM(dislikes_count) as total_dislikes
            FROM images
            WHERE user_id = ?
        ";
        return $this->db->fetchOne($sql, [$userId]);
    }
}
