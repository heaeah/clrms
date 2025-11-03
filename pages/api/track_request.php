<?php
require_once '../../classes/Database.php';
header('Content-Type: application/json');

$code = $_GET['code'] ?? '';
if (!$code) {
    echo json_encode(['success' => false, 'message' => 'No tracking code provided.']);
    exit;
}

$pdo = (new Database())->getConnection();
// Try borrow request first
$stmt = $pdo->prepare("SELECT control_number, date_requested, purpose, location_of_use, status, remarks, borrower_name, borrower_email FROM borrow_requests WHERE tracking_code = ? LIMIT 1");
$stmt->execute([$code]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);
if ($request) {
    // Fetch items
    $stmtItems = $pdo->prepare("SELECT e.name, i.quantity FROM borrow_request_items i JOIN equipment e ON i.equipment_id = e.id WHERE i.request_id = (SELECT id FROM borrow_requests WHERE tracking_code = ? LIMIT 1)");
    $stmtItems->execute([$code]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    $request['items'] = $items;
    echo json_encode(['success' => true, 'type' => 'borrow', 'request' => $request]);
    exit;
}
// Try lab reservation
$stmt = $pdo->prepare("SELECT control_number, date_reserved, time_start, time_end, purpose, needed_tools, status, remarks, requested_by, borrower_email, lab_id, reservation_start, reservation_end, approved_letter, id_photo FROM lab_reservations WHERE tracking_code = ? LIMIT 1");
$stmt->execute([$code]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);
if ($reservation) {
    // Fetch lab name
    $stmtLab = $pdo->prepare("SELECT lab_name FROM labs WHERE id = ?");
    $stmtLab->execute([$reservation['lab_id']]);
    $lab = $stmtLab->fetch(PDO::FETCH_ASSOC);
    $reservation['lab_name'] = $lab['lab_name'] ?? '';
    echo json_encode(['success' => true, 'type' => 'lab', 'reservation' => $reservation]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'No request or reservation found for this tracking code.']);