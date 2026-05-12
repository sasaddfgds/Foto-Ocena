<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Database.php';

$db = Database::getInstance();
$db->query("DELETE FROM images");
$db->query("DELETE FROM likes");

echo "All images and likes deleted from database. <a href='index.php'>Go back</a>";
