<?php
require_once 'LabReservation.php';
require_once 'Database.php';

class LabReservationService {
    private $labReservationModel;
    private $db;

    public function __construct() {
        $this->labReservationModel = new LabReservation();
        $this->db = (new Database())->getConnection();
    }

    /**
     * Get all lab reservations
     * @return array
     * What: Fetches all lab reservations
     * Why: For management/overview
     * How: Calls LabReservation::getAllReservations
     */
    public function getAllReservations() {
        return $this->labReservationModel->getAllReservations();
    }

    /**
     * Approve a lab reservation
     * @param int $id
     * @return bool
     * What: Approves a lab reservation
     * Why: For admin approval
     * How: Updates status
     */
    public function approveReservation($id) {
        $stmt = $this->db->prepare("UPDATE lab_reservations SET status = 'Approved' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Deny a lab reservation
     * @param int $id
     * @param string $remarks
     * @return bool
     * What: Denies a lab reservation
     * Why: For admin denial
     * How: Updates status and remarks
     */
    public function denyReservation($id, $remarks = '') {
        // Use 'Rejected' to match ENUM values in database
        $stmt = $this->db->prepare("UPDATE lab_reservations SET status = 'Rejected', remarks = ? WHERE id = ?");
        return $stmt->execute([$remarks, $id]);
    }

    /**
     * Count all pending lab reservations
     * @return int
     * What: Returns the total number of pending lab reservations
     * Why: For dashboard KPI
     * How: Calls LabReservation::countPendingReservations
     */
    public function countPendingReservations() {
        return $this->labReservationModel->countPendingReservations();
    }
} 