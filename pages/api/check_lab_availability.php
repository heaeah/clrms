<?php
require_once '../../classes/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['lab_id']) || !isset($input['start_time']) || !isset($input['end_time'])) {
        throw new Exception('Missing required parameters');
    }
    
    $labId = $input['lab_id'];
    $startTime = $input['start_time'];
    $endTime = $input['end_time'];
    
    $pdo = (new Database())->getConnection();
    
    // Check for overlapping reservations with comprehensive overlap detection
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count,
               GROUP_CONCAT(CONCAT(requested_by, ' (', DATE_FORMAT(reservation_start, '%M %d, %Y %h:%i %p'), ' - ', DATE_FORMAT(reservation_end, '%M %d, %Y %h:%i %p'), ')') SEPARATOR '; ') as conflicting_reservations
        FROM lab_reservations 
        WHERE lab_id = ? 
        AND status IN ('Approved', 'Pending')
        AND (
            (reservation_start < ? AND reservation_end > ?) OR
            (reservation_start < ? AND reservation_end > ?) OR
            (reservation_start >= ? AND reservation_end <= ?) OR
            (reservation_start <= ? AND reservation_end >= ?)
        )
    ");
    
    $stmt->execute([
        $labId, 
        $endTime, $startTime,    // Existing reservation overlaps with new reservation start
        $endTime, $startTime,    // Existing reservation overlaps with new reservation end
        $startTime, $endTime,    // New reservation is completely within existing reservation
        $startTime, $endTime     // New reservation completely contains existing reservation
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $conflictCount = $result['count'];
    $conflictingReservations = $result['conflicting_reservations'] ?? '';
    
    echo json_encode([
        'available' => $conflictCount === 0,
        'conflict_count' => $conflictCount,
        'conflicting_reservations' => $conflictingReservations
    ]);
    
} catch (Exception $e) {
    error_log('[Check Lab Availability Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to check availability: ' . $e->getMessage()
    ]);
}
?> 