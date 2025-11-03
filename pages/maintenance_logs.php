<?php

require_once '../classes/MaintenanceService.php';
$maintenanceService = new MaintenanceService();

try {
    /**
     * What: Fetch all maintenance logs
     * Why: For audit/history
     * How: Uses MaintenanceService::getAllLogs
     */
    $logs = $maintenanceService->getAllLogs();
} catch (Exception $e) {
    // What: Error fetching maintenance logs
    // Why: DB error, etc.
    // How: Log error and show user-friendly message
    error_log('[Maintenance Logs Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $logs = [];
} 
?>
<link href="../assets/css/maintenance_logs.css" rel="stylesheet">
<script src="../assets/js/maintenance_logs.js"></script> 