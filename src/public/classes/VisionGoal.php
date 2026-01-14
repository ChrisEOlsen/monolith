<?php

class VisionGoal {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO vision_goals (category_id, title, image_url, target_year, is_achieved) VALUES (:category_id, :title, :image_url, :target_year, :is_achieved)";
        $stmt = $this->pdo->prepare($sql);
        
        
        
        $stmt->bindValue(':category_id', $data['category_id']);
        
        
        
        $stmt->bindValue(':title', $data['title']);
        
        
        
        $stmt->bindValue(':image_url', $data['image_url']);
        
        
        
        $stmt->bindValue(':target_year', $data['target_year']);
        
        
        
        $stmt->bindValue(':is_achieved', $data['is_achieved']);
        
        
        
        return $stmt->execute();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM vision_goals");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM vision_goals WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM vision_goals WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}