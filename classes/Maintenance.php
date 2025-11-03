<?php
require_once 'Database.php';
require_once 'BaseModel.php';

class Maintenance extends BaseModel {
    public function __construct() {
        $database = new Database();
        parent::__construct($database->getConnection(), 'maintenance_records');
    }

    // Encapsulation: Getter for conn (if needed)
    public function getConn() {
        return $this->conn;
    }

    // Implement CRUD methods
    public function getAll() {
        return $this->getAllMaintenance();
    }
    public function getById($id) {
        // Implement as needed
        return null;
    }
    public function create($data) {
        return $this->addMaintenance($data);
    }
    public function update($id, $data) {
        // Implement as needed
        return false;
    }
    public function delete($id) {
        // Implement as needed
        return false;
    }

    public function updateMaintenance($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET equipment_id = :equipment_id, 
                      type = :type, 
                      issue_description = :issue_description, 
                      maintenance_date = :maintenance_date, 
                      due_date = :due_date, 
                      repair_status = :repair_status, 
                      notes = :notes 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':equipment_id', $data['equipment_id']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':issue_description', $data['issue_description']);
        $stmt->bindParam(':maintenance_date', $data['maintenance_date']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':repair_status', $data['repair_status']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':id', $id);

        $result = $stmt->execute();
        
        return $result;
    }

    public function addMaintenance($data) {
        $query = "INSERT INTO " . $this->table_name . " 
            (equipment_id, type, issue_description, maintenance_date, due_date, repair_status, notes) 
            VALUES 
            (:equipment_id, :type, :issue_description, :maintenance_date, :due_date, :repair_status, :notes)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':equipment_id', $data['equipment_id']);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':issue_description', $data['issue_description']);
        $stmt->bindParam(':maintenance_date', $data['maintenance_date']);
        $stmt->bindParam(':due_date', $data['due_date']);
        $stmt->bindParam(':repair_status', $data['repair_status']);
        $stmt->bindParam(':notes', $data['notes']);

        $result = $stmt->execute();
        
        if ($result) {
            require_once __DIR__ . '/EquipmentService.php';
            $equipmentService = new EquipmentService();
            $equipmentService->updateLastMaintenanceDate($data['equipment_id'], $data['maintenance_date']);
        }
        return $result;
    }

    public function getAllMaintenance() {
        $query = "SELECT m.*, e.name AS equipment_name, e.location AS equipment_location 
                  FROM " . $this->table_name . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  ORDER BY m.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMaintenanceById($id) {
        $query = "SELECT m.*, e.name AS equipment_name 
                  FROM " . $this->table_name . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  WHERE m.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countMaintenanceDue() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM " . $this->table_name . " WHERE repair_status = 'Pending'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getDueMaintenance() {
        $today = date('Y-m-d');
        $query = "SELECT m.*, e.name AS equipment_name FROM " . $this->table_name . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  WHERE m.due_date IS NOT NULL AND m.due_date <= :today AND m.repair_status != 'Completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOverdueMaintenance() {
        $today = date('Y-m-d');
        $query = "SELECT m.*, e.name AS equipment_name FROM " . $this->table_name . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  WHERE m.due_date IS NOT NULL AND m.due_date < :today AND m.repair_status != 'Completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get maintenance records by type (Maintenance or Repair)
     * @param string $type
     * @return array
     */
    public function getMaintenanceByType($type) {
        $query = "SELECT m.*, e.name AS equipment_name, e.serial_number, e.location AS equipment_location 
                  FROM " . $this->table_name . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  WHERE m.type = :type
                  ORDER BY m.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':type', $type);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all maintenance records with a due date (for calendar events)
    public function getAllDueDates() {
        $query = "SELECT m.*, e.name AS equipment_name FROM " . $this->table_name . " m JOIN equipment e ON m.equipment_id = e.id WHERE m.due_date IS NOT NULL ORDER BY m.due_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all maintenance logs
     * @return array
     * What: Fetches all maintenance logs
     * Why: For audit/history
     * How: Query maintenance_logs table (example)
     */
    public function getAllLogs() {
        $query = "SELECT * FROM maintenance_logs ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get maintenance due today
    public function getDueToday() {
        $today = date('Y-m-d');
        $query = "SELECT m.*, e.name AS equipment_name FROM " . $this->table_name . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  WHERE m.due_date = :today AND m.repair_status != 'Completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get overdue maintenance (due date before today)
    public function getOverdue() {
        $today = date('Y-m-d');
        $query = "SELECT m.*, e.name AS equipment_name FROM " . $this->table_name . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  WHERE m.due_date < :today AND m.repair_status != 'Completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get upcoming maintenance (due date within next 7 days)
    public function getUpcoming($days = 7) {
        $today = date('Y-m-d');
        $future = date('Y-m-d', strtotime("+$days days"));
        $query = "SELECT m.*, e.name AS equipment_name FROM " . $this->table_name . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  WHERE m.due_date > :today AND m.due_date <= :future AND m.repair_status != 'Completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->bindParam(':future', $future);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get expired maintenance (if applicable, e.g., for licenses)
    public function getExpired() {
        $today = date('Y-m-d');
        $query = "SELECT m.*, e.name AS equipment_name FROM " . $this->table_name . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  WHERE m.due_date < :today AND m.repair_status = 'Expired'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get maintenance records by equipment ID
    public function getMaintenanceByEquipmentId($equipmentId) {
        $query = "SELECT m.*, e.name AS equipment_name 
                  FROM " . $this->table_name . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  WHERE m.equipment_id = :equipment_id
                  ORDER BY m.maintenance_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':equipment_id', $equipmentId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}