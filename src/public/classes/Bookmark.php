<?php

class Bookmark {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO bookmarks (category_id, title, url, description) VALUES (:category_id, :title, :url, :description)";
        $stmt = $this->pdo->prepare($sql);
        
        
        
        $stmt->bindValue(':category_id', $data['category_id']);
        
        
        
        $stmt->bindValue(':title', $data['title']);
        
        
        
        $stmt->bindValue(':url', $data['url']);
        
        
        
        $stmt->bindValue(':description', $data['description']);
        
        
        
        return $stmt->execute();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM bookmarks");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM bookmarks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM bookmarks WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}