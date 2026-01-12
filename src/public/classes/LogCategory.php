<?php

class LogCategory {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO log_categories (title, schema_def) VALUES (:title, :schema_def)";
        $stmt = $this->pdo->prepare($sql);
        
        
        
        $stmt->bindValue(':title', $data['title']);
        
        
        
        $stmt->bindValue(':schema_def', $data['schema_def']);
        
        
        
        return $stmt->execute();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM log_categories");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM log_categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM log_categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}