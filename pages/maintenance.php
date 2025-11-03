<?php
require_once '../includes/auth.php';
require_role(['Lab Admin', 'ICT Staff']);
require_once '../classes/Maintenance.php';
require_once '../classes/Equipment.php';
require_once '../classes/MaintenanceService.php';

$maintenanceObj = new Maintenance();
$equipmentObj = new Equipment();
$maintenanceService = new MaintenanceService();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $result = $maintenanceService->handleAddMaintenance($_POST, $_FILES);
        if ($result) {
            set_flash('success', 'Maintenance record added successfully.');
        } else {
            set_flash('danger', 'Failed to add maintenance record.');
        }
        header("Location: maintenance.php");
        exit;
    } catch (Exception $e) {
        error_log('[Maintenance Save Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header("Location: maintenance.php");
        exit;
    }
}

// Get filter parameters
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$locationFilter = isset($_GET['location']) ? $_GET['location'] : '';

// Fetch data based on filters
if ($typeFilter && in_array($typeFilter, ['Maintenance', 'Repair'])) {
    $records = $maintenanceService->getMaintenanceByType($typeFilter);
} else {
    $records = $maintenanceService->getAllMaintenance();
}

// Filter by status if specified
if ($statusFilter && in_array($statusFilter, ['Pending', 'In Progress', 'Completed'])) {
    $records = array_filter($records, function($record) use ($statusFilter) {
        return $record['repair_status'] === $statusFilter;
    });
}

// Filter by location if specified
if ($locationFilter && !empty($locationFilter)) {
    $records = array_filter($records, function($record) use ($locationFilter) {
        return isset($record['equipment_location']) && $record['equipment_location'] === $locationFilter;
    });
}

