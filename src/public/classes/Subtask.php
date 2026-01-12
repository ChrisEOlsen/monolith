<?php

class Subtask {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO subtasks (title, is_done, todo_id) VALUES (:title, :is_done, :todo_id)";
        $stmt = $this->pdo->prepare($sql);
        
        
        
        $stmt->bindValue(':title', $data['title']);
        
        
        
        $stmt->bindValue(':is_done', $data['is_done']);
        
        
        
        $stmt->bindValue(':todo_id', $data['todo_id']);
        
        
        
        return $stmt->execute();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM subtasks");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM subtasks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM subtasks WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}