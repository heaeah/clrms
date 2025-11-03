<?php
require_once 'Database.php';
require_once 'BaseModel.php';

class Software extends BaseModel {
    public function __construct() {
        $database = new Database();
        parent::__construct($database->getConnection(), 'software');
    }

    // Encapsulation: Getter for conn (if needed)
    public function getConn() {
        return $this->conn;
    }

    // Implement CRUD methods
    public function getAll() {
        return $this->getAllSoftware();
    }
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name} WHERE id = :id LIMIT 1");
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function create($data) {
        return $this->addSoftware($data);
    }
    public function update($id, $data) {
        $data['id'] = $id;
        return $this->updateSoftware($data);
    }
    public function delete($id) {
        return $this->deleteSoftware($id);
    }

    // Get all software records
    public function getAllSoftware() {
        try {
            $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // What: Database error during getAllSoftware
            // Why: Query failure, connection issue, etc.
            // How: Log error and return empty array
            error_log('[Software getAllSoftware Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return [];
        }
    }

    // Get software licenses that are expired
    public function getExpiredLicenses() {
        try {
            $today = date('Y-m-d');
            $query = "SELECT * FROM " . $this->table_name . " WHERE license_expiry_date IS NOT NULL AND license_expiry_date < :today ORDER BY license_expiry_date ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[Software getExpiredLicenses Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return [];
        }
    }

    // Get software licenses that are expiring soon (within $days, but not expired)
    public function getExpiringLicenses($days = 30) {
        try {
            $today = date('Y-m-d');
            $future = date('Y-m-d', strtotime("+$days days"));
            $query = "SELECT * FROM " . $this->table_name . " WHERE license_expiry_date IS NOT NULL AND license_expiry_date >= :today AND license_expiry_date <= :future ORDER BY license_expiry_date ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':today', $today);
            $stmt->bindParam(':future', $future);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[Software getExpiringLicenses Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return [];
        }
    }

    // Add new software
    public function addSoftware($data) {
        try {
            $query = "INSERT INTO " . $this->table_name . " (name, lab_id, pc_number, installation_date, license_expiry_date, notes) VALUES (:name, :lab_id, :pc_number, :installation_date, :license_expiry_date, :notes)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':lab_id', $data['lab_id']);
            $stmt->bindParam(':pc_number', $data['pc_number']);
            $stmt->bindParam(':installation_date', $data['installation_date']);
            $stmt->bindParam(':license_expiry_date', $data['license_expiry_date']);
            $stmt->bindParam(':notes', $data['notes']);
            return $stmt->execute();
        } catch (PDOException $e) {
            // What: Database error during addSoftware
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[Software addSoftware Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    // Update software
    public function updateSoftware($data) {
        try {
            $query = "UPDATE " . $this->table_name . " SET name = :name, lab_id = :lab_id, pc_number = :pc_number, installation_date = :installation_date, license_expiry_date = :license_expiry_date, notes = :notes WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $data['id']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':lab_id', $data['lab_id']);
            $stmt->bindParam(':pc_number', $data['pc_number']);
            $stmt->bindParam(':installation_date', $data['installation_date']);
            $stmt->bindParam(':license_expiry_date', $data['license_expiry_date']);
            $stmt->bindParam(':notes', $data['notes']);
            return $stmt->execute();
        } catch (PDOException $e) {
            // What: Database error during updateSoftware
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[Software updateSoftware Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    // Delete software
    public function deleteSoftware($id) {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            // What: Database error during deleteSoftware
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[Software deleteSoftware Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    // Get all software with lab information
    public function getAllSoftwareWithLabInfo() {
        try {
            $query = "SELECT s.*, l.lab_name 
                      FROM " . $this->table_name . " s 
                      LEFT JOIN labs l ON s.lab_id = l.id 
                      ORDER BY s.id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[Software getAllSoftwareWithLabInfo Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return [];
        }
    }

    // Get software by lab
    public function getSoftwareByLab($labId) {
        try {
            $query = "SELECT s.*, l.lab_name 
                      FROM " . $this->table_name . " s 
                      LEFT JOIN labs l ON s.lab_id = l.id 
                      WHERE s.lab_id = :lab_id 
                      ORDER BY s.id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':lab_id', $labId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[Software getSoftwareByLab Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return [];
        }
    }

    // Get equipment from inventory for PC assignment
    public function getEquipmentFromInventory() {
        try {
            $query = "SELECT id, name, serial_number, model, location, category 
                      FROM equipment 
                      WHERE is_archived = 0 
                      ORDER BY name ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[Software getEquipmentFromInventory Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return [];
        }
    }
} 