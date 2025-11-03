<?php
require_once '../../config.php';
require_once '../../classes/MasterlistService.php';

header('Content-Type: application/json');

try {
    $category = $_GET['category'] ?? '';
    
    if (empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Category is required']);
        exit;
    }
    
    $masterlistService = new MasterlistService();
    $items = $masterlistService->getEquipmentItemsByCategory($category);
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    error_log('[Get Items by Category API Error] ' . $e->getMessage(), 3, __DIR__ . '/../../logs/error.log');
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching items'
    ]);
}
?>
