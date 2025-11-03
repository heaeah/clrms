<?php
require_once '../includes/auth.php';
require_once '../classes/Database.php';

$database = new Database();
$pdo = $database->getConnection();

$success = '';
$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                // Add new borrower
                $name = trim($_POST['name']);
                $type = $_POST['type'];
                $contact_person = trim($_POST['contact_person']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $location = trim($_POST['location']);
                
                if (empty($name)) {
                    throw new Exception("Borrower name cannot be empty.");
                }
                
                $stmt = $pdo->prepare("INSERT INTO borrowers (name, type, contact_person, email, phone, location, status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
                $stmt->execute([$name, $type, $contact_person, $email, $phone, $location]);
                $success = "Borrower added successfully!";
                
            } elseif ($_POST['action'] === 'update') {
                // Update borrower
                $id = $_POST['borrower_id'];
                $name = trim($_POST['name']);
                $type = $_POST['type'];
                $contact_person = trim($_POST['contact_person']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $location = trim($_POST['location']);
                $status = $_POST['status'];
                
                if (empty($name)) {
                    throw new Exception("Borrower name cannot be empty.");
                }
                
                $stmt = $pdo->prepare("UPDATE borrowers SET name = ?, type = ?, contact_person = ?, email = ?, phone = ?, location = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $type, $contact_person, $email, $phone, $location, $status, $id]);
                $success = "Borrower updated successfully!";
                
            } elseif ($_POST['action'] === 'delete') {
                // Delete borrower
                $id = $_POST['borrower_id'];
                
                // Check if borrower has any active requests
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_requests WHERE borrower_name = (SELECT name FROM borrowers WHERE id = ?) AND status IN ('Pending', 'Approved')");
                $stmt->execute([$id]);
                $activeRequests = $stmt->fetchColumn();
                
                if ($activeRequests > 0) {
                    throw new Exception("Cannot delete borrower with active requests. Please handle all requests first.");
                }
                
                $stmt = $pdo->prepare("DELETE FROM borrowers WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Borrower deleted successfully!";
            }
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('[Manage Borrowers Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        $error = $e->getMessage();
    }
}

// Fetch all borrowers
try {
    $stmt = $pdo->query("SELECT * FROM borrowers ORDER BY name");
    $borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('[Manage Borrowers Fetch Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $borrowers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Borrowers - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="container-fluid px-4 mt-4">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 text-primary mb-0"><i class="bi bi-people-fill me-2"></i>Manage Borrowers</h1>
                    <p class="text-muted mb-0">Manage school offices and departments as borrowers</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBorrowerModal">
                    <i class="bi bi-plus-circle me-2"></i>Add New Borrower
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-primary shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-building text-primary" style="font-size: 2.5rem;"></i>
                            <h3 class="mt-2 text-primary fw-bold"><?= count($borrowers) ?></h3>
                            <p class="mb-0 text-muted fw-medium">Total Borrowers</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-success shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-check-circle text-success" style="font-size: 2.5rem;"></i>
                            <h3 class="mt-2 text-success fw-bold"><?= count(array_filter($borrowers, fn($b) => $b['status'] === 'Active')) ?></h3>
                            <p class="mb-0 text-muted fw-medium">Active Borrowers</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-warning shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-building-gear text-warning" style="font-size: 2.5rem;"></i>
                            <h3 class="mt-2 text-warning fw-bold"><?= count(array_filter($borrowers, fn($b) => $b['type'] === 'Office')) ?></h3>
                            <p class="mb-0 text-muted fw-medium">Offices</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card border-info shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-diagram-3 text-info" style="font-size: 2.5rem;"></i>
                            <h3 class="mt-2 text-info fw-bold"><?= count(array_filter($borrowers, fn($b) => $b['type'] === 'Department')) ?></h3>
                            <p class="mb-0 text-muted fw-medium">Departments</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Borrowers Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-table me-2"></i>School Offices & Departments
                        </h5>
                        <span class="badge bg-primary fs-6"><?= count($borrowers) ?> Total</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($borrowers)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-building-x text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3 text-muted fw-bold">No Borrowers Found</h4>
                            <p class="text-muted mb-4">Start by adding school offices and departments as borrowers.</p>
                            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addBorrowerModal">
                                <i class="bi bi-plus-circle me-2"></i>Add First Borrower
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 fw-bold">Name</th>
                                        <th class="border-0 fw-bold">Type</th>
                                        <th class="border-0 fw-bold">Contact Person</th>
                                        <th class="border-0 fw-bold">Email</th>
                                        <th class="border-0 fw-bold">Phone</th>
                                        <th class="border-0 fw-bold">Location</th>
                                        <th class="border-0 fw-bold">Status</th>
                                        <th class="border-0 fw-bold text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($borrowers as $borrower): ?>
                                        <tr class="align-middle">
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($borrower['name']) ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $borrower['type'] === 'Office' ? 'primary' : 'info' ?> fs-6">
                                                    <?= htmlspecialchars($borrower['type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?= htmlspecialchars($borrower['contact_person'] ?? 'N/A') ?></span>
                                            </td>
                                            <td>
                                                <?php if ($borrower['email']): ?>
                                                    <a href="mailto:<?= htmlspecialchars($borrower['email']) ?>" class="text-decoration-none">
                                                        <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($borrower['email']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?= htmlspecialchars($borrower['phone'] ?? 'N/A') ?></span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?= htmlspecialchars($borrower['location'] ?? 'N/A') ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $borrower['status'] === 'Active' ? 'success' : 'secondary' ?> fs-6">
                                                    <?= htmlspecialchars($borrower['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="editBorrower(<?= htmlspecialchars(json_encode($borrower)) ?>)"
                                                            title="Edit Borrower">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteBorrower(<?= $borrower['id'] ?>, '<?= htmlspecialchars($borrower['name']) ?>')"
                                                            title="Delete Borrower">
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

    <!-- Add Borrower Modal -->
    <div class="modal fade" id="addBorrowerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-plus-circle me-2"></i>Add New Borrower
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Borrower Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-lg" placeholder="e.g., Office of the Dean" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select form-select-lg" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="Office">Office</option>
                                    <option value="Department">Department</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control form-control-lg" placeholder="e.g., Dr. John Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" class="form-control form-control-lg" placeholder="contact@department.edu">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="tel" name="phone" class="form-control form-control-lg" placeholder="Enter 11-digit mobile number" 
                                       maxlength="11" pattern="[0-9]{11}" inputmode="numeric" 
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11)">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Location</label>
                                <input type="text" name="location" class="form-control form-control-lg" placeholder="e.g., Main Building, 2nd Floor">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle me-2"></i>Add Borrower
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Borrower Modal -->
    <div class="modal fade" id="editBorrowerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil me-2"></i>Edit Borrower
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="borrower_id" id="edit_borrower_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Borrower Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control form-control-lg" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Type <span class="text-danger">*</span></label>
                                <select name="type" id="edit_type" class="form-select form-select-lg" required>
                                    <option value="Office">Office</option>
                                    <option value="Department">Department</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contact Person</label>
                                <input type="text" name="contact_person" id="edit_contact_person" class="form-control form-control-lg">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control form-control-lg">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="tel" name="phone" id="edit_phone" class="form-control form-control-lg" 
                                       maxlength="11" pattern="[0-9]{11}" inputmode="numeric" 
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11)">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Location</label>
                                <input type="text" name="location" id="edit_location" class="form-control form-control-lg">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status</label>
                                <select name="status" id="edit_status" class="form-select form-select-lg">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="bi bi-check-circle me-2"></i>Update Borrower
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteBorrowerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 fw-bold">Are you sure?</h5>
                    <p class="mb-2">You are about to delete the borrower:</p>
                    <p class="fw-bold text-danger fs-5" id="delete_borrower_name"></p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="borrower_id" id="delete_borrower_id">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="bi bi-trash me-2"></i>Delete Borrower
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBorrower(borrower) {
            document.getElementById('edit_borrower_id').value = borrower.id;
            document.getElementById('edit_name').value = borrower.name;
            document.getElementById('edit_type').value = borrower.type;
            document.getElementById('edit_contact_person').value = borrower.contact_person || '';
            document.getElementById('edit_email').value = borrower.email || '';
            document.getElementById('edit_phone').value = borrower.phone || '';
            document.getElementById('edit_location').value = borrower.location || '';
            document.getElementById('edit_status').value = borrower.status;
            
            new bootstrap.Modal(document.getElementById('editBorrowerModal')).show();
        }

        function deleteBorrower(id, name) {
            document.getElementById('delete_borrower_id').value = id;
            document.getElementById('delete_borrower_name').textContent = name;
            
            new bootstrap.Modal(document.getElementById('deleteBorrowerModal')).show();
        }
    </script>
</body>
</html> 