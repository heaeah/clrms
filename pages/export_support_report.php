<?php
/**
 * Export Support Report to CSV
 */

require_once '../includes/auth.php';
require_role(['ICT Staff']);

// Set headers for CSV download
$filename = 'support_report_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, ['Support Report']);
fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
fputcsv($output, []);
fputcsv($output, ['Note: Support requests feature is not yet implemented']);
fputcsv($output, ['This report will be available when the support system is set up']);

fclose($output);
?>
