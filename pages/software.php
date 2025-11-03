<?php
require_once '../includes/auth.php';
require_role(['Lab Admin']);
require_once '../classes/SoftwareService.php';
require_once '../classes/Lab.php';

$softwareService = new SoftwareService();
$labService = new Lab();

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
        header('Location: software.php');
        exit;
    } catch (Exception $e) {
        error_log('[Software Add Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header('Location: software.php');
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
        header('Location: software.php');
        exit;
    } catch (Exception $e) {
        error_log('[Software Edit Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header('Location: software.php');
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
        header('Location: software.php');
        exit;
    } catch (Exception $e) {
        error_log('[Software Delete Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header('Location: software.php');
        exit;
    }
}

// Fetch data
try {
    $softwareList = $softwareService->getAllSoftwareWithLabInfo();
    $equipmentList = $softwareService->getEquipmentFromInventory();
} catch (Exception $e) {
    error_log('[Software List Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $softwareList = [];
    $equipmentList = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Software Management - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/software.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>
        
        <!-- Lab Cards -->
        <div class="row mb-3" id="lab-cards-row">
            <div class="col-md-2 col-6 mb-2">
                <div class="card lab-card text-center p-2 shadow-sm border-primary" data-lab="" id="all-labs-card">
                    <div class="card-body py-2">
                        <div class="fw-bold text-primary mb-1" style="font-size:1.5em;"><i class="bi bi-grid-3x3"></i></div>
                        <div class="lab-title" style="font-size:1.1em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            All Labs
                        </div>
                        <div class="small text-muted" style="font-size:0.9em;">
                            View All Software
                        </div>
                    </div>
                </div>
            </div>
            <?php foreach ($labService->getAll() as $lab): ?>
                <div class="col-md-2 col-6 mb-2">
                    <div class="card lab-card text-center p-2 shadow-sm" data-lab="<?= htmlspecialchars($lab['lab_name']) ?>">
                        <div class="card-body py-2">
                            <div class="fw-bold text-primary mb-1" style="font-size:1.5em;"><i class="bi bi-pc-display-horizontal"></i></div>
                            <div class="lab-title" style="font-size:1.1em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($lab['lab_name']) ?>
                            </div>
                            <div class="small text-muted" style="font-size:0.9em;">
                                Computer Lab
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 text-primary mb-0"><i class="bi bi-pc-display"></i> Software Management</h1>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="bi bi-plus-circle"></i> Add Software
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Filter by Lab:</label>
                <select class="form-select" id="labFilter">
                    <option value="">All Labs</option>
                    <?php foreach ($labService->getAll() as $lab): ?>
                        <option value="<?= $lab['lab_name'] ?>"><?= htmlspecialchars($lab['lab_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter by Equipment:</label>
                <select class="form-select" id="pcFilter">
                    <option value="">All Equipment</option>
                    <?php foreach ($equipmentList as $equipment): ?>
                        <option value="<?= htmlspecialchars($equipment['name']) ?>">
                            <?= htmlspecialchars($equipment['name']) ?> - <?= htmlspecialchars($equipment['location']) ?> (<?= htmlspecialchars($equipment['category']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-text text-muted">Select equipment to filter software assignments</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-outline-secondary d-block" onclick="clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </button>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Lab Location</th>
                            <th>Equipment Assignment</th>
                            <th>Installation Date</th>
                            <th>License Expiry Date</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($softwareList as $sw): ?>
                            <tr>
                                <td><?= htmlspecialchars($sw['name']) ?></td>
                                <td>
                                    <?php if ($sw['lab_name']): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($sw['lab_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">No lab assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sw['pc_number']): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($sw['pc_number']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sw['installation_date']): ?>
                                        <?= htmlspecialchars($sw['installation_date']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sw['license_expiry_date']): ?>
                                        <?php 
                                        $expiryDate = new DateTime($sw['license_expiry_date']);
                                        $today = new DateTime();
                                        $isExpired = $expiryDate < $today;
                                        $isExpiringSoon = $expiryDate <= $today->modify('+30 days') && $expiryDate >= $today;
                                        ?>
                                        <span class="<?= $isExpired ? 'text-danger fw-bold' : ($isExpiringSoon ? 'text-warning fw-bold' : '') ?>">
                                            <?= htmlspecialchars($sw['license_expiry_date']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No expiry date</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($sw['notes']) ?></td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Software Actions">
                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $sw['id'] ?>" title="Edit Software">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSoftware(<?= $sw['id'] ?>, '<?= htmlspecialchars($sw['name']) ?>')" title="Delete Software">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <input type="hidden" name="add" value="1">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addModalLabel"><i class="bi bi-plus-circle"></i> Add Software</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lab Location</label>
                    <select name="lab_id" class="form-control">
                        <option value="">Select a Lab</option>
                        <?php foreach ($labService->getAll() as $lab): ?>
                            <option value="<?= $lab['id'] ?>"><?= htmlspecialchars($lab['lab_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Equipment Assignment</label>
                    <select name="pc_number" class="form-control">
                        <option value="">Select Equipment (Optional)</option>
                        <?php foreach ($equipmentList as $equipment): ?>
                            <option value="<?= htmlspecialchars($equipment['name']) ?>">
                                <?= htmlspecialchars($equipment['name']) ?> - <?= htmlspecialchars($equipment['location']) ?> (<?= htmlspecialchars($equipment['category']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Select the equipment where this software is installed</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Installation Date</label>
                    <input type="date" name="installation_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">License Expiry Date</label>
                    <input type="date" name="license_expiry_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save"></i> Save Software</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modals -->
<?php foreach ($softwareList as $sw): ?>
<div class="modal fade" id="editModal<?= $sw['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $sw['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            <input type="hidden" name="edit_id" value="<?= $sw['id'] ?>">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="editModalLabel<?= $sw['id'] ?>"><i class="bi bi-pencil"></i> Edit Software</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="edit_name" class="form-control" value="<?= htmlspecialchars($sw['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lab Location</label>
                    <select name="edit_lab_id" class="form-control">
                        <option value="">Select a Lab</option>
                        <?php foreach ($labService->getAll() as $lab): ?>
                            <option value="<?= $lab['id'] ?>" <?= $sw['lab_id'] == $lab['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lab['lab_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Equipment Assignment</label>
                    <select name="edit_pc_number" class="form-control">
                        <option value="">Select Equipment (Optional)</option>
                        <?php foreach ($equipmentList as $equipment): ?>
                            <option value="<?= htmlspecialchars($equipment['name']) ?>" <?= $sw['pc_number'] == $equipment['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($equipment['name']) ?> - <?= htmlspecialchars($equipment['location']) ?> (<?= htmlspecialchars($equipment['category']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Select the equipment where this software is installed</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Installation Date</label>
                    <input type="date" name="edit_installation_date" class="form-control" value="<?= htmlspecialchars($sw['installation_date']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">License Expiry Date</label>
                    <input type="date" name="edit_license_expiry_date" class="form-control" value="<?= htmlspecialchars($sw['license_expiry_date']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="edit_notes" class="form-control"><?= htmlspecialchars($sw['notes']) ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning w-100"><i class="bi bi-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/software.js"></script>
<script>
// Delete software function
function deleteSoftware(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_id';
        input.value = id;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html> 