<?php
require_once '../../classes/Database.php';
require_once '../../classes/MasterlistService.php';

header('Content-Type: application/json');

$category = $_GET['category'] ?? '';
$masterlistService = new MasterlistService();

if (!empty($category)) {
    $models = $masterlistService->getEquipmentModelsByCategory($category);
    echo json_encode(['success' => true, 'models' => $models]);
} else {
    echo json_encode(['success' => false, 'message' => 'Category not provided.']);
}
?>
