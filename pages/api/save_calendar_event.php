<?php
require_once '../../includes/auth.php';
require_once '../../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required_fields = ['title', 'type', 'date', 'start_time', 'end_time'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Validate event type
$allowed_types = ['meeting', 'maintenance', 'training', 'other'];
if (!in_array($input['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid event type']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['date'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// Validate time format
if (!preg_match('/^\d{2}:\d{2}$/', $input['start_time']) || !preg_match('/^\d{2}:\d{2}$/', $input['end_time'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit;
}

// Validate that end time is after start time
if ($input['start_time'] >= $input['end_time']) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
    exit;
}

$location = isset($input['location']) ? $input['location'] : null;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $query = "INSERT INTO calendar_events (title, type, event_date, start_time, end_time, location, description, created_by) 
              VALUES (:title, :type, :event_date, :start_time, :end_time, :location, :description, :created_by)";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        ':title' => $input['title'],
        ':type' => $input['type'],
        ':event_date' => $input['date'],
        ':start_time' => $input['start_time'],
        ':end_time' => $input['end_time'],
        ':location' => $location,
        ':description' => $input['description'] ?? '',
        ':created_by' => $_SESSION['user_id']
    ]);
    
    $event_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Event saved successfully',
        'event_id' => $event_id
    ]);
    
} catch (PDOException $e) {
    error_log('[Save Calendar Event Error] ' . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log('[Save Calendar Event Error] ' . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
} 