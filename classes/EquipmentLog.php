<?php
require_once 'Database.php';
require_once 'BaseModel.php';

class EquipmentLog extends BaseModel {
    public function __construct() {
        $db = new Database();
        parent::__construct($db->getConnection(), 'equipment_logs');
    }

    // Encapsulation: Getter for conn (if needed)
    public function getConn() {
        return $this->conn;
    }

    // Implement CRUD methods
    public function getAll() {
        return $this->getAllLogs();
    }
    public function getById($id) {
        // Implement as needed
        return null;
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

    public function getAllLogs() {
        $stmt = $this->conn->prepare("SELECT * FROM equipment_logs ORDER BY timestamp DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
