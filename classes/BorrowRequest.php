<?php
require_once 'Database.php';
require_once 'BaseModel.php';

class BorrowRequest extends BaseModel {
    protected $table_name = "borrow_requests";

    public function __construct() {
        $database = new Database();
        parent::__construct($database->getConnection(), 'borrow_requests');
        
        // Define validation rules for borrow request data
        $this->validation_rules = [
            'control_number' => [
                'type' => 'integer',
                'min' => 1,
                'max' => 999999,
                'default' => 1
            ],
            'date_requested' => [
                'type' => 'date',
                'default' => null
            ],
            'borrower_name' => [
                'type' => 'string',
                'maxLength' => 100,
                'default' => 'Unknown Borrower'
            ],
            'borrower_email' => [
                'type' => 'email',
                'default' => ''
            ],
            'course_year' => [
                'type' => 'string',
                'maxLength' => 50,
                'default' => ''
            ],
            'subject' => [
                'type' => 'string',
                'maxLength' => 100,
                'default' => ''
            ],
            'purpose' => [
                'type' => 'string',
                'maxLength' => 255,
                'default' => 'No purpose specified'
            ],
            'location_of_use' => [
                'type' => 'string',
                'maxLength' => 255,
                'default' => 'Unknown Location'
            ],
            'borrow_start' => [
                'type' => 'date',
                'default' => null
            ],
            'borrow_end' => [
                'type' => 'date',
                'default' => null
            ],
            'status' => [
                'type' => 'enum',
                'allowedValues' => ['Pending', 'Approved', 'Rejected', 'Returned'],
                'default' => 'Pending'
            ],
            'remarks' => [
                'type' => 'string',
                'maxLength' => 1000,
                'default' => ''
            ],
            'released_by' => [
                'type' => 'string',
                'maxLength' => 100,
                'default' => ''
            ]
        ];
    }

    // Encapsulation: Getter for conn (if needed)
    public function getConn() {
        return $this->conn;
    }

    // Implement CRUD methods
    public function getAll() {
        return $this->getAllBorrowRequests();
    }
    public function getById($id) {
        // Implement as needed
        return null;
    }
    public function create($data) {
        return $this->createBorrowRequest($data, $data['user_id']);
    }
    public function update($id, $data) {
        // Implement as needed
        return false;
    }
    public function delete($id) {
        // Implement as needed
        return false;
    }

    public function createBorrowRequest($data, $user_id) {
        // Validate and sanitize input data
        $validatedData = $this->validateData($data);
        
        // Additional validation: required fields
        if (empty($validatedData['borrower_name']) || empty($validatedData['purpose'])) {
            error_log('[BorrowRequest] Validation failed: borrower_name or purpose is empty', 3, __DIR__ . '/../logs/error.log');
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
        (control_number, date_requested, borrower_name, course_year, subject, teacher_signature, 
         datetime_needed, released_by, quantity, description,
         user_id, equipment_id, purpose, location_of_use, borrow_date, return_date, status) 
        VALUES 
        (:control_number, :date_requested, :borrower_name, :course_year, :subject, :teacher_signature, 
         :datetime_needed, :released_by, :quantity, :description,
         :user_id, :equipment_id, :purpose, :location_of_use, :borrow_date, :return_date, 'Pending')";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':control_number', $validatedData['control_number']);
        $stmt->bindParam(':date_requested', $validatedData['date_requested']);
        $stmt->bindParam(':borrower_name', $validatedData['borrower_name']);
        $stmt->bindParam(':course_year', $validatedData['course_year']);
        $stmt->bindParam(':subject', $validatedData['subject']);
        $stmt->bindParam(':teacher_signature', $data['teacher_signature'] ?? null);
        $stmt->bindParam(':datetime_needed', $data['datetime_needed'] ?? null);
        $stmt->bindParam(':released_by', $validatedData['released_by']);
        $stmt->bindParam(':quantity', $data['quantity'] ?? 1);
        $stmt->bindParam(':description', $data['description'] ?? null);

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':equipment_id', $data['equipment_id'] ?? null);
        $stmt->bindParam(':purpose', $validatedData['purpose']);
        $stmt->bindParam(':location_of_use', $validatedData['location_of_use']);
        $stmt->bindParam(':borrow_date', $data['borrow_date'] ?? null);
        $stmt->bindParam(':return_date', $data['return_date'] ?? null);

        $result = $stmt->execute();
        
        if ($result) {
            $requestId = $this->conn->lastInsertId();
            error_log('[BorrowRequest] Successfully created borrow request ID: ' . $requestId, 3, __DIR__ . '/../logs/error.log');
        } else {
            error_log('[BorrowRequest] Failed to create borrow request: ' . implode(', ', $stmt->errorInfo()), 3, __DIR__ . '/../logs/error.log');
        }
        
        return $result;
    }


