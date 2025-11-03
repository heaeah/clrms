<?php
require_once 'Database.php';
require_once 'BaseModel.php';

class Equipment extends BaseModel {
    public function __construct() {
        $database = new Database();
        parent::__construct($database->getConnection(), 'equipment');
        
        // Define validation rules for equipment data
        $this->validation_rules = [
            'name' => [
                'type' => 'string',
                'maxLength' => 255,
                'default' => 'Unknown Equipment'
            ],
            'serial_number' => [
                'type' => 'string',
                'maxLength' => 100,
                'default' => ''
            ],
            'model' => [
                'type' => 'string',
                'maxLength' => 100,
                'default' => ''
            ],
            'status' => [
                'type' => 'enum',
                'allowedValues' => ['Available', 'Borrowed', 'Under Repair', 'Disposed'],
                'default' => 'Available'
            ],
            'location' => [
                'type' => 'string',
                'maxLength' => 255,
                'default' => 'Unknown Location'
            ],
            'category' => [
                'type' => 'string',
                'maxLength' => 50,
                'default' => ''
            ],
            'remarks' => [
                'type' => 'string',
                'maxLength' => 1000,
                'default' => ''
            ],
            'date_transferred' => [
                'type' => 'date',
                'default' => null
            ],
            'installation_date' => [
                'type' => 'date',
                'default' => null
            ],
            'maintenance_interval_months' => [
                'type' => 'integer',
                'min' => 1,
                'max' => 120,
                'default' => 6
            ]
        ];
    }

    // Encapsulation: Getter for conn (if needed)
    public function getConn() {
        return $this->conn;
    }

