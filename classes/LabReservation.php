<?php
require_once 'Database.php';

class LabReservation {
    private $conn;
    private $table_name = "lab_reservations";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createReservation($data, $userId)
    {
        // Sanitize input data
        $labId = $data['lab_id'];
        $dateRequested = $data['date_requested'];
        $datetimeNeeded = $data['datetime_needed'];
        $purpose = $data['purpose'];
        $neededTools = $data['needed_tools'];
        $equipment = $data['equipment'];
        $software = $data['software'];
        $requestedBy = $data['requested_by'];
        $notedBy = $data['noted_by'];
        $approvedBy = $data['approved_by'];

        // Prepare and execute the SQL query
        $query = "INSERT INTO lab_reservations (lab_id, date_requested, datetime_needed, purpose, needed_tools, equipment, software, requested_by, noted_by, approved_by, user_id) 
              VALUES (:lab_id, :date_requested, :datetime_needed, :purpose, :needed_tools, :equipment, :software, :requested_by, :noted_by, :approved_by, :user_id)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':lab_id' => $labId,
            ':date_requested' => $dateRequested,
            ':datetime_needed' => $datetimeNeeded,
            ':purpose' => $purpose,
            ':needed_tools' => $neededTools,
            ':equipment' => $equipment,
            ':software' => $software,
            ':requested_by' => $requestedBy,
            ':noted_by' => $notedBy,
            ':approved_by' => $approvedBy,
            ':user_id' => $userId,
        ]);
    }


    public function getUserReservations($user_id) {
        $query = "SELECT r.*, l.lab_name 
                  FROM " . $this->table_name . " r
                  JOIN labs l ON r.lab_id = l.id
                  WHERE r.user_id = :user_id
                  ORDER BY r.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function updateReservationStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET status = :status WHERE id = :id");
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function getAllReservations() {
        $query = "SELECT r.*, u.username, l.lab_name, b.name as borrower_name, b.type as borrower_type_name
              FROM " . $this->table_name . " r
              JOIN users u ON r.user_id = u.id
              JOIN labs l ON r.lab_id = l.id
              LEFT JOIN borrowers b ON r.borrower_id = b.id
              ORDER BY r.id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPendingReservations() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM " . $this->table_name . " WHERE status = 'Pending'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    public function getAllRequests() {
        $stmt = $this->conn->query("SELECT * FROM borrow_requests ORDER BY date_requested DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

// For calendar integration
    public function getCalendarEvents() {
        $stmt = $this->conn->query("SELECT 
            CONCAT('Lab: ', l.lab_name, ' - ', r.purpose) AS title,
            r.reservation_start,
            r.reservation_end
            FROM lab_reservations r
            JOIN labs l ON r.lab_id = l.id
            WHERE r.status = 'Approved'");

        $events = [];
        $now = new DateTime();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Check if the reservation has finished
            $reservationEnd = new DateTime($row['reservation_end']);
            
            // Only add to calendar if the reservation hasn't finished yet
            if ($reservationEnd > $now) {
                $events[] = [
                    'title' => $row['title'],
                    'start' => $row['reservation_start'],
                    'end' => $row['reservation_end'],
                    'color' => '#43a047'
                ];
            }
        }
        return $events;
    }

}