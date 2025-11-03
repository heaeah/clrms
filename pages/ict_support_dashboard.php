<?php
require_once '../includes/auth.php';
require_role(['ICT Staff']);

require_once '../classes/ICTSupport.php';
require_once '../classes/EquipmentService.php';

$ictSupport = new ICTSupport();
$equipmentService = new EquipmentService();

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$departmentFilter = $_GET['department'] ?? '';
$dateFilter = $_GET['date'] ?? '';

try {
    // Get all ICT support requests
    $supportRequests = $ictSupport->getAllRequests();
    
    // Apply filters
    if ($statusFilter) {
        $supportRequests = array_filter($supportRequests, function($request) use ($statusFilter) {
            return $request['status'] === $statusFilter;
        });
    }
    
    if ($departmentFilter) {
        $supportRequests = array_filter($supportRequests, function($request) use ($departmentFilter) {
            return $request['department'] === $departmentFilter;
        });
    }
    
    if ($dateFilter) {
        $supportRequests = array_filter($supportRequests, function($request) use ($dateFilter) {
            return date('Y-m-d', strtotime($request['request_date'])) === $dateFilter;
        });
    }
    
    // Get statistics
    $totalRequests = count($ictSupport->getAllRequests());
    $activeRequests = count(array_filter($ictSupport->getAllRequests(), function($r) {
        return $r['status'] === 'Active';
    }));
    $resolvedRequests = count(array_filter($ictSupport->getAllRequests(), function($r) {
        return $r['status'] === 'Resolved';
    }));
    
    // Get unique departments for filter
    $departments = array_unique(array_column($ictSupport->getAllRequests(), 'department'));
    
} catch (Exception $e) {
    error_log('[ICT Support Dashboard Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $supportRequests = [];
    $totalRequests = $activeRequests = $resolvedRequests = 0;
    $departments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ICT Support Dashboard - ICT Portal</title>
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
                        <i class="bi bi-headset me-2 text-primary"></i>
                        ICT Support Dashboard
                    </h2>
                    <p class="text-muted mb-0">Manage and track ICT support requests</p>
                </div>
                <div>
                    <a href="ict_support_form.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>New Request
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="kpi-card support">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Requests</h6>
                                <h3 class="mb-0 text-primary"><?= $totalRequests ?></h3>
                                <small class="text-muted">All time</small>
                            </div>
                            <i class="bi bi-clipboard-data text-primary fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="kpi-card support">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Active Requests</h6>
                                <h3 class="mb-0 text-warning"><?= $activeRequests ?></h3>
                                <small class="text-muted">Pending resolution</small>
                            </div>
                            <i class="bi bi-clock text-warning fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="kpi-card support">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Resolved</h6>
                                <h3 class="mb-0 text-success"><?= $resolvedRequests ?></h3>
                                <small class="text-muted">Completed</small>
                            </div>
                            <i class="bi bi-check-circle text-success fs-1"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="kpi-card support">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Resolution Rate</h6>
                                <h3 class="mb-0 text-info"><?= $totalRequests > 0 ? round(($resolvedRequests / $totalRequests) * 100, 1) : 0 ?>%</h3>
                                <small class="text-muted">Success rate</small>
                            </div>
                            <i class="bi bi-graph-up text-info fs-1"></i>
                        </div>
                    </div>
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
                                <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Resolved" <?= $statusFilter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="department" class="form-label">Department</label>
                            <select name="department" id="department" class="form-select">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= htmlspecialchars($department) ?>" <?= $departmentFilter === $department ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($department) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-1"></i>Filter
                                </button>
                                <a href="ict_support_dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Support Requests Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>Support Requests
                        <span class="badge bg-primary ms-2"><?= count($supportRequests) ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($supportRequests)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">No support requests found</h5>
                            <p class="text-muted">Try adjusting your filters or create a new request</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Requester</th>
                                        <th>Department</th>
                                        <th>Nature of Request</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($supportRequests as $request): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold text-primary">#<?= $request['id'] ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                        <?= strtoupper(substr($request['requester_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($request['requester_name']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($request['department']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark"><?= htmlspecialchars($request['department']) ?></span>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($request['nature_of_request']) ?>">
                                                    <?= htmlspecialchars($request['nature_of_request']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?= date('M d, Y', strtotime($request['request_date'])) ?></div>
                                                <small class="text-muted"><?= date('h:i A', strtotime($request['request_time'])) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $request['status'] === 'Active' ? 'warning' : 'success' ?>">
                                                    <?= htmlspecialchars($request['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewRequestModal<?= $request['id'] ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <?php if ($request['status'] === 'Active'): ?>
                                                        <button class="btn btn-sm btn-outline-success" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#resolveRequestModal<?= $request['id'] ?>">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
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

    <!-- View Request Modal -->
    <?php foreach ($supportRequests as $request): ?>
        <div class="modal fade" id="viewRequestModal<?= $request['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-eye me-2"></i>Support Request #<?= $request['id'] ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Requester Name</label>
                                <div class="form-control-plaintext"><?= htmlspecialchars($request['requester_name']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Department</label>
                                <div class="form-control-plaintext"><?= htmlspecialchars($request['department']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Request Date</label>
                                <div class="form-control-plaintext"><?= date('M d, Y', strtotime($request['request_date'])) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Request Time</label>
                                <div class="form-control-plaintext"><?= date('h:i A', strtotime($request['request_time'])) ?></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Nature of Request</label>
                                <div class="form-control-plaintext"><?= htmlspecialchars($request['nature_of_request']) ?></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Action Taken</label>
                                <div class="form-control-plaintext"><?= htmlspecialchars($request['action_taken'] ?? 'No action taken yet') ?></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Status</label>
                                <div>
                                    <span class="badge bg-<?= $request['status'] === 'Active' ? 'warning' : 'success' ?> fs-6">
                                        <?= htmlspecialchars($request['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($request['photo']): ?>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Photo Evidence</label>
                                    <div>
                                        <img src="../uploads/<?= htmlspecialchars($request['photo']) ?>" 
                                             class="img-fluid rounded" style="max-width: 300px;" 
                                             alt="Photo evidence">
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <?php if ($request['status'] === 'Active'): ?>
                            <button type="button" class="btn btn-success" 
                                    data-bs-dismiss="modal" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#resolveRequestModal<?= $request['id'] ?>">
                                <i class="bi bi-check-circle me-1"></i>Resolve Request
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Resolve Request Modal -->
    <?php foreach ($supportRequests as $request): ?>
        <?php if ($request['status'] === 'Active'): ?>
            <div class="modal fade" id="resolveRequestModal<?= $request['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="bi bi-check-circle me-2"></i>Resolve Request #<?= $request['id'] ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="resolve_ict_request.php" method="POST">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="resolution_notes" class="form-label">Resolution Notes</label>
                                    <textarea name="resolution_notes" id="resolution_notes" class="form-control" rows="4" required 
                                              placeholder="Describe how the issue was resolved..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle me-1"></i>Mark as Resolved
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/ict_portal.js"></script>
</body>
</html>