$equipmentList = $equipmentObj->getAllEquipment();
$dueMaintenance = $maintenanceService->getDueMaintenance();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance & Repair - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/maintenance.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>

        <!-- Due Maintenance Alerts -->
        <?php if (!empty($dueMaintenance)): ?>
            <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div>
                    <strong>⚠️ Due Maintenance:</strong> 
                    <?= count($dueMaintenance) ?> equipment item(s) need attention
                    <button class="btn btn-sm btn-outline-warning ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#dueMaintenanceDetails">
                        View Details
                    </button>
                </div>
            </div>
            <div class="collapse mb-3" id="dueMaintenanceDetails">
                <div class="card card-body">
                    <ul class="mb-0">
                        <?php foreach ($dueMaintenance as $due): ?>
                            <li><strong><?= htmlspecialchars($due['equipment_name']) ?></strong> - Due: <?= htmlspecialchars($due['due_date']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 text-primary mb-0"><i class="bi bi-wrench-adjustable-circle"></i> Maintenance & Repair</h1>
                <p class="text-muted mb-0">Track equipment maintenance and repair activities</p>
            </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                    <i class="bi bi-plus-circle"></i> Add Record
                </button>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-list-check fs-1"></i>
                        <h4 class="mt-2"><?= count($records) ?></h4>
                        <p class="mb-0">Total Records</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-clock fs-1"></i>
                        <h4 class="mt-2"><?= count(array_filter($records, fn($r) => $r['repair_status'] === 'Pending')) ?></h4>
                        <p class="mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-gear fs-1"></i>
                        <h4 class="mt-2"><?= count(array_filter($records, fn($r) => $r['repair_status'] === 'In Progress')) ?></h4>
                        <p class="mb-0">In Progress</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle fs-1"></i>
                        <h4 class="mt-2"><?= count(array_filter($records, fn($r) => $r['repair_status'] === 'Completed')) ?></h4>
                        <p class="mb-0">Completed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="type" class="form-label">Type</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="Maintenance" <?= ($typeFilter == 'Maintenance') ? 'selected' : '' ?>>Maintenance</option>
                            <option value="Repair" <?= ($typeFilter == 'Repair') ? 'selected' : '' ?>>Repair</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Pending" <?= ($statusFilter == 'Pending') ? 'selected' : '' ?>>Pending</option>
                            <option value="In Progress" <?= ($statusFilter == 'In Progress') ? 'selected' : '' ?>>In Progress</option>
                            <option value="Completed" <?= ($statusFilter == 'Completed') ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="location" class="form-label">Location</label>
                        <select name="location" id="location" class="form-select">
                            <option value="">All Locations</option>
                            <?php
                            require_once '../classes/MasterlistService.php';
                            $masterlistService = new MasterlistService();
                            $locations = $masterlistService->getLabLocations();
                            foreach ($locations as $loc):
                            ?>
                                <option value="<?= htmlspecialchars($loc['name']) ?>" <?= ($locationFilter == $loc['name']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Records Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Maintenance Records</h5>
            </div>
            <div class="card-body table-responsive">
                <?php if (!empty($records)): ?>
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                    <tr>
                        <th>Type</th>
                        <th>Equipment</th>
                        <th>Issue</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $rec): ?>
                            <tr>
                                    <td>
                                        <span class="badge bg-<?= $rec['type'] == 'Maintenance' ? 'info' : 'warning' ?>">
                                            <?= htmlspecialchars($rec['type'] ?? 'Maintenance') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($rec['equipment_name']) ?></strong>
                                        <?php if (!empty($rec['serial_number'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($rec['serial_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($rec['issue_description']) ?>">
                                            <?= htmlspecialchars($rec['issue_description']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= date('M d, Y', strtotime($rec['maintenance_date'])) ?></div>
                                        <?php if (!empty($rec['due_date'])): ?>
                                            <small class="text-muted">Due: <?= date('M d, Y', strtotime($rec['due_date'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                <td>
                                    <?php
                                    $statusClass = match ($rec['repair_status']) {
                                            'Pending' => 'warning',
                                        'In Progress' => 'primary',
                                        'Completed' => 'success',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($rec['repair_status']) ?></span>
                                </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?= $rec['id'] ?>" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $rec['id'] ?>" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <h5 class="mt-3 text-muted">No maintenance records found</h5>
                        <p class="text-muted">Start by adding a new maintenance record</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                            <i class="bi bi-plus-circle"></i> Add First Record
                        </button>
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
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add Maintenance Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type *</label>
                            <select name="type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Repair">Repair</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Equipment *</label>
                            <select name="equipment_id" class="form-select" required>
                                <option value="">Select Equipment</option>
                                <?php foreach ($equipmentList as $equip): ?>
                                    <option value="<?= $equip['id'] ?>">
                                        <?= htmlspecialchars($equip['name']) ?> - <?= htmlspecialchars($equip['location']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Issue Description *</label>
                            <textarea name="issue_description" class="form-control" rows="3" placeholder="Describe the issue or maintenance needed..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maintenance Date *</label>
                            <input type="date" name="maintenance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="repair_status" class="form-select" required>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View/Edit Modals for each record -->
<?php foreach ($records as $rec): ?>
<!-- View Modal -->
<div class="modal fade" id="viewModal<?= $rec['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-eye"></i> View Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> <?= htmlspecialchars($rec['type'] ?? 'Maintenance') ?></p>
                        <p><strong>Equipment:</strong> <?= htmlspecialchars($rec['equipment_name']) ?></p>
                        <p><strong>Issue:</strong> <?= htmlspecialchars($rec['issue_description']) ?></p>
                        <p><strong>Date:</strong> <?= htmlspecialchars($rec['maintenance_date']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($rec['repair_status']) ?></span>
                        </p>
                        <?php if (!empty($rec['due_date'])): ?>
                            <p><strong>Due Date:</strong> <?= htmlspecialchars($rec['due_date']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($rec['notes'])): ?>
                            <p><strong>Notes:</strong> <?= htmlspecialchars($rec['notes']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($rec['photo'])): ?>
                            <p><strong>Photo:</strong> <a href="../uploads/<?= htmlspecialchars($rec['photo']) ?>" target="_blank">View Photo</a></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/maintenance.js"></script>
</body>
</html>