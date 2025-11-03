<?php
require_once '../includes/auth.php';
require_role(['Lab Admin', 'Chairperson']);
require_once '../classes/BorrowRequest.php';

$borrowRequestObj = new BorrowRequest();

// Handle Approve/Deny actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    try {
        $id = (int)$_POST['request_id'];
        if ($_POST['action'] === 'approve') {
            $borrowRequestObj->updateStatus($id, 'Approved');
            // Mark equipment as Borrowed
            require_once '../classes/Database.php';
            $pdo = (new Database())->getConnection();
            $stmt = $pdo->prepare("SELECT equipment_id FROM borrow_request_items WHERE request_id = ?");
            $stmt->execute([$id]);
            $equipIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($equipIds) {
                $in = implode(',', array_fill(0, count($equipIds), '?'));
                $stmt = $pdo->prepare("UPDATE equipment SET status = 'Borrowed' WHERE id IN ($in)");
                $stmt->execute($equipIds);
            }
            // Email notification
            require_once __DIR__ . '/../includes/send_mail.php';
            $stmt = $pdo->prepare("SELECT borrower_email, borrower_name, tracking_code FROM borrow_requests WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['borrower_email']) {
                $to = $row['borrower_email'];
                $subject = "Your Borrow Request Has Been Approved";
                $message = "Hello {$row['borrower_name']},\n\nYour borrow request has been APPROVED.\n\nTracking Code: {$row['tracking_code']}\n\nYou can track your request status at: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/../pages/borrowers_portal.php\n\nThank you.";
                sendSMTPMail($to, $subject, $message);
            }
            $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Request approved.'];
        } elseif ($_POST['action'] === 'deny') {
            $remarks = trim($_POST['remarks'] ?? '');
            $borrowRequestObj->updateStatus($id, 'Rejected', $remarks);
            // Email notification
            require_once __DIR__ . '/../includes/send_mail.php';
            $pdo = (new Database())->getConnection();
            $stmt = $pdo->prepare("SELECT borrower_email, borrower_name, tracking_code FROM borrow_requests WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['borrower_email']) {
                $to = $row['borrower_email'];
                $subject = "Your Borrow Request Has Been Denied";
                $message = "Hello {$row['borrower_name']},\n\nYour borrow request has been DENIED.\n\nTracking Code: {$row['tracking_code']}\n\nYou can track your request status at: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/../pages/borrowers_portal.php\n\nThank you.";
                sendSMTPMail($to, $subject, $message);
            }
            $_SESSION['flash'][] = ['type' => 'danger', 'msg' => 'Request denied.'];
        } elseif ($_POST['action'] === 'returned') {
            require_once '../classes/Database.php';
            $pdo = (new Database())->getConnection();
            // Get the borrow date/time
            $stmt = $pdo->prepare("SELECT date_requested FROM borrow_requests WHERE id = ?");
            $stmt->execute([$id]);
            $borrow = $stmt->fetch(PDO::FETCH_ASSOC);
            $borrow_date = $borrow ? $borrow['date_requested'] : null;
            // Use provided date and time
            if (!empty($_POST['return_date_date']) && !empty($_POST['return_date_time'])) {
                $return_date = $_POST['return_date_date'] . ' ' . $_POST['return_date_time'] . ':00';
            } else {
                $return_date = date('Y-m-d H:i:s');
            }
            // Compare dates
            if ($borrow_date && strtotime($return_date) < strtotime($borrow_date)) {
                $_SESSION['flash'][] = ['type' => 'danger', 'msg' => 'Return date/time cannot be earlier than the borrowed date/time.'];
                header('Location: manage_borrow_requests.php');
                exit;
            }
            $stmt = $pdo->prepare("UPDATE borrow_requests SET status = 'Returned', return_date = ? WHERE id = ?");
            $stmt->execute([$return_date, $id]);
            $stmt = $pdo->prepare("SELECT equipment_id FROM borrow_request_items WHERE request_id = ?");
            $stmt->execute([$id]);
            $equipIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($equipIds) {
                $in = implode(',', array_fill(0, count($equipIds), '?'));
                $stmt = $pdo->prepare("UPDATE equipment SET status = 'Available' WHERE id IN ($in)");
                $stmt->execute($equipIds);
            }
            $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Marked as returned.'];
        }
        header('Location: manage_borrow_requests.php');
        exit;
    } catch (Exception $e) {
        // What: Error during borrow request status update
        // Why: DB error, validation error, etc.
        // How: Log error and show user-friendly message
        error_log('[Manage Borrow Requests Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        $_SESSION['flash'][] = ['type' => 'danger', 'msg' => 'Error: ' . $e->getMessage()];
        header('Location: manage_borrow_requests.php');
        exit;
    }
}

$requests = $borrowRequestObj->getAllBorrowRequests();

