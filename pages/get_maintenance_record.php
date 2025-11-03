<?php
/**
 * Get maintenance record by ID for editing
 */

require_once '../classes/MaintenanceService.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('Invalid maintenance record ID');
    }
    
    $maintenanceService = new MaintenanceService();
    $record = $maintenanceService->getMaintenanceById($_GET['id']);
    
    if (!$record) {
        throw new Exception('Maintenance record not found');
    }
    
    echo json_encode([
        'success' => true,
        'record' => $record
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
