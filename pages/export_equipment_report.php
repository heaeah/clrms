<?php
/**
 * Export Equipment Report to CSV
 */

require_once '../includes/auth.php';
require_role(['ICT Staff']);

require_once '../classes/EquipmentService.php';

$equipmentService = new EquipmentService();

try {
    // Get equipment data
    $allEquipment = $equipmentService->getAllEquipment();
    $availableEquipment = $equipmentService->getAvailableEquipment();
    $totalEquipment = $equipmentService->countEquipment();
    $borrowedEquipment = $totalEquipment - count($availableEquipment);
    
    // Set headers for CSV download
    $filename = 'equipment_report_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write CSV headers
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
    
    // Write equipment records
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
    
    // Add summary section
    fputcsv($output, []); // Empty row
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Equipment', $totalEquipment]);
    fputcsv($output, ['Available Equipment', count($availableEquipment)]);
    fputcsv($output, ['Borrowed Equipment', $borrowedEquipment]);
    fputcsv($output, ['Generated on', date('Y-m-d H:i:s')]);
    
    fclose($output);
    
} catch (Exception $e) {
    error_log('[Export Equipment Report Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    
    // Send error response
    header('Content-Type: text/plain');
    echo "Error generating report: " . $e->getMessage();
    exit;
}
?>
