<?php

require_once '../includes/auth.php';
require_role(['Lab Admin', 'Chairperson']);
require_once '../classes/LabReservationService.php';

$labReservationService = new LabReservationService();

// Handle Approve/Deny actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $id = isset($_POST['reservation_id']) ? (int)$_POST['reservation_id'] : 0;
        if ($id <= 0) {
            // Log and abort gracefully if missing ID
            error_log('[Manage Lab Reservations] Missing reservation_id on POST for action=' . $_POST['action'] . "\n", 3, __DIR__ . '/../logs/error.log');
            $_SESSION["flash"][] = ['type' => 'danger', 'msg' => 'Unable to process: missing reservation ID. Please try again.'];
            header('Location: manage_lab_reservations.php');
            exit;
        }
        if ($_POST['action'] === 'approve') {
            /**
             * What: Approve lab reservation
             * Why: For admin approval
             * How: Uses LabReservationService::approveReservation
             */
            $labReservationService->approveReservation($id);
            // Email notification
            require_once __DIR__ . '/../includes/send_mail.php';
            require_once '../classes/Database.php';
            $pdo = (new Database())->getConnection();
            $stmt = $pdo->prepare("SELECT borrower_email, requested_by, tracking_code FROM lab_reservations WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['borrower_email']) {
                $to = $row['borrower_email'];
                $subject = "Your Lab Reservation Has Been Approved";
                $message = "Hello {$row['requested_by']},\n\nYour lab reservation has been APPROVED.\n\nTracking Code: {$row['tracking_code']}\n\nYou can track your reservation status at: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/../pages/borrowers_portal.php\n\nThank you.";
                sendSMTPMail($to, $subject, $message);
            }
            $_SESSION['flash'][] = ['type' => 'success', 'msg' => 'Reservation approved.'];
        } elseif ($_POST['action'] === 'deny') {
            /**
             * What: Deny lab reservation
             * Why: For admin denial
             * How: Uses LabReservationService::denyReservation
             */
            $remarks = trim($_POST['remarks'] ?? '');
            // Primary update via service (sets status to 'Rejected')
            $labReservationService->denyReservation($id, $remarks);

            // Fail-safe: ensure exactly this row is updated to Rejected (matching DB enum)
            require_once '../classes/Database.php';
            $pdo = (new Database())->getConnection();
            $stmt = $pdo->prepare("UPDATE lab_reservations SET status = 'Rejected', remarks = COALESCE(NULLIF(?, ''), remarks) WHERE id = ?");
            $stmt->execute([$remarks, $id]);

            // Optional: verify and log
            try {
                $verify = $pdo->prepare("SELECT status FROM lab_reservations WHERE id = ?");
                $verify->execute([$id]);
                $st = $verify->fetchColumn();
                if ($st !== 'Rejected') {
                    error_log('[Manage Lab Reservations] Warning: status not Rejected after update for id=' . $id . " got=" . print_r($st, true) . "\n", 3, __DIR__ . '/../logs/error.log');
                }
            } catch (Exception $e2) {
                error_log('[Manage Lab Reservations Verify Error] ' . $e2->getMessage() . "\n", 3, __DIR__ . '/../logs/error.log');
            }
            // Email notification
            require_once __DIR__ . '/../includes/send_mail.php';
            $stmt = $pdo->prepare("SELECT borrower_email, requested_by, tracking_code FROM lab_reservations WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['borrower_email']) {
                $to = $row['borrower_email'];
                $subject = "Your Lab Reservation Has Been Denied";
                $message = "Hello {$row['requested_by']},\n\nYour lab reservation has been DENIED.\n\nTracking Code: {$row['tracking_code']}\n\nYou can track your reservation status at: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/../pages/borrowers_portal.php\n\nThank you.";
                sendSMTPMail($to, $subject, $message);
            }
            $_SESSION['flash'][] = ['type' => 'danger', 'msg' => 'Reservation denied.'];
        }
        header('Location: manage_lab_reservations.php');
        exit;
    } catch (Exception $e) {
        // What: Error during lab reservation status update
        // Why: DB error, validation error, etc.
        // How: Log error and show user-friendly message
        error_log('[Manage Lab Reservations Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        $_SESSION['flash'][] = ['type' => 'danger', 'msg' => 'Error: ' . $e->getMessage()];
        header('Location: manage_lab_reservations.php');
        exit;
    }
}

