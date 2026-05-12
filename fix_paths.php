<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/Database.php';

$db = Database::getInstance();
$db->query("UPDATE images SET file_path = REPLACE(file_path, '../uploads/', 'uploads/')");

echo "File paths updated in database.\n";
