<?php
require_once 'Equipment.php';

class EquipmentService {
    private $equipmentModel;

    public function __construct() {
        $this->equipmentModel = new Equipment();
    }

    /**
     * Add a new equipment record (with validation)
     * @param array $postData
     * @param array $fileData
     * @return int|false
     * What: Adds an equipment record
     * Why: Centralizes add logic and validation
     * How: Calls Equipment::addEquipment
     */
    public function handleAddEquipment($postData, $fileData) {
        $postData['date_transferred'] = $postData['date_transferred'] ?? null;
        if ($this->equipmentModel->addEquipment($postData)) {
            $lastId = $this->equipmentModel->getLastInsertId();
            // Generate QR code for the new equipment
            $qrGenerated = $this->generateQRCode($lastId);
            if (!$qrGenerated) {
                error_log('[Equipment Service] QR code generation failed for equipment ID: ' . $lastId, 3, __DIR__ . '/../logs/error.log');
            }
            return $lastId;
        }
        return false;
    }

    /**
     * Generate QR code for equipment
     * @param int $equipmentId
     * @return bool
     */
    private function generateQRCode($equipmentId) {
        try {
            // Include the QR code generator
            require_once __DIR__ . '/QRCodeGenerator.php';
            
            // Create QR code directory if it doesn't exist
            $qrDir = __DIR__ . '/../uploads/qrcodes/';
            if (!file_exists($qrDir)) {
                if (!mkdir($qrDir, 0755, true)) {
                    error_log('[QR Code Generation Error] Failed to create directory: ' . $qrDir, 3, __DIR__ . '/../logs/error.log');
                    return false;
                }
            }
            
            // Check if directory is writable
            if (!is_writable($qrDir)) {
                error_log('[QR Code Generation Error] Directory not writable: ' . $qrDir, 3, __DIR__ . '/../logs/error.log');
                return false;
            }
            
            // Generate the link for the equipment
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $link = $baseUrl . "/clrms/pages/equipment_view.php?id=" . $equipmentId;
            
            // Generate QR code file path
            $qrPath = $qrDir . "equipment_" . $equipmentId . ".png";
            
            // Generate the QR code using the new generator
            $result = QRCodeGenerator::generate($link, $qrPath, 200);
            
            // Verify the file was created
            if (!$result || !file_exists($qrPath)) {
                error_log('[QR Code Generation Error] QR code file not created: ' . $qrPath, 3, __DIR__ . '/../logs/error.log');
                return false;
            }
            
            error_log('[QR Code Generation Success] QR code generated for equipment ID: ' . $equipmentId . ' at: ' . $qrPath, 3, __DIR__ . '/../logs/error.log');
            return true;
        } catch (Exception $e) {
            error_log('[QR Code Generation Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    /**
     * Get all equipment records
     * @param bool $includeArchived
     * @return array
     * What: Fetches all equipment records
     * Why: For listing/overview
     * How: Calls Equipment::getAllEquipment
     */
    public function getAllEquipment($includeArchived = false) {
        return $this->equipmentModel->getAllEquipment($includeArchived);
    }

    /**
     * Get equipment by ID
     * @param int $id
     * @return array|null
     * What: Fetches equipment by ID
     * Why: For details/editing
     * How: Calls Equipment::getEquipmentById
     */
    public function getEquipmentById($id) {
        return $this->equipmentModel->getEquipmentById($id);
    }

    /**
     * Update equipment
     * @param int $id
     * @param array $data
     * @return bool
     * What: Updates equipment record
     * Why: For editing/updating
     * How: Calls Equipment::updateEquipment
     */
    public function updateEquipment($id, $data) {
        return $this->equipmentModel->updateEquipment($id, $data, true);
    }

    /**
     * Archive equipment
     * @param int $id
     * @return bool
     * What: Archives equipment
     * Why: For soft delete
     * How: Calls Equipment::archiveEquipment
     */
    public function archiveEquipment($id) {
        return $this->equipmentModel->archiveEquipment($id);
    }

    /**
     * Restore archived equipment
     * @param int $id
     * @return bool
     * What: Restores archived equipment
     * Why: For undoing archive
     * How: Calls Equipment::restoreEquipment
     */
    public function restoreEquipment($id) {
        return $this->equipmentModel->restoreEquipment($id);
    }

    /**
     * Get available equipment
     * @return array
     * What: Fetches available equipment
     * Why: For borrow forms, etc.
     * How: Calls Equipment::getAvailableEquipment
     */
    public function getAvailableEquipment() {
        return $this->equipmentModel->getAvailableEquipment();
    }

    /**
     * Filter and search equipment
     * @param string $status
     * @param string $location
     * @param string $search
     * @return array
     * What: Filters/searches equipment
     * Why: For AJAX table/filter
     * How: Calls Equipment::filterAndSearchEquipment
     */
    public function filterAndSearchEquipment($status = '', $location = '', $search = '', $category = '') {
        return $this->equipmentModel->filterAndSearchEquipment($status, $location, $search, $category);
    }

    /**
     * Log equipment action
     * @param int $equipment_id
     * @param string $action
     * @param int|null $user_id
     * @param string|null $transfer_to
     * @param string|null $authorized_by
     * @param string|null $transfer_date
     * @param string|null $remarks
     * @param string|null $from_location
     * @param string|null $previous_values
     * @return bool
     * What: Logs equipment action
     * Why: For audit/history
     * How: Calls Equipment::logEquipmentAction
     */
    public function logEquipmentAction($equipment_id, $action, $user_id = null, $transfer_to = null, $authorized_by = null, $transfer_date = null, $remarks = null, $from_location = null, $previous_values = null) {
        return $this->equipmentModel->logEquipmentAction($equipment_id, $action, $user_id, $transfer_to, $authorized_by, $transfer_date, $remarks, $from_location, $previous_values);
    }

    /**
     * Count all equipment
     * @return int
     * What: Returns the total number of equipment
     * Why: For dashboard KPI
     * How: Calls Equipment::countEquipment
     */
    public function countEquipment() {
        return $this->equipmentModel->countEquipment();
    }

    public function getDueForMaintenance($days = 30) {
        return $this->equipmentModel->getDueForMaintenance($days);
    }
    public function getOverdueMaintenance() {
        return $this->equipmentModel->getOverdueMaintenance();
    }
    public function updateLastMaintenanceDate($equipment_id, $date) {
        return $this->equipmentModel->updateLastMaintenanceDate($equipment_id, $date);
    }

    /**
     * Regenerate QR code for existing equipment
     * @param int $equipmentId
     * @return bool
     */
    public function regenerateQRCode($equipmentId) {
        return $this->generateQRCode($equipmentId);
    }

    /**
     * Generate QR codes for all equipment that don't have them
     * @return array
     */
    public function generateMissingQRCodes() {
        $results = ['success' => 0, 'failed' => 0, 'errors' => [], 'details' => []];
        
        try {
            $allEquipment = $this->getAllEquipment();
            $qrDir = __DIR__ . '/../uploads/qrcodes/';
            
            // Ensure QR directory exists
            if (!file_exists($qrDir)) {
                if (!mkdir($qrDir, 0755, true)) {
                    $results['errors'][] = "Failed to create QR directory: " . $qrDir;
                    return $results;
                }
            }
            
            foreach ($allEquipment as $equipment) {
                $qrPath = $qrDir . "equipment_" . $equipment['id'] . ".png";
                $results['details'][] = "Checking equipment ID: " . $equipment['id'] . " - " . $equipment['name'];
                
                // Check if QR code doesn't exist
                if (!file_exists($qrPath)) {
                    $results['details'][] = "  QR code missing for equipment ID: " . $equipment['id'];
                    if ($this->generateQRCode($equipment['id'])) {
                        $results['success']++;
                        $results['details'][] = "  ✅ Successfully generated QR for equipment ID: " . $equipment['id'];
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Failed to generate QR for equipment ID: " . $equipment['id'];
                        $results['details'][] = "  ❌ Failed to generate QR for equipment ID: " . $equipment['id'];
                    }
                } else {
                    $results['details'][] = "  QR code already exists for equipment ID: " . $equipment['id'];
                }
            }
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }

    // Add more methods for other equipment-related business logic as needed
} 