// Fetch all reservations with user and lab info
try {
    /**
     * What: Fetch all lab reservations
     * Why: For listing in the table
     * How: Uses LabReservationService::getAllReservations
     */
    $reservations = $labReservationService->getAllReservations();
} catch (Exception $e) {
    error_log('[Lab Reservation List Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $reservations = [];
}

// Data consistency repair: if a reservation has non-empty remarks but non-denied status, set to Denied
try {
    require_once '../classes/Database.php';
    $pdoFix = (new Database())->getConnection();
    $pdoFix->exec("UPDATE lab_reservations 
                   SET status = 'Denied' 
                   WHERE (status IS NULL OR status = '' OR status = 'Pending') 
                     AND remarks IS NOT NULL AND TRIM(remarks) <> ''");
} catch (Exception $e) {
    error_log('[Lab Reservation Consistency Repair] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
}

// Calculate statistics with normalized statuses (map 'Rejected' -> 'Denied', empty -> 'Pending')
$normalizeStatus = function($s) {
    $s = trim($s ?? '');
    if ($s === '') { $s = 'Pending'; }
    if ($s === 'Rejected') { $s = 'Denied'; }
    return $s;
};

$totalReservations = count($reservations);
$pendingReservations = count(array_filter($reservations, function($r) use ($normalizeStatus) { return $normalizeStatus($r['status'] ?? '') === 'Pending'; }));
$approvedReservations = count(array_filter($reservations, function($r) use ($normalizeStatus) { return $normalizeStatus($r['status'] ?? '') === 'Approved'; }));
$deniedReservations = count(array_filter($reservations, function($r) use ($normalizeStatus) { return $normalizeStatus($r['status'] ?? '') === 'Denied'; }));

// Calculate current occupancy and upcoming reservations
$currentOccupancy = 0;
$upcomingReservations = 0;
$pastReservations = 0;

// Check for overlapping reservations
try {
    require_once '../classes/Database.php';
    $pdo = (new Database())->getConnection();
    
    $overlapStmt = $pdo->prepare("
        SELECT COUNT(*) as overlap_count
        FROM lab_reservations r1
        JOIN lab_reservations r2 ON r1.lab_id = r2.lab_id 
            AND r1.id != r2.id 
            AND r1.status IN ('Approved', 'Pending')
            AND r2.status IN ('Approved', 'Pending')
            AND (
                (r1.reservation_start < r2.reservation_end AND r1.reservation_end > r2.reservation_start) OR
                (r2.reservation_start < r1.reservation_end AND r2.reservation_end > r1.reservation_start)
            )
    ");
    $overlapStmt->execute();
    $totalOverlaps = $overlapStmt->fetchColumn();
} catch (Exception $e) {
    error_log('[Overlap Check Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $totalOverlaps = 0;
}

foreach ($reservations as $res) {
    if ($normalizeStatus($res['status'] ?? '') === 'Approved') {
        $now = strtotime(date('Y-m-d H:i:s'));
        $startTime = strtotime($res['reservation_start']);
        $endTime = strtotime($res['reservation_end']);
        
        if ($now >= $startTime && $now <= $endTime) {
            $currentOccupancy++;
        } elseif ($startTime > $now) {
            $upcomingReservations++;
        } else {
            $pastReservations++;
        }
    }
}

// Get filter parameter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Filter reservations if status filter is applied (using normalized statuses)
if ($statusFilter && $statusFilter !== 'all') {
    $reservations = array_filter($reservations, function($r) use ($statusFilter, $normalizeStatus) {
        return $normalizeStatus($r['status'] ?? '') === $statusFilter;
    });
}

// Helper: Check if a reservation is currently occupying the lab
function is_lab_occupied($res) {
    if ($res['status'] !== 'Approved') return false;
    $now = strtotime(date('Y-m-d H:i:s'));
    $date = $res['date_reserved'];
    $start = strtotime($date . ' ' . $res['time_start']);
    $end = strtotime($date . ' ' . $res['time_end']);
    return $now >= $start && $now <= $end;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lab Reservations - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/manage_lab_reservations.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>
        
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 text-primary mb-0"><i class="bi bi-calendar-check me-2"></i>Manage Lab Reservations</h1>
                <p class="text-muted mb-0">Review and manage laboratory reservation requests from faculty and offices/departments</p>
            </div>
        </div>

        <!-- Quick Summary -->
        <div class="alert alert-info border-0 shadow-sm mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-1 fw-bold"><i class="bi bi-info-circle me-2"></i>Quick Overview</h6>
                    <p class="mb-0 small">
                        <strong><?= $currentOccupancy ?></strong> labs are currently occupied, 
                        <strong><?= $upcomingReservations ?></strong> upcoming reservations, and 
                        <strong><?= $pendingReservations ?></strong> pending approval.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-warning text-dark"><?= $currentOccupancy ?> Currently Occupied</span>
                    <?php if ($currentOccupancy > 0): ?>
                        <br><small class="text-muted">Highlighted rows show active reservations</small>
                    <?php endif; ?>
                                         <br><small class="text-info"><i class="bi bi-calendar-range me-1"></i>Full reservation periods displayed</small>
                     <?php if ($totalOverlaps > 0): ?>
                         <br><small class="text-danger fw-bold"><i class="bi bi-exclamation-triangle me-1"></i><?= $totalOverlaps ?> overlapping reservation(s) detected</small>
                     <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-primary shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-event text-primary" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-primary fw-bold"><?= $totalReservations ?></h4>
                        <p class="mb-0 text-muted small">Total Reservations</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-warning shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-clock text-warning" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-warning fw-bold"><?= $pendingReservations ?></h4>
                        <p class="mb-0 text-muted small">Pending Review</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-success shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-success fw-bold"><?= $approvedReservations ?></h4>
                        <p class="mb-0 text-muted small">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-info shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-person-check text-info" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-info fw-bold"><?= $currentOccupancy ?></h4>
                        <p class="mb-0 text-muted small">Currently Occupied</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-secondary shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-plus text-secondary" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-secondary fw-bold"><?= $upcomingReservations ?></h4>
                        <p class="mb-0 text-muted small">Upcoming</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-3">
                <div class="card border-danger shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                        <h4 class="mt-2 text-danger fw-bold"><?= $deniedReservations ?></h4>
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
                            <i class="bi bi-funnel me-2"></i>Filter Reservations
                        </h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-primary fs-6"><?= count($reservations) ?> Results</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Status Filter</label>
                        <select class="form-select" id="statusFilter" onchange="filterByStatus(this.value)">
                            <option value="all" <?= $statusFilter === '' || $statusFilter === 'all' ? 'selected' : '' ?>>All Reservations</option>
                            <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="Denied" <?= $statusFilter === 'Denied' ? 'selected' : '' ?>>Denied</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Time Filter</label>
                        <select class="form-select" id="timeFilter" onchange="filterByTime(this.value)">
                            <option value="all">All Times</option>
                            <option value="current">Currently Occupied</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="past">Past/Completed</option>
                        </select>
                    </div>
                                         <div class="col-md-4">
                         <label class="form-label fw-bold">Search Borrower</label>
                         <input type="text" class="form-control" id="searchRequester" placeholder="Search by borrower name..." onkeyup="searchTable()">
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

        <!-- Reservations Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-primary">
                        <i class="bi bi-table me-2"></i>Lab Reservations
                    </h5>
                    <div class="d-flex gap-2">
                        <span class="badge bg-warning text-dark"><?= $pendingReservations ?> Pending</span>
                        <span class="badge bg-success"><?= $approvedReservations ?> Approved</span>
                        <span class="badge bg-danger"><?= $deniedReservations ?> Denied</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="reservationsTable">
                                                 <thead class="table-light">
                                                   <tr>
                              <th class="border-0 fw-bold">#</th>
                              <th class="border-0 fw-bold">Borrower</th>
                              <th class="border-0 fw-bold">Lab</th>
                              <th class="border-0 fw-bold">Request Date</th>
                              <th class="border-0 fw-bold">Reservation Period</th>
                              <th class="border-0 fw-bold">Purpose</th>
                              <th class="border-0 fw-bold">Status</th>
                              <th class="border-0 fw-bold">Remarks</th>
                              <th class="border-0 fw-bold">Documents</th>
                              <th class="border-0 fw-bold text-center">Actions</th>
                          </tr>
                         </thead>
                        <tbody>
                        <?php if (!empty($reservations)): ?>
                            <?php foreach ($reservations as $i => $res): ?>
                                <?php 
                                $status = trim($res['status'] ?? '');
                                if ($status === '') { $status = 'Pending'; }
                                if ($status === 'Rejected') { $status = 'Denied'; }
                                
                                // Calculate time status for visual indicators using actual reservation period
                                $now = strtotime(date('Y-m-d H:i:s'));
                                $startTime = strtotime($res['reservation_start']);
                                $endTime = strtotime($res['reservation_end']);
                                
                                // Debug: Log the times for troubleshooting
                                if ($res['id'] == 25) { // Assuming this is the problematic reservation
                                    error_log("Debug Reservation #25: Now=" . date('Y-m-d H:i:s', $now) . 
                                             ", Start=" . date('Y-m-d H:i:s', $startTime) . 
                                             ", End=" . date('Y-m-d H:i:s', $endTime) . 
                                             ", Now >= Start: " . ($now >= $startTime ? 'true' : 'false') . 
                                             ", Now <= End: " . ($now <= $endTime ? 'true' : 'false'));
                                }
                                
                                $isCurrentlyOccupied = ($status === 'Approved' && $now >= $startTime && $now <= $endTime);
                                ?>
                                <tr class="align-middle <?= $isCurrentlyOccupied ? 'table-warning' : '' ?>" data-reservation-id="<?= $res['id'] ?>" data-borrower-email="<?= htmlspecialchars($res['borrower_email'] ?? 'N/A') ?>" data-contact-person="<?= htmlspecialchars($res['contact_person'] ?? 'N/A') ?>">
                                    <td>
                                        <span class="fw-bold text-primary"><?= $i + 1 ?></span>
                                    </td>
                                                                         <td>
                                         <div class="fw-bold text-dark">
                                             <?php if ($res['borrower_name']): ?>
                                                 <?= htmlspecialchars($res['borrower_name']) ?>
                                                 <span class="badge bg-info text-white ms-1"><?= htmlspecialchars($res['borrower_type_name']) ?></span>
                                             <?php else: ?>
                                                 <?= htmlspecialchars($res['requested_by']) ?>
                                             <?php endif; ?>
                                         </div>
                                         <small class="text-muted">ID: <?= htmlspecialchars($res['id']) ?></small>
                                         <?php if ($res['contact_person']): ?>
                                             <br><small class="text-muted">Contact: <?= htmlspecialchars($res['contact_person']) ?></small>
                                         <?php endif; ?>
                                     </td>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars($res['lab_name']) ?></div>
                                        <small class="text-muted">Lab</small>
                                    </td>
                                    <td>
                                        <div class="fw-medium request-date"><?= htmlspecialchars(date("M d, Y", strtotime($res['date_reserved']))) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars(date("h:i A", strtotime($res['date_reserved']))) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-medium reservation-period">
                                            <div class="text-primary fw-bold">
                                                <i class="bi bi-calendar-range me-1"></i>
                                                <?= htmlspecialchars(date("M d, Y", strtotime($res['reservation_start']))) ?>
                                            </div>
                                            <div class="text-success">
                                                <i class="bi bi-clock me-1"></i>
                                                <?= htmlspecialchars(date("h:i A", strtotime($res['reservation_start']))) ?>
                                            </div>
                                            <div class="text-muted small">to</div>
                                            <div class="text-danger fw-bold">
                                                <i class="bi bi-calendar-x me-1"></i>
                                                <?= htmlspecialchars(date("M d, Y", strtotime($res['reservation_end']))) ?>
                                            </div>
                                            <div class="text-danger">
                                                <i class="bi bi-clock me-1"></i>
                                                <?= htmlspecialchars(date("h:i A", strtotime($res['reservation_end']))) ?>
                                            </div>
                                        </div>
                                        <small class="text-muted">Full Reservation Period</small>
                                        
                                        <?php
                                        // Check for overlapping reservations with this one
                                        try {
                                            if (!isset($pdo)) {
                                                require_once '../classes/Database.php';
                                                $pdo = (new Database())->getConnection();
                                            }
                                            
                                            $overlapStmt = $pdo->prepare("
                                                SELECT COUNT(*) as overlap_count
                                                FROM lab_reservations 
                                                WHERE lab_id = ? 
                                                AND id != ? 
                                                AND status IN ('Approved', 'Pending')
                                                AND (
                                                    (reservation_start < ? AND reservation_end > ?) OR
                                                    (reservation_start < ? AND reservation_end > ?) OR
                                                    (reservation_start >= ? AND reservation_end <= ?) OR
                                                    (reservation_start <= ? AND reservation_end >= ?)
                                                )
                                            ");
                                            $overlapStmt->execute([
                                                $res['lab_id'], $res['id'],
                                                $res['reservation_end'], $res['reservation_start'],
                                                $res['reservation_end'], $res['reservation_start'],
                                                $res['reservation_start'], $res['reservation_end'],
                                                $res['reservation_start'], $res['reservation_end']
                                            ]);
                                            $overlapCount = $overlapStmt->fetchColumn();
                                        } catch (Exception $e) {
                                            error_log('[Individual Overlap Check Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
                                            $overlapCount = 0;
                                        }
                                        
                                        if ($overlapCount > 0): ?>
                                            <br><small class="text-danger fw-bold">
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                OVERLAP DETECTED: <?= $overlapCount ?> conflicting reservation(s)
                                            </small>
                                        <?php endif; ?>
                                        
                                        <?php if ($res['id'] == 25): // Debug info for the problematic reservation ?>
                                            <br><small class="text-danger">
                                                Debug: Now=<?= date('M d, Y h:i A', $now) ?>, 
                                                Start=<?= date('M d, Y h:i A', $startTime) ?>, 
                                                End=<?= date('M d, Y h:i A', $endTime) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= htmlspecialchars($res['purpose'] ?? 'N/A') ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        // Improved status calculation with better logic
                                        $isPast = ($status === 'Approved' && $now > $endTime);
                                        $isUpcoming = ($status === 'Approved' && $now < $startTime);
                                        $isActive = ($status === 'Approved' && $now >= $startTime && $now <= $endTime);
                                        
                                        // Debug: Log status for troubleshooting
                                        if ($res['id'] == 25) { // Assuming this is the problematic reservation
                                            error_log("Debug Status #25: Status=$status, IsPast=$isPast, IsUpcoming=$isUpcoming, IsActive=$isActive, IsCurrentlyOccupied=$isCurrentlyOccupied");
                                        }
                                        ?>
                                        
                                        <?php if ($status === 'Pending'): ?>
                                            <span class="badge bg-warning text-dark fs-6">Pending</span>
                                        <?php elseif ($status === 'Approved' && $isActive): ?>
                                            <span class="badge bg-info fs-6">
                                                <i class="bi bi-person-check me-1"></i>Currently Occupied
                                            </span>
                                        <?php elseif ($status === 'Approved' && $isPast): ?>
                                            <span class="badge bg-secondary fs-6">
                                                <i class="bi bi-clock-history me-1"></i>Completed
                                            </span>
                                        <?php elseif ($status === 'Approved' && $isUpcoming): ?>
                                            <span class="badge bg-success fs-6">
                                                <i class="bi bi-calendar-check me-1"></i>Upcoming
                                            </span>
                                        <?php elseif ($status === 'Approved'): ?>
                                            <span class="badge bg-success fs-6">Approved</span>
                                        <?php elseif ($status === 'Denied'): ?>
                                            <span class="badge bg-secondary fs-6">Denied</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary fs-6"><?= htmlspecialchars($status) ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($status === 'Approved'): ?>
                                            <br><small class="text-muted">
                                                <?php if ($isActive): ?>
                                                    <i class="bi bi-exclamation-triangle text-warning"></i> Active now
                                                <?php elseif ($isPast): ?>
                                                    <i class="bi bi-check-circle text-success"></i> Past reservation
                                                <?php elseif ($isUpcoming): ?>
                                                    <i class="bi bi-arrow-up-circle text-info"></i> Future reservation
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                                                         <td>
                                         <span class="text-muted small"><?= htmlspecialchars($res['remarks'] ?? 'No remarks') ?></span>
                                     </td>
                                     <td>
                                         <div class="d-flex flex-column gap-1">
                                             <?php if (!empty($res['approved_letter'])): ?>
                                                 <a href="../<?= htmlspecialchars($res['approved_letter']) ?>" 
                                                    target="_blank" 
                                                    class="btn btn-sm btn-outline-primary">
                                                     <i class="bi bi-file-earmark-text me-1"></i>View Letter
                                                 </a>
                                             <?php else: ?>
                                                 <span class="text-muted small">No letter uploaded</span>
                                             <?php endif; ?>
                                             
                                                                                           <?php if (!empty($res['id_photo'])): ?>
                                                  <button class="btn btn-sm btn-outline-info" onclick="viewIDPhoto('<?= htmlspecialchars($res['id_photo']) ?>', '<?= htmlspecialchars($res['requested_by']) ?>')" title="View ID Photo">
                                                      <i class="bi bi-person-badge me-1"></i>View ID Photo
                                                  </button>
                                              <?php else: ?>
                                                  <span class="text-muted small">No ID photo uploaded</span>
                                              <?php endif; ?>
                                         </div>
                                     </td>
                                     <td class="text-center">
                                        <div class="d-flex flex-column gap-1 align-items-center">
                                            <!-- Print Button (Always visible) -->
                                            <button class="btn btn-outline-secondary btn-sm" onclick="printLabReservation(<?= $res['id'] ?>)" title="Print Reservation">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                            
                                            <!-- Action Buttons -->
                                            <?php if ($status === 'Pending'): ?>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-success" data-bs-toggle="modal"
                                                            data-bs-target="#approveModal"
                                                            data-id="<?= $res['id'] ?>" 
                                                            data-borrower="<?= htmlspecialchars($res['requested_by']) ?>"
                                                            title="Approve Reservation">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                    <button class="btn btn-danger" data-bs-toggle="modal"
                                                            data-bs-target="#denyModal"
                                                            data-id="<?= $res['id'] ?>" 
                                                            data-borrower="<?= htmlspecialchars($res['requested_by']) ?>"
                                                            title="Deny Reservation">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">No actions</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                                                         <tr>
                                 <td colspan="10" class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="mt-3 text-muted fw-bold">No Reservations Found</h5>
                                    <p class="text-muted">No lab reservations match your current filters.</p>
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
            <form method="post" class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="approveModalLabel">
                        <i class="bi bi-check-circle me-2"></i>Approve Lab Reservation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reservation_id" id="approveReservationId">
                    <input type="hidden" name="action" value="approve">
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>
                            <strong>Confirm Approval</strong><br>
                            <span id="approveBorrowerInfo">Are you sure you want to approve this lab reservation?</span>
                        </div>
                    </div>
                    <p class="mb-0">
                        <i class="bi bi-check-circle text-success me-2"></i> The lab will be reserved for the requested time<br>
                        <i class="bi bi-envelope text-success me-2"></i> An approval email will be sent to the requester
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Yes, Approve Reservation
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
                        <i class="bi bi-x-circle me-2"></i>Deny Lab Reservation
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reservation_id" id="denyReservationId">
                    <input type="hidden" name="action" value="deny">
                    <div class="mb-3">
                        <label for="denyRemarks" class="form-label fw-bold">Remarks (optional):</label>
                        <textarea class="form-control form-control-lg" name="remarks" id="denyRemarks" rows="3" placeholder="Provide a reason for denying this reservation..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-danger btn-lg">
                        <i class="bi bi-x-circle me-2"></i>Deny Reservation
                    </button>
                </div>
            </form>
        </div>
         </div>
 
     <!-- ID Photo Modal -->
     <div class="modal fade" id="idPhotoModal" tabindex="-1" aria-labelledby="idPhotoModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-lg">
             <div class="modal-content">
                 <div class="modal-header bg-info text-white">
                     <h5 class="modal-title fw-bold" id="idPhotoModalLabel">
                         <i class="bi bi-person-badge me-2"></i>ID Photo
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
                     <a id="downloadIDPhoto" href="" download class="btn btn-info">
                         <i class="bi bi-download me-2"></i>Download
                     </a>
                 </div>
             </div>
         </div>
     </div>
 </main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

// Filter by time (client-side)
function filterByTime(timeFilter) {
    const table = document.getElementById('reservationsTable');
    const rows = table.getElementsByTagName('tr');
    const now = new Date();
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const statusCell = row.querySelector('.badge');
        
        if (!statusCell) continue;
        
        const status = statusCell.textContent.trim();
        let showRow = true;
        
        if (timeFilter === 'current') {
            showRow = status.includes('Currently Occupied');
        } else if (timeFilter === 'upcoming') {
            showRow = status.includes('Upcoming');
        } else if (timeFilter === 'past') {
            showRow = status.includes('Completed');
        }
        
        row.style.display = showRow ? '' : 'none';
    }
    
    // Update results count
    updateResultsCount();
}

// Update results count
function updateResultsCount() {
    const table = document.getElementById('reservationsTable');
    const rows = table.getElementsByTagName('tr');
    let visibleCount = 0;
    
    for (let i = 1; i < rows.length; i++) {
        if (rows[i].style.display !== 'none') {
            visibleCount++;
        }
    }
    
    const badge = document.querySelector('.badge.bg-primary');
    if (badge) {
        badge.textContent = visibleCount + ' Results';
    }
}

// Search table
function searchTable() {
    const input = document.getElementById('searchRequester');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('reservationsTable');
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
    
    // Update results count
    updateResultsCount();
}

// Export to CSV
function exportToCSV() {
    const table = document.getElementById('reservationsTable');
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
    link.setAttribute('download', 'lab_reservations_' + new Date().toISOString().slice(0, 10) + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

 // Refresh page
 function refreshPage() {
     window.location.reload();
 }
 
 // View ID Photo function
 function viewIDPhoto(photoPath, requestedBy) {
     const modal = new bootstrap.Modal(document.getElementById('idPhotoModal'));
     const image = document.getElementById('idPhotoImage');
     const borrowerDisplay = document.getElementById('borrowerNameDisplay');
     const downloadLink = document.getElementById('downloadIDPhoto');
     
     // Set the image source
     image.src = '../' + photoPath;
     image.alt = 'ID Photo for ' + requestedBy;
     
     // Set borrower name
     borrowerDisplay.textContent = 'ID Photo for: ' + requestedBy;
     
     // Set download link
     downloadLink.href = '../' + photoPath;
     downloadLink.download = 'ID_Photo_' + requestedBy.replace(/\s+/g, '_') + '.jpg';
     
     // Show modal
     modal.show();
 }

// Set modal data
document.addEventListener('DOMContentLoaded', function() {
    // Initialize real-time updates for this page
    initializeLabReservationUpdates();
    
    // Add CSS for new reservation highlighting
    addNewReservationStyles();

    // Wire up deny modal to receive reservation id
    const denyModal = document.getElementById('denyModal');
    if (denyModal) {
        let currentDenyReservationId = '';
        denyModal.addEventListener('show.bs.modal', function (event) {
            const triggerButton = event.relatedTarget;
            const id = triggerButton ? triggerButton.getAttribute('data-id') : '';
            const input = document.getElementById('denyReservationId');
            currentDenyReservationId = id || '';
            if (input) input.value = currentDenyReservationId;
            console.log('Deny modal opened for ID:', currentDenyReservationId);
        });

        // Ensure ID is set on submit as a fallback
        const denyForm = denyModal.querySelector('form');
        if (denyForm) {
            denyForm.addEventListener('submit', function(e) {
                const input = document.getElementById('denyReservationId');
                if (input && !input.value) {
                    input.value = currentDenyReservationId;
                }
                console.log('Deny form submitted with ID:', input ? input.value : 'no input found');
            });
        }
    } else {
        console.error('Deny modal not found!');
    }
});

// Add CSS styles for new reservation highlighting
function addNewReservationStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .new-reservation-highlight {
            animation: highlightNewReservation 3s ease-out;
            background-color: #d4edda !important;
            border-left: 4px solid #28a745 !important;
        }
        
        @keyframes highlightNewReservation {
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
        
        .new-reservation-highlight td {
            border-color: #28a745 !important;
        }
    `;
    document.head.appendChild(style);
}

// Real-time updates for manage lab reservations page
function initializeLabReservationUpdates() {
    let lastCheck = new Date().toISOString().slice(0, 19).replace('T', ' ');
    let lastReservationCount = 0;
    
    // Check for new reservations every 5 seconds
    setInterval(async function() {
        try {
            const response = await fetch(`api/notifications.php?lastCheck=${encodeURIComponent(lastCheck)}`);
            const data = await response.json();
            
            if (data.success) {
                const currentPendingCount = data.counts.pendingLabReservations;
                
                // If there are new pending reservations, fetch and display them
                if (currentPendingCount > lastReservationCount && lastReservationCount > 0) {
                    showNewReservationNotification(currentPendingCount - lastReservationCount);
                    
                    // Fetch new reservations and add them to the table
                    await fetchAndDisplayNewReservations();
                }
                
                lastReservationCount = currentPendingCount;
                lastCheck = data.timestamp;
            }
        } catch (error) {
            console.error('Error checking for updates:', error);
        }
    }, 5000);
    
    // Set initial count
    const pendingBadge = document.querySelector('.badge.bg-warning');
    if (pendingBadge) {
        lastReservationCount = parseInt(pendingBadge.textContent) || 0;
    }
}

// Fetch new reservations and display them dynamically
async function fetchAndDisplayNewReservations() {
    try {
        const response = await fetch('api/get_new_reservations.php');
        const data = await response.json();
        
        if (data.success && data.reservations.length > 0) {
            const tbody = document.querySelector('#reservationsTable tbody');
            
            data.reservations.forEach((reservation, index) => {
                // Check if this reservation is already displayed
                if (!document.querySelector(`tr[data-reservation-id="${reservation.id}"]`)) {
                    const newRow = createReservationRow(reservation, tbody.children.length + 1);
                    tbody.insertBefore(newRow, tbody.firstChild);
                    
                    // Add animation class
                    newRow.classList.add('new-reservation-highlight');
                    setTimeout(() => {
                        newRow.classList.remove('new-reservation-highlight');
                    }, 3000);
                }
            });
            
            // Update statistics
            updateStatistics();
        }
    } catch (error) {
        console.error('Error fetching new reservations:', error);
    }
}

// Create a new reservation row
function createReservationRow(reservation, rowNumber) {
    const row = document.createElement('tr');
    row.className = 'align-middle';
    row.setAttribute('data-reservation-id', reservation.id);
    
    const status = reservation.status.trim();
    
    row.innerHTML = `
        <td>
            <span class="fw-bold text-primary">${rowNumber}</span>
        </td>
        <td>
            <div class="fw-bold text-dark">${escapeHtml(reservation.requested_by)}</div>
            <small class="text-muted">ID: ${escapeHtml(reservation.id)}</small>
        </td>
        <td>
            <div class="fw-medium">${escapeHtml(reservation.lab_name)}</div>
            <small class="text-muted">Lab</small>
        </td>
        <td>
            <div class="fw-medium">${formatDate(reservation.date_reserved)}</div>
            <small class="text-muted">${escapeHtml(reservation.time_start)} - ${escapeHtml(reservation.time_end)}</small>
        </td>
        <td>
            <span class="text-muted">${escapeHtml(reservation.purpose || 'N/A')}</span>
        </td>
        <td>
            ${getStatusBadge(status)}
        </td>
        <td>
            <span class="text-muted small">${escapeHtml(reservation.remarks || 'No remarks')}</span>
        </td>
        <td class="text-center">
            ${getActionButtons(reservation)}
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

function getStatusBadge(status) {
    if (status === 'Pending') {
        return '<span class="badge bg-warning text-dark fs-6">Pending</span>';
    } else if (status === 'Approved') {
        return '<span class="badge bg-success fs-6">Approved</span>';
    } else if (status === 'Denied') {
        return '<span class="badge bg-secondary fs-6">Denied</span>';
    } else {
        return `<span class="badge bg-secondary fs-6">${escapeHtml(status)}</span>`;
    }
}

function getActionButtons(reservation) {
    const status = reservation.status.trim();
    
    if (status === 'Pending') {
        return `
            <div class="btn-group btn-group-sm">
                <form method="post" class="d-inline">
                    <input type="hidden" name="reservation_id" value="${reservation.id}">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success" title="Approve Reservation">
                        <i class="bi bi-check-circle"></i>
                    </button>
                </form>
                <button class="btn btn-danger" data-bs-toggle="modal"
                        data-bs-target="#denyModal"
                        data-id="${reservation.id}" title="Deny Reservation">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        `;
    } else {
        return '<span class="text-muted small">No actions</span>';
    }
}

// Update statistics without page refresh
function updateStatistics() {
    const rows = document.querySelectorAll('#reservationsTable tbody tr');
    let totalReservations = rows.length;
    let pendingReservations = 0;
    let approvedReservations = 0;
    let deniedReservations = 0;
    
    rows.forEach(row => {
        const status = row.querySelector('.badge').textContent.trim();
        if (status === 'Pending') pendingReservations++;
        else if (status === 'Approved') approvedReservations++;
        else if (status === 'Denied') deniedReservations++;
    });
    
    // Update statistics cards
    const totalCard = document.querySelector('.card.border-primary .fw-bold');
    const pendingCard = document.querySelector('.card.border-warning .fw-bold');
    const approvedCard = document.querySelector('.card.border-success .fw-bold');
    const deniedCard = document.querySelector('.card.border-danger .fw-bold');
    
    if (totalCard) totalCard.textContent = totalReservations;
    if (pendingCard) pendingCard.textContent = pendingReservations;
    if (approvedCard) approvedCard.textContent = approvedReservations;
    if (deniedCard) deniedCard.textContent = deniedReservations;
    
    // Update badges in header
    const pendingBadge = document.querySelector('.card-header .badge.bg-warning');
    const approvedBadge = document.querySelector('.card-header .badge.bg-success');
    const deniedBadge = document.querySelector('.card-header .badge.bg-danger');
    
    if (pendingBadge) pendingBadge.textContent = `${pendingReservations} Pending`;
    if (approvedBadge) approvedBadge.textContent = `${approvedReservations} Approved`;
    if (deniedBadge) deniedBadge.textContent = `${deniedReservations} Denied`;
}

function showNewReservationNotification(newCount) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="bi bi-bell-fill me-2"></i>
        <strong>New Reservation${newCount > 1 ? 's' : ''}!</strong> 
        ${newCount} new lab reservation${newCount > 1 ? 's' : ''} received. 
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
</script>
<script src="../assets/js/manage_lab_reservations.js?v=<?= time() ?>"></script>

<!-- Real-time Updates Script -->
<script>
// Real-time updates for manage lab reservations page
function initializeLabReservationUpdates() {
    let lastCheck = new Date().toISOString().slice(0, 19).replace('T', ' ');
    let lastReservationCount = 0;
    
    // Check for new reservations every 5 seconds
    setInterval(async function() {
        try {
            const response = await fetch(`api/notifications.php?lastCheck=${encodeURIComponent(lastCheck)}`);
            const data = await response.json();
            
            if (data.success) {
                const currentPendingCount = data.counts.pendingLabReservations;
                
                // If there are new pending reservations, fetch and display them
                if (currentPendingCount > lastReservationCount && lastReservationCount > 0) {
                    showNewReservationNotification(currentPendingCount - lastReservationCount);
                    
                    // Fetch new reservations and add them to the table
                    await fetchAndDisplayNewReservations();
                }
                
                lastReservationCount = currentPendingCount;
                lastCheck = data.timestamp;
            }
        } catch (error) {
            console.error('Error checking for updates:', error);
        }
    }, 5000);
    
    // Set initial count
    const pendingReservations = document.querySelectorAll('.badge.bg-warning');
    if (pendingReservations.length > 0) {
        lastReservationCount = pendingReservations.length;
    }
}

// Fetch new reservations and display them dynamically
async function fetchAndDisplayNewReservations() {
    try {
        const response = await fetch('api/get_new_reservations.php');
        const data = await response.json();
        
        if (data.success && data.reservations.length > 0) {
            const tbody = document.querySelector('table tbody');
            
            data.reservations.forEach((reservation, index) => {
                // Check if this reservation is already displayed
                if (!document.querySelector(`tr[data-reservation-id="${reservation.id}"]`)) {
                    const newRow = createReservationRow(reservation, tbody.children.length + 1);
                    tbody.insertBefore(newRow, tbody.firstChild);
                    
                    // Add animation class
                    newRow.classList.add('new-reservation-highlight');
                    setTimeout(() => {
                        newRow.classList.remove('new-reservation-highlight');
                    }, 3000);
                }
            });
        }
    } catch (error) {
        console.error('Error fetching new reservations:', error);
    }
}

// Create a new reservation row
function createReservationRow(reservation, rowNumber) {
    const row = document.createElement('tr');
    row.setAttribute('data-reservation-id', reservation.id);
    
    const status = reservation.status.trim();
    const isOccupied = isLabCurrentlyOccupied(reservation);
    
    row.innerHTML = `
        <td>${rowNumber}</td>
        <td>${escapeHtml(reservation.requested_by)}</td>
        <td>${escapeHtml(reservation.lab_name)}</td>
        <td>${formatDate(reservation.date_reserved)}</td>
        <td>${escapeHtml(reservation.time_start)}</td>
        <td>${escapeHtml(reservation.time_end)}</td>
        <td>${escapeHtml(reservation.purpose)}</td>
        <td>${getReservationStatusBadge(status, isOccupied)}</td>
        <td>${escapeHtml(reservation.remarks || '')}</td>
        <td class="table-actions">${getReservationActionButtons(reservation)}</td>
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

function isLabCurrentlyOccupied(reservation) {
    if (reservation.status !== 'Approved') return false;
    const now = new Date();
    const date = new Date(reservation.date_reserved);
    const start = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    start.setHours(parseInt(reservation.time_start.split(':')[0]), parseInt(reservation.time_start.split(':')[1]));
    const end = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    end.setHours(parseInt(reservation.time_end.split(':')[0]), parseInt(reservation.time_end.split(':')[1]));
    return now >= start && now <= end;
}

function getReservationStatusBadge(status, isOccupied) {
    if (status === 'Pending') {
        return '<span class="badge bg-warning text-dark status-badge">Pending</span>';
    } else if (status === 'Approved' && isOccupied) {
        return '<span class="badge bg-danger status-badge">Occupied</span>';
    } else if (status === 'Approved') {
        return '<span class="badge bg-success status-badge">Approved</span>';
    } else if (status === 'Rejected' || status === 'Denied') {
        return '<span class="badge bg-danger status-badge">Denied</span>';
    } else {
        return '<span class="badge bg-secondary status-badge">Other</span>';
    }
}

function getReservationActionButtons(reservation) {
    const status = reservation.status.trim();
    
    if (status === 'Pending' || status === '') {
        return `
            <form method="post" class="d-inline">
                <input type="hidden" name="reservation_id" value="${reservation.id}">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle"></i> Approve</button>
            </form>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#denyModal" data-id="${reservation.id}"><i class="bi bi-x-circle"></i> Deny</button>
        `;
    } else {
        return '<span class="text-muted">No actions</span>';
    }
}

function showNewReservationNotification(newCount) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="bi bi-building me-2"></i>
        <strong>New Reservation${newCount > 1 ? 's' : ''}!</strong> 
        ${newCount} new lab reservation${newCount > 1 ? 's' : ''} received. 
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

    // Print functionality for lab reservations
    function printLabReservation(reservationId) {
        // Find the reservation data from the table
        const row = document.querySelector(`tr[data-reservation-id="${reservationId}"]`);
        if (!row) {
            alert('Reservation data not found');
            return;
        }
        
        // Extract data from the row
        const borrowerName = row.querySelector('td:nth-child(2) .fw-bold').textContent.trim();
        const reservationIdText = row.querySelector('td:nth-child(2) small').textContent.replace('ID: ', '');
        const labName = row.querySelector('td:nth-child(3) .fw-medium').textContent;
        const requestDate = row.querySelector('td:nth-child(4) .fw-medium').textContent;
        const requestTime = row.querySelector('td:nth-child(4) small').textContent;
        const purpose = row.querySelector('td:nth-child(6) span').textContent;
        const status = row.querySelector('td:nth-child(7) .badge').textContent;
        
        // Extract reservation period data
        const reservationPeriod = row.querySelector('td:nth-child(5) .reservation-period');
        const startDate = reservationPeriod.querySelector('.text-primary').textContent.trim();
        const startTime = reservationPeriod.querySelector('.text-success').textContent.trim();
        const endDate = reservationPeriod.querySelector('.text-danger.fw-bold').textContent.trim();
        const endTime = reservationPeriod.querySelector('.text-danger:not(.fw-bold)').textContent.trim();
        
        // Get additional data from data attributes
        const borrowerEmail = row.getAttribute('data-borrower-email') || 'N/A';
        const contactPerson = row.getAttribute('data-contact-person') || 'N/A';
        
        // Create print window content matching the form layout
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Lab Reservation Form - ${reservationIdText}</title>
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
                        .lab-checkboxes { 
                            margin-bottom: 16px; 
                        }
                        .lab-checkboxes label { 
                            margin-right: 18px; 
                            font-weight: normal; 
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
                            <div class="slip-title">REQUEST FORM</div>
                            <div style="font-size:1.1rem;">(USE OF COMPUTER LABORATORIES)</div>
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
                    
                    <div class="lab-checkboxes">
                        <div class="form-label">Laboratory Requested:</div>
                        <div class="form-value">${labName}</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <div class="form-label">Control No.</div>
                            <div class="form-value">${reservationIdText}</div>
                        </div>
                        <div class="form-field">
                            <div class="form-label">Date and Time of Request</div>
                            <div class="form-value">${requestDate} at ${requestTime}</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <div class="form-label">Reservation Start Date/Time</div>
                            <div class="form-value">${startDate} at ${startTime}</div>
                        </div>
                        <div class="form-field">
                            <div class="form-label">Reservation End Date/Time</div>
                            <div class="form-value">${endDate} at ${endTime}</div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 16px;">
                        <div class="form-label">Purpose</div>
                        <div class="form-value">${purpose}</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <div class="form-label">Contact Person</div>
                            <div class="form-value">${contactPerson}</div>
                        </div>
                        <div class="form-field">
                            <div class="form-label">Email Address</div>
                            <div class="form-value">${borrowerEmail}</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <div class="form-label">Noted by</div>
                            <div class="form-value">BSIS LABORATORY ASSISTANT</div>
                        </div>
                        <div class="form-field">
                            <div class="form-label">Approved by</div>
                            <div class="form-value">BSIS PROGRAM CHAIRPERSON</div>
                        </div>
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

// Initialize real-time updates when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeLabReservationUpdates();
    
    // Add CSS for new reservation highlighting
    addNewReservationStyles();
});

// Add CSS styles for new reservation highlighting
function addNewReservationStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .new-reservation-highlight {
            animation: highlightNewReservation 3s ease-out;
            background-color: #d4edda !important;
            border-left: 4px solid #28a745 !important;
        }
        
        @keyframes highlightNewReservation {
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
        
        .new-reservation-highlight td {
            border-color: #28a745 !important;
        }
    `;
    document.head.appendChild(style);
}
</script>
</body>
</html> 