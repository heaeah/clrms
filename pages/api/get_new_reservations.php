<?php
require_once '../../includes/auth.php';
require_once '../../classes/LabReservation.php';
require_once '../../classes/Database.php';

header('Content-Type: application/json');

try {
    $pdo = (new Database())->getConnection();
    
    // Get the latest lab reservations (created in the last 5 minutes)
    $stmt = $pdo->prepare("
        SELECT 
            lr.id,
            lr.requested_by,
            lr.borrower_email,
            lr.purpose,
            lr.date_reserved,
            lr.time_start,
            lr.time_end,
            lr.status,
            lr.remarks,
            lr.tracking_code,
            l.name as lab_name
        FROM lab_reservations lr
        LEFT JOIN labs l ON lr.lab_id = l.id
        WHERE lr.date_reserved >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY lr.date_reserved DESC
    ");
    $stmt->execute();
    $newReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'reservations' => $newReservations,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('[Get New Reservations API Error] ' . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch new reservations',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?> 