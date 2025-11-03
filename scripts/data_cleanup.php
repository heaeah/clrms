<?php
/**
 * Data Cleanup Script
 * What: System-wide data cleanup and repair tool
 * Why: Fixes common data integrity issues and ensures clean data
 * How: Runs automated cleanup procedures and generates reports
 */

require_once __DIR__ . '/../classes/DataIntegrityChecker.php';
require_once __DIR__ . '/../classes/DataValidator.php';
require_once __DIR__ . '/../classes/Database.php';

class DataCleanup {
    private $pdo;
    private $fixes = [];
    private $errors = [];
    
    public function __construct() {
        $database = new Database();
        $this->pdo = $database->getConnection();
    }
    
    /**
     * Run complete data cleanup
     */
    public function runCleanup() {
        echo "Starting data cleanup process...\n";
        
        $this->fixEmptyEquipmentNames();
        $this->fixEmptyBorrowerNames();
        $this->fixEmptyLabReservationPurposes();
        $this->fixInvalidDates();
        $this->fixInvalidEmails();
        $this->fixInvalidStatuses();
        $this->fixOrphanedRecords();
        
        $this->generateReport();
    }
    
    /**
     * Fix empty equipment names
     */
    private function fixEmptyEquipmentNames() {
        try {
            $stmt = $this->pdo->prepare("UPDATE equipment SET name = 'Unknown Equipment' WHERE name IS NULL OR name = ''");
            $result = $stmt->execute();
            $affected = $stmt->rowCount();
            
            if ($affected > 0) {
                $this->fixes[] = "Fixed {$affected} equipment records with empty names";
                echo "Fixed {$affected} equipment records with empty names\n";
            }
        } catch (Exception $e) {
            $this->errors[] = "Error fixing empty equipment names: " . $e->getMessage();
            echo "Error fixing empty equipment names: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Fix empty borrower names
     */
    private function fixEmptyBorrowerNames() {
        try {
            $stmt = $this->pdo->prepare("UPDATE borrow_requests SET borrower_name = 'Unknown Borrower' WHERE borrower_name IS NULL OR borrower_name = ''");
            $result = $stmt->execute();
            $affected = $stmt->rowCount();
            
            if ($affected > 0) {
                $this->fixes[] = "Fixed {$affected} borrow requests with empty borrower names";
                echo "Fixed {$affected} borrow requests with empty borrower names\n";
            }
        } catch (Exception $e) {
            $this->errors[] = "Error fixing empty borrower names: " . $e->getMessage();
            echo "Error fixing empty borrower names: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Fix empty lab reservation purposes
     */
    private function fixEmptyLabReservationPurposes() {
        try {
            $stmt = $this->pdo->prepare("UPDATE lab_reservations SET purpose = 'No purpose specified' WHERE purpose IS NULL OR purpose = ''");
            $result = $stmt->execute();
            $affected = $stmt->rowCount();
            
            if ($affected > 0) {
                $this->fixes[] = "Fixed {$affected} lab reservations with empty purposes";
                echo "Fixed {$affected} lab reservations with empty purposes\n";
            }
        } catch (Exception $e) {
            $this->errors[] = "Error fixing empty lab reservation purposes: " . $e->getMessage();
            echo "Error fixing empty lab reservation purposes: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Fix invalid default dates
     */
    private function fixInvalidDates() {
        try {
            // Fix equipment dates
            $stmt = $this->pdo->prepare("UPDATE equipment SET installation_date = NULL WHERE installation_date = '1970-01-01 00:00:00'");
            $stmt->execute();
            $equipmentFixed = $stmt->rowCount();
            
            $stmt = $this->pdo->prepare("UPDATE equipment SET date_transferred = NULL WHERE date_transferred = '1970-01-01 00:00:00'");
            $stmt->execute();
            $equipmentTransferredFixed = $stmt->rowCount();
            
            // Fix borrow request dates
            $stmt = $this->pdo->prepare("UPDATE borrow_requests SET borrow_start = NULL WHERE borrow_start = '1970-01-01 00:00:00'");
            $stmt->execute();
            $borrowStartFixed = $stmt->rowCount();
            
            $stmt = $this->pdo->prepare("UPDATE borrow_requests SET borrow_end = NULL WHERE borrow_end = '1970-01-01 00:00:00'");
            $stmt->execute();
            $borrowEndFixed = $stmt->rowCount();
            
            $totalFixed = $equipmentFixed + $equipmentTransferredFixed + $borrowStartFixed + $borrowEndFixed;
            
            if ($totalFixed > 0) {
                $this->fixes[] = "Fixed {$totalFixed} records with invalid default dates";
                echo "Fixed {$totalFixed} records with invalid default dates\n";
            }
        } catch (Exception $e) {
            $this->errors[] = "Error fixing invalid dates: " . $e->getMessage();
            echo "Error fixing invalid dates: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Fix invalid email addresses
     */
    private function fixInvalidEmails() {
        try {
            // Fix borrow request emails
            $stmt = $this->pdo->prepare("UPDATE borrow_requests SET borrower_email = NULL WHERE borrower_email IS NOT NULL AND borrower_email != '' AND borrower_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'");
            $stmt->execute();
            $borrowEmailsFixed = $stmt->rowCount();
            
            // Fix lab reservation emails
            $stmt = $this->pdo->prepare("UPDATE lab_reservations SET contact_email = NULL WHERE contact_email IS NOT NULL AND contact_email != '' AND contact_email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'");
            $stmt->execute();
            $labEmailsFixed = $stmt->rowCount();
            
            // Fix user emails
            $stmt = $this->pdo->prepare("UPDATE users SET email = NULL WHERE email IS NOT NULL AND email != '' AND email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'");
            $stmt->execute();
            $userEmailsFixed = $stmt->rowCount();
            
            $totalFixed = $borrowEmailsFixed + $labEmailsFixed + $userEmailsFixed;
            
            if ($totalFixed > 0) {
                $this->fixes[] = "Fixed {$totalFixed} records with invalid email addresses";
                echo "Fixed {$totalFixed} records with invalid email addresses\n";
            }
        } catch (Exception $e) {
            $this->errors[] = "Error fixing invalid emails: " . $e->getMessage();
            echo "Error fixing invalid emails: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Fix invalid status values
     */
    private function fixInvalidStatuses() {
        try {
            // Fix equipment statuses
            $stmt = $this->pdo->prepare("UPDATE equipment SET status = 'Working' WHERE status IS NULL OR status = '' OR status NOT IN ('Working', 'Maintenance', 'Broken', 'Retired')");
            $stmt->execute();
            $equipmentStatusFixed = $stmt->rowCount();
            
            // Fix borrow request statuses
            $stmt = $this->pdo->prepare("UPDATE borrow_requests SET status = 'Pending' WHERE status IS NULL OR status = '' OR status NOT IN ('Pending', 'Approved', 'Rejected', 'Returned')");
            $stmt->execute();
            $borrowStatusFixed = $stmt->rowCount();
            
            // Fix lab reservation statuses
            $stmt = $this->pdo->prepare("UPDATE lab_reservations SET status = 'Pending' WHERE status IS NULL OR status = '' OR status NOT IN ('Pending', 'Approved', 'Rejected', 'Completed')");
            $stmt->execute();
            $labStatusFixed = $stmt->rowCount();
            
            $totalFixed = $equipmentStatusFixed + $borrowStatusFixed + $labStatusFixed;
            
            if ($totalFixed > 0) {
                $this->fixes[] = "Fixed {$totalFixed} records with invalid status values";
                echo "Fixed {$totalFixed} records with invalid status values\n";
            }
        } catch (Exception $e) {
            $this->errors[] = "Error fixing invalid statuses: " . $e->getMessage();
            echo "Error fixing invalid statuses: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Fix orphaned records
     */
    private function fixOrphanedRecords() {
        try {
            // Remove orphaned borrow requests
            $stmt = $this->pdo->prepare("DELETE br FROM borrow_requests br LEFT JOIN equipment e ON br.equipment_id = e.id WHERE br.equipment_id IS NOT NULL AND e.id IS NULL");
            $stmt->execute();
            $orphanedBorrowFixed = $stmt->rowCount();
            
            // Remove orphaned maintenance records
            $stmt = $this->pdo->prepare("DELETE ms FROM maintenance_schedule ms LEFT JOIN equipment e ON ms.equipment_id = e.id WHERE ms.equipment_id IS NOT NULL AND e.id IS NULL");
            $stmt->execute();
            $orphanedMaintenanceFixed = $stmt->rowCount();
            
            $totalFixed = $orphanedBorrowFixed + $orphanedMaintenanceFixed;
            
            if ($totalFixed > 0) {
                $this->fixes[] = "Removed {$totalFixed} orphaned records";
                echo "Removed {$totalFixed} orphaned records\n";
            }
        } catch (Exception $e) {
            $this->errors[] = "Error fixing orphaned records: " . $e->getMessage();
            echo "Error fixing orphaned records: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Generate cleanup report
     */
    private function generateReport() {
        echo "\n=== DATA CLEANUP REPORT ===\n";
        echo "Fixes Applied: " . count($this->fixes) . "\n";
        echo "Errors Encountered: " . count($this->errors) . "\n\n";
        
        if (!empty($this->fixes)) {
            echo "FIXES APPLIED:\n";
            foreach ($this->fixes as $fix) {
                echo "- " . $fix . "\n";
            }
            echo "\n";
        }
        
        if (!empty($this->errors)) {
            echo "ERRORS ENCOUNTERED:\n";
            foreach ($this->errors as $error) {
                echo "- " . $error . "\n";
            }
            echo "\n";
        }
        
        // Log the report
        $logMessage = "[DataCleanup] Cleanup completed. Fixes: " . count($this->fixes) . ", Errors: " . count($this->errors);
        error_log($logMessage, 3, __DIR__ . '/../logs/error.log');
        
        echo "Cleanup process completed.\n";
    }
}

// Run cleanup if script is executed directly
if (php_sapi_name() === 'cli') {
    $cleanup = new DataCleanup();
    $cleanup->runCleanup();
}
?>