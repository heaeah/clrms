<?php
require_once '../includes/auth.php';
require_role(['Lab Admin', 'ICT Staff']);
require_once '../classes/Maintenance.php';
require_once '../classes/Equipment.php';
require_once '../classes/MaintenanceService.php';

$maintenanceObj = new Maintenance();
$equipmentObj = new Equipment();
$maintenanceService = new MaintenanceService();

// Handle form submission for repairs
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_repair'])) {
    try {
        $data = [
            'equipment_id' => $_POST['equipment_id'],
            'type' => 'Repair',
            'issue_description' => $_POST['issue_description'],
            'maintenance_date' => $_POST['repair_date'],
            'due_date' => $_POST['due_date'],
            'repair_status' => $_POST['repair_status'],
            'notes' => $_POST['notes'],
            'photo' => null
        ];
        
        // Handle file upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/repairs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                $data['photo'] = 'repairs/' . $fileName;
            }
        }
        
        $result = $maintenanceService->handleAddMaintenance($data);
        if ($result) {
            set_flash('success', 'Repair record added successfully.');
        } else {
            set_flash('danger', 'Failed to add repair record.');
        }
        header("Location: repair.php");
        exit;
    } catch (Exception $e) {
        error_log('[Repair Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header("Location: repair.php");
        exit;
    }
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$priorityFilter = isset($_GET['priority']) ? $_GET['priority'] : '';
$locationFilter = isset($_GET['location']) ? $_GET['location'] : '';

// Fetch repair records
$repairRecords = $maintenanceService->getMaintenanceByType('Repair');
$allEquipment = $equipmentObj->getAllEquipment();

// Filter by status if specified
if ($statusFilter && in_array($statusFilter, ['Pending', 'In Progress', 'Completed'])) {
    $repairRecords = array_filter($repairRecords, function($record) use ($statusFilter) {
        return $record['repair_status'] === $statusFilter;
    });
}

// Filter by location if specified
if ($locationFilter && !empty($locationFilter)) {
    $repairRecords = array_filter($repairRecords, function($record) use ($locationFilter) {
        return isset($record['equipment_location']) && $record['equipment_location'] === $locationFilter;
    });
}

// Calculate repair statistics
$totalRepairs = count($repairRecords);
$pendingRepairs = count(array_filter($repairRecords, fn($r) => $r['repair_status'] === 'Pending'));
$inProgressRepairs = count(array_filter($repairRecords, fn($r) => $r['repair_status'] === 'In Progress'));
$completedRepairs = count(array_filter($repairRecords, fn($r) => $r['repair_status'] === 'Completed'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Repairs - CLRMS</title>
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

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="bi bi-tools text-danger me-2"></i>Equipment Repairs
                </h1>
                <p class="text-muted small mb-0">Track and manage equipment repair requests</p>
            </div>
            <div>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addRepairModal">
                    <i class="bi bi-plus-circle me-1"></i>Add Repair Request
                </button>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-tools text-primary mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-primary fw-bold"><?= $totalRepairs ?></h3>
                        <p class="mb-0 text-muted small">Total Repairs</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-clock-history text-warning mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-warning fw-bold"><?= $pendingRepairs ?></h3>
                        <p class="mb-0 text-muted small">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-gear-fill text-info mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-info fw-bold"><?= $inProgressRepairs ?></h3>
                        <p class="mb-0 text-muted small">In Progress</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-check-circle-fill text-success mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-success fw-bold"><?= $completedRepairs ?></h3>
                        <p class="mb-0 text-muted small">Completed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Options</h6>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label fw-semibold small">Repair Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Pending" <?= ($statusFilter == 'Pending') ? 'selected' : '' ?>>🟡 Pending</option>
                            <option value="In Progress" <?= ($statusFilter == 'In Progress') ? 'selected' : '' ?>>🔵 In Progress</option>
                            <option value="Completed" <?= ($statusFilter == 'Completed') ? 'selected' : '' ?>>🟢 Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="priority" class="form-label fw-semibold small">Priority Level</label>
                        <select name="priority" id="priority" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="High" <?= ($priorityFilter == 'High') ? 'selected' : '' ?>>🔴 High</option>
                            <option value="Medium" <?= ($priorityFilter == 'Medium') ? 'selected' : '' ?>>🟡 Medium</option>
                            <option value="Low" <?= ($priorityFilter == 'Low') ? 'selected' : '' ?>>🟢 Low</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="location" class="form-label fw-semibold small">Location</label>
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
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-funnel"></i> Apply
                        </button>
                        <a href="repair.php" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Repair Records Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-wrench-adjustable-circle text-danger me-2"></i>Repair Records</h5>
                    <?php if (!empty($repairRecords)): ?>
                    <span class="badge bg-danger"><?= count($repairRecords) ?> records</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($repairRecords)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Equipment</th>
                                    <th>Issue Description</th>
                                    <th>Reported Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th class="text-center pe-4">Actions</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php foreach ($repairRecords as $repair): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong class="text-primary"><?= htmlspecialchars($repair['equipment_name']) ?></strong>
                                            <?php if (!empty($repair['serial_number'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars($repair['serial_number']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($repair['issue_description']) ?>">
                                            <?= htmlspecialchars($repair['issue_description']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <i class="bi bi-calendar-event text-primary me-1"></i>
                                        <?= date('M d, Y', strtotime($repair['maintenance_date'])) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($repair['due_date'])): ?>
                                            <i class="bi bi-calendar-check text-success me-1"></i>
                                            <?= date('M d, Y', strtotime($repair['due_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match ($repair['repair_status']) {
                                            'Pending' => 'warning',
                                            'In Progress' => 'primary',
                                            'Completed' => 'success',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?> fs-6"><?= htmlspecialchars($repair['repair_status']) ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewRepairModal<?= $repair['id'] ?>" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editRepairModal<?= $repair['id'] ?>" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-tools text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">No Repair Records Found</h5>
                        <p class="text-muted mb-4">Start by adding a new repair request to track equipment issues</p>
                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addRepairModal">
                            <i class="bi bi-plus-circle me-2"></i>Add First Repair Request
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Add Repair Modal -->
<div class="modal fade" id="addRepairModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Add Repair Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_repair" value="1">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Repair Request Form:</strong> Fill in the details below to create a new repair request for equipment maintenance.
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Equipment *</label>
                            <select name="equipment_id" class="form-select" required>
                                <option value="">Select Equipment</option>
                                <?php foreach ($allEquipment as $equip): ?>
                                    <option value="<?= $equip['id'] ?>">
                                        <?= htmlspecialchars($equip['name']) ?> - <?= htmlspecialchars($equip['location']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Repair Date *</label>
                            <input type="date" name="repair_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Issue Description *</label>
                            <textarea name="issue_description" class="form-control" rows="3" placeholder="Describe the issue that needs repair..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status *</label>
                            <select name="repair_status" class="form-select" required>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes about the repair..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Photo (Optional)</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                            <small class="text-muted">Upload a photo of the equipment issue for better documentation</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Save Repair Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View/Edit Modals for each repair record -->
<?php foreach ($repairRecords as $repair): ?>
<!-- View Modal -->
<div class="modal fade" id="viewRepairModal<?= $repair['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i> View Repair Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-primary">Equipment</label>
                            <p class="mb-0"><?= htmlspecialchars($repair['equipment_name']) ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-primary">Issue Description</label>
                            <p class="mb-0"><?= htmlspecialchars($repair['issue_description']) ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-primary">Reported Date</label>
                            <p class="mb-0">
                                <i class="bi bi-calendar-event text-primary me-1"></i>
                                <?= date('M d, Y', strtotime($repair['maintenance_date'])) ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-primary">Status</label>
                            <p class="mb-0">
                                <?php
                                $statusClass = match ($repair['repair_status']) {
                                    'Pending' => 'warning',
                                    'In Progress' => 'primary',
                                    'Completed' => 'success',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?> fs-6"><?= htmlspecialchars($repair['repair_status']) ?></span>
                            </p>
                        </div>
                        <?php if (!empty($repair['due_date'])): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-primary">Due Date</label>
                            <p class="mb-0">
                                <i class="bi bi-calendar-check text-success me-1"></i>
                                <?= date('M d, Y', strtotime($repair['due_date'])) ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($repair['notes'])): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-primary">Notes</label>
                            <p class="mb-0"><?= htmlspecialchars($repair['notes']) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($repair['photo'])): ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-primary">Photo</label>
                            <p class="mb-0">
                                <a href="../uploads/<?= htmlspecialchars($repair['photo']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-image me-1"></i>View Photo
                                </a>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/maintenance.js"></script>
</body>
</html> 