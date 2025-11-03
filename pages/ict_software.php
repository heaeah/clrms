<?php
require_once '../includes/auth.php';
require_role(['ICT Staff']);

require_once '../classes/SoftwareService.php';

$softwareService = new SoftwareService();

// Handle Add Software
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    try {
        $data = [
            'name' => $_POST['name'],
            'lab_id' => $_POST['lab_id'] ?: null,
            'pc_number' => $_POST['pc_number'] ?: null,
            'installation_date' => $_POST['installation_date'] ?: null,
            'license_expiry_date' => $_POST['license_expiry_date'],
            'notes' => $_POST['notes']
        ];
        if ($softwareService->handleAddSoftware($data)) {
            set_flash('success', 'Software added successfully.');
        } else {
            set_flash('danger', 'Failed to add software.');
        }
        header('Location: ict_software.php');
        exit;
    } catch (Exception $e) {
        error_log('[ICT Software Add Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header('Location: ict_software.php');
        exit;
    }
}

// Handle Edit Software
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    try {
        $id = intval($_POST['edit_id']);
        $data = [
            'name' => $_POST['edit_name'],
            'lab_id' => $_POST['edit_lab_id'] ?: null,
            'pc_number' => $_POST['edit_pc_number'] ?: null,
            'installation_date' => $_POST['edit_installation_date'] ?: null,
            'license_expiry_date' => $_POST['edit_license_expiry_date'],
            'notes' => $_POST['edit_notes']
        ];
        if ($softwareService->updateSoftware($id, $data)) {
            set_flash('success', 'Software updated successfully.');
        } else {
            set_flash('danger', 'Failed to update software.');
        }
        header('Location: ict_software.php');
        exit;
    } catch (Exception $e) {
        error_log('[ICT Software Edit Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header('Location: ict_software.php');
        exit;
    }
}

// Handle Delete Software
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $id = intval($_POST['delete_id']);
        if ($softwareService->deleteSoftware($id)) {
            set_flash('success', 'Software deleted successfully.');
        } else {
            set_flash('danger', 'Failed to delete software.');
        }
        header('Location: ict_software.php');
        exit;
    } catch (Exception $e) {
        error_log('[ICT Software Delete Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header('Location: ict_software.php');
        exit;
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$expiryFilter = $_GET['expiry'] ?? '';

try {
    // Get all software
    $softwareList = $softwareService->getAllSoftware();
    
    // Apply filters
    if ($statusFilter === 'active') {
        $softwareList = array_filter($softwareList, function($software) {
            return $software['status'] === 'Active';
        });
    } elseif ($statusFilter === 'expired') {
        $softwareList = array_filter($softwareList, function($software) {
            return $software['status'] === 'Expired';
        });
    }
    
    if ($expiryFilter === 'expiring') {
        $softwareList = array_filter($softwareList, function($software) {
            if (!$software['license_expiry_date']) return false;
            $expiryDate = strtotime($software['license_expiry_date']);
            $thirtyDaysFromNow = strtotime('+30 days');
            return $expiryDate <= $thirtyDaysFromNow && $expiryDate > time();
        });
    }
    
    // Get statistics
    $totalSoftware = count($softwareService->getAllSoftware());
    $activeSoftware = count(array_filter($softwareService->getAllSoftware(), function($s) {
        return $s['status'] === 'Active';
    }));
    $expiredSoftware = count(array_filter($softwareService->getAllSoftware(), function($s) {
        return $s['status'] === 'Expired';
    }));
    $expiringSoftware = count(array_filter($softwareService->getAllSoftware(), function($s) {
        if (!$s['license_expiry_date']) return false;
        $expiryDate = strtotime($s['license_expiry_date']);
        $thirtyDaysFromNow = strtotime('+30 days');
        return $expiryDate <= $thirtyDaysFromNow && $expiryDate > time();
    }));
    
} catch (Exception $e) {
    error_log('[ICT Software Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $softwareList = [];
    $totalSoftware = $activeSoftware = $expiredSoftware = $expiringSoftware = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software Licenses - ICT Portal</title>
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
                        <i class="bi bi-software me-2 text-primary"></i>
                        Software Licenses
                    </h2>
                    <p class="text-muted mb-0">Manage software licenses and installations</p>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSoftwareModal">
                        <i class="bi bi-plus-circle me-2"></i>Add Software
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Software</h6>
                                    <h3 class="mb-0"><?= $totalSoftware ?></h3>
                                </div>
                                <i class="bi bi-software fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Active</h6>
                                    <h3 class="mb-0"><?= $activeSoftware ?></h3>
                                </div>
                                <i class="bi bi-check-circle fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Expiring Soon</h6>
                                    <h3 class="mb-0"><?= $expiringSoftware ?></h3>
                                </div>
                                <i class="bi bi-exclamation-triangle fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Expired</h6>
                                    <h3 class="mb-0"><?= $expiredSoftware ?></h3>
                                </div>
                                <i class="bi bi-x-circle fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="expiry" class="form-label">Expiry</label>
                            <select name="expiry" id="expiry" class="form-select">
                                <option value="">All</option>
                                <option value="expiring" <?= $expiryFilter === 'expiring' ? 'selected' : '' ?>>Expiring Soon (30 days)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-1"></i>Filter
                                </button>
                                <a href="ict_software.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Software List -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>Software Licenses
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($softwareList)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-software text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3 text-muted">No software licenses found</h5>
                            <p class="text-muted">Try adjusting your filters or add new software licenses</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Software Name</th>
                                        <th>Lab/PC</th>
                                        <th>Installation Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($softwareList as $software): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($software['name']) ?></div>
                                            </td>
                                            <td>
                                                <?php if ($software['lab_name']): ?>
                                                    <span class="badge bg-info"><?= htmlspecialchars($software['lab_name']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($software['pc_number']): ?>
                                                    <span class="badge bg-secondary">PC <?= htmlspecialchars($software['pc_number']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($software['installation_date']): ?>
                                                    <?= date('M d, Y', strtotime($software['installation_date'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($software['license_expiry_date']): ?>
                                                    <?= date('M d, Y', strtotime($software['license_expiry_date'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= match($software['status']) {
                                                    'Active' => 'success',
                                                    'Expired' => 'danger',
                                                    default => 'secondary'
                                                } ?>">
                                                    <?= htmlspecialchars($software['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($software['notes']): ?>
                                                    <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?= htmlspecialchars($software['notes']) ?>">
                                                        <?= htmlspecialchars($software['notes']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="editSoftware(<?= $software['id'] ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" onclick="deleteSoftware(<?= $software['id'] ?>)">
                                                        <i class="bi bi-trash"></i>
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

    <!-- Add Software Modal -->
    <div class="modal fade" id="addSoftwareModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-software me-2"></i>Add Software License
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="add" value="1">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Software Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="pc_number" class="form-label">PC Number</label>
                                <input type="text" name="pc_number" id="pc_number" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="installation_date" class="form-label">Installation Date</label>
                                <input type="date" name="installation_date" id="installation_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="license_expiry_date" class="form-label">License Expiry Date</label>
                                <input type="date" name="license_expiry_date" id="license_expiry_date" class="form-control">
                            </div>
                            <div class="col-12">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Add Software
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/ict_portal.js"></script>
    <script>
        function editSoftware(id) {
            // TODO: Implement edit software
            alert('Edit software: ' + id);
        }
        
        function deleteSoftware(id) {
            if (confirm('Are you sure you want to delete this software license?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_id" value="' + id + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
