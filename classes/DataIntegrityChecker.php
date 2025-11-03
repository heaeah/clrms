<?php
/**
 * DataIntegrityChecker Class
 * What: System-wide data integrity monitoring and cleanup
 * Why: Ensures data quality across the entire system and identifies issues
 * How: Performs comprehensive checks and provides cleanup recommendations
 */

require_once 'DataValidator.php';
require_once 'Database.php';

class DataIntegrityChecker {
    private $pdo;
    private $issues = [];
    private $recommendations = [];
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }
    
    /**
     * Run comprehensive data integrity check
     * @return array Complete integrity report
     */
    public function runFullCheck() {
        $this->issues = [];
        $this->recommendations = [];
        
        $this->checkEquipmentData();
        $this->checkBorrowRequestData();
        $this->checkLabReservationData();
        $this->checkUserData();
        $this->checkMaintenanceData();
        $this->checkSoftwareData();
        $this->checkOrphanedRecords();
        $this->checkInvalidDates();
        $this->checkDuplicateRecords();
        
        return [
            'issues' => $this->issues,
            'recommendations' => $this->recommendations,
            'summary' => $this->generateSummary()
        ];
    }
    
    /**
     * Check equipment data integrity
     */
    private function checkEquipmentData() {
        $checks = [
            'missing_names' => "SELECT COUNT(*) as count FROM equipment WHERE name IS NULL OR name = ''",
            'missing_status' => "SELECT COUNT(*) as count FROM equipment WHERE status IS NULL OR status = ''",
            'missing_location' => "SELECT COUNT(*) as count FROM equipment WHERE location IS NULL OR location = ''",
            'invalid_status' => "SELECT COUNT(*) as count FROM equipment WHERE status NOT IN ('Working', 'Maintenance', 'Broken', 'Retired')",
            'invalid_dates' => "SELECT COUNT(*) as count FROM equipment WHERE installation_date = '1970-01-01 00:00:00' OR date_transferred = '1970-01-01 00:00:00'"
        ];
        
        foreach ($checks as $checkName => $query) {
            $result = $this->pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $this->issues[] = [
                    'table' => 'equipment',
                    'type' => $checkName,
                    'count' => $result['count'],
                    'severity' => $this->getSeverity($checkName),
                    'description' => $this->getDescription('equipment', $checkName)
                ];
            }
        }
    }
    
    /**
     * Check borrow request data integrity
     */
    private function checkBorrowRequestData() {
        $checks = [
            'missing_borrower_names' => "SELECT COUNT(*) as count FROM borrow_requests WHERE borrower_name IS NULL OR borrower_name = ''",
            'missing_purpose' => "SELECT COUNT(*) as count FROM borrow_requests WHERE purpose IS NULL OR purpose = ''",
            'missing_status' => "SELECT COUNT(*) as count FROM borrow_requests WHERE status IS NULL OR status = ''",
            'invalid_status' => "SELECT COUNT(*) as count FROM borrow_requests WHERE status NOT IN ('Pending', 'Approved', 'Rejected', 'Returned')",
            'invalid_dates' => "SELECT COUNT(*) as count FROM borrow_requests WHERE borrow_start = '1970-01-01 00:00:00' OR borrow_end = '1970-01-01 00:00:00'",
            'invalid_email' => "SELECT COUNT(*) as count FROM borrow_requests WHERE borrower_email IS NOT NULL AND borrower_email != '' AND borrower_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'"
        ];
        
        foreach ($checks as $checkName => $query) {
            $result = $this->pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $this->issues[] = [
                    'table' => 'borrow_requests',
                    'type' => $checkName,
                    'count' => $result['count'],
                    'severity' => $this->getSeverity($checkName),
                    'description' => $this->getDescription('borrow_requests', $checkName)
                ];
            }
        }
    }
    
    /**
     * Check lab reservation data integrity
     */
    private function checkLabReservationData() {
        $checks = [
            'missing_purpose' => "SELECT COUNT(*) as count FROM lab_reservations WHERE purpose IS NULL OR purpose = ''",
            'missing_status' => "SELECT COUNT(*) as count FROM lab_reservations WHERE status IS NULL OR status = ''",
            'invalid_status' => "SELECT COUNT(*) as count FROM lab_reservations WHERE status NOT IN ('Pending', 'Approved', 'Rejected', 'Completed')",
            'missing_contact' => "SELECT COUNT(*) as count FROM lab_reservations WHERE contact_person IS NULL OR contact_person = ''",
            'invalid_email' => "SELECT COUNT(*) as count FROM lab_reservations WHERE contact_email IS NOT NULL AND contact_email != '' AND contact_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'"
        ];
        
        foreach ($checks as $checkName => $query) {
            $result = $this->pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $this->issues[] = [
                    'table' => 'lab_reservations',
                    'type' => $checkName,
                    'count' => $result['count'],
                    'severity' => $this->getSeverity($checkName),
                    'description' => $this->getDescription('lab_reservations', $checkName)
                ];
            }
        }
    }
    
    /**
     * Check user data integrity
     */
    private function checkUserData() {
        $checks = [
            'missing_usernames' => "SELECT COUNT(*) as count FROM users WHERE username IS NULL OR username = ''",
            'missing_roles' => "SELECT COUNT(*) as count FROM users WHERE role IS NULL OR role = ''",
            'invalid_roles' => "SELECT COUNT(*) as count FROM users WHERE role NOT IN ('Admin', 'Lab Admin', 'Student Assistant')",
            'duplicate_usernames' => "SELECT COUNT(*) as count FROM (SELECT username, COUNT(*) as cnt FROM users GROUP BY username HAVING cnt > 1) as duplicates",
            'invalid_email' => "SELECT COUNT(*) as count FROM users WHERE email IS NOT NULL AND email != '' AND email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'"
        ];
        
        foreach ($checks as $checkName => $query) {
            $result = $this->pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $this->issues[] = [
                    'table' => 'users',
                    'type' => $checkName,
                    'count' => $result['count'],
                    'severity' => $this->getSeverity($checkName),
                    'description' => $this->getDescription('users', $checkName)
                ];
            }
        }
    }
    
    /**
     * Check maintenance data integrity
     */
    private function checkMaintenanceData() {
        $checks = [
            'missing_equipment_id' => "SELECT COUNT(*) as count FROM maintenance_schedule WHERE equipment_id IS NULL",
            'missing_due_dates' => "SELECT COUNT(*) as count FROM maintenance_schedule WHERE due_date IS NULL OR due_date = '0000-00-00 00:00:00'",
            'invalid_dates' => "SELECT COUNT(*) as count FROM maintenance_schedule WHERE due_date = '1970-01-01 00:00:00'"
        ];
        
        foreach ($checks as $checkName => $query) {
            $result = $this->pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $this->issues[] = [
                    'table' => 'maintenance_schedule',
                    'type' => $checkName,
                    'count' => $result['count'],
                    'severity' => $this->getSeverity($checkName),
                    'description' => $this->getDescription('maintenance_schedule', $checkName)
                ];
            }
        }
    }
    
    /**
     * Check software data integrity
     */
    private function checkSoftwareData() {
        $checks = [
            'missing_names' => "SELECT COUNT(*) as count FROM software WHERE name IS NULL OR name = ''",
            'invalid_license_dates' => "SELECT COUNT(*) as count FROM software WHERE license_expiry_date = '1970-01-01 00:00:00' OR license_expiry_date = '0000-00-00 00:00:00'"
        ];
        
        foreach ($checks as $checkName => $query) {
            $result = $this->pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $this->issues[] = [
                    'table' => 'software',
                    'type' => $checkName,
                    'count' => $result['count'],
                    'severity' => $this->getSeverity($checkName),
                    'description' => $this->getDescription('software', $checkName)
                ];
            }
        }
    }
    
    /**
     * Check for orphaned records
     */
    private function checkOrphanedRecords() {
        $checks = [
            'orphaned_borrow_requests' => "SELECT COUNT(*) as count FROM borrow_requests br LEFT JOIN equipment e ON br.equipment_id = e.id WHERE br.equipment_id IS NOT NULL AND e.id IS NULL",
            'orphaned_maintenance' => "SELECT COUNT(*) as count FROM maintenance_schedule ms LEFT JOIN equipment e ON ms.equipment_id = e.id WHERE ms.equipment_id IS NOT NULL AND e.id IS NULL"
        ];
        
        foreach ($checks as $checkName => $query) {
            $result = $this->pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $this->issues[] = [
                    'table' => 'orphaned_records',
                    'type' => $checkName,
                    'count' => $result['count'],
                    'severity' => 'high',
                    'description' => $this->getDescription('orphaned_records', $checkName)
                ];
            }
        }
    }
    
    /**
     * Check for invalid dates
     */
    private function checkInvalidDates() {
        $checks = [
            'invalid_equipment_dates' => "SELECT COUNT(*) as count FROM equipment WHERE installation_date = '1970-01-01 00:00:00' OR date_transferred = '1970-01-01 00:00:00'",
            'invalid_borrow_dates' => "SELECT COUNT(*) as count FROM borrow_requests WHERE borrow_start = '1970-01-01 00:00:00' OR borrow_end = '1970-01-01 00:00:00'"
        ];
        
        foreach ($checks as $checkName => $query) {
            $result = $this->pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $this->issues[] = [
                    'table' => 'invalid_dates',
                    'type' => $checkName,
                    'count' => $result['count'],
                    'severity' => 'medium',
                    'description' => $this->getDescription('invalid_dates', $checkName)
                ];
            }
        }
    }
    
    /**
     * Check for duplicate records
     */
    private function checkDuplicateRecords() {
        $checks = [
            'duplicate_equipment' => "SELECT COUNT(*) as count FROM (SELECT serial_number, COUNT(*) as cnt FROM equipment WHERE serial_number IS NOT NULL AND serial_number != '' GROUP BY serial_number HAVING cnt > 1) as duplicates"
        ];
        
        foreach ($checks as $checkName => $query) {
            $result = $this->pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $this->issues[] = [
                    'table' => 'duplicates',
                    'type' => $checkName,
                    'count' => $result['count'],
                    'severity' => 'medium',
                    'description' => $this->getDescription('duplicates', $checkName)
                ];
            }
        }
    }
    
    /**
     * Get severity level for issue type
     */
    private function getSeverity($checkName) {
        $highSeverity = ['missing_names', 'missing_borrower_names', 'missing_usernames', 'orphaned_borrow_requests', 'orphaned_maintenance'];
        $mediumSeverity = ['missing_status', 'invalid_status', 'invalid_dates', 'duplicate_equipment'];
        
        if (in_array($checkName, $highSeverity)) {
            return 'high';
        } elseif (in_array($checkName, $mediumSeverity)) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Get description for issue
     */
    private function getDescription($table, $checkName) {
        $descriptions = [
            'equipment' => [
                'missing_names' => 'Equipment records with missing names',
                'missing_status' => 'Equipment records with missing status',
                'missing_location' => 'Equipment records with missing location',
                'invalid_status' => 'Equipment records with invalid status values',
                'invalid_dates' => 'Equipment records with invalid default dates'
            ],
            'borrow_requests' => [
                'missing_borrower_names' => 'Borrow requests with missing borrower names',
                'missing_purpose' => 'Borrow requests with missing purpose',
                'missing_status' => 'Borrow requests with missing status',
                'invalid_status' => 'Borrow requests with invalid status values',
                'invalid_dates' => 'Borrow requests with invalid default dates',
                'invalid_email' => 'Borrow requests with invalid email addresses'
            ],
            'lab_reservations' => [
                'missing_purpose' => 'Lab reservations with missing purpose',
                'missing_status' => 'Lab reservations with missing status',
                'invalid_status' => 'Lab reservations with invalid status values',
                'missing_contact' => 'Lab reservations with missing contact person',
                'invalid_email' => 'Lab reservations with invalid email addresses'
            ],
            'users' => [
                'missing_usernames' => 'Users with missing usernames',
                'missing_roles' => 'Users with missing roles',
                'invalid_roles' => 'Users with invalid role values',
                'duplicate_usernames' => 'Duplicate usernames found',
                'invalid_email' => 'Users with invalid email addresses'
            ],
            'maintenance_schedule' => [
                'missing_equipment_id' => 'Maintenance records with missing equipment ID',
                'missing_due_dates' => 'Maintenance records with missing due dates',
                'invalid_dates' => 'Maintenance records with invalid dates'
            ],
            'software' => [
                'missing_names' => 'Software records with missing names',
                'invalid_license_dates' => 'Software records with invalid license dates'
            ],
            'orphaned_records' => [
                'orphaned_borrow_requests' => 'Borrow requests referencing non-existent equipment',
                'orphaned_maintenance' => 'Maintenance records referencing non-existent equipment'
            ],
            'invalid_dates' => [
                'invalid_equipment_dates' => 'Equipment records with invalid default dates',
                'invalid_borrow_dates' => 'Borrow requests with invalid default dates'
            ],
            'duplicates' => [
                'duplicate_equipment' => 'Equipment records with duplicate serial numbers'
            ]
        ];
        
        return $descriptions[$table][$checkName] ?? 'Unknown issue';
    }
    
    /**
     * Generate summary of integrity check
     */
    private function generateSummary() {
        $totalIssues = count($this->issues);
        $highSeverity = count(array_filter($this->issues, function($issue) { return $issue['severity'] === 'high'; }));
        $mediumSeverity = count(array_filter($this->issues, function($issue) { return $issue['severity'] === 'medium'; }));
        $lowSeverity = count(array_filter($this->issues, function($issue) { return $issue['severity'] === 'low'; }));
        
        return [
            'total_issues' => $totalIssues,
            'high_severity' => $highSeverity,
            'medium_severity' => $mediumSeverity,
            'low_severity' => $lowSeverity,
            'status' => $totalIssues === 0 ? 'clean' : ($highSeverity > 0 ? 'critical' : ($mediumSeverity > 0 ? 'warning' : 'info'))
        ];
    }
    
    /**
     * Log integrity issues to error log
     */
    public function logIssues() {
        if (!empty($this->issues)) {
            $logMessage = "[DataIntegrityChecker] Found " . count($this->issues) . " data integrity issues:\n";
            foreach ($this->issues as $issue) {
                $logMessage .= "- [{$issue['severity']}] {$issue['description']}: {$issue['count']} records\n";
            }
            error_log($logMessage, 3, __DIR__ . '/../logs/error.log');
        }
    }
}
?>