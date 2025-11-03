<?php
require_once 'Lab.php';

class LabService {
    private $labModel;

    public function __construct() {
        $this->labModel = new Lab();
    }

    /**
     * Get all labs
     * @return array
     * What: Fetches all labs
     * Why: For listing/overview
     * How: Calls Lab::getAll
     */
    public function getAllLabs() {
        return $this->labModel->getAll();
    }

    /**
     * Get lab by ID
     * @param int $id
     * @return array|null
     * What: Fetches lab by ID
     * Why: For details/editing
     * How: Calls Lab::getById
     */
    public function getLabById($id) {
        return $this->labModel->getById($id);
    }

    /**
     * Create a new lab (not implemented in model)
     * @param array $data
     * @return bool
     * What: Creates a new lab
     * Why: For adding labs
     * How: Calls Lab::create
     */
    public function createLab($data) {
        return $this->labModel->create($data);
    }

    /**
     * Update a lab (not implemented in model)
     * @param int $id
     * @param array $data
     * @return bool
     * What: Updates a lab
     * Why: For editing labs
     * How: Calls Lab::update
     */
    public function updateLab($id, $data) {
        return $this->labModel->update($id, $data);
    }

    /**
     * Delete a lab (not implemented in model)
     * @param int $id
     * @return bool
     * What: Deletes a lab
     * Why: For removal
     * How: Calls Lab::delete
     */
    public function deleteLab($id) {
        return $this->labModel->delete($id);
    }
} 