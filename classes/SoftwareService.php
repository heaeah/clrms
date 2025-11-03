<?php
require_once 'Software.php';

class SoftwareService {
    private $softwareModel;

    public function __construct() {
        $this->softwareModel = new Software();
    }

    /**
     * Add a new software record (with validation)
     * @param array $postData
     * @return bool
     * What: Adds a software record
     * Why: Centralizes add logic and validation
     * How: Calls Software::addSoftware
     */
    public function handleAddSoftware($postData) {
        return $this->softwareModel->addSoftware($postData);
    }

    /**
     * Get all software records
     * @return array
     * What: Fetches all software records
     * Why: For listing/overview
     * How: Calls Software::getAllSoftware
     */
    public function getAllSoftware() {
        return $this->softwareModel->getAllSoftware();
    }

    /**
     * Get software by ID
     * @param int $id
     * @return array|null
     * What: Fetches software by ID
     * Why: For details/editing
     * How: Calls Software::getById
     */
    public function getSoftwareById($id) {
        return $this->softwareModel->getById($id);
    }

    /**
     * Update software
     * @param int $id
     * @param array $data
     * @return bool
     * What: Updates software record
     * Why: For editing/updating
     * How: Calls Software::updateSoftware
     */
    public function updateSoftware($id, $data) {
        return $this->softwareModel->updateSoftware(array_merge(['id' => $id], $data));
    }

    /**
     * Delete software
     * @param int $id
     * @return bool
     * What: Deletes software record
     * Why: For removal
     * How: Calls Software::deleteSoftware
     */
    public function deleteSoftware($id) {
        return $this->softwareModel->deleteSoftware($id);
    }

    /**
     * Get expiring licenses
     * @param int $days
     * @return array
     * What: Fetches expiring licenses
     * Why: For notifications
     * How: Calls Software::getExpiringLicenses
     */
    public function getExpiringLicenses($days = 30) {
        return $this->softwareModel->getExpiringLicenses($days);
    }

    public function getExpiredLicenses() {
        return $this->softwareModel->getExpiredLicenses();
    }

    /**
     * Get equipment from inventory for software assignment
     * @return array
     */
    public function getEquipmentFromInventory() {
        return $this->softwareModel->getEquipmentFromInventory();
    }

    /**
     * Get all software with lab information
     * @return array
     */
    public function getAllSoftwareWithLabInfo() {
        return $this->softwareModel->getAllSoftwareWithLabInfo();
    }

    /**
     * Get software by lab
     * @param int $labId
     * @return array
     */
    public function getSoftwareByLab($labId) {
        return $this->softwareModel->getSoftwareByLab($labId);
    }
} 