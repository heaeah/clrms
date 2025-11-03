<?php
require_once '../includes/auth.php';
require_once '../classes/EquipmentService.php';
require_once '../classes/MasterlistService.php';
require_once '../vendor/phpqrcode/qrlib.php'; // Include QR library

$equipmentService = new EquipmentService();
$masterlistService = new MasterlistService();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate against masterlists
        $category = $_POST['category'] ?? '';
        if (!empty($category) && !$masterlistService->validateMasterlistValue('equipment_categories', 'name', $category)) {
            set_flash('danger', 'Invalid category selected. Please choose from the available options.');
            header("Location: inventory.php");
            exit;
        }
        
        // Validate item name against masterlist
        $itemName = $_POST['name'] ?? '';
        if (!empty($itemName) && !empty($category) && !$masterlistService->validateEquipmentItemName($category, $itemName)) {
            set_flash('danger', 'Invalid item name selected. Please choose from the available options for this category.');
            header("Location: inventory.php");
            exit;
        }
        
        $status = $_POST['status'] ?? '';
        if (!empty($status) && !$masterlistService->validateMasterlistValue('equipment_status', 'name', $status)) {
            set_flash('danger', 'Invalid status selected. Please choose from the available options.');
            header("Location: inventory.php");
            exit;
        }
        
        // Validate location against labs table
        $location = $_POST['location'] ?? '';
        if (!empty($location)) {
            $db = new Database();
            $conn = $db->getConnection();
            $query = "SELECT id FROM labs WHERE lab_name = :location LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':location', $location);
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                set_flash('danger', 'Invalid location selected. Please choose from the available options.');
                header("Location: inventory.php");
                exit;
            }
        }
        
        $lastId = $equipmentService->handleAddEquipment($_POST, $_FILES);
        if ($lastId) {
            // Check if QR code was generated (use absolute path from document root)
            $qrPath = __DIR__ . "/../uploads/qrcodes/equipment_" . $lastId . ".png";
            if (file_exists($qrPath)) {
                set_flash('success', 'Equipment added successfully with QR code generated.');
            } else {
                set_flash('success', 'Equipment added successfully. QR code generation may have failed.');
            }
        } else {
            set_flash('danger', 'Failed to add equipment.');
        }
        header("Location: inventory.php?refresh=1");
        exit;
    } catch (Exception $e) {
        // What: Error during equipment save
        // Why: DB error, file error, etc.
        // How: Log error and show user-friendly message
        error_log('[Save Equipment Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header("Location: inventory.php");
        exit;
    }
}

header("Location: inventory.php");
exit;