    // Implement CRUD methods
    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table_name} WHERE is_archived = 0 ORDER BY id DESC");
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
        return $this->addEquipment($data);
    }
    public function update($id, $data) {
        return $this->updateEquipment($id, $data, true);
    }
    public function delete($id) {
        return $this->archiveEquipment($id);
    }

    /**
     * Get all equipment with automatic status calculation
     * 
     * AUTOMATIC STATUS LOGIC:
     * - Borrowed: Equipment is in an approved borrow request that hasn't been returned
     * - Repair: Equipment has an active repair record (Pending or In Progress)
     * - Maintenance: Equipment has an active maintenance record (Pending or In Progress)
     * - Disposed/Retired: Manually set statuses (permanent)
     * - Available: Default status when none of the above conditions are met
     * 
     * @param bool $includeArchived Whether to include archived equipment
     * @return array List of equipment with calculated status
     */
    public function getAllEquipment($includeArchived = false) {
        $query = "SELECT e.*,
                    CASE
                        -- Check if equipment is currently borrowed (approved and not returned)
                        WHEN EXISTS (
                            SELECT 1 FROM borrow_request_items bri
                            JOIN borrow_requests br ON bri.request_id = br.id
                            WHERE bri.equipment_id = e.id
                            AND br.status = 'Approved'
                            AND (br.return_date IS NULL OR br.return_date > NOW())
                        ) THEN 'Borrowed'
                        
                        -- Check if equipment is under repair (pending or in progress)
                        WHEN EXISTS (
                            SELECT 1 FROM maintenance_records mr
                            WHERE mr.equipment_id = e.id
                            AND mr.type = 'Repair'
                            AND mr.repair_status IN ('Pending', 'In Progress')
                        ) THEN 'Repair'
                        
                        -- Check if equipment is under maintenance (pending or in progress)
                        WHEN EXISTS (
                            SELECT 1 FROM maintenance_records mr
                            WHERE mr.equipment_id = e.id
                            AND mr.type = 'Maintenance'
                            AND mr.repair_status IN ('Pending', 'In Progress')
                        ) THEN 'Maintenance'
                        
                        -- Check if equipment is disposed/retired
                        WHEN e.status IN ('Disposed', 'Retired') THEN e.status
                        
                        -- Otherwise, it's available
                        ELSE 'Available'
                    END as calculated_status
                FROM equipment e";
        if (!$includeArchived) {
            $query .= " WHERE e.is_archived = 0";
        }
        $query .= " ORDER BY e.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update the status field with calculated status for each equipment
        foreach ($results as &$equipment) {
            $equipment['status'] = $equipment['calculated_status'];
            unset($equipment['calculated_status']);
        }
        
        return $results;
    }

    public function addEquipment($data) {
        // Validate and sanitize input data
        $validatedData = $this->validateData($data);
        
        // Additional validation: status and location must not be empty
        if (empty($validatedData['status']) || empty($validatedData['location'])) {
            error_log('[Equipment] Validation failed: status or location is empty', 3, __DIR__ . '/../logs/error.log');
            return false;
        }
        
        // Validate against masterlists
        require_once __DIR__ . '/MasterlistService.php';
        $masterlistService = new MasterlistService();
        
        if (!empty($validatedData['category']) && !$masterlistService->validateMasterlistValue('equipment_categories', 'name', $validatedData['category'])) {
            error_log('[Equipment] Validation failed: invalid category - ' . $validatedData['category'], 3, __DIR__ . '/../logs/error.log');
            return false;
        }
        
        // Validate item name against masterlist
        if (!empty($validatedData['name']) && !empty($validatedData['category']) && !$masterlistService->validateEquipmentItemName($validatedData['category'], $validatedData['name'])) {
            error_log('[Equipment] Validation failed: invalid item name - ' . $validatedData['name'] . ' for category - ' . $validatedData['category'], 3, __DIR__ . '/../logs/error.log');
            return false;
        }
        
        if (!empty($validatedData['status']) && !$masterlistService->validateMasterlistValue('equipment_status', 'name', $validatedData['status'])) {
            error_log('[Equipment] Validation failed: invalid status - ' . $validatedData['status'], 3, __DIR__ . '/../logs/error.log');
            return false;
        }
        
        // Validate location against labs table
        if (!empty($validatedData['location'])) {
            $query = "SELECT id FROM labs WHERE lab_name = :location LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':location', $validatedData['location']);
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                error_log('[Equipment] Validation failed: invalid location - ' . $validatedData['location'], 3, __DIR__ . '/../logs/error.log');
                return false;
            }
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
            (name, serial_number, model, status, location, category, remarks, date_transferred, installation_date, maintenance_interval_months) 
            VALUES (:name, :serial_number, :model, :status, :location, :category, :remarks, :date_transferred, :installation_date, :maintenance_interval_months)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $validatedData['name']);
        $stmt->bindParam(':serial_number', $validatedData['serial_number']);
        $stmt->bindParam(':model', $validatedData['model']);
        $stmt->bindParam(':status', $validatedData['status']);
        $stmt->bindParam(':location', $validatedData['location']);
        $stmt->bindParam(':category', $validatedData['category']);
        $stmt->bindParam(':remarks', $validatedData['remarks']);
        $stmt->bindParam(':date_transferred', $validatedData['date_transferred']);
        
        // Store values in variables to avoid reference issues
        $installationDate = $validatedData['installation_date'] ?? $validatedData['date_transferred'];
        $maintenanceInterval = $validatedData['maintenance_interval_months'] ?? 6;
        
        $stmt->bindParam(':installation_date', $installationDate);
        $stmt->bindParam(':maintenance_interval_months', $maintenanceInterval);

        $result = $stmt->execute();
        
        if ($result) {
            $equipmentId = $this->conn->lastInsertId();
            $this->scheduleInitialMaintenance($equipmentId, $installationDate);
            
            // Log successful creation
            error_log('[Equipment] Successfully created equipment ID: ' . $equipmentId, 3, __DIR__ . '/../logs/error.log');
        } else {
            error_log('[Equipment] Failed to create equipment: ' . implode(', ', $stmt->errorInfo()), 3, __DIR__ . '/../logs/error.log');
        }
        
        return $result;
    }

    public function archiveEquipment($id) {
        $stmt = $this->conn->prepare("UPDATE equipment SET is_archived = 1 WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $result = $stmt->execute();

        if ($result && isset($_SESSION['user_id'])) {
            $this->logEquipmentAction($id, 'Archived', $_SESSION['user_id']);
        }

        return $result;
    }


    public function restoreEquipment($id) {
        $stmt = $this->conn->prepare("UPDATE equipment SET is_archived = 0 WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Schedule initial maintenance for new equipment
     */
    public function scheduleInitialMaintenance($equipmentId, $installationDate) {
        $nextMaintenanceDate = date('Y-m-d', strtotime($installationDate . ' + 6 months'));
        
        // Update equipment with next maintenance date
        $stmt = $this->conn->prepare("UPDATE equipment SET next_maintenance_date = :next_date WHERE id = :id");
        $stmt->bindParam(':next_date', $nextMaintenanceDate);
        $stmt->bindParam(':id', $equipmentId);
        $stmt->execute();
        
        // Insert into maintenance schedule
        $stmt = $this->conn->prepare("INSERT INTO maintenance_schedule (equipment_id, scheduled_date, maintenance_type, status) VALUES (:equipment_id, :scheduled_date, 'Scheduled', 'Pending')");
        $stmt->bindParam(':equipment_id', $equipmentId);
        $stmt->bindParam(':scheduled_date', $nextMaintenanceDate);
        $stmt->execute();
        
        // Schedule reminders
        $this->scheduleReminders($equipmentId, $nextMaintenanceDate);
    }

    /**
     * Schedule reminders for maintenance
     */
    public function scheduleReminders($equipmentId, $maintenanceDate) {
        $reminders = [
            ['type' => '30_days_before', 'date' => date('Y-m-d', strtotime($maintenanceDate . ' - 30 days'))],
            ['type' => '7_days_before', 'date' => date('Y-m-d', strtotime($maintenanceDate . ' - 7 days'))],
            ['type' => 'due_date', 'date' => $maintenanceDate]
        ];
        
        $stmt = $this->conn->prepare("INSERT INTO maintenance_reminders (equipment_id, reminder_date, reminder_type, status) VALUES (:equipment_id, :reminder_date, :reminder_type, 'Pending')");
        
        foreach ($reminders as $reminder) {
            $stmt->bindParam(':equipment_id', $equipmentId);
            $stmt->bindParam(':reminder_date', $reminder['date']);
            $stmt->bindParam(':reminder_type', $reminder['type']);
            $stmt->execute();
        }
    }

    /**
     * Update maintenance schedule after maintenance is completed
     */
    public function updateMaintenanceSchedule($equipmentId, $maintenanceDate) {
        // Get equipment details
        $equipment = $this->getById($equipmentId);
        if (!$equipment) return false;
        
        // Calculate next maintenance date
        $nextMaintenanceDate = date('Y-m-d', strtotime($maintenanceDate . ' + ' . ($equipment['maintenance_interval_months'] ?? 6) . ' months'));
        
        // Update equipment
        $stmt = $this->conn->prepare("UPDATE equipment SET last_maintenance_date = :last_date, next_maintenance_date = :next_date WHERE id = :id");
        $stmt->bindParam(':last_date', $maintenanceDate);
        $stmt->bindParam(':next_date', $nextMaintenanceDate);
        $stmt->bindParam(':id', $equipmentId);
        $stmt->execute();
        
        // Schedule next maintenance
        $this->scheduleInitialMaintenance($equipmentId, $maintenanceDate);
        
        return true;
    }


    /**
     * Get pending reminders
     */
    public function getPendingReminders() {
        $query = "SELECT * FROM pending_reminders ORDER BY reminder_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get equipment by ID with automatic status calculation
     * 
     * Status is automatically calculated based on current state:
     * - Borrowed, Maintenance, Repair: Automatic based on records
     * - Disposed, Retired, Transferred: Manual statuses
     * - Available: Default
     * 
     * @param int $id Equipment ID
     * @return array|false Equipment data with calculated status, or false if not found
     */
    public function getEquipmentById($id) {
        $query = "SELECT e.*,
                    CASE
                        -- Check if equipment is currently borrowed (approved and not returned)
                        WHEN EXISTS (
                            SELECT 1 FROM borrow_request_items bri
                            JOIN borrow_requests br ON bri.request_id = br.id
                            WHERE bri.equipment_id = e.id
                            AND br.status = 'Approved'
                            AND (br.return_date IS NULL OR br.return_date > NOW())
                        ) THEN 'Borrowed'
                        
                        -- Check if equipment is under repair (pending or in progress)
                        WHEN EXISTS (
                            SELECT 1 FROM maintenance_records mr
                            WHERE mr.equipment_id = e.id
                            AND mr.type = 'Repair'
                            AND mr.repair_status IN ('Pending', 'In Progress')
                        ) THEN 'Repair'
                        
                        -- Check if equipment is under maintenance (pending or in progress)
                        WHEN EXISTS (
                            SELECT 1 FROM maintenance_records mr
                            WHERE mr.equipment_id = e.id
                            AND mr.type = 'Maintenance'
                            AND mr.repair_status IN ('Pending', 'In Progress')
                        ) THEN 'Maintenance'
                        
                        -- Check if equipment is disposed/retired
                        WHEN e.status IN ('Disposed', 'Retired') THEN e.status
                        
                        -- Otherwise, it's available
                        ELSE 'Available'
                    END as calculated_status
                FROM equipment e
                WHERE e.id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $result['status'] = $result['calculated_status'];
            unset($result['calculated_status']);
        }
        
        return $result;
    }

    public function updateEquipment($id, $data, $log = true) {
        // Validation: status and location must not be empty
        if (empty($data['status']) || empty($data['location'])) {
            return false;
        }
        
        // IMPORTANT: Prevent manual setting of automatic statuses
        // Only allow manual statuses: Available, Disposed, Retired, Transferred
        $automaticStatuses = ['Borrowed', 'Maintenance', 'Repair'];
        if (in_array($data['status'], $automaticStatuses)) {
            error_log('[Equipment] Attempted to manually set automatic status: ' . $data['status'] . ' for equipment ID: ' . $id, 3, __DIR__ . '/../logs/error.log');
            throw new Exception('Cannot manually set status to "' . $data['status'] . '". This status is automatically calculated based on borrow/maintenance records.');
        }
        
        // Validate against masterlists
        require_once __DIR__ . '/MasterlistService.php';
        $masterlistService = new MasterlistService();
        
        if (!empty($data['category']) && !$masterlistService->validateMasterlistValue('equipment_categories', 'name', $data['category'])) {
            error_log('[Equipment] Update validation failed: invalid category - ' . $data['category'], 3, __DIR__ . '/../logs/error.log');
            throw new Exception('Invalid category selected. Please choose from the available options.');
        }
        
        // Validate item name against masterlist
        if (!empty($data['name']) && !empty($data['category']) && !$masterlistService->validateEquipmentItemName($data['category'], $data['name'])) {
            error_log('[Equipment] Update validation failed: invalid item name - ' . $data['name'] . ' for category - ' . $data['category'], 3, __DIR__ . '/../logs/error.log');
            throw new Exception('Invalid item name selected. Please choose from the available options for this category.');
        }
        
        if (!empty($data['status']) && !$masterlistService->validateMasterlistValue('equipment_status', 'name', $data['status'])) {
            error_log('[Equipment] Update validation failed: invalid status - ' . $data['status'], 3, __DIR__ . '/../logs/error.log');
            throw new Exception('Invalid status selected. Please choose from the available options.');
        }
        
        // Validate location against labs table
        if (!empty($data['location'])) {
            $query = "SELECT id FROM labs WHERE lab_name = :location LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':location', $data['location']);
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                error_log('[Equipment] Update validation failed: invalid location - ' . $data['location'], 3, __DIR__ . '/../logs/error.log');
                throw new Exception('Invalid location selected. Please choose from the available options.');
            }
        }
        // Fetch current data
        $current = $this->getEquipmentById($id);

        // Fill in missing fields in $data with current values
        $fields = ['name', 'serial_number', 'model', 'status', 'location', 'category', 'remarks', 'installation_date'];
        foreach ($fields as $field) {
            if (!isset($data[$field])) {
                $data[$field] = $current[$field];
            }
        }

        // Check if any field actually changed (with proper trimming and null handling)
        $changed = false;
        $changes = [];
        foreach ($fields as $field) {
            $currentValue = trim($current[$field] ?? '');
            $newValue = trim($data[$field] ?? '');
            
            // Convert null/empty to empty string for consistent comparison
            $currentValue = $currentValue === null ? '' : $currentValue;
            $newValue = $newValue === null ? '' : $newValue;
            
            if ($currentValue !== $newValue) {
                $changed = true;
                $changes[$field] = $current[$field];
            }
        }

        if (!$changed) {
            // Nothing changed, don't update or log
            error_log('[Equipment Update] No changes detected for equipment ID: ' . $id . ' by user: ' . ($_SESSION['user_id'] ?? 'unknown'), 3, __DIR__ . '/../logs/error.log');
            return false;
        }

        $query = "UPDATE " . $this->table_name . " SET 
        name = :name, 
        serial_number = :serial_number, 
        model = :model, 
        status = :status, 
        location = :location, 
        category = :category,
        remarks = :remarks,
        installation_date = :installation_date,
        last_updated_at = NOW(),
        last_updated_by = :updated_by
    WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':serial_number', $data['serial_number']);
        $stmt->bindParam(':model', $data['model']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':remarks', $data['remarks']);
        $stmt->bindParam(':installation_date', $data['installation_date']);
        $stmt->bindParam(':updated_by', $_SESSION['user_id']);
        $stmt->bindParam(':id', $id);

        $result = $stmt->execute();

        if ($result && $log) {
            $this->logEquipmentAction($id, 'Updated', $_SESSION['user_id'], null, null, null, null, $current['location'], json_encode($changes));
            error_log('[Equipment Update] Changes logged for equipment ID: ' . $id . ' by user: ' . ($_SESSION['user_id'] ?? 'unknown') . ' - Changes: ' . json_encode($changes), 3, __DIR__ . '/../logs/error.log');
        }

        return $result;
    }

    public function getAvailableEquipment() {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . " WHERE status = 'Available' AND is_archived = 0");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }

    public function countEquipment() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM " . $this->table_name);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function logEquipmentAction($equipment_id, $action, $user_id = null, $transfer_to = null, $authorized_by = null, $transfer_date = null, $remarks = null, $from_location = null, $previous_values = null) {
        $query = "INSERT INTO equipment_logs (equipment_id, action, deleted_by, transferred_to, authorized_by, transfer_date, remarks, from_location, previous_values) 
                  VALUES (:equipment_id, :action, :user_id, :transferred_to, :authorized_by, :transfer_date, :remarks, :from_location, :previous_values)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':equipment_id', $equipment_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':transferred_to', $transfer_to);
        $stmt->bindParam(':authorized_by', $authorized_by);
        $stmt->bindParam(':transfer_date', $transfer_date);
        $stmt->bindParam(':remarks', $remarks);
        $stmt->bindParam(':from_location', $from_location);
        $stmt->bindParam(':previous_values', $previous_values);
        return $stmt->execute();
    }

    public function getEquipmentByStatus($status) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table_name . " WHERE status = :status");
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArchivedEquipment() {
        $stmt = $this->conn->prepare("SELECT * FROM equipment WHERE is_archived = 1 ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function filterAndSearchEquipment($status = '', $location = '', $search = '', $category = '') {
        $query = "SELECT * FROM equipment WHERE is_archived = 0";
        $params = [];

        if (!empty($status)) {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }

        if (!empty($location)) {
            $query .= " AND location = :location";
            $params[':location'] = $location;
        }

        if (!empty($category)) {
            $query .= " AND category = :category";
            $params[':category'] = $category;
        }

        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR serial_number LIKE :search OR model LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $query .= " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get equipment due for maintenance within $days days
    public function getDueForMaintenance($days = 30) {
        $query = "SELECT *, DATE_ADD(COALESCE(last_maintenance_date, date_transferred, CURDATE()), INTERVAL 6 MONTH) AS next_maintenance_due
                  FROM equipment
                  WHERE is_archived = 0
                  HAVING next_maintenance_due >= CURDATE() AND next_maintenance_due <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                  ORDER BY next_maintenance_due ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get equipment overdue for maintenance
    public function getOverdueMaintenance() {
        $query = "SELECT *, DATE_ADD(COALESCE(last_maintenance_date, date_transferred, CURDATE()), INTERVAL 6 MONTH) AS next_maintenance_due
                  FROM equipment
                  WHERE is_archived = 0
                  HAVING next_maintenance_due < CURDATE()
                  ORDER BY next_maintenance_due ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update last_maintenance_date for equipment
    public function updateLastMaintenanceDate($equipment_id, $date) {
        $query = "UPDATE equipment SET last_maintenance_date = :date WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':id', $equipment_id);
        return $stmt->execute();
    }

    /**
     * Check and create automated maintenance schedules for equipment
     * This method should be called periodically (e.g., daily cron job)
     */
    public function checkAndScheduleMaintenance() {
        try {
            // Get all equipment with installation dates that need maintenance
            $query = "SELECT id, name, installation_date, maintenance_interval_months 
                      FROM equipment 
                      WHERE installation_date IS NOT NULL 
                      AND status IN ('Available', 'Borrowed')
                      AND is_archived = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $equipmentList = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $scheduledCount = 0;
            $today = date('Y-m-d');
            
            foreach ($equipmentList as $equipment) {
                $installationDate = $equipment['installation_date'];
                $intervalMonths = $equipment['maintenance_interval_months'] ?? 6; // Default 6 months
                
                // Calculate next maintenance due date
                $nextMaintenanceDate = date('Y-m-d', strtotime($installationDate . " +{$intervalMonths} months"));
                
                // Check if maintenance is due (within 7 days)
                $dueDate = date('Y-m-d', strtotime($nextMaintenanceDate . ' -7 days'));
                
                if ($today >= $dueDate) {
                    // Check if there's already a pending maintenance record for this equipment
                    $existingMaintenance = $this->checkExistingMaintenance($equipment['id'], $nextMaintenanceDate);
                    
                    if (!$existingMaintenance) {
                        // Create automated maintenance record
                        $this->createAutomatedMaintenance($equipment['id'], $equipment['name'], $nextMaintenanceDate);
                        $scheduledCount++;
                    }
                }
            }
            
            return [
                'success' => true,
                'scheduled_count' => $scheduledCount,
                'message' => "Scheduled maintenance for {$scheduledCount} equipment items"
            ];
            
        } catch (Exception $e) {
            error_log('[Automated Maintenance Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if there's already a maintenance record for the equipment around the due date
     */
    private function checkExistingMaintenance($equipmentId, $dueDate) {
        $query = "SELECT COUNT(*) as count 
                  FROM maintenance_records 
                  WHERE equipment_id = :equipment_id 
                  AND maintenance_date BETWEEN :start_date AND :end_date
                  AND repair_status IN ('Pending', 'In Progress')";
        
        $startDate = date('Y-m-d', strtotime($dueDate . ' -30 days'));
        $endDate = date('Y-m-d', strtotime($dueDate . ' +30 days'));
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':equipment_id', $equipmentId);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }
    
    /**
     * Create an automated maintenance record
     */
    private function createAutomatedMaintenance($equipmentId, $equipmentName, $dueDate) {
        $query = "INSERT INTO maintenance_records 
                  (equipment_id, maintenance_type, description, maintenance_date, due_date, repair_status, technician_name, cost, created_by) 
                  VALUES 
                  (:equipment_id, :maintenance_type, :description, :maintenance_date, :due_date, :repair_status, :technician_name, :cost, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $maintenanceType = 'Maintenance';
        $description = "Scheduled maintenance for {$equipmentName} (Automated - 6 month interval)";
        $maintenanceDate = $dueDate;
        $repairStatus = 'Pending';
        $technicianName = 'System';
        $cost = 0.00;
        $createdBy = 'system';
        
        $stmt->bindParam(':equipment_id', $equipmentId);
        $stmt->bindParam(':maintenance_type', $maintenanceType);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':maintenance_date', $maintenanceDate);
        $stmt->bindParam(':due_date', $dueDate);
        $stmt->bindParam(':repair_status', $repairStatus);
        $stmt->bindParam(':technician_name', $technicianName);
        $stmt->bindParam(':cost', $cost);
        $stmt->bindParam(':created_by', $createdBy);
        
        return $stmt->execute();
    }
    
    /**
     * Get equipment due for maintenance (within next 30 days)
     */
    public function getEquipmentDueForMaintenance() {
        $query = "SELECT e.*, 
                         e.name as equipment_name,
                         DATE_ADD(e.installation_date, INTERVAL COALESCE(e.maintenance_interval_months, 6) MONTH) as next_maintenance_date,
                         DATEDIFF(DATE_ADD(e.installation_date, INTERVAL COALESCE(e.maintenance_interval_months, 6) MONTH), CURDATE()) as days_until_maintenance,
                         CASE 
                             WHEN DATE_ADD(e.installation_date, INTERVAL COALESCE(e.maintenance_interval_months, 6) MONTH) < CURDATE() THEN 'Overdue'
                             WHEN DATE_ADD(e.installation_date, INTERVAL COALESCE(e.maintenance_interval_months, 6) MONTH) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Due Soon'
                             ELSE 'Upcoming'
                         END as maintenance_status
                  FROM equipment e
                  WHERE e.installation_date IS NOT NULL 
                  AND e.status IN ('Available', 'Borrowed')
                  AND e.is_archived = 0
                  AND DATE_ADD(e.installation_date, INTERVAL COALESCE(e.maintenance_interval_months, 6) MONTH) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                  AND e.id NOT IN (
                      SELECT DISTINCT equipment_id 
                      FROM maintenance_records 
                      WHERE maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      AND repair_status IN ('Pending', 'In Progress', 'Completed')
                  )
                  ORDER BY next_maintenance_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get overdue equipment for maintenance
     */
    public function getOverdueEquipmentForMaintenance() {
        $query = "SELECT e.*, 
                         DATE_ADD(e.installation_date, INTERVAL COALESCE(e.maintenance_interval_months, 6) MONTH) as next_maintenance_date
                  FROM equipment e
                  WHERE e.installation_date IS NOT NULL 
                  AND e.status IN ('Available', 'Borrowed')
                  AND e.is_archived = 0
                  AND DATE_ADD(e.installation_date, INTERVAL COALESCE(e.maintenance_interval_months, 6) MONTH) < CURDATE()
                  AND e.id NOT IN (
                      SELECT DISTINCT equipment_id 
                      FROM maintenance_records 
                      WHERE maintenance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      AND repair_status IN ('Completed')
                  )
                  ORDER BY next_maintenance_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
