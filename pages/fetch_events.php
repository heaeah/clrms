<?php
require_once '../classes/BorrowRequest.php';
require_once '../classes/LabReservation.php';

$borrow = new BorrowRequest();
$reserve = new LabReservation();

// Get calendar-compatible event arrays
$events = array_merge(
    $borrow->getCalendarEvents(),      // Should return equipment borrow events
    $reserve->getCalendarEvents()      // Should return lab reservation events
);

header('Content-Type: application/json');
echo json_encode($events);
