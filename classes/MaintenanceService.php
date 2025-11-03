<?php
require_once 'Maintenance.php';

class MaintenanceService {
    private $maintenanceModel;

    public function __construct() {
        $this->maintenanceModel = new Maintenance();
    }

    /**
     * Add a new maintenance record (with validation and file handling)
     * @param array $data
     * @return bool
     */
    public function handleAddMaintenance($data, $files = []) {
        // Map form fields to database fields
        $mappedData = [
            'equipment_id' => $data['equipment_id'] ?? null,
            'type' => $data['type'] ?? $data['maintenance_type'] ?? 'Maintenance',
            'issue_description' => $data['issue_description'] ?? $data['description'] ?? '',
            'maintenance_date' => $data['maintenance_date'] ?? date('Y-m-d'),
            'due_date' => $data['due_date'] ?? $data['maintenance_date'] ?? date('Y-m-d'),
            'repair_status' => $data['repair_status'] ?? 'Pending',
            'notes' => $data['notes'] ?? $data['description'] ?? ''
        ];
        
        // Handle file upload if present in $_FILES
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $filename = uniqid('maint_') . '_' . basename($_FILES['photo']['name']);
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $mappedData['photo'] = $filename;
            } else {
                $mappedData['photo'] = null;
            }
        } else {
            $mappedData['photo'] = null;
        }
        
        return $this->maintenanceModel->addMaintenance($mappedData);
    }

    /**
     * Get all maintenance records
     * @return array
     */
    public function getAllMaintenance() {
        return $this->maintenanceModel->getAllMaintenance();
    }

    /**
     * Get maintenance record by ID
     * @param int $id
     * @return array|null
     */
    public function getMaintenanceById($id) {
        return $this->maintenanceModel->getMaintenanceById($id);
    }

    /**
     * Update a maintenance record
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateMaintenance($id, $data) {
        // Map form fields to database fields
        $mappedData = [
            'equipment_id' => $data['equipment_id'] ?? null,
            'type' => $data['maintenance_type'] ?? 'Maintenance',
            'issue_description' => $data['description'] ?? '',
            'maintenance_date' => $data['maintenance_date'] ?? date('Y-m-d'),
            'due_date' => $data['maintenance_date'] ?? date('Y-m-d'),
            'repair_status' => $data['repair_status'] ?? 'Pending',
            'notes' => $data['description'] ?? ''
        ];
        
        return $this->maintenanceModel->updateMaintenance($id, $mappedData);
    }

    /**
     * Get due/overdue maintenance
     * @return array
     */
    public function getDueMaintenance() {
        return $this->maintenanceModel->getDueMaintenance();
    }

    /**
     * Get all maintenance logs
     * @return array
     */
    public function getAllLogs() {
        if (method_exists($this->maintenanceModel, 'getAllLogs')) {
            return $this->maintenanceModel->getAllLogs();
        }
        return [];
    }

    /**
     * Count all maintenance due
     * @return int
     */
    public function countMaintenanceDue() {
        return $this->maintenanceModel->countMaintenanceDue();
    }

    /**
     * Get all maintenance records with a due date (for calendar events)
     * @return array
     */
    public function getAllDueDates() {
        return $this->maintenanceModel->getAllDueDates();
    }

    /**
     * Get maintenance due today
     * @return array
     */
    public function getDueToday() {
        return $this->maintenanceModel->getDueToday();
    }

    /**
     * Get overdue maintenance
     * @return array
     */
    public function getOverdue() {
        return $this->maintenanceModel->getOverdue();
    }

    /**
     * Get overdue maintenance (alias for getOverdue)
     * @return array
     */
    public function getOverdueMaintenance() {
        return $this->maintenanceModel->getOverdue();
    }

    /**
     * Get maintenance records by type (Maintenance or Repair)
     * @param string $type
     * @return array
     */
    public function getMaintenanceByType($type) {
        return $this->maintenanceModel->getMaintenanceByType($type);
    }

    /**
     * Get upcoming maintenance
     * @param int $days
     * @return array
     */
    public function getUpcoming($days = 7) {
        return $this->maintenanceModel->getUpcoming($days);
    }

    /**
     * Get expired maintenance
     * @return array
     */
    public function getExpired() {
        return $this->maintenanceModel->getExpired();
    }

    /**
     * Get maintenance by equipment ID
     * @param int $equipmentId
     * @return array
     */
    public function getMaintenanceByEquipmentId($equipmentId) {
        return $this->maintenanceModel->getMaintenanceByEquipmentId($equipmentId);
    }
} 