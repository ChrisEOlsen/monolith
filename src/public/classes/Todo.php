<?php

class Todo {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO todos (title, is_done, list_id) VALUES (:title, :is_done, :list_id)";
        $stmt = $this->pdo->prepare($sql);
        
        
        
        $stmt->bindValue(':title', $data['title']);
        
        
        
        $stmt->bindValue(':is_done', $data['is_done']);
        
        
        
        $stmt->bindValue(':list_id', $data['list_id']);
        
        
        
        return $stmt->execute();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM todos");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM todos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM todos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}