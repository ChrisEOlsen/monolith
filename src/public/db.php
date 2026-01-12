<?php
$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'myapp';
$user = getenv('DB_USER') ?: 'user';
$pass = getenv('DB_PASS') ?: 'password';
$charset = 'utf8mb4';

// Set Timezone (Default to EST/EDT for this user)
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/New_York');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Hide connection details in production
    if (getenv('APP_ENV') === 'local') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        error_log($e->getMessage()); // Log to server logs
        die("Database connection failed. Please check the logs.");
    }
}
