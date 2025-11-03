<?php
require_once '../../includes/auth.php';
require_once '../../classes/BorrowRequest.php';
require_once '../../classes/Database.php';

header('Content-Type: application/json');

try {
    $pdo = (new Database())->getConnection();
    
    // Get the latest borrow requests (created in the last 5 minutes)
    $stmt = $pdo->prepare("
        SELECT 
            br.id,
            br.borrower_name,
            br.borrower_email,
            br.purpose,
            br.date_requested,
            br.borrow_start,
            br.borrow_end,
            br.return_date,
            br.status,
            br.tracking_code,
            GROUP_CONCAT(e.name SEPARATOR ', ') as equipment_names,
            GROUP_CONCAT(bri.quantity SEPARATOR ', ') as quantities
        FROM borrow_requests br
        LEFT JOIN borrow_request_items bri ON br.id = bri.request_id
        LEFT JOIN equipment e ON bri.equipment_id = e.id
        WHERE br.date_requested >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        GROUP BY br.id
        ORDER BY br.date_requested DESC
    ");
    $stmt->execute();
    $newRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'requests' => $newRequests,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('[Get New Requests API Error] ' . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch new requests',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?> 