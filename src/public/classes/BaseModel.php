<?php
require_once __DIR__ . '/../redis.php';
require_once __DIR__ . '/../cache.php';

class BaseModel {
    protected $pdo;
    protected $cache;
    protected $table;

    public function __construct($pdo, $redis) {
        $this->pdo = $pdo;
        $this->cache = new Cache($redis);
    }

    public function getAll(): array {
        $key = "resource:{$this->table}:all";
        $cached = $this->cache->get($key);
        if ($cached !== null) return $cached;
        $stmt = $this->pdo->query("SELECT * FROM `{$this->table}`");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->cache->set($key, $result);
        return $result;
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM `{$this->table}` WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, array $data): bool {
        $fields = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) continue;
            $fields[] = "`$key` = :$key";
            $params[$key] = $value;
        }
        if (empty($fields)) return false;
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);
        if ($result) $this->bust("resource:{$this->table}:all");
        return $result;
    }

    public function delete($id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->table}` WHERE id = :id");
        $result = $stmt->execute(['id' => $id]);
        if ($result) $this->bust("resource:{$this->table}:all");
        return $result;
    }

    public function bust(string $key): void {
        $this->cache->bust($key);
    }
}
