<?php
class Config {
    private static $config = [];

    public static function load() {
        if (empty(self::$config)) {
            $envFile = __DIR__ . '/../.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line, 2);
                        self::$config[trim($key)] = trim($value);
                    }
                }
            }
        }
        return self::$config;
    }

    public static function get($key, $default = null) {
        self::load();
        return self::$config[$key] ?? $default;
    }
}
