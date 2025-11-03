<?php
require_once '../includes/auth.php';
require_once '../classes/EquipmentService.php';
require_once '../classes/Lab.php';
require_once '../classes/MasterlistService.php';

$equipmentService = new EquipmentService();
$labObj = new Lab();
$masterlistService = new MasterlistService();
$labList = $labObj->getAll();

if (!isset($_GET['id'])) {
    header("Location: inventory.php");
    exit;
}

$id = intval($_GET['id']);
/**
 * What: Fetch equipment by ID
 * Why: For editing
 * How: Uses EquipmentService::getEquipmentById
 */
$equipment = $equipmentService->getEquipmentById($id);

if (!$equipment) {
    header("Location: inventory.php");
    exit;
}

$last_updated = $equipment['last_updated_at'] ? date("F j, Y g:i A", strtotime($equipment['last_updated_at'])) : 'N/A';
$updated_by_name = 'Unknown';

if ($equipment['last_updated_by']) {
    require_once '../classes/User.php';
    $userObj = new User();
    $user = $userObj->getUserById($equipment['last_updated_by']);
    if ($user) $updated_by_name = htmlspecialchars($user['name']);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $action = $_POST['action_type'] ?? '';

        if ($action === 'delete') {
            $reason = $_POST['delete_reason'] ?? '';
            $authorized_by = $_POST['authorized_by_delete'] ?? '';

            if (!empty($reason) && !empty($authorized_by)) {
                $equipmentService->logEquipmentAction($id, 'Deleted', $_SESSION['user_id'], null, $authorized_by, null, $reason);
                $equipmentService->archiveEquipment($id);
                set_flash('success', 'Equipment archived and logged as deleted.');
            } else {
                set_flash('danger', 'Provide reason and authorized person for deletion.');
                header("Location: edit_equipment.php?id=$id");
                exit;
            }

        } elseif ($action === 'transfer') {
            $transferred_to = $_POST['transferred_to_select'] === 'Other' ? ($_POST['transferred_to_other'] ?? '') : ($_POST['transferred_to_select'] ?? '');
            $authorized_by = $_POST['authorized_by_transfer'] ?? '';

            if (!empty($transferred_to) && !empty($authorized_by)) {
                // Prepare previous values for logging
                $previous_values = [];
                if ($equipment['location'] !== $transferred_to) {
                    $previous_values['location'] = $equipment['location'];
                }
                $equipmentService->logEquipmentAction($id, 'Transferred', $_SESSION['user_id'], $transferred_to, $authorized_by, date('Y-m-d'), '', $equipment['location'], json_encode($previous_values));
                $_POST['status'] = 'Transferred';
                $_POST['location'] = $transferred_to;
                $equipmentService->updateEquipment($id, $_POST);
                set_flash('success', 'Equipment transferred and logged.');
            } else {
                set_flash('danger', 'Provide destination and authorized person for transfer.');
                header("Location: edit_equipment.php?id=$id");
                exit;
            }

        } else {
            try {
                $updated = $equipmentService->updateEquipment($id, $_POST);
                if ($updated) {
                    set_flash('success', 'Equipment updated successfully.');
                } else {
                    set_flash('info', 'No changes were made to the equipment.');
                }
            } catch (Exception $e) {
                set_flash('danger', $e->getMessage());
                header("Location: edit_equipment.php?id=$id");
                exit;
            }
        }

        header("Location: inventory.php");
        exit;
    } catch (Exception $e) {
        // What: Error during equipment edit (delete/transfer)
        // Why: DB error, validation error, etc.
        // How: Log error and show user-friendly message
        error_log('[Edit Equipment Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header("Location: edit_equipment.php?id=$id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Equipment - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/edit_equipment.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid fade-in">
        <?php show_flash(); ?>
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-pencil-square"></i>
                Edit Equipment
            </h1>
            <p class="page-subtitle">Modify equipment information and manage transfers or deletions</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="bi bi-gear-fill"></i> Equipment Details</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <!-- Basic Information Section -->
                            <div class="form-grid">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-tag-fill"></i>
                                        Equipment Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($equipment['name']) ?>" readonly style="background-color: #e9ecef;">
                                    <input type="hidden" name="name" value="<?= htmlspecialchars($equipment['name']) ?>">
                                    <small class="form-text text-muted">
                                        <i class="bi bi-lock-fill"></i>
                                        Equipment name cannot be changed after creation
                                    </small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-hash"></i>
                                        Serial Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($equipment['serial_number']) ?>" readonly style="background-color: #e9ecef;">
                                    <input type="hidden" name="serial_number" value="<?= htmlspecialchars($equipment['serial_number']) ?>">
                                    <small class="form-text text-muted">
                                        <i class="bi bi-lock-fill"></i>
                                        Serial number cannot be changed after creation
                                    </small>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-cpu-fill"></i>
                                        Model
                                    </label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($equipment['model']) ?>" readonly style="background-color: #e9ecef;">
                                    <input type="hidden" name="model" value="<?= htmlspecialchars($equipment['model']) ?>">
                                    <small class="form-text text-muted">
                                        <i class="bi bi-lock-fill"></i>
                                        Model cannot be changed after creation
                                    </small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <?php if (in_array($equipment['status'], ['Borrowed', 'Maintenance', 'Repair'])): ?>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($equipment['status']) ?> (Automatic)" readonly style="background-color: #e9ecef;">
                                        <input type="hidden" name="status" value="<?= htmlspecialchars($equipment['status']) ?>">
                                        <small class="form-text text-muted">
                                            <i class="bi bi-info-circle"></i>
                                            Status is automatically set based on borrow/maintenance records
                                        </small>
                                    <?php else: ?>
                                        <select name="status" class="form-select" required>
                                            <option value="Available" <?= $equipment['status'] == 'Available' ? 'selected' : '' ?>>Available</option>
                                            <option value="Disposed" <?= $equipment['status'] == 'Disposed' ? 'selected' : '' ?>>Disposed</option>
                                            <option value="Retired" <?= $equipment['status'] == 'Retired' ? 'selected' : '' ?>>Retired</option>
                                            <option value="Transferred" <?= $equipment['status'] == 'Transferred' ? 'selected' : '' ?>>Transferred</option>
                                        </select>
                                        <small class="form-text text-muted">
                                            <i class="bi bi-info-circle"></i>
                                            <strong>Note:</strong> 'Borrowed', 'Maintenance', and 'Repair' statuses are automatically set and cannot be manually selected.
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-collection-fill"></i>
                                        Category <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($equipment['category']) ?>" readonly style="background-color: #e9ecef;">
                                    <input type="hidden" name="category" value="<?= htmlspecialchars($equipment['category']) ?>">
                                    <small class="form-text text-muted">
                                        <i class="bi bi-lock-fill"></i>
                                        Category cannot be changed after creation
                                    </small>
                                </div>
                                <div class="mb-3 readonly-field">
                                    <label class="form-label">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        Current Location
                                    </label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($equipment['location']) ?>" readonly>
                                    <input type="hidden" name="location" value="<?= htmlspecialchars($equipment['location']) ?>">
                                    <small class="form-text">Use transfer action below to change location</small>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-calendar-event-fill"></i>
                                        Installation Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" name="installation_date" class="form-control" value="<?= htmlspecialchars($equipment['installation_date'] ?? date('Y-m-d')) ?>" required>
                                    <small class="form-text">Used for maintenance scheduling (every 6 months)</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-chat-text-fill"></i>
                                        Remarks
                                    </label>
                                    <textarea name="remarks" class="form-control" rows="3" placeholder="Additional notes or comments..."><?= htmlspecialchars($equipment['remarks']) ?></textarea>
                                </div>
                            </div>

                            <div class="alert alert-secondary small d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-clock-history me-1"></i>
                                    Last Updated: <strong><?= $last_updated ?></strong>
                                    by <strong><?= $updated_by_name ?></strong>
                                </span>
                            </div>

                            <hr>
                            <h5><i class="bi bi-exclamation-triangle-fill"></i> Special Actions</h5>

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-gear-wide-connected"></i>
                                    Action Type
                                </label>
                                <select name="action_type" id="action_type" class="form-select">
                                    <option value="">Select an action...</option>
                                    <option value="delete">🗑️ Archive as Deleted</option>
                                    <option value="transfer">📦 Transfer Equipment</option>
                                </select>
                                <small class="form-text">Choose an action to perform on this equipment</small>
                            </div>

                            <div id="delete_fields" class="d-none action-fields">
                                <h6 class="text-danger mb-3">
                                    <i class="bi bi-trash-fill"></i> Equipment Deletion Details
                                </h6>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-file-text-fill"></i>
                                        Reason for Deletion <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="delete_reason" class="form-control" rows="3" placeholder="Explain why this equipment is being deleted..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-person-check-fill"></i>
                                        Authorized By <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="authorized_by_delete" class="form-control" placeholder="Name of person authorizing deletion">
                                </div>
                            </div>

                            <div id="transfer_fields" class="d-none action-fields">
                                <h6 class="text-info mb-3">
                                    <i class="bi bi-arrow-left-right"></i> Equipment Transfer Details
                                </h6>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-building-fill"></i>
                                        Transfer To (Lab) <span class="text-danger">*</span>
                                    </label>
                                    <select name="transferred_to_select" id="transferred_to_select" class="form-select">
                                        <option value="" disabled selected>Select destination lab...</option>
                                        <?php foreach ($labList as $lab): ?>
                                            <option value="<?= htmlspecialchars($lab['lab_name']) ?>">
                                                <?= htmlspecialchars($lab['lab_name']) ?> (<?= htmlspecialchars($lab['location']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="Other">🏢 Other Location</option>
                                    </select>
                                </div>
                                <div class="mb-3 d-none" id="other_lab_input">
                                    <label class="form-label">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        Other Location Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="transferred_to_other" class="form-control" placeholder="Enter custom location name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-person-check-fill"></i>
                                        Authorized By <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="authorized_by_transfer" class="form-control" placeholder="Name of person authorizing transfer">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="inventory.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left-circle"></i> Back to Inventory
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle-fill"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../assets/js/edit_equipment.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
