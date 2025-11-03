<?php
require_once 'Database.php';
require_once 'BaseModel.php';

class Lab extends BaseModel {
    public function __construct() {
        $database = new Database();
        parent::__construct($database->getConnection(), 'labs');
    }

    // Encapsulation: Getter for conn (if needed)
    public function getConn() {
        return $this->conn;
    }

    // Implement CRUD methods
    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name} WHERE id = :id LIMIT 1");
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function create($data) {
        // Implement as needed
        return false;
    }
    public function update($id, $data) {
        // Implement as needed
        return false;
    }
    public function delete($id) {
        // Implement as needed
        return false;
    }

    // ... (keep all existing methods, but use $this->table_name and $this->conn)
    // ... existing code ...
}
