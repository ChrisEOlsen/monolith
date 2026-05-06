<?php
$redisHost = getenv('DB_REDIS_HOST') ?: 'redis';
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', "tcp://{$redisHost}:6379");
