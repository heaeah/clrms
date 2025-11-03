<?php
// Remove authentication requirement for API testing
// require_once '../../includes/auth.php';
require_once '../../classes/BorrowRequest.php';
require_once '../../classes/LabReservation.php';
require_once '../../classes/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = (new Database())->getConnection();
    
    // Debug: Log that we're starting
    error_log('[Notifications API] Starting API call');
    
    // Get the last check timestamp from the request
    $lastCheck = isset($_GET['lastCheck']) ? $_GET['lastCheck'] : date('Y-m-d H:i:s', strtotime('-5 minutes'));
    
    // Debug: Log the lastCheck value
    error_log('[Notifications API] LastCheck parameter: ' . $lastCheck);
    
    // Get new borrow requests (created after the last check)
    $stmt = $pdo->prepare("
        SELECT 
            br.id,
            br.borrower_name,
            br.date_requested,
            br.status,
            GROUP_CONCAT(e.name SEPARATOR ', ') as equipment_names,
            'borrow_request' as type
        FROM borrow_requests br
        LEFT JOIN borrow_request_items bri ON br.id = bri.request_id
        LEFT JOIN equipment e ON bri.equipment_id = e.id
        WHERE br.date_requested > ?
        AND br.status = 'Pending'
        GROUP BY br.id
        ORDER BY br.date_requested DESC
    ");
    $stmt->execute([$lastCheck]);
    $newBorrowRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get new lab reservations (created after the last check)
    $stmt = $pdo->prepare("
        SELECT 
            lr.id,
            lr.purpose,
            lr.date_reserved,
            lr.time_start,
            lr.time_end,
            lr.status,
            l.name as lab_name,
            'lab_reservation' as type
        FROM lab_reservations lr
        LEFT JOIN labs l ON lr.lab_id = l.id
        WHERE lr.date_reserved > ?
        AND lr.status = 'Pending'
        ORDER BY lr.date_reserved DESC
    ");
    $stmt->execute([$lastCheck]);
    $newLabReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get counts for dashboard updates
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrow_requests WHERE status = 'Pending'");
    $stmt->execute();
    $pendingBorrowRequests = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Debug logging
    error_log('[Notifications API] Last check: ' . $lastCheck . ', Pending borrow requests: ' . $pendingBorrowRequests);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lab_reservations WHERE status = 'Pending'");
    $stmt->execute();
    $pendingLabReservations = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Debug logging
    error_log('[Notifications API] Pending lab reservations: ' . $pendingLabReservations);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrow_requests WHERE status = 'Approved'");
    $stmt->execute();
    $borrowedItems = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Combine all notifications
    $notifications = array_merge($newBorrowRequests, $newLabReservations);
    
    // Sort by date (newest first)
    usort($notifications, function($a, $b) {
        return strtotime($b['date_requested'] ?? $b['date_reserved']) - strtotime($a['date_requested'] ?? $a['date_reserved']);
    });
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'counts' => [
            'pendingBorrowRequests' => $pendingBorrowRequests,
            'pendingLabReservations' => $pendingLabReservations,
            'borrowedItems' => $borrowedItems
        ],
        'timestamp' => date('Y-m-d H:i:s'),
        'lastCheck' => $lastCheck
    ]);
    
} catch (Exception $e) {
    error_log('[Notifications API Error] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch notifications: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?> 