<?php
require_once '../includes/auth.php';
require_once '../classes/EquipmentService.php';
require_once '../classes/BorrowRequestService.php';
require_once '../classes/LabReservationService.php';
require_once '../classes/SoftwareService.php';
require_once '../classes/MaintenanceService.php';
require_once '../classes/ICTSupport.php';

$equipmentService = new EquipmentService();
$borrowRequestService = new BorrowRequestService();
$labReservationService = new LabReservationService();
$softwareService = new SoftwareService();
$maintenanceService = new MaintenanceService();
$ictSupport = new ICTSupport();

try {
    /**
     * What: Fetch all equipment
     * Why: For equipment report
     * How: Uses EquipmentService::getAllEquipment
     */
    $equipmentList = $equipmentService->getAllEquipment();
} catch (Exception $e) {
    error_log('[Reports Equipment Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $equipmentList = [];
}

try {
    /**
     * What: Fetch all borrowed equipment
     * Why: For borrowed equipment report
     * How: Uses BorrowRequestService::getAllBorrowRequests
     */
    $borrowedList = $borrowRequestService->getAllBorrowRequests();
} catch (Exception $e) {
    error_log('[Reports Borrowed Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $borrowedList = [];
}

try {
    /**
     * What: Fetch all lab reservations
     * Why: For lab reservation report
     * How: Uses LabReservationService::getAllReservations
     */
    $labReservations = $labReservationService->getAllReservations();
} catch (Exception $e) {
    error_log('[Reports Lab Reservations Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $labReservations = [];
}

try {
    /**
     * What: Fetch all software
     * Why: For software license report
     * How: Uses SoftwareService::getAllSoftware
     */
    $softwareList = $softwareService->getAllSoftware();
} catch (Exception $e) {
    error_log('[Reports Software Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $softwareList = [];
}

try {
    /**
     * What: Fetch all maintenance records
     * Why: For maintenance/repair report
     * How: Uses MaintenanceService::getAllMaintenance
     */
    $maintenanceList = $maintenanceService->getAllMaintenance();
} catch (Exception $e) {
    error_log('[Reports Maintenance Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $maintenanceList = [];
}

try {
    /**
     * What: Fetch all job accomplishment requests
     * Why: For job accomplishment report
     * How: Uses ICTSupport::getAllRequests
     */
    $jobAccomplishments = $ictSupport->getAllRequests();
} catch (Exception $e) {
    error_log('[Reports Job Accomplishment Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $jobAccomplishments = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/reports.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <h1 class="mb-4">Reports</h1>

        <!-- Equipment Report -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                Equipment Inventory Report
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Serial No.</th>
                        <th>Model</th>
                        <th>Status</th>
                        <th>Location</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($equipmentList as $eq): ?>
                        <tr>
                            <td><?= htmlspecialchars($eq['id']) ?></td>
                            <td><?= htmlspecialchars($eq['name']) ?></td>
                            <td><?= htmlspecialchars($eq['serial_number']) ?></td>
                            <td><?= htmlspecialchars($eq['model']) ?></td>
                            <td><?= htmlspecialchars($eq['status']) ?></td>
                            <td><?= htmlspecialchars($eq['location']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="#" class="btn btn-success mb-2 disabled">
                    <i class="bi bi-download"></i> Export Equipment PDF (coming soon)
                </a>
            </div>
        </div>

        <!-- Borrowed Equipment Report -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                Borrowed Equipment Report
            </div>
            <div class="card-body">
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
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($borrowedList as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['borrower_name']) ?></td>
                            <td><?= htmlspecialchars($b['equipment_names']) ?></td>
                            <td><?= htmlspecialchars($b['purpose'] ?? '') ?></td>
                            <td><?= htmlspecialchars($b['location_of_use'] ?? '') ?></td>
                            <td><?= htmlspecialchars($b['date_requested']) ?></td>
                            <td><?= htmlspecialchars($b['return_date']) ?></td>
                            <td><?= htmlspecialchars($b['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="#" class="btn btn-success mb-2 disabled">
                    <i class="bi bi-download"></i> Export Borrowed PDF (coming soon)
                </a>
            </div>
        </div>

        <!-- Lab Reservation Report -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                Lab Reservations Report
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th>Requester</th>
                        <th>Lab</th>
                        <th>Date Reserved</th>
                        <th>Time Start</th>
                        <th>Time End</th>
                        <th>Purpose</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($labReservations as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['requested_by'] ?? $r['username'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['lab_name']) ?></td>
                            <td><?= htmlspecialchars($r['date_reserved'] ?? $r['date_requested'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['time_start'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['time_end'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['purpose']) ?></td>
                            <td><?= htmlspecialchars($r['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="#" class="btn btn-success mb-2 disabled">
                    <i class="bi bi-download"></i> Export Reservations PDF (coming soon)
                </a>
            </div>
        </div>

        <!-- Software License Report -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                Software License Report
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>License Expiry Date</th>
                        <th>Notes</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($softwareList as $sw): ?>
                        <tr>
                            <td><?= htmlspecialchars($sw['name']) ?></td>
                            <td><?= htmlspecialchars($sw['license_expiry_date']) ?></td>
                            <td><?= htmlspecialchars($sw['notes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="#" class="btn btn-success mb-2 disabled">
                    <i class="bi bi-download"></i> Export Software PDF (coming soon)
                </a>
            </div>
        </div>

        <!-- Maintenance/Repair Report -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                Maintenance/Repair Report
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th>Type</th>
                        <th>Equipment</th>
                        <th>Issue</th>
                        <th>Maintenance Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($maintenanceList as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['type']) ?></td>
                            <td><?= htmlspecialchars($m['equipment_name']) ?></td>
                            <td><?= htmlspecialchars($m['issue_description']) ?></td>
                            <td><?= htmlspecialchars($m['maintenance_date']) ?></td>
                            <td><?= htmlspecialchars($m['due_date']) ?></td>
                            <td><?= htmlspecialchars($m['repair_status']) ?></td>
                            <td><?= htmlspecialchars($m['notes']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="#" class="btn btn-success mb-2 disabled">
                    <i class="bi bi-download"></i> Export Maintenance PDF (coming soon)
                </a>
            </div>
        </div>

        <!-- Service History Report -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                Service History Report
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th>Equipment</th>
                        <th>Type</th>
                        <th>Issue</th>
                        <th>Maintenance Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Photo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Desktop Computer</td>
                        <td>Repair</td>
                        <td>No power</td>
                        <td>2024-05-15</td>
                        <td>2024-05-20</td>
                        <td>Completed</td>
                        <td>Replaced power supply</td>
                        <td><a href="#" class="btn btn-link btn-sm disabled">View</a></td>
                    </tr>
                    </tbody>
                </table>
                <a href="#" class="btn btn-success mb-2 disabled">
                    <i class="bi bi-download"></i> Export Service History PDF (coming soon)
                </a>
            </div>
        </div>

        <!-- Job Accomplishment Report -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                Job Accomplishment Report
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th>Completed by</th>
                        <th>Date Completed</th>
                        <th>Concern/Diagnose</th>
                        <th>Work Performed</th>
                        <th>Recommendation</th>
                        <th>Status</th>
                        <th>Noted by</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($jobAccomplishments as $job): ?>
                        <tr>
                            <td><?= htmlspecialchars($job['requester_name']) ?></td>
                            <td><?= htmlspecialchars($job['request_date']) ?></td>
                            <td><?= htmlspecialchars($job['nature_of_request']) ?></td>
                            <td><?= htmlspecialchars($job['action_taken']) ?></td>
                            <td><?= htmlspecialchars($job['recommendation'] ?? '') ?></td>
                            <td><?= htmlspecialchars($job['status'] ?? '') ?></td>
                            <td><?= htmlspecialchars($job['noted_by'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/reports.js"></script>
</body>
</html>