// Calculate statistics
$totalRequests = count($requests);
$pendingRequests = count(array_filter($requests, fn($r) => $r['status'] === 'Pending'));
$approvedRequests = count(array_filter($requests, fn($r) => $r['status'] === 'Approved'));
$returnedRequests = count(array_filter($requests, fn($r) => $r['status'] === 'Returned'));
$deniedRequests = count(array_filter($requests, fn($r) => $r['status'] === 'Rejected'));

// Calculate late returns
$lateReturns = count(array_filter($requests, function($r) {
    return $r['status'] === 'Returned' && 
           !empty($r['return_date']) && 
           !empty($r['borrow_end']) && 
           strtotime($r['return_date']) > strtotime($r['borrow_end']);
}));

// Get filter parameter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Filter requests if status filter is applied
if ($statusFilter && $statusFilter !== 'all') {
    $requests = array_filter($requests, fn($r) => $r['status'] === $statusFilter);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Borrow Requests - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/manage_borrow_requests.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>
        
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 text-primary mb-0"><i class="bi bi-journal-arrow-up me-2"></i>Manage Borrow Requests</h1>
                <p class="text-muted mb-0">Review and manage equipment borrow requests from faculty and offices/departments</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-primary shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-journal-text text-primary" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-primary fw-bold"><?= $totalRequests ?></h4>
                        <p class="mb-0 text-muted small">Total Requests</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-warning shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-warning fw-bold"><?= $pendingRequests ?></h4>
                        <p class="mb-0 text-muted small">Pending Review</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-danger shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-box-arrow-right text-danger" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-danger fw-bold"><?= $approvedRequests ?></h4>
                        <p class="mb-0 text-muted small">Currently Borrowed</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-success shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-success fw-bold"><?= $returnedRequests ?></h4>
                        <p class="mb-0 text-muted small">Returned</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-info shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-exclamation-triangle text-info" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-info fw-bold"><?= $lateReturns ?></h4>
                        <p class="mb-0 text-muted small">Late Returns</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-secondary shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-x-circle text-secondary" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-secondary fw-bold"><?= $deniedRequests ?></h4>
                        <p class="mb-0 text-muted small">Denied</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-funnel me-2"></i>Filter Requests
                        </h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-primary fs-6"><?= count($requests) ?> Results</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Status Filter</label>
                        <select class="form-select" id="statusFilter" onchange="filterByStatus(this.value)">
                            <option value="all" <?= $statusFilter === '' || $statusFilter === 'all' ? 'selected' : '' ?>>All Requests</option>
                            <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Currently Borrowed</option>
                            <option value="Returned" <?= $statusFilter === 'Returned' ? 'selected' : '' ?>>Returned</option>
                            <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Search Borrower</label>
                        <input type="text" class="form-control" id="searchBorrower" placeholder="Search by borrower name..." onkeyup="searchTable()">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Quick Actions</label>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="exportToCSV()">
                                <i class="bi bi-download me-1"></i>Export
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="refreshPage()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-table me-2"></i>Borrow Requests
                    </h5>
                    <div class="d-flex gap-2">
                        <span class="badge bg-warning text-dark"><?= $pendingRequests ?> Pending</span>
                        <span class="badge bg-danger"><?= $approvedRequests ?> Borrowed</span>
                        <span class="badge bg-success"><?= $returnedRequests ?> Returned</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="requestsTable">
                        <thead class="table-light">
                        <tr>
                            <th class="border-0 fw-bold">#</th>
                            <th class="border-0 fw-bold">Borrower</th>
                            <th class="border-0 fw-bold">ID Photo</th>
                            <th class="border-0 fw-bold">Equipment</th>
                            <th class="border-0 fw-bold">Purpose</th>
                            <th class="border-0 fw-bold">Date Requested</th>
                            <th class="border-0 fw-bold">Status</th>
                            <th class="border-0 fw-bold">Expected Return</th>
                            <th class="border-0 fw-bold">Actual Return</th>
                            <th class="border-0 fw-bold text-center">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $i => $req): ?>
                                <?php 
                                $status = trim($req['status']); 
                                // Fallback: if status is empty, assume it's rejected
                                if (empty($status)) {
                                    $status = 'Rejected';
                                }
                                ?>
                                <tr class="align-middle" 
                                    data-request-id="<?= $req['id'] ?>" 
                                    data-borrower-email="<?= htmlspecialchars($req['borrower_email'] ?? 'N/A') ?>"
                                    data-borrower-name="<?= htmlspecialchars($req['borrower_name']) ?>"
                                    data-date-requested="<?= htmlspecialchars(date("M d, Y", strtotime($req['date_requested']))) ?>"
                                    data-time-requested="<?= htmlspecialchars(date("h:i A", strtotime($req['date_requested']))) ?>"
                                    data-status="<?= htmlspecialchars($status) ?>"
                                    data-purpose="<?= htmlspecialchars($req['purpose'] ?? 'N/A') ?>"
                                    data-equipment="<?= htmlspecialchars($req['equipment_names']) ?>"
                                    data-quantities="<?= htmlspecialchars($req['quantities']) ?>"
                                    data-borrow-start="<?= !empty($req['borrow_start']) ? htmlspecialchars(date('M d, Y', strtotime($req['borrow_start']))) : 'N/A' ?>"
                                    data-borrow-start-time="<?= !empty($req['borrow_start']) ? htmlspecialchars(date('h:i A', strtotime($req['borrow_start']))) : '' ?>"
                                    data-expected-return="<?= !empty($req['borrow_end']) ? htmlspecialchars(date('M d, Y', strtotime($req['borrow_end']))) : 'N/A' ?>"
                                    data-expected-return-time="<?= !empty($req['borrow_end']) ? htmlspecialchars(date('h:i A', strtotime($req['borrow_end']))) : '' ?>"
                                                                         data-location-of-use="<?= htmlspecialchars($req['location_of_use'] ?? 'N/A') ?>"
                                     data-released-by="<?= htmlspecialchars($req['released_by'] ?? 'N/A') ?>"
                                     data-borrower-type="Faculty">
                                    <td>
                                        <span class="fw-bold text-primary"><?= $i + 1 ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($req['borrower_name']) ?></div>
                                        <small class="text-muted">ID: <?= htmlspecialchars($req['id']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($req['id_picture'])): ?>
                                            <button class="btn btn-outline-primary btn-sm" onclick="viewIDPhoto('<?= htmlspecialchars($req['id_picture']) ?>', '<?= htmlspecialchars($req['borrower_name']) ?>')" title="View ID Photo">
                                                <i class="bi bi-image me-1"></i>View ID
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">No ID Photo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars($req['equipment_names']) ?></div>
                                        <small class="text-muted">Qty: <?= htmlspecialchars($req['quantities']) ?></small>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= htmlspecialchars($req['purpose'] ?? 'N/A') ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars(date("M d, Y", strtotime($req['date_requested']))) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars(date("h:i A", strtotime($req['date_requested']))) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($status === 'Pending'): ?>
                                            <span class="badge bg-warning text-dark fs-6">Pending</span>
                                        <?php elseif ($status === 'Approved'): ?>
                                            <span class="badge bg-danger fs-6">Borrowed</span>
                                        <?php elseif ($status === 'Rejected'): ?>
                                            <span class="badge bg-secondary fs-6">Rejected</span>
                                        <?php elseif ($status === 'Returned'): ?>
                                            <span class="badge bg-success fs-6">Returned</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary fs-6"><?= htmlspecialchars($status) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($req['borrow_end'])): ?>
                                            <div class="fw-medium text-primary"><?= htmlspecialchars(date('M d, Y', strtotime($req['borrow_end']))) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars(date('h:i A', strtotime($req['borrow_end']))) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($status === 'Returned' && !empty($req['return_date'])): ?>
                                            <div class="fw-medium"><?= htmlspecialchars(date('M d, Y', strtotime($req['return_date']))) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars(date('h:i A', strtotime($req['return_date']))) ?></small>
                                            <?php 
                                            // Check if returned late
                                            if (!empty($req['borrow_end']) && strtotime($req['return_date']) > strtotime($req['borrow_end'])): 
                                            ?>
                                                <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Late Return</small>
                                            <?php endif; ?>
                                        <?php elseif ($status === 'Approved'): ?>
                                            <span class="text-muted">Not returned yet</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column gap-1 align-items-center">
                                            <!-- Print Button (Always visible) -->
                                            <button class="btn btn-outline-secondary btn-sm" onclick="printBorrowRequest(<?= $req['id'] ?>)" title="Print Request">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                            
                                            <!-- Action Buttons -->
                                            <?php if ($status === 'Pending'): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-success" data-bs-toggle="modal"
                                                            data-bs-target="#approveModal"
                                                            data-id="<?= $req['id'] ?>" 
                                                            data-borrower="<?= htmlspecialchars($req['borrower_name']) ?>"
                                                            title="Approve Request">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                    <button class="btn btn-danger" data-bs-toggle="modal"
                                                            data-bs-target="#denyModal"
                                                            data-id="<?= $req['id'] ?>" 
                                                            data-borrower="<?= htmlspecialchars($req['borrower_name']) ?>"
                                                            title="Deny Request">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </div>
                                            <?php elseif ($status === 'Approved'): ?>
                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                                        data-bs-target="#returnModal" data-id="<?= $req['id'] ?>" title="Mark as Returned">
                                                    <i class="bi bi-arrow-90deg-left me-1"></i>Return
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">No actions</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 text-muted fw-bold">No Requests Found</h5>
                                    <p class="text-muted">No borrow requests match your current filters.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Deny Modal -->
    <!-- Approve Confirmation Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" action="manage_borrow_requests.php" class="modal-content" id="approveForm">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="approveModalLabel">
                        <i class="bi bi-check-circle me-2"></i>Approve Borrow Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="approveRequestId">
                    <input type="hidden" name="action" value="approve">
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Confirm Approval</strong><br>
                            <span id="approveBorrowerInfo">Are you sure you want to approve this borrow request?</span>
                        </div>
                    </div>
                    <p class="mb-0">
                        <i class="bi bi-check-circle text-success me-2"></i> The equipment will be marked as "Borrowed"<br>
                        <i class="bi bi-envelope text-success me-2"></i> An approval email will be sent to the borrower
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success btn-lg" id="approveSubmitBtn">
                        <i class="bi bi-check-circle me-2"></i>Yes, Approve Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deny Modal -->
    <div class="modal fade" id="denyModal" tabindex="-1" aria-labelledby="denyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="denyModalLabel">
                        <i class="bi bi-x-circle me-2"></i>Deny Borrow Request
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="denyRequestId">
                    <input type="hidden" name="action" value="deny">
                    <div class="mb-3">
                        <label for="denyRemarks" class="form-label fw-bold">Remarks (optional):</label>
                        <textarea class="form-control form-control-lg" name="remarks" id="denyRemarks" rows="3" placeholder="Provide a reason for denying this request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="bi bi-x-circle me-2"></i>Deny Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Return Modal -->
    <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="returnModalLabel">
                        <i class="bi bi-arrow-90deg-left me-2"></i>Mark as Returned
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="returnRequestId">
                    <input type="hidden" name="action" value="returned">
                    <div class="mb-3">
                        <label for="returnDate" class="form-label fw-bold">Date of Return</label>
                        <input type="date" class="form-control form-control-lg" name="return_date_date" id="returnDate" required>
                    </div>
                    <div class="mb-3">
                        <label for="returnTime" class="form-label fw-bold">Time of Return</label>
                        <input type="time" class="form-control form-control-lg" name="return_date_time" id="returnTime" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Mark as Returned
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ID Photo Modal -->
    <div class="modal fade" id="idPhotoModal" tabindex="-1" aria-labelledby="idPhotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="idPhotoModalLabel">
                        <i class="bi bi-image me-2"></i>ID Photo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <h6 class="text-muted" id="borrowerNameDisplay"></h6>
                    </div>
                    <div class="id-photo-container">
                        <img id="idPhotoImage" src="" alt="ID Photo" class="img-fluid rounded shadow-sm" style="max-height: 500px; max-width: 100%;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Close
                    </button>
                    <a id="downloadIDPhoto" href="" download class="btn btn-primary">
                        <i class="bi bi-download me-2"></i>Download
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/manage_borrow_requests.js?v=<?= time() ?>"></script>
<script>
// Filter by status
function filterByStatus(status) {
    const url = new URL(window.location);
    if (status === 'all') {
        url.searchParams.delete('status');
    } else {
        url.searchParams.set('status', status);
    }
    window.location.href = url.toString();
}

