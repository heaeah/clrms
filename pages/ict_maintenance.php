<?php
require_once '../includes/auth.php';
require_role(['ICT Staff']);

require_once '../classes/MaintenanceService.php';
require_once '../classes/EquipmentService.php';
require_once '../classes/Equipment.php';

$maintenanceService = new MaintenanceService();
$equipmentService = new EquipmentService();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['edit_id'])) {
            // Handle edit maintenance
            $editId = $_POST['edit_id'];
            $editData = [
                'equipment_id' => $_POST['equipment_id'],
                'maintenance_type' => $_POST['maintenance_type'],
                'description' => $_POST['description'],
                'maintenance_date' => $_POST['maintenance_date'],
                'repair_status' => $_POST['repair_status']
            ];
            
            $result = $maintenanceService->updateMaintenance($editId, $editData);
            if ($result) {
                set_flash('success', 'Maintenance record updated successfully.');
            } else {
                set_flash('danger', 'Failed to update maintenance record.');
            }
        } else {
            // Handle add maintenance
            $result = $maintenanceService->handleAddMaintenance($_POST, $_FILES);
            if ($result) {
                set_flash('success', 'Maintenance record added successfully.');
            } else {
                set_flash('danger', 'Failed to add maintenance record.');
            }
        }
        header("Location: ict_maintenance.php");
        exit;
    } catch (Exception $e) {
        error_log('[ICT Maintenance Save Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header("Location: ict_maintenance.php");
        exit;
    }
}

// Get filter parameters
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$equipmentFilter = $_GET['equipment'] ?? '';

