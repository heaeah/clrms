<?php
require_once '../../classes/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $pdo = (new Database())->getConnection();
    
    error_log('[Simple Notifications API] Starting API call');
    
    // Simple count queries
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrow_requests WHERE status = 'Pending'");
    $stmt->execute();
    $pendingBorrowRequests = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lab_reservations WHERE status = 'Pending'");
    $stmt->execute();
    $pendingLabReservations = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrow_requests WHERE status = 'Approved'");
    $stmt->execute();
    $borrowedItems = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    error_log('[Simple Notifications API] Counts - Borrow: ' . $pendingBorrowRequests . ', Reservations: ' . $pendingLabReservations . ', Borrowed: ' . $borrowedItems);
    
    echo json_encode([
        'success' => true,
        'counts' => [
            'pendingBorrowRequests' => $pendingBorrowRequests,
            'pendingLabReservations' => $pendingLabReservations,
            'borrowedItems' => $borrowedItems
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('[Simple Notifications API Error] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch notifications: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?> 