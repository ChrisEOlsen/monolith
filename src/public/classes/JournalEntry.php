<?php

class JournalEntry {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO journal_entries (title, content, mood, entry_date) VALUES (:title, :content, :mood, :entry_date)";
        $stmt = $this->pdo->prepare($sql);
        
        
        
        $stmt->bindValue(':title', $data['title']);
        
        
        
        $stmt->bindValue(':content', $data['content']);
        
        
        
        $stmt->bindValue(':mood', $data['mood']);
        
        
        
        $stmt->bindValue(':entry_date', $data['entry_date']);
        
        
        
        return $stmt->execute();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM journal_entries");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM journal_entries WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM journal_entries WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}