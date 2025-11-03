<?php
/**
 * Automated Maintenance Check Script
 * This script should be run daily via cron job to check for equipment due for maintenance
 * and create automated maintenance records.
 */

require_once __DIR__ . '/../classes/Equipment.php';
require_once __DIR__ . '/../classes/Database.php';


// Log the start of the script
error_log('[Maintenance Check] Starting automated maintenance check at ' . date('Y-m-d H:i:s'));

try {
    $equipment = new Equipment();
    $result = $equipment->checkAndScheduleMaintenance();
    
    if ($result['success']) {
        error_log('[Maintenance Check] ' . $result['message']);
        echo "SUCCESS: " . $result['message'] . "\n";
    } else {
        error_log('[Maintenance Check] ERROR: ' . $result['error']);
        echo "ERROR: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    error_log('[Maintenance Check] FATAL ERROR: ' . $e->getMessage());
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Log completion
error_log('[Maintenance Check] Automated maintenance check completed at ' . date('Y-m-d H:i:s'));
echo "Maintenance check completed at " . date('Y-m-d H:i:s') . "\n";
?>
