<?php
class Cache {
    private $redis;

    public function __construct($redis) {
        $this->redis = $redis;
    }

    public function get(string $key) {
        if (!$this->redis) return null;
        $value = $this->redis->get($key);
        return $value !== false ? json_decode($value, true) : null;
    }

    public function set(string $key, $value, int $ttl = 300): void {
        if (!$this->redis) return;
        $this->redis->setex($key, $ttl, json_encode($value));
    }

    public function bust(string $key): void {
        if (!$this->redis) return;
        $this->redis->del($key);
    }
}
