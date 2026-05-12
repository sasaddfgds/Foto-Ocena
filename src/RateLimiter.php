<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/database.php';

class RateLimiter {
    private $db;
    private $maxRequests;
    private $windowSeconds;

    public function __construct($action = 'upload') {
        $this->db = Database::getInstance();
        $this->maxRequests = (int)Config::get('RATE_LIMIT_UPLOADS', 10);
        $this->windowSeconds = (int)Config::get('RATE_LIMIT_WINDOW', 60);
    }

    public function check($userId, $action = 'upload') {
        $windowStart = "datetime('now', '-{$this->windowSeconds} seconds')";
        $this->db->query("DELETE FROM rate_limits WHERE window_start < {$windowStart}");
        $current = $this->db->fetchOne(
            "SELECT * FROM rate_limits WHERE user_id = ? AND action = ? AND window_start >= {$windowStart}",
            [$userId, $action]
        );

        if (!$current) {
            $this->db->query(
                "INSERT INTO rate_limits (user_id, action, request_count, window_start) VALUES (?, ?, 1, datetime('now'))",
                [$userId, $action]
            );
            return ['allowed' => true, 'remaining' => $this->maxRequests - 1];
        }

        if ($current['request_count'] >= $this->maxRequests) {
            $resetTime = $this->db->fetchOne("SELECT datetime(window_start, '+{$this->windowSeconds} seconds') as reset_time FROM rate_limits WHERE id = ?", [$current['id']]);
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset_time' => strtotime($resetTime['reset_time'])
            ];
        }

        $this->db->query(
            "UPDATE rate_limits SET request_count = request_count + 1 WHERE id = ?",
            [$current['id']]
        );

        return [
            'allowed' => true,
            'remaining' => $this->maxRequests - $current['request_count'] - 1
        ];
    }
}
