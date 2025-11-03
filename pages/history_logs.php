<?php

require_once '../includes/auth.php';
require_role(['Lab Admin', 'Chairperson']);

require_once '../classes/Database.php';
require_once '../classes/BorrowRequest.php';
require_once '../classes/LabReservation.php';
require_once '../classes/MaintenanceService.php';

$db = (new Database())->getConnection();
$maintenanceService = new MaintenanceService();

// Filter parameters
$filterType = $_GET['type'] ?? '';
$filterAction = $_GET['action'] ?? '';
$filterFrom = $_GET['from'] ?? '';
$filterTo = $_GET['to'] ?? '';
$filterUser = $_GET['user'] ?? '';
$filterEquipment = $_GET['equipment'] ?? '';
$export = isset($_GET['export']);

// Initialize logs array
$allLogs = [];
$errorLogs = [];

try {
    // 1. Equipment Logs (Most detailed and reliable)
    $equipmentQuery = "
        SELECT 
            'Equipment' as log_type,
            logs.id,
            logs.action,
            logs.timestamp,
            COALESCE(u.username, 'System') AS user_name,
            COALESCE(e.name, 'Unknown Equipment') AS equipment_name,
            COALESCE(e.model, 'N/A') AS equipment_model,
            COALESCE(e.serial_number, 'N/A') AS equipment_serial,
            logs.from_location,
            logs.transferred_to,
            logs.authorized_by,
            logs.transfer_date,
            logs.previous_values,
            logs.remarks,
            'equipment_logs' as source_table,
            'High' as data_quality
        FROM equipment_logs logs
        LEFT JOIN users u ON logs.deleted_by = u.id
        LEFT JOIN equipment e ON logs.equipment_id = e.id
        WHERE 1=1
    ";
    
    $equipmentParams = [];
    
    if ($filterAction !== '') {
        $equipmentQuery .= " AND logs.action = :action";
        $equipmentParams[':action'] = $filterAction;
    }
    
    if ($filterFrom !== '') {
        $equipmentQuery .= " AND DATE(logs.timestamp) >= :from";
        $equipmentParams[':from'] = $filterFrom;
    }
    
    if ($filterTo !== '') {
        $equipmentQuery .= " AND DATE(logs.timestamp) <= :to";
        $equipmentParams[':to'] = $filterTo;
    }
    
    if ($filterUser !== '') {
        $equipmentQuery .= " AND u.username LIKE :user";
        $equipmentParams[':user'] = '%' . $filterUser . '%';
    }
    
    if ($filterEquipment !== '') {
        $equipmentQuery .= " AND e.name LIKE :equipment";
        $equipmentParams[':equipment'] = '%' . $filterEquipment . '%';
    }
    
    $equipmentQuery .= " ORDER BY logs.timestamp DESC";
    
    $stmt = $db->prepare($equipmentQuery);
    $stmt->execute($equipmentParams);
    $equipmentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Borrow Request Logs (Enhanced with better data mapping)
    $borrowQuery = "
        SELECT 
            'Borrow Request' as log_type,
            br.id,
            CASE 
                WHEN br.status = 'Pending' THEN 'Request Submitted'
                WHEN br.status = 'Approved' THEN 'Request Approved'
                WHEN br.status = 'Rejected' THEN 'Request Denied'
                WHEN br.status = 'Returned' THEN 'Equipment Returned'
                ELSE CONCAT('Status: ', br.status)
            END as action,
            br.date_requested as timestamp,
            u.username AS user_name,
            'Equipment Request' AS equipment_name,
            br.purpose AS equipment_model,
            br.location_of_use AS equipment_serial,
            br.borrow_start as from_location,
            br.borrow_end as transferred_to,
            br.released_by as authorized_by,
            br.return_date as transfer_date,
            CONCAT('Purpose: ', COALESCE(br.purpose, 'N/A'), ' | Borrower: ', COALESCE(br.borrower_name, 'N/A'), ' | Location: ', COALESCE(br.location_of_use, 'N/A')) as previous_values,
            br.remarks,
            'borrow_requests' as source_table,
            'Medium' as data_quality
        FROM borrow_requests br
        LEFT JOIN users u ON br.user_id = u.id
        WHERE 1=1
    ";
    
    $borrowParams = [];
    
    if ($filterAction !== '') {
        // Map the filter action to the actual status in database
        $statusMapping = [
            'Request Submitted' => 'Pending',
            'Request Approved' => 'Approved',
            'Request Denied' => 'Rejected',
            'Equipment Returned' => 'Returned'
        ];
        $actualStatus = $statusMapping[$filterAction] ?? $filterAction;
        $borrowQuery .= " AND br.status = :action";
        $borrowParams[':action'] = $actualStatus;
    }
    
    if ($filterFrom !== '') {
        $borrowQuery .= " AND DATE(br.date_requested) >= :from";
        $borrowParams[':from'] = $filterFrom;
    }
    
    if ($filterTo !== '') {
        $borrowQuery .= " AND DATE(br.date_requested) <= :to";
        $borrowParams[':to'] = $filterTo;
    }
    
    if ($filterUser !== '') {
        $borrowQuery .= " AND u.username LIKE :user";
        $borrowParams[':user'] = '%' . $filterUser . '%';
    }
    
    if ($filterEquipment !== '') {
        $borrowQuery .= " AND br.purpose LIKE :equipment";
        $borrowParams[':equipment'] = '%' . $filterEquipment . '%';
    }
    
    $borrowQuery .= " ORDER BY br.date_requested DESC";
    
    $stmt = $db->prepare($borrowQuery);
    $stmt->execute($borrowParams);
    $borrowLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Lab Reservation Logs (Enhanced with better data mapping)
    $labQuery = "
        SELECT 
            'Lab Reservation' as log_type,
            lr.id,
            CASE 
                WHEN lr.status = 'Pending' THEN 'Reservation Submitted'
                WHEN lr.status = 'Approved' THEN 'Reservation Approved'
                WHEN lr.status = 'Rejected' THEN 'Reservation Denied'
                ELSE CONCAT('Status: ', lr.status)
            END as action,
            lr.date_reserved as timestamp,
            u.username AS user_name,
            lab.lab_name AS equipment_name,
            lab.location AS equipment_model,
            CONCAT('Capacity: ', lab.capacity, ' seats') AS equipment_serial,
            lr.reservation_start as from_location,
            lr.reservation_end as transferred_to,
            lr.approved_by as authorized_by,
            lr.date_reserved as transfer_date,
            CONCAT('Purpose: ', COALESCE(lr.purpose, 'N/A'), ' | Requested By: ', COALESCE(lr.requested_by, 'N/A'), ' | Tools: ', COALESCE(lr.needed_tools, 'None')) as previous_values,
            lr.remarks,
            'lab_reservations' as source_table,
            'Medium' as data_quality
        FROM lab_reservations lr
        LEFT JOIN users u ON lr.user_id = u.id
        LEFT JOIN labs lab ON lr.lab_id = lab.id
        WHERE 1=1
    ";
    
    $labParams = [];
    
    if ($filterAction !== '') {
        // Map the filter action to the actual status in database
        $statusMapping = [
            'Reservation Submitted' => 'Pending',
            'Reservation Approved' => 'Approved',
            'Reservation Denied' => 'Rejected'
        ];
        $actualStatus = $statusMapping[$filterAction] ?? $filterAction;
        $labQuery .= " AND lr.status = :action";
        $labParams[':action'] = $actualStatus;
    }
    
    if ($filterFrom !== '') {
        $labQuery .= " AND DATE(lr.date_reserved) >= :from";
        $labParams[':from'] = $filterFrom;
    }
    
    if ($filterTo !== '') {
        $labQuery .= " AND DATE(lr.date_reserved) <= :to";
        $labParams[':to'] = $filterTo;
    }
    
    if ($filterUser !== '') {
        $labQuery .= " AND u.username LIKE :user";
        $labParams[':user'] = '%' . $filterUser . '%';
    }
    
    if ($filterEquipment !== '') {
        $labQuery .= " AND lab.lab_name LIKE :equipment";
        $labParams[':equipment'] = '%' . $filterEquipment . '%';
    }
    
    $labQuery .= " ORDER BY lr.date_reserved DESC";
    
    $stmt = $db->prepare($labQuery);
    $stmt->execute($labParams);
    $labLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Maintenance Logs (Enhanced with better data mapping)
    $maintenanceQuery = "
        SELECT 
            'Maintenance' as log_type,
            m.id,
            CASE 
                WHEN m.status = 'Pending' THEN 'Maintenance Scheduled'
                WHEN m.status = 'In Progress' THEN 'Maintenance Started'
                WHEN m.status = 'Completed' THEN 'Maintenance Completed'
                WHEN m.status = 'Skipped' THEN 'Maintenance Skipped'
                ELSE CONCAT('Status: ', m.status)
            END as action,
            m.created_at as timestamp,
            'System' AS user_name,
            e.name AS equipment_name,
            e.model AS equipment_model,
            e.serial_number AS equipment_serial,
            m.scheduled_date as from_location,
            m.updated_at as transferred_to,
            'System' as authorized_by,
            m.scheduled_date as transfer_date,
            CONCAT('Type: ', COALESCE(m.maintenance_type, 'N/A'), ' | Status: ', m.status) as previous_values,
            'Scheduled maintenance' as remarks,
            'maintenance_schedule' as source_table,
            'Medium' as data_quality
        FROM maintenance_schedule m
        LEFT JOIN equipment e ON m.equipment_id = e.id
        WHERE 1=1
    ";
    
    $maintenanceParams = [];
    
    if ($filterAction !== '') {
        // Map the filter action to the actual status in database
        $statusMapping = [
            'Maintenance Scheduled' => 'Pending',
            'Maintenance Started' => 'In Progress',
            'Maintenance Completed' => 'Completed',
            'Maintenance Skipped' => 'Skipped'
        ];
        $actualStatus = $statusMapping[$filterAction] ?? $filterAction;
        $maintenanceQuery .= " AND m.status = :action";
        $maintenanceParams[':action'] = $actualStatus;
    }
    
    if ($filterFrom !== '') {
        $maintenanceQuery .= " AND DATE(m.created_at) >= :from";
        $maintenanceParams[':from'] = $filterFrom;
    }
    
    if ($filterTo !== '') {
        $maintenanceQuery .= " AND DATE(m.created_at) <= :to";
        $maintenanceParams[':to'] = $filterTo;
    }
    
    if ($filterUser !== '') {
        $maintenanceQuery .= " AND 'System' LIKE :user";
        $maintenanceParams[':user'] = '%' . $filterUser . '%';
    }
    
    if ($filterEquipment !== '') {
        $maintenanceQuery .= " AND e.name LIKE :equipment";
        $maintenanceParams[':equipment'] = '%' . $filterEquipment . '%';
    }
    
    $maintenanceQuery .= " ORDER BY m.created_at DESC";
    
    $stmt = $db->prepare($maintenanceQuery);
    $stmt->execute($maintenanceParams);
    $maintenanceLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine all logs
$allLogs = array_merge($equipmentLogs, $borrowLogs, $labLogs, $maintenanceLogs);

// Debug: Log the counts
error_log('[History Logs Debug] Equipment logs: ' . count($equipmentLogs));
error_log('[History Logs Debug] Borrow logs: ' . count($borrowLogs));
error_log('[History Logs Debug] Lab logs: ' . count($labLogs));
error_log('[History Logs Debug] Maintenance logs: ' . count($maintenanceLogs));
error_log('[History Logs Debug] Total logs before filter: ' . count($allLogs));

// Sort by timestamp (newest first)
usort($allLogs, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Filter by type if specified
if ($filterType !== '') {
    $allLogs = array_filter($allLogs, function($log) use ($filterType) {
        return $log['log_type'] === $filterType;
    });
}

error_log('[History Logs Debug] Total logs after filter: ' . count($allLogs));
    
} catch (Exception $e) {
    error_log('[History Logs Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $errorLogs[] = 'Database Error: ' . $e->getMessage();
    $allLogs = [];
}

// Export functionality
if ($export) {
    try {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="history_logs_' . date('Y-m-d_H-i-s') . '.csv"');
        $output = fopen('php://output', 'w');
        
        // Add BOM for proper UTF-8 encoding in Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, [
            'Log Type', 'Action', 'Timestamp', 'User', 'Equipment/Resource', 
            'Model/Details', 'Serial/Location', 'From/Start', 'To/End', 
            'Authorized By', 'Date', 'Details', 'Remarks', 'Data Quality', 'Source Table'
        ]);
        
        foreach ($allLogs as $log) {
            fputcsv($output, [
                $log['log_type'],
                $log['action'],
                $log['timestamp'],
                $log['user_name'] ?? 'N/A',
                $log['equipment_name'] ?? 'N/A',
                $log['equipment_model'] ?? '—',
                $log['equipment_serial'] ?? '—',
                $log['from_location'] ?? '—',
                $log['transferred_to'] ?? '—',
                $log['authorized_by'] ?? '—',
                $log['transfer_date'] ?? '—',
                $log['previous_values'] ?? '—',
                $log['remarks'] ?? '—',
                $log['data_quality'] ?? 'Unknown',
                $log['source_table'] ?? 'Unknown'
            ]);
        }
        fclose($output);
        exit;
    } catch (Exception $e) {
        error_log('[History Logs Export Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        echo 'Error exporting logs: ' . htmlspecialchars($e->getMessage());
        exit;
    }
}

// Calculate statistics
$totalLogs = count($allLogs);
$equipmentCount = count(array_filter($allLogs, function($log) { return $log['log_type'] === 'Equipment'; }));
$borrowCount = count(array_filter($allLogs, function($log) { return $log['log_type'] === 'Borrow Request'; }));
$labCount = count(array_filter($allLogs, function($log) { return $log['log_type'] === 'Lab Reservation'; }));
$maintenanceCount = count(array_filter($allLogs, function($log) { return $log['log_type'] === 'Maintenance'; }));

// Get unique users and equipment for filters
$uniqueUsers = array_unique(array_filter(array_column($allLogs, 'user_name')));
$uniqueEquipment = array_unique(array_filter(array_column($allLogs, 'equipment_name')));
sort($uniqueUsers);
sort($uniqueEquipment);

// Calculate data quality statistics
$highQualityCount = count(array_filter($allLogs, function($log) { return $log['data_quality'] === 'High'; }));
$mediumQualityCount = count(array_filter($allLogs, function($log) { return $log['data_quality'] === 'Medium'; }));
$lowQualityCount = count(array_filter($allLogs, function($log) { return $log['data_quality'] === 'Low'; }));

// Function to format equipment details
function formatEquipmentDetails($previousValues, $logType, $action) {
    if (empty($previousValues)) {
        return '<span class="text-muted">—</span>';
    }
    
    // Try to decode JSON for equipment logs
    if ($logType === 'Equipment') {
        $decoded = json_decode($previousValues, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $formatted = [];
            
            // Handle different equipment actions
            switch ($action) {
                case 'Updated':
                    foreach ($decoded as $key => $value) {
                        if (!empty($value)) {
                            $label = ucfirst(str_replace('_', ' ', $key));
                            $formatted[] = "<strong>$label:</strong> " . htmlspecialchars($value);
                        }
                    }
                    break;
                    
                case 'Transferred':
                    if (isset($decoded['from_location']) && isset($decoded['to_location'])) {
                        $formatted[] = "<strong>From:</strong> " . htmlspecialchars($decoded['from_location']);
                        $formatted[] = "<strong>To:</strong> " . htmlspecialchars($decoded['to_location']);
                    } else {
                        foreach ($decoded as $key => $value) {
                            if (!empty($value)) {
                                $label = ucfirst(str_replace('_', ' ', $key));
                                $formatted[] = "<strong>$label:</strong> " . htmlspecialchars($value);
                            }
                        }
                    }
                    break;
                    
                case 'Archived':
                    $formatted[] = "<strong>Reason:</strong> " . htmlspecialchars($decoded['reason'] ?? 'Equipment archived');
                    break;
                    
                case 'Restored':
                    $formatted[] = "<strong>Action:</strong> Equipment restored from archive";
                    break;
                    
                default:
                    foreach ($decoded as $key => $value) {
                        if (!empty($value)) {
                            $label = ucfirst(str_replace('_', ' ', $key));
                            $formatted[] = "<strong>$label:</strong> " . htmlspecialchars($value);
                        }
                    }
            }
            
            if (!empty($formatted)) {
                return '<div class="text-muted small">' . implode('<br>', $formatted) . '</div>';
            } else {
                // If no formatted data, show a generic message based on action
                switch ($action) {
                    case 'Updated':
                        return '<div class="text-muted small"><strong>Equipment details updated</strong></div>';
                    case 'Transferred':
                        return '<div class="text-muted small"><strong>Equipment location changed</strong></div>';
                    case 'Archived':
                        return '<div class="text-muted small"><strong>Equipment archived</strong></div>';
                    case 'Restored':
                        return '<div class="text-muted small"><strong>Equipment restored</strong></div>';
                    default:
                        return '<div class="text-muted small"><strong>Equipment action performed</strong></div>';
                }
            }
        }
    }
    
    // For non-JSON or other log types, display as is
    return '<div class="text-muted small">' . htmlspecialchars($previousValues) . '</div>';
}

// Get date range for transparency
$dateRange = '';
if (!empty($allLogs)) {
    try {
        $firstDate = date('M d, Y', strtotime(end($allLogs)['timestamp']));
        $lastDate = date('M d, Y', strtotime($allLogs[0]['timestamp']));
        $dateRange = "$firstDate to $lastDate";
    } catch (Exception $e) {
        $dateRange = 'Date range unavailable';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History Logs - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/history_logs.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>
        
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="bi bi-clock-history text-primary me-2"></i>History Logs
                </h1>
                <p class="text-muted small mb-0">Complete audit trail of all system activities</p>
                <?php if ($dateRange): ?>
                    <small class="text-muted">📅 <?= $dateRange ?></small>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-primary fs-6 align-self-center"><?= $totalLogs ?> Total Activities</span>
                <button onclick="printHistoryLogs()" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>" class="btn btn-success">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>
        </div>

        <!-- Error Alerts -->
        <?php if (!empty($errorLogs)): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <h6 class="mb-1 fw-bold">
                    <i class="bi bi-exclamation-triangle me-2"></i>Data Retrieval Issues
                </h6>
                <ul class="mb-0 small">
                    <?php foreach ($errorLogs as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-box-seam text-primary mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-primary fw-bold"><?= $equipmentCount ?></h3>
                        <p class="mb-0 text-muted small">Equipment Activities</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-journal-check text-success mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-success fw-bold"><?= $borrowCount ?></h3>
                        <p class="mb-0 text-muted small">Borrow Requests</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-calendar-check text-info mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-info fw-bold"><?= $labCount ?></h3>
                        <p class="mb-0 text-muted small">Lab Reservations</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center py-3">
                        <i class="bi bi-tools text-warning mb-2" style="font-size: 2rem;"></i>
                        <h3 class="mb-1 text-warning fw-bold"><?= $maintenanceCount ?></h3>
                        <p class="mb-0 text-muted small">Maintenance Records</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter Options</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label fw-semibold small">Activity Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="Equipment" <?= $filterType === 'Equipment' ? 'selected' : '' ?>>📦 Equipment</option>
                            <option value="Borrow Request" <?= $filterType === 'Borrow Request' ? 'selected' : '' ?>>📋 Borrow Request</option>
                            <option value="Lab Reservation" <?= $filterType === 'Lab Reservation' ? 'selected' : '' ?>>🏫 Lab Reservation</option>
                            <option value="Maintenance" <?= $filterType === 'Maintenance' ? 'selected' : '' ?>>🔧 Maintenance</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label fw-semibold small">Action</label>
                        <select name="action" class="form-select">
                            <option value="">All Actions</option>
                            <optgroup label="Equipment">
                                <option value="Updated" <?= $filterAction === 'Updated' ? 'selected' : '' ?>>Updated</option>
                                <option value="Transferred" <?= $filterAction === 'Transferred' ? 'selected' : '' ?>>Transferred</option>
                                <option value="Archived" <?= $filterAction === 'Archived' ? 'selected' : '' ?>>Archived</option>
                                <option value="Restored" <?= $filterAction === 'Restored' ? 'selected' : '' ?>>Restored</option>
                            </optgroup>
                            <optgroup label="Requests">
                                <option value="Pending" <?= $filterAction === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Approved" <?= $filterAction === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="Rejected" <?= $filterAction === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="Returned" <?= $filterAction === 'Returned' ? 'selected' : '' ?>>Returned</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label fw-semibold small">From Date</label>
                        <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filterFrom) ?>">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label fw-semibold small">To Date</label>
                        <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filterTo) ?>">
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label fw-semibold small">User</label>
                        <select name="user" class="form-select">
                            <option value="">All Users</option>
                            <?php foreach ($uniqueUsers as $user): ?>
                                <option value="<?= htmlspecialchars($user) ?>" <?= $filterUser === $user ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <label class="form-label fw-semibold small">Equipment</label>
                        <select name="equipment" class="form-select">
                            <option value="">All Equipment</option>
                            <?php foreach ($uniqueEquipment as $equipment): ?>
                                <option value="<?= htmlspecialchars($equipment) ?>" <?= $filterEquipment === $equipment ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($equipment) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Apply
                        </button>
                        <a href="history_logs.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Activity History</h5>
                        <small class="text-muted">Showing <?= count($allLogs) ?> of <?= $totalLogs ?> total activities</small>
                    </div>
                    <?php if (!empty($allLogs)): ?>
                    <span class="badge bg-primary"><?= count($allLogs) ?> records</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="historyTable">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Type</th>
                                <th>Action</th>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Equipment/Resource</th>
                                <th>Details</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allLogs)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                        <h5 class="mt-3 text-muted">No Activities Found</h5>
                                        <p class="text-muted mb-0">No activities match your current filters.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allLogs as $i => $log): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <?php
                                            $typeIcons = [
                                                'Equipment' => 'box-seam',
                                                'Borrow Request' => 'journal-check',
                                                'Lab Reservation' => 'calendar-check',
                                                'Maintenance' => 'tools'
                                            ];
                                            $typeColors = [
                                                'Equipment' => 'primary',
                                                'Borrow Request' => 'success',
                                                'Lab Reservation' => 'info',
                                                'Maintenance' => 'warning'
                                            ];
                                            $icon = $typeIcons[$log['log_type']] ?? 'circle';
                                            $color = $typeColors[$log['log_type']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>">
                                                <i class="bi bi-<?= $icon ?>"></i>
                                                <?= htmlspecialchars($log['log_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $actionColors = [
                                                'Updated' => 'info',
                                                'Transferred' => 'warning',
                                                'Archived' => 'danger',
                                                'Restored' => 'success',
                                                'Request Submitted' => 'secondary',
                                                'Request Approved' => 'success',
                                                'Request Denied' => 'danger',
                                                'Equipment Returned' => 'primary',
                                                'Reservation Submitted' => 'secondary',
                                                'Reservation Approved' => 'success',
                                                'Reservation Denied' => 'danger',
                                                'Maintenance Scheduled' => 'info',
                                                'Maintenance Started' => 'warning',
                                                'Maintenance Completed' => 'success',
                                                'Maintenance Skipped' => 'danger'
                                            ];
                                            $actionColor = $actionColors[$log['action']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $actionColor ?>">
                                                <?= htmlspecialchars($log['action']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div><?= date('M d, Y', strtotime($log['timestamp'])) ?></div>
                                            <small class="text-muted"><?= date('h:i A', strtotime($log['timestamp'])) ?></small>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($log['user_name'] ?? 'System') ?></strong>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($log['equipment_name'] ?? 'N/A') ?></div>
                                            <?php if (!empty($log['from_location']) && !empty($log['transferred_to'])): ?>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($log['from_location']) ?> → <?= htmlspecialchars($log['transferred_to']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['remarks'])): ?>
                                                <small class="text-muted"><?= htmlspecialchars($log['remarks']) ?></small>
                                            <?php elseif (!empty($log['previous_values'])): ?>
                                                <?php echo formatEquipmentDetails($log['previous_values'], $log['log_type'], $log['action']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center pe-4">
                                            <button class="btn btn-sm btn-outline-primary" onclick="printHistoryLog(<?= $i ?>, '<?= htmlspecialchars($log['log_type'], ENT_QUOTES) ?>', '<?= htmlspecialchars($log['action'], ENT_QUOTES) ?>')" title="Print">
                                                <i class="bi bi-printer"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/history_logs.js"></script>

</body>
</html> 