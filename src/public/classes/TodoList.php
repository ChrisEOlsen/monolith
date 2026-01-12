<?php

class TodoList {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO todo_lists (title) VALUES (:title)";
        $stmt = $this->pdo->prepare($sql);
        
        
        
        $stmt->bindValue(':title', $data['title']);
        
        
        
        return $stmt->execute();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM todo_lists");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM todo_lists WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM todo_lists WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}