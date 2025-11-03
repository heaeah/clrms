<?php
require_once '../includes/auth.php';
require_role(['ICT Staff']);

// Check for success message from form submission
$successMessage = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $successMessage = 'ICT Support request submitted successfully!';
}

// Get ICT Staff specific data
require_once '../classes/EquipmentService.php';
require_once '../classes/MaintenanceService.php';
require_once '../classes/ICTSupport.php';
require_once '../classes/SoftwareService.php';

$equipmentService = new EquipmentService();
$maintenanceService = new MaintenanceService();
$ictSupport = new ICTSupport();
$softwareService = new SoftwareService();

try {
    // Run automated maintenance check
    $equipment = new Equipment();
    $maintenanceCheck = $equipment->checkAndScheduleMaintenance();
    
    // Get equipment statistics
    $totalEquipment = $equipmentService->countEquipment();
    $availableEquipment = count($equipmentService->getAvailableEquipment());
    $borrowedEquipment = $totalEquipment - $availableEquipment;
    
    // Get maintenance statistics
    $dueMaintenance = $maintenanceService->getDueMaintenance();
    $overdueMaintenance = $maintenanceService->getOverdueMaintenance();
    $pendingMaintenance = count(array_filter($maintenanceService->getAllMaintenance(), function($m) {
        return $m['repair_status'] === 'Pending';
    }));
    
    // Get equipment due for maintenance (6-month interval)
    $equipmentDueForMaintenance = $equipment->getEquipmentDueForMaintenance();
    $overdueEquipment = $equipment->getOverdueEquipmentForMaintenance();
    
    // Get ICT support requests
    $ictRequests = $ictSupport->getAllRequests();
    $pendingRequests = count(array_filter($ictRequests, function($r) {
        return $r['status'] === 'Active';
    }));
    
    // Get software statistics
    $softwareList = $softwareService->getAllSoftware();
    $activeSoftware = count(array_filter($softwareList, function($s) {
        return $s['status'] === 'Active';
    }));
    $expiringSoftware = count(array_filter($softwareList, function($s) {
        return $s['license_expiry_date'] && strtotime($s['license_expiry_date']) <= strtotime('+30 days');
    }));
    
    // Get recent maintenance activities
    $recentMaintenance = array_slice($maintenanceService->getAllMaintenance(), 0, 5);
    
} catch (Exception $e) {
    error_log('[ICT Portal Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $totalEquipment = $availableEquipment = $borrowedEquipment = 0;
    $dueMaintenance = $overdueMaintenance = [];
    $pendingMaintenance = $pendingRequests = 0;
    $activeSoftware = $expiringSoftware = 0;
    $recentMaintenance = [];
    $equipmentDueForMaintenance = $overdueEquipment = [];
    $maintenanceCheck = ['success' => false, 'scheduled_count' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Staff Portal - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/ict_portal.css" rel="stylesheet">
    <style>
        .ict-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .kpi-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
        }
        .kpi-card.equipment { border-left-color: #28a745; }
        .kpi-card.maintenance { border-left-color: #ffc107; }
        .kpi-card.support { border-left-color: #dc3545; }
        .kpi-card.software { border-left-color: #17a2b8; }
        
        .quick-action {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .quick-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: inherit;
        }
        .quick-action i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #667eea;
        }
        
        .alert-item {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
        }
        .alert-item.urgent {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
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
            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Hero Section -->
            <div class="ict-hero rounded-3">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-5 fw-bold mb-3">
                                <i class="bi bi-pc-display me-3"></i>
                                ICT Staff Portal
                            </h1>
                            <p class="lead mb-0">Manage equipment, maintenance, and technical support efficiently</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-flex align-items-center justify-content-end">
                                <div class="me-3">
                                    <small class="text-light opacity-75">Welcome back,</small>
                                    <div class="fw-bold"><?= htmlspecialchars($_SESSION['name'] ?? 'ICT Staff') ?></div>
                                </div>
                                <div class="avatar bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="bi bi-person-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="kpi-card equipment">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Equipment</h6>
                                <h3 class="mb-0 text-success"><?= $totalEquipment ?></h3>
                                <small class="text-muted"><?= $availableEquipment ?> available</small>
                            </div>
                            <i class="bi bi-laptop text-success fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="kpi-card maintenance">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Maintenance</h6>
                                <h3 class="mb-0 text-warning"><?= count($dueMaintenance) + count($overdueMaintenance) + count($equipmentDueForMaintenance) + count($overdueEquipment) ?></h3>
                                <small class="text-muted"><?= count($overdueMaintenance) + count($overdueEquipment) ?> overdue</small>
                            </div>
                            <i class="bi bi-tools text-warning fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="kpi-card support">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Support Requests</h6>
                                <h3 class="mb-0 text-danger"><?= $pendingRequests ?></h3>
                                <small class="text-muted">Pending</small>
                            </div>
                            <i class="bi bi-headset text-danger fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="kpi-card software">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Software Licenses</h6>
                                <h3 class="mb-0 text-info"><?= $activeSoftware ?></h3>
                                <small class="text-muted"><?= $expiringSoftware ?> expiring soon</small>
                            </div>
                            <i class="bi bi-software text-info fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Quick Actions -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="ict_maintenance.php" class="quick-action d-block">
                                        <i class="bi bi-tools"></i>
                                        <div class="fw-bold">Maintenance</div>
                                        <small class="text-muted">Equipment care</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="ict_support_dashboard.php" class="quick-action d-block">
                                        <i class="bi bi-headset"></i>
                                        <div class="fw-bold">Support</div>
                                        <small class="text-muted">ICT requests</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="ict_equipment.php" class="quick-action d-block">
                                        <i class="bi bi-laptop"></i>
                                        <div class="fw-bold">Equipment</div>
                                        <small class="text-muted">Equipment list</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="ict_software.php" class="quick-action d-block">
                                        <i class="bi bi-software"></i>
                                        <div class="fw-bold">Software</div>
                                        <small class="text-muted">Licenses</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerts & Notifications -->
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Alerts & Notifications</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($overdueEquipment) > 0): ?>
                                <div class="alert-item urgent">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-exclamation-circle-fill text-danger me-2"></i>
                                        <div>
                                            <strong><?= count($overdueEquipment) ?> Equipment Overdue for 6-Month Maintenance</strong>
                                            <br><small>Equipment past due for scheduled maintenance</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (count($equipmentDueForMaintenance) > 0): ?>
                                <div class="alert-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-clock-fill text-warning me-2"></i>
                                        <div>
                                            <strong><?= count($equipmentDueForMaintenance) ?> Equipment Due for 6-Month Maintenance</strong>
                                            <br><small>Schedule maintenance within 30 days</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($maintenanceCheck['success'] && $maintenanceCheck['scheduled_count'] > 0): ?>
                                <div class="alert-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        <div>
                                            <strong><?= $maintenanceCheck['scheduled_count'] ?> Automated Maintenance Records Created</strong>
                                            <br><small>System automatically scheduled maintenance for equipment</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (count($dueMaintenance) > 0): ?>
                                <div class="alert-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-clock-fill text-warning me-2"></i>
                                        <div>
                                            <strong><?= count($dueMaintenance) ?> Equipment Due for Maintenance</strong>
                                            <br><small>Schedule maintenance soon</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($expiringSoftware > 0): ?>
                                <div class="alert-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-calendar-x-fill text-info me-2"></i>
                                        <div>
                                            <strong><?= $expiringSoftware ?> Software Licenses Expiring Soon</strong>
                                            <br><small>Renew licenses to avoid service interruption</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($pendingRequests > 0): ?>
                                <div class="alert-item">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-workspace text-primary me-2"></i>
                                        <div>
                                            <strong><?= $pendingRequests ?> Pending ICT Support Requests</strong>
                                            <br><small>Review and respond to requests</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (count($overdueMaintenance) == 0 && count($dueMaintenance) == 0 && $expiringSoftware == 0 && $pendingRequests == 0): ?>
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-check-circle-fill text-success fs-1 mb-2"></i>
                                    <div>All systems running smoothly!</div>
                                    <small>No urgent alerts at this time</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="bi bi-activity me-2"></i>Recent Maintenance Activities</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentMaintenance)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Equipment</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Technician</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentMaintenance as $activity): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($activity['equipment_name'] ?? 'Unknown') ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $activity['maintenance_type'] === 'Maintenance' ? 'primary' : 'warning' ?>">
                                                            <?= htmlspecialchars($activity['maintenance_type']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= match($activity['repair_status']) {
                                                            'Completed' => 'success',
                                                            'In Progress' => 'warning',
                                                            'Pending' => 'danger',
                                                            default => 'secondary'
                                                        } ?>">
                                                            <?= htmlspecialchars($activity['repair_status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('M d, Y', strtotime($activity['maintenance_date'])) ?></td>
                                                    <td><?= htmlspecialchars($activity['technician_name'] ?? 'N/A') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-clipboard-data fs-1 mb-2"></i>
                                    <div>No recent maintenance activities</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/ict_portal.js"></script>
</body>
</html>