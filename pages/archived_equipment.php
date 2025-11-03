<?php
require_once '../includes/auth.php';
require_role(['Lab Admin', 'Student Assistant']);
require_once '../classes/EquipmentService.php';

$equipmentService = new EquipmentService();
try {
    /**
     * What: Fetch all equipment (including archived)
     * Why: For listing archived equipment
     * How: Uses EquipmentService::getAllEquipment(true)
     */
    $archivedList = $equipmentService->getAllEquipment(true); // includes archived
} catch (Exception $e) {
    error_log('[Archived Equipment Fetch Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $archivedList = [];
}

// Handle restore
if (isset($_GET['restore'])) {
    $id = intval($_GET['restore']);
    try {
        if ($equipmentService->restoreEquipment($id)) {
            $equipmentService->logEquipmentAction($id, 'Restored', $_SESSION['user_id']);
            set_flash('success', 'Equipment restored successfully.');
        } else {
            set_flash('danger', 'Failed to restore equipment.');
        }
    } catch (Exception $e) {
        error_log('[Archived Equipment Restore Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header("Location: archived_equipment.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Archived Equipment - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/archived_equipment.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-primary">Archived Equipment</h2>
            <a href="inventory.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
        </div>

        <div class="card shadow rounded-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-secondary">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Serial No.</th>
                            <th>Model</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $hasArchived = false;
                        foreach ($archivedList as $item):
                            if ($item['is_archived'] == 1):
                                $hasArchived = true;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['id']) ?></td>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= htmlspecialchars($item['serial_number']) ?></td>
                                    <td><?= htmlspecialchars($item['model']) ?></td>
                                    <td><span class="badge bg-dark">Archived</span></td>
                                    <td><?= htmlspecialchars($item['location']) ?></td>
                                    <td><?= htmlspecialchars($item['remarks']) ?></td>
                                    <td>
                                        <a href="archived_equipment.php?restore=<?= $item['id'] ?>"
                                           onclick="return confirm('Restore this equipment to active inventory?');"
                                           class="btn btn-sm btn-success">
                                            <i class="bi bi-arrow-clockwise"></i> Restore
                                        </a>
                                    </td>
                                </tr>
                            <?php
                            endif;
                        endforeach;

                        if (!$hasArchived): ?>
                            <tr><td colspan="8" class="text-center text-muted">No archived equipment found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/archived_equipment.js"></script>
</body>
</html>
