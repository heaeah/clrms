<?php
require_once '../../includes/auth.php';
require_once '../../classes/Database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$pdo = (new Database())->getConnection();

// Borrow requests
$borrowRequests = [];
$stmt = $pdo->prepare("SELECT id, control_number, date_requested, status, purpose, location_of_use FROM borrow_requests WHERE user_id = ? ORDER BY date_requested DESC LIMIT 20");
$stmt->execute([$user_id]);
$borrowRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lab reservations
$labReservations = [];
$stmt = $pdo->prepare("SELECT id, lab_id, date_reserved, time_start, time_end, status, purpose FROM lab_reservations WHERE user_id = ? ORDER BY date_reserved DESC LIMIT 20");
$stmt->execute([$user_id]);
$labReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get lab names for reservations
$labNames = [];
if (!empty($labReservations)) {
    $labIds = array_unique(array_column($labReservations, 'lab_id'));
    $in = str_repeat('?,', count($labIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, lab_name FROM labs WHERE id IN ($in)");
    $stmt->execute($labIds);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $labNames[$row['id']] = $row['lab_name'];
    }
}
foreach ($labReservations as &$res) {
    $res['lab_name'] = $labNames[$res['lab_id']] ?? 'Lab #' . $res['lab_id'];
}

// Output
echo json_encode([
    'success' => true,
    'borrow_requests' => $borrowRequests,
    'lab_reservations' => $labReservations
]);