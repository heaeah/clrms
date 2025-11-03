<?php
require_once '../includes/auth.php';
require_once '../classes/EquipmentService.php';
require_once '../classes/BorrowRequest.php';

$equipmentService = new EquipmentService();
$borrowObj = new BorrowRequest();

// Get Equipment ID from URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$id = intval($_GET['id']);
/**
 * What: Fetch equipment by ID
 * Why: For details view
 * How: Uses EquipmentService::getEquipmentById
 */
try {
    $equipment = $equipmentService->getEquipmentById($id);
    if (!$equipment) {
        http_response_code(404);
        echo "<h2 style='margin: 50px; color: red;'>Equipment not found or deleted.</h2>";
        exit;
    }
} catch (Exception $e) {
    error_log('[Equipment Details Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    http_response_code(500);
    echo "<h2 style='margin: 50px; color: red;'>Error loading equipment details.</h2>";
    exit;
}


// Handle Borrow Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'equipment_id' => $id,
        'purpose' => $_POST['purpose'],
        'location' => $_POST['location'],
        'borrow_date' => $_POST['borrow_date'],
        'return_date' => $_POST['return_date']
    ];

    if ($borrowObj->createBorrowRequest($data, $_SESSION['user_id'])) {
        set_flash('success', 'Borrow request submitted.');
        header("Location: dashboard.php");
        exit;
    } else {
        set_flash('danger', 'Failed to submit request.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Equipment Details - CLRMS</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/equipment_details.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="p-4">
    <div class="container">
        <?php show_flash(); ?>

        <h1 class="mb-4">Equipment Details</h1>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Equipment Information
            </div>
            <div class="card-body">
                <h5><?= htmlspecialchars($equipment['name']) ?></h5>
                <p><strong>Serial Number:</strong> <?= htmlspecialchars($equipment['serial_number']) ?></p>
                <p><strong>Model:</strong> <?= htmlspecialchars($equipment['model']) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($equipment['status']) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($equipment['location']) ?></p>
                <p><strong>Remarks:</strong> <?= htmlspecialchars($equipment['remarks']) ?></p>
            </div>
        </div>

        <?php if ($equipment['status'] == 'Available'): ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    Borrow This Equipment
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Purpose</label>
                            <input type="text" name="purpose" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Location of Use</label>
                            <input type="text" name="location" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Borrow Date</label>
                            <input type="date" name="borrow_date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Return Date</label>
                            <input type="date" name="return_date" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Submit Borrow Request</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                This equipment is currently not available for borrowing.
            </div>
        <?php endif; ?>

    </div>
</main>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/equipment_details.js"></script>
</body>
</html>
