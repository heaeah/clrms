<?php
require_once '../../classes/Database.php';

header('Content-Type: application/json');

if (!isset($_GET['date']) || !isset($_GET['time_start']) || !isset($_GET['time_end'])) {
    echo json_encode([]);
    exit;
}

$date = $_GET['date'];
$time_start = $_GET['time_start'];
$time_end = $_GET['time_end'];

$pdo = (new Database())->getConnection();

// Find labs that are reserved for the given date and time range
$stmt = $pdo->prepare('SELECT lab_id FROM lab_reservations WHERE date_reserved = ? AND status IN ("Approved", "Pending") AND (time_start < ? AND time_end > ?)');
$stmt->execute([$date, $time_end, $time_start]);
$occupied = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($occupied); 