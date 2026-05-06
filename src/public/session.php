<?php
if (session_status() !== PHP_SESSION_NONE) {
    error_log('session.php loaded after session_start() — Redis session handler NOT applied');
    return;
}
$redisHost = getenv('DB_REDIS_HOST') ?: 'redis';
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', "tcp://{$redisHost}:6379");
