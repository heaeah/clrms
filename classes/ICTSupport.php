<?php
require_once 'Database.php';
require_once 'BaseModel.php';

class ICTSupport extends BaseModel {
    public function __construct() {
        $database = new Database();
        parent::__construct($database->getConnection(), 'ict_support_requests');
    }

    // Encapsulation: Getter for conn (if needed)
    public function getConn() {
        return $this->conn;
    }

    // Implement CRUD methods
    public function getAll() {
        return $this->getAllRequests();
    }
    public function getById($id) {
        // Implement as needed
        return null;
    }
    public function create($data) {
        return $this->addRequest($data);
    }
    public function update($id, $data) {
        // Implement as needed
        return false;
    }
    public function delete($id) {
        return $this->deleteRequest($id);
    }

    // Add a new ICT support/job accomplishment request
    public function addRequest($data) {
        $query = "INSERT INTO " . $this->table_name . " (requester_name, department, request_date, request_time, nature_of_request, action_taken, photo) VALUES (:requester_name, :department, :request_date, :request_time, :nature_of_request, :action_taken, :photo)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':requester_name', $data['requester_name']);
        $stmt->bindParam(':department', $data['department']);
        $stmt->bindParam(':request_date', $data['request_date']);
        $stmt->bindParam(':request_time', $data['request_time']);
        $stmt->bindParam(':nature_of_request', $data['nature_of_request']);
        $stmt->bindParam(':action_taken', $data['action_taken']);
        $stmt->bindParam(':photo', $data['photo']);
        return $stmt->execute();
    }

    // Get all requests
    public function getAllRequests() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delete a request
    public function deleteRequest($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
} 