try {
    // Run automated maintenance check
    $equipment = new Equipment();
    $maintenanceCheck = $equipment->checkAndScheduleMaintenance();
    
    // Get all maintenance records
    $records = $maintenanceService->getAllMaintenance();
    
    // Apply filters
    if ($typeFilter && in_array($typeFilter, ['Maintenance', 'Repair'])) {
        $records = array_filter($records, function($record) use ($typeFilter) {
            return $record['type'] === $typeFilter;
        });
    }
    
    if ($statusFilter && in_array($statusFilter, ['Pending', 'In Progress', 'Completed'])) {
        $records = array_filter($records, function($record) use ($statusFilter) {
            return $record['repair_status'] === $statusFilter;
        });
    }
    
    if ($equipmentFilter) {
        $records = array_filter($records, function($record) use ($equipmentFilter) {
            return $record['equipment_id'] == $equipmentFilter;
        });
    }
    
    // Get equipment list for filters
    $equipmentList = $equipmentService->getAllEquipment();
    
    // Get maintenance statistics
    $dueMaintenance = $maintenanceService->getDueMaintenance();
    $overdueMaintenance = $maintenanceService->getOverdueMaintenance();
    $pendingMaintenance = count(array_filter($records, function($m) {
        return $m['repair_status'] === 'Pending';
    }));
    
} catch (Exception $e) {
    error_log('[ICT Maintenance Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $records = [];
    $equipmentList = [];
    $dueMaintenance = $overdueMaintenance = [];
    $pendingMaintenance = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Management - ICT Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/ict_portal.css" rel="stylesheet">
</head>
<body class="bg-light ict-portal">
    <?php include '../includes/ict_sidebar.php'; ?>
    
    <!-- Mobile Menu Button -->
    <button class="btn btn-primary mobile-menu-btn d-md-none position-fixed" 
            style="top: 1rem; left: 1rem; z-index: 1060;">
        <i class="bi bi-list"></i>
    </button>

    <main class="main-content">
        <div class="container-fluid px-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-tools me-2 text-primary"></i>
                        Maintenance Management
                    </h2>
                    <p class="text-muted mb-0">Manage equipment maintenance and repairs</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                        <i class="bi bi-plus-circle me-2"></i>Add Maintenance
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Due Maintenance</h6>
                                    <h3 class="mb-0"><?= count($dueMaintenance) ?></h3>
                                </div>
                                <i class="bi bi-clock-history fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Overdue</h6>
                                    <h3 class="mb-0"><?= count($overdueMaintenance) ?></h3>
                                </div>
                                <i class="bi bi-exclamation-triangle fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Pending</h6>
                                    <h3 class="mb-0"><?= $pendingMaintenance ?></h3>
                                </div>
                                <i class="bi bi-hourglass-split fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Records</h6>
                                    <h3 class="mb-0"><?= count($records) ?></h3>
                                </div>
                                <i class="bi bi-clipboard-data fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="type" class="form-label">Type</label>
                            <select name="type" id="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="Maintenance" <?= $typeFilter === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                <option value="Repair" <?= $typeFilter === 'Repair' ? 'selected' : '' ?>>Repair</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="In Progress" <?= $statusFilter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="equipment" class="form-label">Equipment</label>
                            <select name="equipment" id="equipment" class="form-select">
                                <option value="">All Equipment</option>
                                <?php foreach ($equipmentList as $equipment): ?>
                                    <option value="<?= $equipment['id'] ?>" <?= $equipmentFilter == $equipment['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($equipment['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-1"></i>Filter
                                </button>
                                <a href="ict_maintenance.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Maintenance Records Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-clipboard-data me-2"></i>Maintenance Records
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($records)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-tools text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">No maintenance records found</h5>
                            <p class="text-muted">Try adjusting your filters or add new maintenance records</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Equipment</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($record['equipment_name'] ?? 'Unknown') ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($record['serial_number'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $record['type'] === 'Maintenance' ? 'primary' : 'warning' ?>">
                                                    <?= htmlspecialchars($record['type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= match($record['repair_status']) {
                                                    'Completed' => 'success',
                                                    'In Progress' => 'warning',
                                                    'Pending' => 'danger',
                                                    default => 'secondary'
                                                } ?>">
                                                    <?= htmlspecialchars($record['repair_status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($record['maintenance_date'])) ?></td>
                                            <td>
                                                <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= htmlspecialchars($record['issue_description']) ?>">
                                                    <?= htmlspecialchars($record['issue_description']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="viewMaintenance(<?= $record['id'] ?>)">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <button class="btn btn-outline-warning" onclick="editMaintenance(<?= $record['id'] ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                </div>
                                            </td>
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

    <!-- Add Maintenance Modal -->
    <div class="modal fade" id="addMaintenanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-tools me-2"></i>Add Maintenance Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="equipment_id" class="form-label">Equipment <span class="text-danger">*</span></label>
                                <select name="equipment_id" id="equipment_id" class="form-select" required>
                                    <option value="">Select Equipment</option>
                                    <?php foreach ($equipmentList as $equipment): ?>
                                        <option value="<?= $equipment['id'] ?>">
                                            <?= htmlspecialchars($equipment['name']) ?> - <?= htmlspecialchars($equipment['serial_number']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="maintenance_type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="maintenance_type" id="maintenance_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Repair">Repair</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="maintenance_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="maintenance_date" id="maintenance_date" 
                                       class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="repair_status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="repair_status" id="repair_status" class="form-select" required>
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Add Maintenance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Maintenance Modal -->
    <div class="modal fade" id="editMaintenanceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i>Edit Maintenance Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editMaintenanceForm">
                    <input type="hidden" name="edit_id" id="edit_maintenance_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_equipment_id" class="form-label">Equipment <span class="text-danger">*</span></label>
                                <select name="equipment_id" id="edit_equipment_id" class="form-select" required>
                                    <option value="">Select Equipment</option>
                                    <?php foreach ($equipmentList as $equipment): ?>
                                        <option value="<?= $equipment['id'] ?>">
                                            <?= htmlspecialchars($equipment['name']) ?> - <?= htmlspecialchars($equipment['serial_number']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_maintenance_type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="maintenance_type" id="edit_maintenance_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Repair">Repair</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_maintenance_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" name="maintenance_date" id="edit_maintenance_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_repair_status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="repair_status" id="edit_repair_status" class="form-select" required>
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="edit_description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Update Maintenance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/ict_portal.js"></script>
    <script>
        function viewMaintenance(id) {
            // TODO: Implement view maintenance details
            alert('View maintenance record: ' + id);
        }
        
        function editMaintenance(id) {
            // Fetch maintenance record data via AJAX
            fetch('get_maintenance_record.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate the edit form
                        document.getElementById('edit_maintenance_id').value = data.record.id;
                        document.getElementById('edit_equipment_id').value = data.record.equipment_id;
                        document.getElementById('edit_maintenance_type').value = data.record.type;
                        document.getElementById('edit_maintenance_date').value = data.record.maintenance_date;
                        document.getElementById('edit_repair_status').value = data.record.repair_status;
                        document.getElementById('edit_description').value = data.record.issue_description;
                        
                        // Show the modal
                        const modal = new bootstrap.Modal(document.getElementById('editMaintenanceModal'));
                        modal.show();
                    } else {
                        alert('Error loading maintenance record: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading maintenance record');
                });
        }
    </script>
</body>
</html>
