<?php
header('Content-Type: application/json');
require_once '../../includes/auth.php';
require_once '../../classes/EquipmentService.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$equipmentId = $input['equipment_id'] ?? null;

if (!$equipmentId || !is_numeric($equipmentId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid equipment ID']);
    exit;
}

try {
    $equipmentService = new EquipmentService();
    
    // Check if equipment exists
    $equipment = $equipmentService->getEquipmentById($equipmentId);
    if (!$equipment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Equipment not found']);
        exit;
    }
    
    // Regenerate QR code
    $result = $equipmentService->regenerateQRCode($equipmentId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'QR Code regenerated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to regenerate QR code']);
    }
    
} catch (Exception $e) {
    error_log('[Regenerate QR Error] ' . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?> 