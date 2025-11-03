<?php
require_once '../includes/auth.php';
require_role(['Lab Admin']); // Only admins can access
require_once '../classes/LabService.php';

$labService = new LabService();

// Handle add lab
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lab'])) {
    try {
        /**
         * What: Add new lab
         * Why: For lab management
         * How: Uses LabService::createLab
         */
        $data = [
            'lab_name' => $_POST['lab_name'],
            'location' => $_POST['location'],
            'capacity' => $_POST['capacity']
        ];
        if ($labService->createLab($data)) {
            set_flash('success', 'Lab added successfully.');
        } else {
            set_flash('danger', 'Failed to add lab.');
        }
        header('Location: labs.php');
        exit;
    } catch (Exception $e) {
        error_log('[Lab Add Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header('Location: labs.php');
        exit;
    }
}

// Fetch all labs
try {
    /**
     * What: Fetch all labs
     * Why: For listing in the table
     * How: Uses LabService::getAllLabs
     */
    $labs = $labService->getAllLabs();
} catch (Exception $e) {
    error_log('[Lab List Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $labs = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Labs - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/labs.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-primary"><i class="bi bi-building-fill-gear"></i> Labs</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLabModal">
                <i class="bi bi-plus-circle"></i> Add Lab
            </button>
        </div>

        <div class="card shadow rounded-3">
            <div class="card-body">
                <?php if (empty($labs)): ?>
                    <p class="text-muted">No labs found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center">
                            <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Lab Name</th>
                                <th>Location</th>
                                <th>Capacity</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($labs as $lab): ?>
                                <tr>
                                    <td><?= htmlspecialchars($lab['id']) ?></td>
                                    <td><?= htmlspecialchars($lab['lab_name']) ?></td>
                                    <td><?= htmlspecialchars($lab['location']) ?></td>
                                    <td><?= htmlspecialchars($lab['capacity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Add Lab Modal -->
<div class="modal fade" id="addLabModal" tabindex="-1" aria-labelledby="addLabModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content shadow rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="addLabModalLabel">Add New Lab</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="add_lab" value="1">
                <div class="mb-3">
                    <label for="lab_name" class="form-label">Lab Name</label>
                    <input type="text" name="lab_name" id="lab_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" name="location" id="location" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" name="capacity" id="capacity" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Save Lab</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/labs.js"></script>
</body>
</html>