    public function getUserBorrowRequests($user_id) {
        $stmt = $this->conn->prepare("SELECT br.*, e.name AS equipment_name
                                FROM borrow_requests br
                                JOIN equipment e ON br.equipment_id = e.id
                                WHERE br.user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status, $remarks = null) {
        if ($remarks !== null) {
            $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET status = :status, remarks = :remarks WHERE id = :id");
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":remarks", $remarks);
            $stmt->bindParam(":id", $id);
        } else {
            $stmt = $this->conn->prepare("UPDATE " . $this->table_name . " SET status = :status WHERE id = :id");
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":id", $id);
        }
        return $stmt->execute();
    }

    public function getAllBorrowRequests() {
        $sql = "SELECT br.id, br.borrower_name AS borrower_name, br.borrower_email, br.date_requested, br.status, br.remarks, br.return_date,
                       br.purpose, br.location_of_use, br.borrow_start, br.borrow_end, br.id_picture, br.released_by,
                       COALESCE(GROUP_CONCAT(e.name SEPARATOR ', '), 'N/A') AS equipment_names,
                       COALESCE(GROUP_CONCAT(bri.quantity SEPARATOR ', '), 'N/A') AS quantities
                FROM borrow_requests br
                LEFT JOIN users u ON br.user_id = u.id
                LEFT JOIN borrow_request_items bri ON br.id = bri.request_id
                LEFT JOIN equipment e ON bri.equipment_id = e.id
                GROUP BY br.id, br.borrower_name, br.borrower_email, br.date_requested, br.status, br.remarks, br.return_date, br.purpose, br.location_of_use, br.borrow_start, br.borrow_end, br.id_picture, br.released_by
                ORDER BY br.date_requested DESC, br.id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure status is never null or empty
        foreach ($results as &$row) {
            if (empty($row['status']) || $row['status'] === null) {
                $row['status'] = 'Rejected';
            }
        }
        
        return $results;
    }

    public function countBorrowedItems() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM borrow_requests WHERE status = 'Borrowed'");
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    public function getLastBorrowRequest() {
        $stmt = $this->conn->query("SELECT MAX(id) AS last_id FROM borrow_requests");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function updateBorrowRequestStatus($request_id, $status)
    {
        $stmt = $this->conn->prepare("UPDATE borrow_requests SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $request_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Fetch all borrow requests for admin/chairperson management page.
     * Returns: id, requester, items, date_requested, status, remarks
     */
    public function getAllRequests() {
        $query = "SELECT 
                id,
                borrower_name AS requester,
                description AS items,
                date_requested,
                status,
                remarks
              FROM borrow_requests
              ORDER BY date_requested DESC";

        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


// For calendar integration
    public function getCalendarEvents() {
        $stmt = $this->conn->query("SELECT 
        COALESCE(borrower_name, 'Unknown') AS borrower_name,
        COALESCE(description, '') AS description,
        date_requested,
        borrow_end
        FROM borrow_requests 
        WHERE status = 'Approved'");

        $events = [];
        $now = new DateTime();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Check if the borrow period has ended
            $borrowEnd = new DateTime($row['borrow_end']);
            
            // Only add to calendar if the borrow period hasn't ended yet
            if ($borrowEnd > $now) {
                $events[] = [
                    'title' => 'Borrow: ' . $row['borrower_name'] . ' - ' . $row['description'],
                    'start' => $row['date_requested'],
                    'end' => $row['borrow_end'],
                    'color' => '#1976d2'
                ];
            }
        }
        return $events;
    }


}
