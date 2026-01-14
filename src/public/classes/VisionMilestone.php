<?php

class VisionMilestone {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($data) {
        $sql = "INSERT INTO vision_milestones (goal_id, title, is_done) VALUES (:goal_id, :title, :is_done)";
        $stmt = $this->pdo->prepare($sql);
        
        
        
        $stmt->bindValue(':goal_id', $data['goal_id']);
        
        
        
        $stmt->bindValue(':title', $data['title']);
        
        
        
        $stmt->bindValue(':is_done', $data['is_done']);
        
        
        
        return $stmt->execute();
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM vision_milestones");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM vision_milestones WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM vision_milestones WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}