// Search table
function searchTable() {
    const input = document.getElementById('searchBorrower');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('requestsTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const borrowerCell = rows[i].getElementsByTagName('td')[1];
        if (borrowerCell) {
            const borrowerName = borrowerCell.textContent || borrowerCell.innerText;
            if (borrowerName.toLowerCase().indexOf(filter) > -1) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
}

// Export to CSV
function exportToCSV() {
    const table = document.getElementById('requestsTable');
    const rows = table.getElementsByTagName('tr');
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cols = row.querySelectorAll('td, th');
        let csvRow = [];
        
        for (let j = 0; j < cols.length - 1; j++) { // Exclude Actions column
            let text = '';
            const col = cols[j];
            
            // Extract text content properly
            if (col.tagName === 'TH') {
                // Header row - get direct text
                text = col.textContent.trim();
            } else {
                // Data row - handle nested elements
                const mainText = col.querySelector('.fw-bold, .fw-medium');
                const subText = col.querySelector('small');
                
                if (mainText && subText) {
                    text = mainText.textContent.trim() + ' (' + subText.textContent.trim() + ')';
                } else if (mainText) {
                    text = mainText.textContent.trim();
                } else if (subText) {
                    text = subText.textContent.trim();
                } else {
                    // Fallback to all text content
                    text = col.textContent.trim();
                }
            }
            
            // Clean up the text
            text = text.replace(/"/g, '""').replace(/\n/g, ' ').replace(/\s+/g, ' ');
            csvRow.push('"' + text + '"');
        }
        
        csv.push(csvRow.join(','));
    }
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'borrow_requests_' + new Date().toISOString().slice(0, 10) + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// View ID Photo function
function viewIDPhoto(photoPath, borrowerName) {
    const modal = new bootstrap.Modal(document.getElementById('idPhotoModal'));
    const image = document.getElementById('idPhotoImage');
    const borrowerDisplay = document.getElementById('borrowerNameDisplay');
    const downloadLink = document.getElementById('downloadIDPhoto');
    
    // Set the image source
    image.src = '../' + photoPath;
    image.alt = 'ID Photo for ' + borrowerName;
    
    // Set borrower name
    borrowerDisplay.textContent = 'ID Photo for: ' + borrowerName;
    
    // Set download link
    downloadLink.href = '../' + photoPath;
    downloadLink.download = 'ID_Photo_' + borrowerName.replace(/\s+/g, '_') + '.jpg';
    
    // Show modal
    modal.show();
}

// Refresh page
function refreshPage() {
    window.location.reload();
}

// Set current date and time for return modal
document.addEventListener('DOMContentLoaded', function() {
    const returnDate = document.getElementById('returnDate');
    const returnTime = document.getElementById('returnTime');
    
    if (returnDate && returnTime) {
        const now = new Date();
        returnDate.value = now.toISOString().slice(0, 10);
        returnTime.value = now.toTimeString().slice(0, 5);
    }
    
    // Initialize real-time updates for this page
    initializeRealTimeUpdates();
    
    // Add CSS for new request highlighting
    addNewRequestStyles();
});

// Add CSS styles for new request highlighting
function addNewRequestStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .new-request-highlight {
            animation: highlightNewRequest 3s ease-out;
            background-color: #d4edda !important;
            border-left: 4px solid #28a745 !important;
        }
        
        @keyframes highlightNewRequest {
            0% {
                background-color: #d4edda;
                transform: translateX(-20px);
                opacity: 0.8;
            }
            50% {
                background-color: #d4edda;
                transform: translateX(0);
                opacity: 1;
            }
            100% {
                background-color: transparent;
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .new-request-highlight td {
            border-color: #28a745 !important;
        }
    `;
    document.head.appendChild(style);
}

// Real-time updates for manage borrow requests page
function initializeRealTimeUpdates() {
    let lastCheck = new Date().toISOString().slice(0, 19).replace('T', ' ');
    let lastRequestCount = 0;
    
    // Check for new requests every 5 seconds
    setInterval(async function() {
        try {
            const response = await fetch(`api/notifications.php?lastCheck=${encodeURIComponent(lastCheck)}`);
            const data = await response.json();
            
            if (data.success) {
                const currentPendingCount = data.counts.pendingBorrowRequests;
                
                // If there are new pending requests, fetch and display them
                if (currentPendingCount > lastRequestCount && lastRequestCount > 0) {
                    showNewRequestNotification(currentPendingCount - lastRequestCount);
                    
                    // Fetch new requests and add them to the table
                    await fetchAndDisplayNewRequests();
                }
                
                lastRequestCount = currentPendingCount;
                lastCheck = data.timestamp;
            }
        } catch (error) {
            console.error('Error checking for updates:', error);
        }
    }, 5000);
    
    // Set initial count
    const pendingBadge = document.querySelector('.badge.bg-warning');
    if (pendingBadge) {
        lastRequestCount = parseInt(pendingBadge.textContent) || 0;
    }
}

// Fetch new requests and display them dynamically
async function fetchAndDisplayNewRequests() {
    try {
        const response = await fetch('api/get_new_requests.php');
        const data = await response.json();
        
        if (data.success && data.requests.length > 0) {
            const tbody = document.querySelector('#requestsTable tbody');
            
            data.requests.forEach((request, index) => {
                // Check if this request is already displayed
                if (!document.querySelector(`tr[data-request-id="${request.id}"]`)) {
                    const newRow = createRequestRow(request, tbody.children.length + 1);
                    tbody.insertBefore(newRow, tbody.firstChild);
                    
                    // Add animation class
                    newRow.classList.add('new-request-highlight');
                    setTimeout(() => {
                        newRow.classList.remove('new-request-highlight');
                    }, 3000);
                }
            });
            
            // Update statistics
            updateStatistics();
        }
    } catch (error) {
        console.error('Error fetching new requests:', error);
    }
}

// Create a new request row
function createRequestRow(request, rowNumber) {
    const row = document.createElement('tr');
    row.className = 'align-middle';
    row.setAttribute('data-request-id', request.id);
    row.setAttribute('data-borrower-email', request.borrower_email || 'N/A');
    row.setAttribute('data-borrower-name', request.borrower_name);
    row.setAttribute('data-date-requested', formatDate(request.date_requested));
    row.setAttribute('data-time-requested', formatTime(request.date_requested));
    row.setAttribute('data-status', request.status);
    row.setAttribute('data-purpose', request.purpose || 'N/A');
    row.setAttribute('data-equipment', request.equipment_names);
    row.setAttribute('data-quantities', request.quantities);
    row.setAttribute('data-borrow-start', request.borrow_start ? formatDate(request.borrow_start) : 'N/A');
    row.setAttribute('data-borrow-start-time', request.borrow_start ? formatTime(request.borrow_start) : '');
    row.setAttribute('data-expected-return', request.borrow_end ? formatDate(request.borrow_end) : 'N/A');
    row.setAttribute('data-expected-return-time', request.borrow_end ? formatTime(request.borrow_end) : '');
    row.setAttribute('data-location-of-use', request.location_of_use || 'N/A');
    row.setAttribute('data-released-by', request.released_by || 'N/A');
    row.setAttribute('data-borrower-type', 'Faculty');
    
    const status = request.status.trim();
    
    row.innerHTML = `
        <td>
            <span class="fw-bold text-primary">${rowNumber}</span>
        </td>
        <td>
            <div class="fw-bold text-dark">${escapeHtml(request.borrower_name)}</div>
            <small class="text-muted">ID: ${escapeHtml(request.id)}</small>
        </td>
        <td>
            <div class="fw-medium">${escapeHtml(request.equipment_names)}</div>
            <small class="text-muted">Qty: ${escapeHtml(request.quantities)}</small>
        </td>
        <td>
            <span class="text-muted">${escapeHtml(request.purpose || 'N/A')}</span>
        </td>
        <td>
            <div class="fw-medium">${formatDate(request.date_requested)}</div>
            <small class="text-muted">${formatTime(request.date_requested)}</small>
        </td>
        <td>
            ${getStatusBadge(status)}
        </td>
        <td>
            ${getReturnDateCell(request)}
        </td>
        <td class="text-center">
            ${getActionButtons(request)}
        </td>
    `;
    
    return row;
}

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });
}

function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
}

function getStatusBadge(status) {
    if (status === 'Pending') {
        return '<span class="badge bg-warning text-dark fs-6">Pending</span>';
    } else if (status === 'Approved') {
        return '<span class="badge bg-danger fs-6">Borrowed</span>';
    } else if (status === 'Rejected') {
        return '<span class="badge bg-secondary fs-6">Rejected</span>';
    } else if (status === 'Returned') {
        return '<span class="badge bg-success fs-6">Returned</span>';
    } else {
        return `<span class="badge bg-secondary fs-6">${escapeHtml(status)}</span>`;
    }
}

function getReturnDateCell(request) {
    const status = request.status.trim();
    
    if (status === 'Returned' && request.return_date) {
        return `
            <div class="fw-medium">${formatDate(request.return_date)}</div>
            <small class="text-muted">${formatTime(request.return_date)}</small>
        `;
    } else if (status === 'Approved' && request.borrow_end) {
        return `
            <div class="fw-medium text-primary">${formatDate(request.borrow_end)}</div>
            <small class="text-muted">Expected Return</small>
        `;
    } else {
        return '<span class="text-muted">-</span>';
    }
}

function getActionButtons(request) {
    const status = request.status.trim();
    
    if (status === 'Pending') {
        return `
            <div class="btn-group btn-group-sm">
                <form method="post" class="d-inline">
                    <input type="hidden" name="request_id" value="${request.id}">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success" title="Approve Request">
                        <i class="bi bi-check-circle"></i>
                    </button>
                </form>
                <button class="btn btn-danger" data-bs-toggle="modal"
                        data-bs-target="#denyModal"
                        data-id="${request.id}" title="Deny Request">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        `;
    } else if (status === 'Approved') {
        return `
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                    data-bs-target="#returnModal" data-id="${request.id}" title="Mark as Returned">
                <i class="bi bi-arrow-90deg-left me-1"></i>Return
            </button>
        `;
    } else {
        return '<span class="text-muted small">No actions</span>';
    }
}

// Update statistics without page refresh
function updateStatistics() {
    const rows = document.querySelectorAll('#requestsTable tbody tr');
    let totalRequests = rows.length;
    let pendingRequests = 0;
    let approvedRequests = 0;
    let returnedRequests = 0;
    
    rows.forEach(row => {
        const status = row.querySelector('.badge').textContent.trim();
        if (status === 'Pending') pendingRequests++;
        else if (status === 'Borrowed') approvedRequests++;
        else if (status === 'Returned') returnedRequests++;
    });
    
    // Update statistics cards
    const totalCard = document.querySelector('.card.border-primary .fw-bold');
    const pendingCard = document.querySelector('.card.border-warning .fw-bold');
    const approvedCard = document.querySelector('.card.border-danger .fw-bold');
    const returnedCard = document.querySelector('.card.border-success .fw-bold');
    
    if (totalCard) totalCard.textContent = totalRequests;
    if (pendingCard) pendingCard.textContent = pendingRequests;
    if (approvedCard) approvedCard.textContent = approvedRequests;
    if (returnedCard) returnedCard.textContent = returnedRequests;
    
    // Update badges in header
    const pendingBadge = document.querySelector('.card-header .badge.bg-warning');
    const approvedBadge = document.querySelector('.card-header .badge.bg-danger');
    const returnedBadge = document.querySelector('.card-header .badge.bg-success');
    
    if (pendingBadge) pendingBadge.textContent = `${pendingRequests} Pending`;
    if (approvedBadge) approvedBadge.textContent = `${approvedRequests} Borrowed`;
    if (returnedBadge) returnedBadge.textContent = `${returnedRequests} Returned`;
}

function showNewRequestNotification(newCount) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="bi bi-bell-fill me-2"></i>
        <strong>New Request${newCount > 1 ? 's' : ''}!</strong> 
        ${newCount} new borrow request${newCount > 1 ? 's' : ''} received. 
        Page will refresh automatically.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Print functionality for borrow requests
function printBorrowRequest(requestId) {
    // Find the request data from the table
    const row = document.querySelector(`tr[data-request-id="${requestId}"]`);
    if (!row) {
        alert('Request data not found');
        return;
    }
    
    // Extract data from data attributes
    const requestIdText = row.getAttribute('data-request-id');
    const borrowerName = row.getAttribute('data-borrower-name');
    const borrowerEmail = row.getAttribute('data-borrower-email');
    const dateRequested = row.getAttribute('data-date-requested');
    const timeRequested = row.getAttribute('data-time-requested');
    const status = row.getAttribute('data-status');
    const purpose = row.getAttribute('data-purpose');
    const equipment = row.getAttribute('data-equipment');
    const quantities = row.getAttribute('data-quantities');
    const borrowStart = row.getAttribute('data-borrow-start');
    const borrowStartTime = row.getAttribute('data-borrow-start-time');
    const expectedReturn = row.getAttribute('data-expected-return');
    const expectedReturnTime = row.getAttribute('data-expected-return-time');
    const locationOfUse = row.getAttribute('data-location-of-use');
    const releasedBy = row.getAttribute('data-released-by');
    const borrowerType = row.getAttribute('data-borrower-type');
        
        // Create print window content matching the form layout
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Borrower's Slip - ${requestIdText}</title>
                <style>
                    @media print {
                        body { 
                            margin: 0; 
                            padding: 20px; 
                            font-family: Arial, sans-serif; 
                            background: #fff;
                        }
                        .slip-box { 
                            max-width: 900px; 
                            margin: 0 auto; 
                            background: #fff; 
                            border: 2px solid #333; 
                            padding: 32px 40px; 
                            border-radius: 12px; 
                            box-shadow: 0 0 12px #eee; 
                        }
                        .slip-header { 
                            border-bottom: 2px solid #333; 
                            margin-bottom: 16px; 
                            padding-bottom: 8px; 
                            display: flex;
                            align-items: center;
                        }
                        .slip-title { 
                            font-size: 1.5rem; 
                            font-weight: bold; 
                            text-align: center;
                        }
                        .document-control { 
                            border: 2px solid #333; 
                            border-radius: 6px; 
                            padding: 6px 8px; 
                            font-size: 0.75em; 
                            background: #f8f9fa; 
                            min-width: 180px; 
                            white-space: nowrap;
                        }
                        .document-control table { 
                            width: 100%; 
                            font-size: inherit; 
                        }
                        .document-control td { 
                            padding: 2px 4px; 
                        }
                        .form-row { 
                            display: flex; 
                            margin-bottom: 16px; 
                            gap: 16px; 
                        }
                        .form-field { 
                            flex: 1; 
                        }
                        .form-label { 
                            font-weight: bold; 
                            margin-bottom: 4px; 
                            display: block; 
                        }
                        .form-value { 
                            border: 1px solid #ccc; 
                            padding: 8px; 
                            background: #f9f9f9; 
                            min-height: 20px; 
                        }
                        .form-value.full-width { 
                            width: 100%; 
                        }
                        .note { 
                            font-size: 0.95em; 
                            margin-top: 12px; 
                            border: 1px solid #333; 
                            padding: 16px; 
                            background: #f8f9fa; 
                        }
                        .stamp-box { 
                            width: 170px; 
                            height: 130px; 
                            border: 2px solid #222; 
                            border-radius: 6px; 
                            background: #fff; 
                            display: flex; 
                            flex-direction: column; 
                            align-items: center; 
                            justify-content: flex-start; 
                            position: relative; 
                            padding-top: 8px; 
                        }
                        .stamp-status-label { 
                            font-weight: bold; 
                            font-size: 1.1em; 
                            letter-spacing: 1px; 
                            text-align: center; 
                            width: 100%; 
                            border-bottom: 2px solid #222; 
                            padding-bottom: 2px; 
                            margin-bottom: 6px; 
                        }
                        .no-print { display: none; }
                        .print-button { position: fixed; top: 20px; right: 20px; z-index: 1000; }
                    }
                </style>
            </head>
            <body>
                <button class="print-button no-print" onclick="window.print()">Print</button>
                
                <div class="slip-box">
                    <div class="slip-header">
                        <div style="flex: 0 0 15%;">
                            <img src="../assets/images/chmsu_logo.jpg" alt="CHMSU Logo" style="height: 60px;">
                        </div>
                        <div style="flex: 0 0 55%; text-align: center;">
                            <div class="slip-title">BORROWER'S SLIP</div>
                        </div>
                        <div style="flex: 0 0 30%; text-align: right;">
                            <div class="document-control">
                                <table>
                                    <tr>
                                        <td style="font-weight:bold; padding-right:4px;">Document Code:</td>
                                        <td style="text-align:right;">F.01-BSIS-TAL</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:bold; padding-right:4px;">Revision No.:</td>
                                        <td style="text-align:right;">0</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:bold; padding-right:4px;">Effective Date:</td>
                                        <td style="text-align:right;">May 27, 2024</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:bold; padding-right:4px;">Page:</td>
                                        <td style="text-align:right;">1 of 1</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-bottom: 16px; font-weight: bold;">BSIS LABORATORY COPY</div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <div class="form-label">Control No.</div>
                            <div class="form-value">${requestIdText}</div>
                        </div>
                        <div class="form-field">
                            <div class="form-label">Date and Time of Request</div>
                            <div class="form-value">${dateRequested} at ${timeRequested}</div>
                        </div>
                        <div class="form-field">
                            <div class="form-label">Status</div>
                            <div class="form-value">${status}</div>
                        </div>
                    </div>
                    
                                         <div class="form-row">
                         <div class="form-field">
                             <div class="form-label">Contact Person</div>
                             <div class="form-value">${borrowerName}</div>
                         </div>
                         <div class="form-field">
                             <div class="form-label">Email Address</div>
                             <div class="form-value">${borrowerEmail}</div>
                         </div>
                     </div>
                     
                     <div class="form-row">
                         <div class="form-field">
                             <div class="form-label">Borrow Start Date/Time</div>
                             <div class="form-value">${borrowStart} ${borrowStartTime}</div>
                         </div>
                         <div class="form-field">
                             <div class="form-label">Expected Return Date/Time</div>
                             <div class="form-value">${expectedReturn} ${expectedReturnTime}</div>
                         </div>
                     </div>
                     
                     <div class="form-row">
                         <div class="form-field">
                             <div class="form-label">Purpose</div>
                             <div class="form-value">${purpose}</div>
                         </div>
                         <div class="form-field">
                             <div class="form-label">Location of Use</div>
                             <div class="form-value">${locationOfUse}</div>
                         </div>
                     </div>
                     
                     <div class="form-row">
                         <div class="form-field">
                             <div class="form-label">Released By</div>
                             <div class="form-value">${releasedBy}</div>
                         </div>
                         <div class="form-field">
                             <div class="form-label">Borrower Type</div>
                             <div class="form-value">${borrowerType}</div>
                         </div>
                     </div>
                    
                                                              <hr style="margin: 24px 0; border: 1px solid #ccc;">
                     
                     <div style="margin-bottom: 24px;">
                         <h5 style="margin-bottom: 16px;">Items Borrowed</h5>
                         <div class="form-value full-width">${equipment} - Quantity: ${quantities}</div>
                     </div>
                     
                     <div style="display: flex; gap: 16px; margin-bottom: 24px;">
                        <div style="flex: 1;">
                            <div class="note">
                                <strong>Note:</strong> Received tools and equipment in good conditions. In the event that the borrower will lose or break the items the borrower will replace the items immediately.<br>
                                Accomplished in duplicate copy.
                            </div>
                        </div>
                        <div style="flex: 0 0 auto;">
                            <div class="stamp-box">
                                <div class="stamp-status-label">STATUS</div>
                                <!-- Empty area for staff to stamp -->
                            </div>
                        </div>
                    </div>
                </div>
            </body>
            </html>
        `;
        
        // Open print window
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write(printContent);
        printWindow.document.close();
        
        // Auto-print after content loads
        printWindow.onload = function() {
            printWindow.print();
        };
    }
</script>
</body>
</html>
