<?php
require_once 'BorrowRequest.php';
require_once 'Database.php';

class BorrowRequestService {
    private $borrowRequestModel;
    private $db;

    public function __construct() {
        $this->borrowRequestModel = new BorrowRequest();
        $this->db = (new Database())->getConnection();
    }

    /**
     * Get all borrow requests
     * @return array
     * What: Fetches all borrow requests
     * Why: For management/overview
     * How: Calls BorrowRequest::getAllBorrowRequests
     */
    public function getAllBorrowRequests() {
        return $this->borrowRequestModel->getAllBorrowRequests();
    }

    /**
     * Approve a borrow request and mark equipment as borrowed
     * @param int $id
     * @return bool
     * What: Approves a borrow request
     * Why: For admin approval
     * How: Updates status and equipment
     */
    public function approveRequest($id) {
        $this->borrowRequestModel->updateStatus($id, 'Approved');
        $stmt = $this->db->prepare("SELECT equipment_id FROM borrow_request_items WHERE request_id = ?");
        $stmt->execute([$id]);
        $equipIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($equipIds) {
            $in = implode(',', array_fill(0, count($equipIds), '?'));
            $stmt = $this->db->prepare("UPDATE equipment SET status = 'Borrowed' WHERE id IN ($in)");
            $stmt->execute($equipIds);
        }
        return true;
    }

    /**
     * Deny a borrow request
     * @param int $id
     * @param string $remarks
     * @return bool
     * What: Denies a borrow request
     * Why: For admin denial
     * How: Updates status and remarks
     */
    public function denyRequest($id, $remarks = '') {
        return $this->borrowRequestModel->updateStatus($id, 'Denied', $remarks);
    }

    /**
     * Mark a borrow request as returned and update equipment status
     * @param int $id
     * @param string $return_date
     * @return bool
     * What: Marks a request as returned
     * Why: For return processing
     * How: Updates status, return date, and equipment
     */
    public function markReturned($id, $return_date) {
        $stmt = $this->db->prepare("UPDATE borrow_requests SET status = 'Returned', return_date = ? WHERE id = ?");
        $stmt->execute([$return_date, $id]);
        $stmt = $this->db->prepare("SELECT equipment_id FROM borrow_request_items WHERE request_id = ?");
        $stmt->execute([$id]);
        $equipIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($equipIds) {
            $in = implode(',', array_fill(0, count($equipIds), '?'));
            $stmt = $this->db->prepare("UPDATE equipment SET status = 'Available' WHERE id IN ($in)");
            $stmt->execute($equipIds);
        }
        return true;
    }

    /**
     * Update status of a borrow request (generic)
     * @param int $id
     * @param string $status
     * @param string|null $remarks
     * @return bool
     * What: Updates status of a borrow request
     * Why: For status changes
     * How: Calls BorrowRequest::updateStatus
     */
    public function updateStatus($id, $status, $remarks = null) {
        return $this->borrowRequestModel->updateStatus($id, $status, $remarks);
    }

    /**
     * Count all borrowed items
     * @return int
     * What: Returns the total number of borrowed items
     * Why: For dashboard KPI
     * How: Calls BorrowRequest::countBorrowedItems
     */
    public function countBorrowedItems() {
        return $this->borrowRequestModel->countBorrowedItems();
    }
} 