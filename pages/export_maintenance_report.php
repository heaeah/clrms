<?php
/**
 * Export Maintenance Report to CSV
 */

require_once '../includes/auth.php';
require_role(['ICT Staff']);

require_once '../classes/MaintenanceService.php';
require_once '../classes/EquipmentService.php';

$maintenanceService = new MaintenanceService();
$equipmentService = new EquipmentService();

try {
    // Get maintenance data
    $maintenanceRecords = $maintenanceService->getAllMaintenance();
    $dueMaintenance = $maintenanceService->getDueMaintenance();
    $overdueMaintenance = $maintenanceService->getOverdueMaintenance();
    
    // Set headers for CSV download
    $filename = 'maintenance_report_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write CSV headers
    fputcsv($output, [
        'ID',
        'Equipment Name',
        'Type',
        'Issue Description',
        'Maintenance Date',
        'Due Date',
        'Status',
        'Notes'
    ]);
    
    // Write maintenance records
    foreach ($maintenanceRecords as $record) {
        fputcsv($output, [
            $record['id'],
            $record['equipment_name'] ?? 'Unknown',
            $record['type'],
            $record['issue_description'],
            $record['maintenance_date'],
            $record['due_date'],
            $record['repair_status'],
            $record['notes']
        ]);
    }
    
    // Add summary section
    fputcsv($output, []); // Empty row
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Maintenance Records', count($maintenanceRecords)]);
    fputcsv($output, ['Due Maintenance', count($dueMaintenance)]);
    fputcsv($output, ['Overdue Maintenance', count($overdueMaintenance)]);
    fputcsv($output, ['Generated on', date('Y-m-d H:i:s')]);
    
    fclose($output);
    
} catch (Exception $e) {
    error_log('[Export Maintenance Report Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    
    // Send error response
    header('Content-Type: text/plain');
    echo "Error generating report: " . $e->getMessage();
    exit;
}
?>
