<?php
require_once '../includes/auth.php';
require_role(['Lab Admin', 'Student Assistant']);
require_once '../classes/LabReservation.php';
require_once '../classes/Lab.php';

$reservationObj = new LabReservation();
$labObj = new Lab();

// User role validation
$role = $_SESSION['role']; // Assume `role` is stored in the session, e.g., 'lab_assistant' or 'user'

// Handle reservation creation (for regular users)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_reservation'])) {
    try {
        $reservationObj->createReservation($_POST, $_SESSION['user_id']);
        $_SESSION['flash'] = ['success', 'Reservation successfully submitted!'];
        header("Location: reservations.php");
        exit;
    } catch (Exception $e) {
        // What: Error during reservation creation
        // Why: DB error, validation error, etc.
        // How: Log error and show user-friendly message
        error_log('[Reservations Create Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        $_SESSION['flash'] = ['danger', 'Error: ' . $e->getMessage()];
        header("Location: reservations.php");
        exit;
    }
}

// Handle approve or deny actions for reservations (for lab assistants)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $reservationId = $_POST['reservation_id'];
    $action = $_POST['action'];

    if ($role === 'lab_assistant') {
        try {
            if ($action === 'approve') {
                $reservationObj->updateReservationStatus($reservationId, 'Approved');
                $_SESSION['flash'] = ['success', 'Reservation approved successfully!'];
            } elseif ($action === 'deny') {
                $reservationObj->updateReservationStatus($reservationId, 'Denied');
                $_SESSION['flash'] = ['success', 'Reservation denied successfully!'];
            } else {
                throw new Exception("Invalid action.");
            }
        } catch (Exception $e) {
            // What: Error during reservation status update
            // Why: DB error, validation error, etc.
            // How: Log error and show user-friendly message
            error_log('[Reservations Status Update Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            $_SESSION['flash'] = ['danger', $e->getMessage()];
        }
    } else {
        $_SESSION['flash'] = ['danger', 'You are not authorized to perform this action.'];
    }

    header("Location: reservations.php");
    exit;
}

// Regular user-specific data
$userReservations = $reservationObj->getUserReservations($_SESSION['user_id']);

// Lab assistant-specific data
$pendingReservations = [];
if ($role === 'lab_assistant') {
    $pendingReservations = $reservationObj->getPendingReservations();
}

// Get list of laboratories for making reservations
$labs = $labObj->getAllLabs();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Reservations - CLRMS</title>

    <!-- Correct Bootstrap and CSS Links -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet"> <!-- Ensures consistent sidebar styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Sidebar -->
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <!-- Flash Messages -->
        <?php show_flash(); ?>

        <!-- Page Heading -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 text-primary mb-0"><i class="bi bi-calendar-event"></i> Lab Reservations</h1>
        </div>

        <!-- Pending Reservations for Lab Assistants -->
        <?php if ($role === 'lab_assistant'): ?>
            <h2 class="h5 text-primary mb-3"><i class="bi bi-list-check"></i> Pending Reservations</h2>
            <div class="card shadow-sm mb-4">
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle text-center">
                        <thead class="table-dark">
                        <tr>
                            <th>Laboratory</th>
                            <th>Date of Request</th>
                            <th>Date & Time Needed</th>
                            <th>Purpose</th>
                            <th>Tools & Equipment</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($pendingReservations)): ?>
                            <?php foreach ($pendingReservations as $res): ?>
                                <tr>
                                    <td><?= htmlspecialchars($res['lab_name']) ?></td>
                                    <td><?= htmlspecialchars($res['date_requested']) ?></td>
                                    <td><?= htmlspecialchars($res['datetime_needed']) ?></td>
                                    <td><?= htmlspecialchars($res['purpose']) ?></td>
                                    <td><?= htmlspecialchars($res['tools_needed']) ?>, <?= htmlspecialchars($res['equipment_needed']) ?>, <?= htmlspecialchars($res['software_needed']) ?></td>
                                    <td>
                                        <form method="POST" action="reservations.php" class="d-inline">
                                            <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                                <i class="bi bi-check-circle"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="reservations.php" class="d-inline">
                                            <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                            <button type="submit" name="action" value="deny" class="btn btn-danger btn-sm">
                                                <i class="bi bi-x-circle"></i> Deny
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No pending reservations found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Make a Reservation -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <strong>Make a Reservation</strong>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="create_reservation" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="lab_id" class="form-label">Choose Laboratory</label>
                            <select name="lab_id" id="lab_id" class="form-select" required>
                                <option value="" disabled selected>Select a laboratory</option>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?= $lab['id'] ?>">
                                        <?= htmlspecialchars($lab['lab_name']) ?> (<?= htmlspecialchars($lab['location']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="date_reserved" class="form-label">Date</label>
                            <input type="date" name="date_reserved" id="date_reserved" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="time_start" class="form-label">Start Time</label>
                            <input type="time" name="time_start" id="time_start" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="time_end" class="form-label">End Time</label>
                            <input type="time" name="time_end" id="time_end" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label for="purpose" class="form-label">Purpose</label>
                            <input type="text" name="purpose" id="purpose" class="form-control" placeholder="E.g., Research, Project Meeting" required>
                        </div>
                        <div class="col-12">
                            <label for="tools_needed" class="form-label">Tools & Equipment Needed</label>
                            <input type="text" name="tools_needed" id="tools_needed" class="form-control" placeholder="E.g., Computers, Whiteboards" required>
                        </div>
                        <div class="col-12">
                            <label for="software_needed" class="form-label">Software Needed</label>
                            <input type="text" name="software_needed" id="software_needed" class="form-control" placeholder="E.g., MS Office, MATLAB" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send-check"></i> Submit Reservation
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reservations Table -->
        <h2 class="h5 text-primary mb-3"><i class="bi bi-list-check"></i> Your Reservations</h2>
        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead class="table-dark">
                    <tr>
                        <th>Laboratory</th>
                        <th>Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Purpose</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($userReservations)): ?>
                        <?php foreach ($userReservations as $res): ?>
                            <tr>
                                <td><?= htmlspecialchars($res['lab_name']) ?></td>
                                <td><?= htmlspecialchars($res['date_reserved']) ?></td>
                                <td><?= htmlspecialchars($res['time_start']) ?></td>
                                <td><?= htmlspecialchars($res['time_end']) ?></td>
                                <td><?= htmlspecialchars($res['purpose']) ?></td>
                                <td>
                                    <?php
                                    $statusClass = match ($res['status']) {
                                        'Approved' => 'success',
                                        'Denied' => 'danger',
                                        default => 'warning text-dark'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($res['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No reservations found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
