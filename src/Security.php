<?php
class Security {
    public static function sanitizeOutput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeOutput'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return strip_tags(trim($data));
    }

    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function setSecurityHeaders() {
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    }

    public static function validateJsonInput() {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            http_response_code(400);
            echo json_encode(['error' => 'Nieprawidłowy Content-Type']);
            exit;
        }
    }

    public static function rateLimitCheck($identifier, $maxRequests = 60, $window = 60) {
        $key = 'rate_limit_' . md5($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => time()
            ];
            return true;
        }
        
        $data = $_SESSION[$key];
        
        if (time() - $data['start_time'] > $window) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => time()
            ];
            return true;
        }
        
        if ($data['count'] >= $maxRequests) {
            return false;
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }

    public static function checkAuthRateLimit($ip, $username) {
        $key = 'auth_limit_' . md5($ip . '_' . $username);
        $maxAttempts = 5;
        $blockTime = 300; // 5 minutes
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'blocked_until' => 0
            ];
        }
        
        $data = $_SESSION[$key];
        
        // Check if currently blocked
        if ($data['blocked_until'] > time()) {
            return [
                'allowed' => false,
                'blocked_until' => $data['blocked_until']
            ];
        }
        
        // Increment attempts
        $_SESSION[$key]['attempts']++;
        
        // Block if too many attempts
        if ($_SESSION[$key]['attempts'] >= $maxAttempts) {
            $_SESSION[$key]['blocked_until'] = time() + $blockTime;
            $_SESSION[$key]['attempts'] = 0;
            return [
                'allowed' => false,
                'blocked_until' => time() + $blockTime
            ];
        }
        
        return ['allowed' => true];
    }
}
