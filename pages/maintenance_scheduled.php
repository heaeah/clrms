<?php
require_once '../includes/auth.php';
require_role(['Lab Admin', 'ICT Staff']);
require_once '../classes/Equipment.php';
require_once '../classes/MaintenanceService.php';

$equipmentObj = new Equipment();
$maintenanceService = new MaintenanceService();

// Handle form submission for scheduled maintenance
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_scheduled'])) {
    try {
        $data = [
            'equipment_id' => $_POST['equipment_id'],
            'type' => 'Maintenance',
            'issue_description' => 'Scheduled maintenance - ' . ($_POST['maintenance_type'] ?? 'Regular checkup'),
            'maintenance_date' => $_POST['maintenance_date'],
            'due_date' => $_POST['due_date'],
            'repair_status' => $_POST['repair_status'],
            'notes' => $_POST['notes'],
            'photo' => null
        ];
        
        // Handle file upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/maintenance/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                $data['photo'] = 'maintenance/' . $fileName;
            }
        }
        
        $result = $maintenanceService->handleAddMaintenance($data);
        if ($result) {
            // Update maintenance schedule
            $equipmentObj->updateMaintenanceSchedule($_POST['equipment_id'], $_POST['maintenance_date']);
            set_flash('success', 'Scheduled maintenance completed successfully.');
        } else {
            set_flash('danger', 'Failed to add maintenance record.');
        }
        header("Location: maintenance_scheduled.php");
        exit;
    } catch (Exception $e) {
        error_log('[Scheduled Maintenance Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header("Location: maintenance_scheduled.php");
        exit;
    }
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$locationFilter = isset($_GET['location']) ? $_GET['location'] : '';

// Fetch data
$equipmentDueForMaintenance = $equipmentObj->getEquipmentDueForMaintenance();
$pendingReminders = $equipmentObj->getPendingReminders();
$allEquipment = $equipmentObj->getAllEquipment();

// Filter equipment based on status
if ($statusFilter) {
    $equipmentDueForMaintenance = array_filter($equipmentDueForMaintenance, function($item) use ($statusFilter) {
        return $item['maintenance_status'] === $statusFilter;
    });
}

// Filter equipment based on location
if ($locationFilter && !empty($locationFilter)) {
    $equipmentDueForMaintenance = array_filter($equipmentDueForMaintenance, function($item) use ($locationFilter) {
        return isset($item['location']) && $item['location'] === $locationFilter;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance - CLRMS</title>
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

                 <!-- Pending Reminders Alert -->
         <?php if (!empty($pendingReminders)): ?>
             <div class="alert alert-info d-flex align-items-center mb-4" role="alert" id="reminders-section">
                 <i class="bi bi-bell-fill me-2"></i>
                 <div>
                     <strong>🔔 Maintenance Reminders:</strong> 
                     <?= count($pendingReminders) ?> reminder(s) pending
                     <button class="btn btn-sm btn-outline-info ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#remindersDetails">
                         View Details
                     </button>
                 </div>
             </div>
             <div class="collapse mb-3" id="remindersDetails">
                <div class="card card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Equipment</th>
                                    <th>Location</th>
                                    <th>Reminder Type</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingReminders as $reminder): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($reminder['equipment_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($reminder['location']) ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($reminder['reminder_message']) ?></span></td>
                                        <td><?= htmlspecialchars($reminder['reminder_date']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="bi bi-calendar-check text-primary"></i> Scheduled Maintenance
                </h1>
                <p class="text-muted small mb-0">6-Month automated maintenance scheduling and reminders</p>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-calendar-event text-primary mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-primary fw-bold"><?= count($equipmentDueForMaintenance) ?></h3>
                        <p class="mb-0 text-muted small">Due for Maintenance</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-exclamation-triangle text-danger mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-danger fw-bold"><?= count(array_filter($equipmentDueForMaintenance, fn($e) => $e['maintenance_status'] === 'Overdue')) ?></h3>
                        <p class="mb-0 text-muted small">Overdue</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-clock text-warning mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-warning fw-bold"><?= count(array_filter($equipmentDueForMaintenance, fn($e) => $e['maintenance_status'] === 'Due Soon')) ?></h3>
                        <p class="mb-0 text-muted small">Due Soon</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-success fw-bold"><?= count(array_filter($equipmentDueForMaintenance, fn($e) => $e['maintenance_status'] === 'Upcoming')) ?></h3>
                        <p class="mb-0 text-muted small">Upcoming</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-bell text-info mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-info fw-bold"><?= count($pendingReminders) ?></h3>
                        <p class="mb-0 text-muted small">Pending Reminders</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-arrow-repeat text-secondary mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-secondary fw-bold">6</h3>
                        <p class="mb-0 text-muted small">Month Interval</p>
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
                    <div class="col-md-5">
                        <label for="status" class="form-label fw-semibold small">Maintenance Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Overdue" <?= ($statusFilter == 'Overdue') ? 'selected' : '' ?>>🔴 Overdue</option>
                            <option value="Due Soon" <?= ($statusFilter == 'Due Soon') ? 'selected' : '' ?>>🟡 Due Soon (Within 30 Days)</option>
                            <option value="Upcoming" <?= ($statusFilter == 'Upcoming') ? 'selected' : '' ?>>🟢 Upcoming</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="location" class="form-label fw-semibold small">Location</label>
                        <select name="location" id="location" class="form-select">
                            <option value="">All Locations</option>
                            <?php 
                            require_once '../classes/MasterlistService.php';
                            $masterlistService = new MasterlistService();
                            $locations = $masterlistService->getLabLocations();
                            foreach ($locations as $loc): ?>
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
                        <a href="maintenance_scheduled.php" class="btn btn-outline-secondary" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- 6-Month Maintenance Schedule Overview -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        How 6-Month Maintenance Scheduling Works
                    </h6>
                    <button class="btn btn-sm btn-link text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#scheduleInfo">
                        <i class="bi bi-chevron-down"></i> Show Details
                    </button>
                </div>
            </div>
            <div class="collapse" id="scheduleInfo">
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3"><i class="bi bi-gear me-2"></i>How It Works</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Maintenance automatically scheduled 6 months from installation</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Reminders sent at 30 days, 7 days, and on due date</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Next cycle starts 6 months after completion</li>
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Status updates automatically based on dates</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3"><i class="bi bi-bookmark me-2"></i>Status Legend</h6>
                            <div class="d-flex flex-column gap-2">
                                <div><span class="badge bg-danger">Overdue</span> <span class="text-muted small">- Maintenance date has passed</span></div>
                                <div><span class="badge bg-warning">Due Soon</span> <span class="text-muted small">- Due within 30 days</span></div>
                                <div><span class="badge bg-success">Upcoming</span> <span class="text-muted small">- Due in more than 30 days</span></div>
                                <div><span class="badge bg-info">Pending</span> <span class="text-muted small">- Reminders waiting to be sent</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipment Due for Maintenance -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-tools text-warning me-2"></i>Equipment Due for Maintenance</h5>
                    <?php if (!empty($equipmentDueForMaintenance)): ?>
                    <span class="badge bg-warning"><?= count($equipmentDueForMaintenance) ?> items</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($equipmentDueForMaintenance)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Equipment</th>
                                    <th>Location</th>
                                    <th>Installation Date</th>
                                    <th>Last Maintenance</th>
                                    <th>Next Maintenance</th>
                                    <th>Status</th>
                                    <th>Days Left</th>
                                    <th class="pe-4">Actions</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php foreach ($equipmentDueForMaintenance as $equipment): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($equipment['equipment_name']) ?></strong>
                                        <?php if (!empty($equipment['serial_number'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($equipment['serial_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($equipment['location']) ?></td>
                                    <td><?= $equipment['installation_date'] ? date('M d, Y', strtotime($equipment['installation_date'])) : 'N/A' ?></td>
                                    <td><?= $equipment['last_maintenance_date'] ? date('M d, Y', strtotime($equipment['last_maintenance_date'])) : 'Never' ?></td>
                                    <td>
                                        <strong><?= date('M d, Y', strtotime($equipment['next_maintenance_date'])) ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match ($equipment['maintenance_status']) {
                                            'Overdue' => 'danger',
                                            'Due Soon' => 'warning',
                                            'Upcoming' => 'info',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($equipment['maintenance_status']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($equipment['days_until_maintenance'] < 0): ?>
                                            <span class="text-danger fw-bold"><?= abs($equipment['days_until_maintenance']) ?> days overdue</span>
                                        <?php else: ?>
                                            <span class="text-muted"><?= $equipment['days_until_maintenance'] ?> days</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#completeMaintenanceModal<?= $equipment['id'] ?>" title="Complete Maintenance">
                                            <i class="bi bi-check-circle"></i> Complete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">All Equipment Up to Date!</h5>
                        <p class="text-muted mb-0">No equipment is due for maintenance at this time.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- All Equipment Maintenance Schedule -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="bi bi-calendar3 text-primary me-2"></i>Complete Maintenance Schedule</h5>
                        <small class="text-muted">All equipment with 6-month maintenance cycles</small>
                    </div>
                    <?php if (!empty($allEquipmentWithDates)): ?>
                    <span class="badge bg-primary"><?= count($allEquipmentWithDates) ?> items</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php 
                // Get all equipment with installation dates
                $allEquipmentWithDates = array_filter($allEquipment, function($item) {
                    return !empty($item['installation_date']);
                });
                
                // Apply location filter to all equipment table
                if ($locationFilter && !empty($locationFilter)) {
                    $allEquipmentWithDates = array_filter($allEquipmentWithDates, function($item) use ($locationFilter) {
                        return isset($item['location']) && $item['location'] === $locationFilter;
                    });
                }
                ?>
                
                <?php if (!empty($allEquipmentWithDates)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Equipment</th>
                                    <th>Location</th>
                                    <th>Installation Date</th>
                                    <th>Last Maintenance</th>
                                    <th>Next Maintenance</th>
                                    <th>Cycle</th>
                                    <th>Status</th>
                                    <th class="pe-4">Days Left</th>
                                </tr>
                            </thead>
                        <tbody>
                            <?php foreach ($allEquipmentWithDates as $equipment): ?>
                                <?php 
                                $installDate = new DateTime($equipment['installation_date']);
                                $nextMaintenance = new DateTime($equipment['next_maintenance_date']);
                                $today = new DateTime();
                                $daysUntilMaintenance = $today->diff($nextMaintenance)->days;
                                
                                // Calculate which 6-month cycle this is
                                $monthsSinceInstall = $today->diff($installDate)->m + ($today->diff($installDate)->y * 12);
                                $cycleNumber = floor($monthsSinceInstall / 6) + 1;
                                
                                // Determine status
                                if ($nextMaintenance < $today) {
                                    $status = 'Overdue';
                                    $statusClass = 'danger';
                                } elseif ($daysUntilMaintenance <= 30) {
                                    $status = 'Due Soon';
                                    $statusClass = 'warning';
                                } else {
                                    $status = 'Upcoming';
                                    $statusClass = 'success';
                                }
                                
                                // Apply status filter
                                if ($statusFilter && $status !== $statusFilter) {
                                    continue; // Skip this item if it doesn't match the status filter
                                }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($equipment['name']) ?></strong>
                                        <?php if (!empty($equipment['serial_number'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($equipment['serial_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($equipment['location']) ?></td>
                                    <td>
                                        <i class="bi bi-calendar-plus text-primary"></i>
                                        <?= date('M d, Y', strtotime($equipment['installation_date'])) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($equipment['last_maintenance_date'])): ?>
                                            <i class="bi bi-calendar-check text-success"></i>
                                            <?= date('M d, Y', strtotime($equipment['last_maintenance_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="bi bi-calendar-x"></i> Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= date('M d, Y', strtotime($equipment['next_maintenance_date'])) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">Cycle #<?= $cycleNumber ?></span>
                                        <br><small class="text-muted"><?= $monthsSinceInstall ?> months since install</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $statusClass ?>"><?= $status ?></span>
                                    </td>
                                    <td>
                                        <?php if ($daysUntilMaintenance < 0): ?>
                                            <span class="text-danger fw-bold">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                <?= abs($daysUntilMaintenance) ?> days overdue
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">
                                                <i class="bi bi-clock"></i>
                                                <?= $daysUntilMaintenance ?> days
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-exclamation-circle-fill text-warning" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">No Equipment with Installation Dates</h5>
                        <p class="text-muted mb-3">Add installation dates to equipment in the inventory to enable 6-month maintenance scheduling.</p>
                        <a href="inventory.php" class="btn btn-primary">
                            <i class="bi bi-box-arrow-up-right me-2"></i>Go to Inventory
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Complete Maintenance Modal -->
<?php foreach ($equipmentDueForMaintenance as $equipment): ?>
<div class="modal fade" id="completeMaintenanceModal<?= $equipment['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check-circle"></i> Complete Maintenance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_scheduled" value="1">
                <input type="hidden" name="equipment_id" value="<?= $equipment['id'] ?>">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong><?= htmlspecialchars($equipment['equipment_name']) ?></strong><br>
                        Location: <?= htmlspecialchars($equipment['location']) ?><br>
                        Next Maintenance: <?= date('M d, Y', strtotime($equipment['next_maintenance_date'])) ?>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Maintenance Type</label>
                            <select name="maintenance_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="Regular checkup">Regular checkup</option>
                                <option value="Preventive maintenance">Preventive maintenance</option>
                                <option value="Cleaning">Cleaning</option>
                                <option value="Software update">Software update</option>
                                <option value="Hardware check">Hardware check</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Maintenance Date *</label>
                            <input type="date" name="maintenance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+6 months')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status *</label>
                            <select name="repair_status" class="form-select" required>
                                <option value="Completed">Completed</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Describe what was done during maintenance..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Photo (Optional)</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Complete Maintenance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/maintenance.js"></script>
</body>
</html> 