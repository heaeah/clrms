<?php
require_once '../includes/auth.php';
require_role(['ICT Staff']);

require_once '../classes/EquipmentService.php';
require_once '../classes/MaintenanceService.php';

$equipmentService = new EquipmentService();
$maintenanceService = new MaintenanceService();

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$locationFilter = $_GET['location'] ?? '';
$searchFilter = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

try {
    // Get equipment data
    $equipmentList = $equipmentService->filterAndSearchEquipment($statusFilter, $locationFilter, $searchFilter, $categoryFilter);
    
    // Get maintenance data for each equipment
    $equipmentWithMaintenance = [];
    foreach ($equipmentList as $equipment) {
        $maintenance = $maintenanceService->getMaintenanceByEquipmentId($equipment['id']);
        $equipment['maintenance'] = $maintenance;
        $equipmentWithMaintenance[] = $equipment;
    }
    
    // Get unique locations and categories for filters
    $locations = array_unique(array_column($equipmentList, 'location'));
    $categories = array_unique(array_column($equipmentList, 'category'));
    
} catch (Exception $e) {
    error_log('[ICT Equipment Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $equipmentList = $equipmentWithMaintenance = [];
    $locations = $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Management - ICT Portal</title>
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
                        <i class="bi bi-laptop me-2 text-primary"></i>
                        Equipment Management
                    </h2>
                    <p class="text-muted mb-0">Manage and monitor all ICT equipment</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                        <i class="bi bi-plus-circle me-2"></i>Add Maintenance
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="Available" <?= $statusFilter === 'Available' ? 'selected' : '' ?>>Available</option>
                                <option value="Borrowed" <?= $statusFilter === 'Borrowed' ? 'selected' : '' ?>>Borrowed</option>
                                <option value="Under Repair" <?= $statusFilter === 'Under Repair' ? 'selected' : '' ?>>Under Repair</option>
                                <option value="Disposed" <?= $statusFilter === 'Disposed' ? 'selected' : '' ?>>Disposed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="location" class="form-label">Location</label>
                            <select name="location" id="location" class="form-select">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= htmlspecialchars($location) ?>" <?= $locationFilter === $location ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($location) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select name="category" id="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Search equipment..." value="<?= htmlspecialchars($searchFilter) ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                            <a href="ict_equipment.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Equipment Grid -->
            <div class="row">
                <?php if (empty($equipmentWithMaintenance)): ?>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-laptop text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">No equipment found</h5>
                                <p class="text-muted">Try adjusting your filters or add new equipment</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($equipmentWithMaintenance as $equipment): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($equipment['name']) ?></h6>
                                    <span class="badge bg-<?= match($equipment['status']) {
                                        'Available' => 'success',
                                        'Borrowed' => 'warning',
                                        'Under Repair' => 'danger',
                                        'Disposed' => 'secondary',
                                        default => 'dark'
                                    } ?>">
                                        <?= htmlspecialchars($equipment['status']) ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Serial Number</small>
                                            <div class="fw-bold"><?= htmlspecialchars($equipment['serial_number']) ?></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Model</small>
                                            <div class="fw-bold"><?= htmlspecialchars($equipment['model']) ?></div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Location</small>
                                            <div class="fw-bold"><?= htmlspecialchars($equipment['location']) ?></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Category</small>
                                            <div class="fw-bold"><?= htmlspecialchars($equipment['category']) ?></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Maintenance Status -->
                                    <div class="maintenance-status mb-3">
                                        <small class="text-muted">Maintenance Status</small>
                                        <?php if (!empty($equipment['maintenance'])): ?>
                                            <?php $latestMaintenance = $equipment['maintenance'][0]; ?>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-<?= match($latestMaintenance['repair_status']) {
                                                    'Completed' => 'success',
                                                    'In Progress' => 'warning',
                                                    'Pending' => 'danger',
                                                    default => 'secondary'
                                                } ?>">
                                                    <?= htmlspecialchars($latestMaintenance['repair_status']) ?>
                                                </span>
                                                <small class="text-muted">
                                                    <?= date('M d, Y', strtotime($latestMaintenance['maintenance_date'])) ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted">
                                                <i class="bi bi-dash-circle me-1"></i>No maintenance records
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($equipment['remarks']): ?>
                                        <div class="remarks">
                                            <small class="text-muted">Remarks</small>
                                            <div class="text-muted small"><?= htmlspecialchars($equipment['remarks']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-light">
                                    <div class="d-flex justify-content-between">
                                        <a href="equipment_details.php?id=<?= $equipment['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                        <a href="ict_maintenance.php?equipment_id=<?= $equipment['id'] ?>" 
                                           class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-tools me-1"></i>Maintain
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                <form action="ict_maintenance.php" method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="equipment_id" class="form-label">Equipment</label>
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
                                <label for="maintenance_type" class="form-label">Type</label>
                                <select name="maintenance_type" id="maintenance_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Repair">Repair</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="maintenance_date" class="form-label">Date</label>
                                <input type="date" name="maintenance_date" id="maintenance_date" 
                                       class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="technician_name" class="form-label">Technician</label>
                                <input type="text" name="technician_name" id="technician_name" 
                                       class="form-control" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="repair_status" class="form-label">Status</label>
                                <select name="repair_status" id="repair_status" class="form-select" required>
                                    <option value="Pending">Pending</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="cost" class="form-label">Cost (₱)</label>
                                <input type="number" name="cost" id="cost" class="form-control" step="0.01" min="0">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/ict_portal.js"></script>
</body>
</html>