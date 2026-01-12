<?php

class LogEntry {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO log_entries (category_id, entry_data) VALUES (:category_id, :entry_data)";
        $stmt = $this->pdo->prepare($sql);
        
        
        
        $stmt->bindValue(':category_id', $data['category_id']);
        
        
        
        $stmt->bindValue(':entry_data', $data['entry_data']);
        
        
        
        return $stmt->execute();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM log_entries");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM log_entries WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM log_entries WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}