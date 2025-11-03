<?php
require_once '../includes/auth.php';
require_once '../classes/BorrowRequest.php';
require_once '../classes/Database.php';
require_once __DIR__ . '/../includes/send_mail.php';

$borrowObj = new BorrowRequest();
$pdo = (new Database())->getConnection();

// Approve or Reject request if action is passed
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    try {
        if ($action == 'approve') {
            $borrowObj->updateStatus($id, 'Approved');
            
            // Mark equipment as Borrowed
            $stmt = $pdo->prepare("SELECT equipment_id FROM borrow_request_items WHERE request_id = ?");
            $stmt->execute([$id]);
            $equipIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($equipIds) {
                $in = implode(',', array_fill(0, count($equipIds), '?'));
                $stmt = $pdo->prepare("UPDATE equipment SET status = 'Borrowed' WHERE id IN ($in)");
                $stmt->execute($equipIds);
            }
            
            // Email notification for approval
            $stmt = $pdo->prepare("SELECT borrower_email, borrower_name, tracking_code FROM borrow_requests WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['borrower_email']) {
                $to = $row['borrower_email'];
                $subject = "Your Borrow Request Has Been Approved";
                $message = "Hello {$row['borrower_name']},\n\nYour borrow request has been APPROVED.\n\nTracking Code: {$row['tracking_code']}\n\nYou can track your request status at: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/../pages/borrowers_portal.php\n\nThank you.";
                sendSMTPMail($to, $subject, $message);
            }
            
            set_flash('success', 'Request approved successfully.');
            
        } elseif ($action == 'reject') {
            $borrowObj->updateStatus($id, 'Rejected');
            
            // Email notification for rejection
            $stmt = $pdo->prepare("SELECT borrower_email, borrower_name, tracking_code FROM borrow_requests WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['borrower_email']) {
                $to = $row['borrower_email'];
                $subject = "Your Borrow Request Has Been Rejected";
                $message = "Hello {$row['borrower_name']},\n\nYour borrow request has been REJECTED.\n\nTracking Code: {$row['tracking_code']}\n\nYou can track your request status at: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/../pages/borrowers_portal.php\n\nThank you.";
                sendSMTPMail($to, $subject, $message);
            }
            
            set_flash('danger', 'Request rejected successfully.');
        }
    } catch (Exception $e) {
        error_log('[Borrow Approval Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
    }

    header("Location: borrow_approval.php");
    exit;
}

// Get all borrow requests (pending ones first)
$allRequests = $borrowObj->getAllBorrowRequests();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrow Approvals - CLRMS</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>
        <h1 class="mb-4">Borrow Requests Approval</h1>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
            <tr>
                <th>Borrower</th>
                <th>Equipment</th>
                <th>Purpose</th>
                <th>Location</th>
                <th>Borrow Date</th>
                <th>Return Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($allRequests as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['borrower_name']) ?></td>
                    <td><?= htmlspecialchars($req['equipment_names']) ?></td>
                    <td><?= htmlspecialchars($req['purpose'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($req['location_of_use'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($req['borrow_start'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($req['borrow_end'] ?? 'N/A') ?></td>
                    <td>
                        <?php if ($req['status'] == 'Pending'): ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php elseif ($req['status'] == 'Approved'): ?>
                            <span class="badge bg-success">Approved</span>
                        <?php elseif ($req['status'] == 'Rejected'): ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($req['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($req['status'] == 'Pending'): ?>
                            <a href="borrow_approval.php?action=approve&id=<?= $req['id'] ?>"
                               class="btn btn-sm btn-success">Approve</a>
                            <a href="borrow_approval.php?action=reject&id=<?= $req['id'] ?>"
                               class="btn btn-sm btn-danger">Reject</a>
                        <?php else: ?>
                            <span class="text-muted">No Action</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</main>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
