<?php
$redis = null;
try {
    $r = new Redis();
    $r->connect(getenv('DB_REDIS_HOST') ?: 'redis', 6379);
    $redis = $r;
} catch (Exception $e) {
    error_log('Redis connection failed: ' . $e->getMessage());
}
