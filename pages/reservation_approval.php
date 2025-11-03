<?php
require_once '../includes/auth.php';
require_once '../classes/LabReservation.php';

$reservationObj = new LabReservation();

// Approve or Reject reservation
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'approve') {
        $reservationObj->updateReservationStatus($id, 'Approved');
    } elseif ($action == 'reject') {
        $reservationObj->updateReservationStatus($id, 'Rejected');
    }

    header("Location: reservation_approval.php");
    exit;
}

// Get all reservations
$allReservations = $reservationObj->getAllReservations();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Approvals - CLRMS</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>
        <h1 class="mb-4">Lab Reservation Approvals</h1>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
            <tr>
                <th>Requester</th>
                <th>Laboratory</th>
                <th>Date</th>
                <th>Time Start</th>
                <th>Time End</th>
                <th>Purpose</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($allReservations as $res): ?>
                <tr>
                    <td><?= htmlspecialchars($res['username']) ?></td>
                    <td><?= htmlspecialchars($res['lab_name']) ?></td>
                    <td><?= htmlspecialchars($res['date_reserved']) ?></td>
                    <td><?= htmlspecialchars($res['time_start']) ?></td>
                    <td><?= htmlspecialchars($res['time_end']) ?></td>
                    <td><?= htmlspecialchars($res['purpose']) ?></td>
                    <td>
                        <?php if ($res['status'] == 'Pending'): ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php elseif ($res['status'] == 'Approved'): ?>
                            <span class="badge bg-success">Approved</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($res['status'] == 'Pending'): ?>
                            <a href="reservation_approval.php?action=approve&id=<?= $res['id'] ?>"
                               class="btn btn-sm btn-success">Approve</a>
                            <a href="reservation_approval.php?action=reject&id=<?= $res['id'] ?>"
                               class="btn btn-sm btn-danger">Reject</a>
                        <?php else: ?>
                            <span>No Action</span>
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
