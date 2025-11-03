<?php
/**
 * Export All Reports to CSV
 */

require_once '../includes/auth.php';
require_role(['ICT Staff']);

require_once '../classes/EquipmentService.php';
require_once '../classes/MaintenanceService.php';

$equipmentService = new EquipmentService();
$maintenanceService = new MaintenanceService();

try {
    // Get all data
    $allEquipment = $equipmentService->getAllEquipment();
    $maintenanceRecords = $maintenanceService->getAllMaintenance();
    $dueMaintenance = $maintenanceService->getDueMaintenance();
    $overdueMaintenance = $maintenanceService->getOverdueMaintenance();
    
    // Set headers for CSV download
    $filename = 'ict_complete_report_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write report header
    fputcsv($output, ['ICT COMPLETE REPORT']);
    fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty row
    
    // Equipment Section
    fputcsv($output, ['EQUIPMENT INVENTORY']);
    fputcsv($output, [
        'ID',
        'Name',
        'Serial Number',
        'Model',
        'Status',
        'Location',
        'Installation Date',
        'Last Maintenance Date',
        'Remarks'
    ]);
    
    foreach ($allEquipment as $equipment) {
        fputcsv($output, [
            $equipment['id'],
            $equipment['name'],
            $equipment['serial_number'],
            $equipment['model'],
            $equipment['status'],
            $equipment['location'],
            $equipment['installation_date'],
            $equipment['last_maintenance_date'],
            $equipment['remarks']
        ]);
    }
    
    fputcsv($output, []); // Empty row
    
    // Maintenance Section
    fputcsv($output, ['MAINTENANCE RECORDS']);
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
    
    fputcsv($output, []); // Empty row
    
    // Summary Section
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Equipment', count($allEquipment)]);
    fputcsv($output, ['Total Maintenance Records', count($maintenanceRecords)]);
    fputcsv($output, ['Due Maintenance', count($dueMaintenance)]);
    fputcsv($output, ['Overdue Maintenance', count($overdueMaintenance)]);
    
    fclose($output);
    
} catch (Exception $e) {
    error_log('[Export All Reports Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    
    // Send error response
    header('Content-Type: text/plain');
    echo "Error generating report: " . $e->getMessage();
    exit;
}
?>
