<?php
require_once '../../classes/BorrowRequestService.php';
require_once '../../classes/LabReservationService.php';
header('Content-Type: application/json');

$borrowRequestService = new BorrowRequestService();
$labReservationService = new LabReservationService();

if (isset($_GET['id'])) {
    // Return HTML details for a single event (for modal)
    $id = intval($_GET['id']);
    $type = $_GET['type'] ?? '';
    try {
        if ($type === 'lab') {
            $reservations = $labReservationService->getAllReservations();
            foreach ($reservations as $event) {
                if ($event['id'] == $id) {
                    echo '<strong>Lab:</strong> ' . htmlspecialchars($event['lab_name'] ?? $event['lab_id']) . '<br>';
                    echo '<strong>Date:</strong> ' . htmlspecialchars($event['date_reserved'] ?? $event['date_requested']) . '<br>';
                    echo '<strong>Start:</strong> ' . htmlspecialchars($event['time_start'] ?? $event['start_datetime'] ?? '') . '<br>';
                    echo '<strong>End:</strong> ' . htmlspecialchars($event['time_end'] ?? $event['end_datetime'] ?? '') . '<br>';
                    echo '<strong>Status:</strong> ' . htmlspecialchars($event['status']) . '<br>';
                    exit;
                }
            }
        } else {
            $requests = $borrowRequestService->getAllBorrowRequests();
            foreach ($requests as $event) {
                if ($event['id'] == $id) {
                    echo '<strong>Equipment:</strong> ' . htmlspecialchars($event['equipment_names'] ?? $event['equipment_id']) . '<br>';
                    echo '<strong>Borrower:</strong> ' . htmlspecialchars($event['borrower_name']) . '<br>';
                    echo '<strong>Date:</strong> ' . htmlspecialchars($event['date_requested']) . '<br>';
                    echo '<strong>Start:</strong> ' . htmlspecialchars($event['start_datetime'] ?? '') . '<br>';
                    echo '<strong>End:</strong> ' . htmlspecialchars($event['end_datetime'] ?? $event['return_date'] ?? '') . '<br>';
                    echo '<strong>Status:</strong> ' . htmlspecialchars($event['status']) . '<br>';
                    exit;
                }
            }
        }
    } catch (Exception $e) {
        error_log('[Resource Events Modal Error] ' . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
    }
    echo 'Event not found.';
    exit;
}

// Return all events as JSON
$events = [];
try {
    /**
     * What: Fetch all borrow requests for equipment events
     * Why: For resource calendar
     * How: Uses BorrowRequestService::getAllBorrowRequests
     */
    $requests = $borrowRequestService->getAllBorrowRequests();
    foreach ($requests as $row) {
        // Only include approved requests that haven't finished yet
        if ($row['status'] === 'Approved') {
            $now = new DateTime();
            $borrowEnd = new DateTime($row['borrow_end']);
            
            // Only add to events if the borrow period hasn't ended yet
            if ($borrowEnd > $now) {
                $events[] = [
                    'id' => $row['id'],
                    'title' => 'Equipment: ' . ($row['equipment_names'] ?? $row['equipment_id']),
                    'start' => $row['start_datetime'] ?? $row['date_requested'],
                    'end' => $row['end_datetime'] ?? $row['borrow_end'],
                    'type' => 'equipment',
                    'status' => $row['status'],
                ];
            }
        }
    }
} catch (Exception $e) {
    error_log('[Resource Events Equipment Error] ' . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
}
try {
    /**
     * What: Fetch all lab reservations for lab events
     * Why: For resource calendar
     * How: Uses LabReservationService::getAllReservations
     */
    $reservations = $labReservationService->getAllReservations();
    foreach ($reservations as $row) {
        // Only include approved reservations that haven't finished yet
        if ($row['status'] === 'Approved') {
            $now = new DateTime();
            $reservationEnd = new DateTime($row['reservation_end']);
            
            // Only add to events if the reservation hasn't finished yet
            if ($reservationEnd > $now) {
                $events[] = [
                    'id' => $row['id'],
                    'title' => 'Lab: ' . ($row['lab_name'] ?? $row['lab_id']),
                    'start' => $row['reservation_start'],
                    'end' => $row['reservation_end'],
                    'type' => 'lab',
                    'status' => $row['status'],
                ];
            }
        }
    }
} catch (Exception $e) {
    error_log('[Resource Events Lab Error] ' . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
}

// Add custom calendar events
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $query = "SELECT * FROM calendar_events ORDER BY event_date ASC, start_time ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $customEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($customEvents as $event) {
        $color = '#ff9800'; // Default orange
        switch ($event['type']) {
            case 'meeting':
                $color = '#1976d2'; // Blue
                break;
            case 'maintenance':
                $color = '#f44336'; // Red
                break;
            case 'training':
                $color = '#43a047'; // Green
                break;
        }
        
        $events[] = [
            'id' => 'custom_' . $event['id'],
            'title' => $event['title'],
            'start' => $event['event_date'] . 'T' . $event['start_time'],
            'end' => $event['event_date'] . 'T' . $event['end_time'],
            'type' => 'custom',
            'color' => $color,
            'description' => $event['description']
        ];
    }
} catch (Exception $e) {
    error_log('[Resource Events Custom Error] ' . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
}

echo json_encode($events); 