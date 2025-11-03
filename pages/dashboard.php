<?php
require_once '../includes/auth.php';
require_once '../classes/EquipmentService.php';
require_once '../classes/BorrowRequestService.php';
require_once '../classes/MaintenanceService.php';
require_once '../classes/LabReservationService.php';
require_once '../classes/SoftwareService.php';

/**
 * Data Validation and Cleanup Functions
 * What: Ensures clean, validated data for dashboard display
 * Why: Prevents display of corrupted, null, or invalid data
 * How: Validates, sanitizes, and provides fallback values
 */

function validateAndCleanString($value, $default = 'N/A', $maxLength = 255) {
    if (is_null($value) || $value === '' || $value === false) {
        return $default;
    }
    $cleaned = trim(strip_tags($value));
    return strlen($cleaned) > $maxLength ? substr($cleaned, 0, $maxLength) . '...' : $cleaned;
}

function validateAndCleanNumber($value, $default = 0, $min = 0, $max = 999999) {
    if (is_null($value) || $value === '' || !is_numeric($value)) {
        return $default;
    }
    $num = (int)$value;
    return max($min, min($max, $num));
}

function validateAndCleanDate($value, $default = null) {
    if (is_null($value) || $value === '' || $value === '0000-00-00 00:00:00' || $value === '1970-01-01 00:00:00') {
        return $default;
    }
    try {
        $date = new DateTime($value);
        return $date->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return $default;
    }
}

function validateAndCleanArray($array, $default = []) {
    if (!is_array($array) || empty($array)) {
        return $default;
    }
    return array_filter($array, function($item) {
        return !is_null($item) && $item !== '';
    });
}

function sanitizeCalendarEvent($event) {
    return [
        'title' => validateAndCleanString($event['title'] ?? '', 'Untitled Event', 100),
        'start' => validateAndCleanDate($event['start'] ?? ''),
        'end' => validateAndCleanDate($event['end'] ?? ''),
        'color' => validateAndCleanString($event['color'] ?? '#ff9800', '#ff9800', 7),
        'description' => validateAndCleanString($event['description'] ?? '', '', 500),
        'className' => validateAndCleanString($event['className'] ?? '', 'other', 50),
        'id' => validateAndCleanNumber($event['id'] ?? 0, 0)
    ];
}

function performDataIntegrityCheck() {
    $issues = [];
    
    // Check for common data integrity issues
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check for null or empty critical fields
        $checks = [
            'equipment' => "SELECT COUNT(*) as count FROM equipment WHERE name IS NULL OR name = ''",
            'borrow_requests' => "SELECT COUNT(*) as count FROM borrow_requests WHERE borrower_name IS NULL OR borrower_name = ''",
            'lab_reservations' => "SELECT COUNT(*) as count FROM lab_reservations WHERE purpose IS NULL OR purpose = ''",
            'invalid_dates' => "SELECT COUNT(*) as count FROM borrow_requests WHERE borrow_start = '1970-01-01 00:00:00' OR borrow_end = '1970-01-01 00:00:00'"
        ];
        
        foreach ($checks as $checkName => $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $issues[] = "Found {$result['count']} records with data integrity issues in {$checkName}";
            }
        }
        
        if (!empty($issues)) {
            error_log('[Dashboard Data Integrity Check] Issues found: ' . implode(', ', $issues), 3, __DIR__ . '/../logs/error.log');
        }
        
    } catch (Exception $e) {
        error_log('[Dashboard Data Integrity Check Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    }
    
    return $issues;
}

// Perform data integrity check
$dataIntegrityIssues = performDataIntegrityCheck();

$equipmentService = new EquipmentService();
$borrowRequestService = new BorrowRequestService();
$maintenanceService = new MaintenanceService();
$labReservationService = new LabReservationService();
$softwareService = new SoftwareService();

// Fetch dashboard KPIs and notifications with data validation
try {
    /**
     * What: Fetch total equipment count
     * Why: For dashboard KPI
     * How: Uses EquipmentService::countEquipment with validation
     */
    $rawTotalEquipment = $equipmentService->countEquipment();
    $totalEquipment = validateAndCleanNumber($rawTotalEquipment, 0, 0, 9999);
} catch (Exception $e) {
    error_log('[Dashboard Equipment Count Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $totalEquipment = 0;
}

try {
    /**
     * What: Fetch borrowed items count
     * Why: For dashboard KPI
     * How: Uses BorrowRequestService::countBorrowedItems with validation
     */
    $rawBorrowedItems = $borrowRequestService->countBorrowedItems();
    $borrowedItems = validateAndCleanNumber($rawBorrowedItems, 0, 0, 9999);
} catch (Exception $e) {
    error_log('[Dashboard Borrowed Items Count Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $borrowedItems = 0;
}

try {
    /**
     * What: Fetch pending reservations count
     * Why: For dashboard KPI
     * How: Uses LabReservationService::countPendingReservations with validation
     */
    $rawPendingReservations = $labReservationService->countPendingReservations();
    $pendingReservations = validateAndCleanNumber($rawPendingReservations, 0, 0, 9999);
} catch (Exception $e) {
    error_log('[Dashboard Pending Reservations Count Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $pendingReservations = 0;
}

try {
    /**
     * What: Fetch maintenance due count
     * Why: For dashboard KPI
     * How: Uses MaintenanceService::countMaintenanceDue with validation
     */
    $rawMaintenanceDue = $maintenanceService->countMaintenanceDue();
    $maintenanceDue = validateAndCleanNumber($rawMaintenanceDue, 0, 0, 9999);
} catch (Exception $e) {
    error_log('[Dashboard Maintenance Due Count Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $maintenanceDue = 0;
}

try {
    $rawExpiredLicenses = $softwareService->getExpiredLicenses();
    $rawExpiringLicenses = $softwareService->getExpiringLicenses(30);
    $expiredLicenses = validateAndCleanArray($rawExpiredLicenses, []);
    $expiringLicenses = validateAndCleanArray($rawExpiringLicenses, []);
} catch (Exception $e) {
    error_log('[Dashboard Software License Categories Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $expiredLicenses = $expiringLicenses = [];
}

try {
    /**
     * What: Fetch due/overdue maintenance
     * Why: For dashboard notification
     * How: Uses MaintenanceService::getDueMaintenance
     */
    $dueMaintenance = $maintenanceService->getDueMaintenance();
} catch (Exception $e) {
    error_log('[Dashboard Due Maintenance Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $dueMaintenance = [];
}

try {
    $dueToday = $maintenanceService->getDueToday();
    $overdue = $maintenanceService->getOverdue();
    $upcoming = $maintenanceService->getUpcoming(7);
    $expired = $maintenanceService->getExpired();
} catch (Exception $e) {
    error_log('[Dashboard Maintenance Categories Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $dueToday = $overdue = $upcoming = $expired = [];
}

try {
    $equipmentDueForMaintenance = $equipmentService->getDueForMaintenance(30);
    $equipmentOverdueMaintenance = $equipmentService->getOverdueMaintenance();
} catch (Exception $e) {
    error_log('[Dashboard Equipment Maintenance Reminder Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $equipmentDueForMaintenance = $equipmentOverdueMaintenance = [];
}

// Build calendar events array
$calendarEvents = [];

// Equipment Borrow Requests (Approved only - Current and Upcoming)
$rawBorrowRequests = $borrowRequestService->getAllBorrowRequests();
$validBorrowRequests = validateAndCleanArray($rawBorrowRequests, []);

foreach ($validBorrowRequests as $req) {
    if (validateAndCleanString($req['status'] ?? '') === 'Approved') {
        // Check if the borrow period has ended
        $now = new DateTime();
        $borrowEndDate = validateAndCleanDate($req['borrow_end'] ?? '');
        
        if ($borrowEndDate) {
            try {
                $borrowEnd = new DateTime($borrowEndDate);
                
                // Only add to calendar if the borrow period hasn't ended yet
                if ($borrowEnd > $now) {
                    $calendarEvents[] = sanitizeCalendarEvent([
                        'title' => 'Borrow: ' . validateAndCleanString($req['borrower_name'] ?? 'Unknown') . ' - ' . validateAndCleanString($req['equipment_names'] ?? 'N/A'),
                        'start' => validateAndCleanDate($req['date_requested'] ?? ''),
                        'end' => $borrowEndDate,
                        'color' => '#1976d2',
                        'description' => validateAndCleanString($req['description'] ?? '', 'No description'),
                        'className' => 'equipment'
                    ]);
                }
            } catch (Exception $e) {
                error_log('[Dashboard Calendar Event Error] Invalid borrow end date: ' . $borrowEndDate, 3, __DIR__ . '/../logs/error.log');
            }
        }
    }
}
// Lab Reservations (Approved only - Current and Upcoming)
$rawLabReservations = $labReservationService->getAllReservations();
$validLabReservations = validateAndCleanArray($rawLabReservations, []);

foreach ($validLabReservations as $res) {
    if (validateAndCleanString($res['status'] ?? '') === 'Approved') {
        // Check if the reservation is finished (end time has passed)
        $now = new DateTime();
        $reservationEndDate = validateAndCleanDate($res['reservation_end'] ?? '');
        
        if ($reservationEndDate) {
            try {
                $reservationEnd = new DateTime($reservationEndDate);
                
                // Only add to calendar if the reservation hasn't finished yet
                if ($reservationEnd > $now) {
                    $calendarEvents[] = sanitizeCalendarEvent([
                        'title' => 'Lab: ' . validateAndCleanString($res['lab_name'] ?? 'Unknown Lab') . ' - ' . validateAndCleanString($res['purpose'] ?? 'No Purpose'),
                        'start' => validateAndCleanDate($res['reservation_start'] ?? ''),
                        'end' => $reservationEndDate,
                        'color' => '#43a047',
                        'description' => validateAndCleanString($res['purpose'] ?? '', 'No description'),
                        'className' => 'lab'
                    ]);
                }
            } catch (Exception $e) {
                error_log('[Dashboard Calendar Event Error] Invalid reservation end date: ' . $reservationEndDate, 3, __DIR__ . '/../logs/error.log');
            }
        }
    }
}
// Maintenance Due Dates
$rawMaintenanceDue = $maintenanceService->getAllDueDates();
$validMaintenanceDue = validateAndCleanArray($rawMaintenanceDue, []);

foreach ($validMaintenanceDue as $maint) {
    $dueDate = validateAndCleanDate($maint['due_date'] ?? '');
    if ($dueDate) {
        $calendarEvents[] = sanitizeCalendarEvent([
            'title' => 'Maintenance Due: ' . validateAndCleanString($maint['equipment_name'] ?? 'Unknown Equipment'),
            'start' => $dueDate,
            'color' => '#f44336',
            'description' => validateAndCleanString($maint['issue_description'] ?? '', 'No description'),
            'className' => 'maintenance'
        ]);
    }
}
// Software License Expiry
$rawExpiringLicenses = $softwareService->getExpiringLicenses(365);
$validExpiringLicenses = validateAndCleanArray($rawExpiringLicenses, []);

foreach ($validExpiringLicenses as $sw) {
    $expiryDate = validateAndCleanDate($sw['license_expiry_date'] ?? '');
    if ($expiryDate) {
        $softwareName = validateAndCleanString($sw['name'] ?? 'Unknown Software');
        $calendarEvents[] = sanitizeCalendarEvent([
            'title' => 'License Expiry: ' . $softwareName,
            'start' => $expiryDate,
            'color' => '#ffeb3b',
            'description' => 'License for ' . $softwareName . ' expires on ' . $expiryDate,
            'className' => 'other'
        ]);
    }
}
// Custom Calendar Events
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $query = "SELECT * FROM calendar_events ORDER BY event_date ASC, start_time ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rawCustomEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $validCustomEvents = validateAndCleanArray($rawCustomEvents, []);
    
    foreach ($validCustomEvents as $event) {
        $eventType = validateAndCleanString($event['type'] ?? '', 'other', 20);
        $color = '#ff9800'; // Default orange
        $className = 'other';
        
        switch ($eventType) {
            case 'meeting':
                $color = '#7c4dff';
                $className = 'meeting';
                break;
            case 'maintenance':
                $color = '#f44336';
                $className = 'maintenance';
                break;
            case 'training':
                $color = '#ff9800';
                $className = 'training';
                break;
            case 'other':
                $color = '#ffeb3b';
                $className = 'other';
                break;
        }
        
        $eventDate = validateAndCleanDate($event['event_date'] ?? '');
        $startTime = validateAndCleanString($event['start_time'] ?? '00:00:00', '00:00:00', 8);
        $endTime = validateAndCleanString($event['end_time'] ?? '23:59:59', '23:59:59', 8);
        
        if ($eventDate) {
            $calendarEvents[] = sanitizeCalendarEvent([
                'title' => validateAndCleanString($event['title'] ?? 'Untitled Event'),
                'start' => $eventDate . 'T' . $startTime,
                'end' => $eventDate . 'T' . $endTime,
                'color' => $color,
                'description' => validateAndCleanString($event['description'] ?? '', 'No description'),
                'type' => $eventType,
                'className' => $className,
                'id' => validateAndCleanNumber($event['id'] ?? 0, 0)
            ]);
        }
    }
} catch (Exception $e) {
    error_log('[Dashboard Custom Events Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
}

$calendarEventsJson = json_encode($calendarEvents);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <!-- Software License Expiry Notification -->
        <?php if (!empty($expiredLicenses)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-x-octagon me-2"></i>
            <div>
                <strong>Expired Software Licenses:</strong> The following software licenses have expired:<br>
                <ul class="mb-0">
                    <?php foreach ($expiredLicenses as $sw): ?>
                        <li><?= htmlspecialchars(validateAndCleanString($sw['name'] ?? 'Unknown Software')) ?> (Expired: <?= htmlspecialchars(validateAndCleanString($sw['license_expiry_date'] ?? 'Unknown Date')) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($expiringLicenses)): ?>
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            <div>
                <strong>Expiring Soon:</strong> The following software licenses are expiring within 30 days:<br>
                <ul class="mb-0">
                    <?php foreach ($expiringLicenses as $sw): ?>
                        <li><?= htmlspecialchars(validateAndCleanString($sw['name'] ?? 'Unknown Software')) ?> (Expiry: <?= htmlspecialchars(validateAndCleanString($sw['license_expiry_date'] ?? 'Unknown Date')) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($equipmentOverdueMaintenance)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-x-octagon me-2"></i>
            <div>
                <strong>Overdue Maintenance:</strong> The following equipment is overdue for maintenance:<br>
                <ul class="mb-0">
                    <?php foreach ($equipmentOverdueMaintenance as $item): ?>
                        <li><?= htmlspecialchars($item['name']) ?> (Next Due: <?= htmlspecialchars($item['next_maintenance_due']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($equipmentDueForMaintenance)): ?>
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            <div>
                <strong>Maintenance Due Soon:</strong> The following equipment is due for maintenance within 30 days:<br>
                <ul class="mb-0">
                    <?php foreach ($equipmentDueForMaintenance as $item): ?>
                        <li><?= htmlspecialchars($item['name']) ?> (Next Due: <?= htmlspecialchars($item['next_maintenance_due']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <!-- Equipment Due for Maintenance Notification -->
        <?php if (!empty($dueToday)): ?>
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-calendar-day me-2"></i>
            <div>
                <strong>Due Today:</strong> The following equipment is due for maintenance today:<br>
                <ul class="mb-0">
                    <?php foreach ($dueToday as $item): ?>
                        <li><?= htmlspecialchars($item['equipment_name']) ?> (Due: <?= htmlspecialchars($item['due_date']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($overdue)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>
                <strong>Overdue:</strong> The following equipment is overdue for maintenance:<br>
                <ul class="mb-0">
                    <?php foreach ($overdue as $item): ?>
                        <li><?= htmlspecialchars($item['equipment_name']) ?> (Due: <?= htmlspecialchars($item['due_date']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($upcoming)): ?>
        <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-clock-history me-2"></i>
            <div>
                <strong>Upcoming:</strong> The following equipment is due for maintenance soon:<br>
                <ul class="mb-0">
                    <?php foreach ($upcoming as $item): ?>
                        <li><?= htmlspecialchars($item['equipment_name']) ?> (Due: <?= htmlspecialchars($item['due_date']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($expired)): ?>
        <div class="alert alert-secondary d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-x-octagon me-2"></i>
            <div>
                <strong>Expired:</strong> The following equipment has expired maintenance:<br>
                <ul class="mb-0">
                    <?php foreach ($expired as $item): ?>
                        <li><?= htmlspecialchars($item['equipment_name']) ?> (Due: <?= htmlspecialchars($item['due_date']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Real-time Notifications Section -->
        <div id="realTimeNotifications" class="mb-4">
            <!-- Notifications will be dynamically inserted here -->
        </div>
        
        <!-- Notification Counter Badge -->
        <div id="notificationCounter" class="position-fixed" style="top: 20px; right: 20px; z-index: 9999; display: none;">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-bell-fill me-2"></i>
                <span id="notificationText"></span>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        
        <div class="dashboard-header mb-4">
            <h1>Welcome, <?= htmlspecialchars($_SESSION['role'] ?? 'User') ?>!</h1>
            <div>This is your Laboratory and ICT Resource Management Dashboard.</div>
        </div>
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <a href="inventory.php" class="dashboard-card-link">
                    <div class="card dashboard-card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-box-seam"></i> Total Equipment</h5>
                            <p class="card-text fs-3"><?= $totalEquipment ?></p>
                            <div class="card-hover-text">View Inventory</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="manage_borrow_requests.php" class="dashboard-card-link">
                    <div class="card dashboard-card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-bag-check"></i> Borrowed Items</h5>
                            <p class="card-text fs-3"><?= $borrowedItems ?></p>
                            <div class="card-hover-text">Manage Requests</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="manage_lab_reservations.php" class="dashboard-card-link">
                    <div class="card dashboard-card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-clock-history"></i> Pending Reservations</h5>
                            <p class="card-text fs-3"><?= $pendingReservations ?></p>
                            <div class="card-hover-text">View Reservations</div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="maintenance_scheduled.php" class="dashboard-card-link">
                    <div class="card dashboard-card text-white bg-danger">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-wrench"></i> Maintenance Due</h5>
                            <p class="card-text fs-3"><?= $maintenanceDue ?></p>
                            <div class="card-hover-text">View Maintenance</div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="calendar-section">
            <h4 class="mb-3"><i class="bi bi-calendar-event"></i> Resource Calendar</h4>
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Tip:</strong> Click on any date to add a new event with time and duration.
            </div>
            <div class="calendar-legend mb-3">
                <span><span class="legend-dot legend-equip" style="background:#1976d2;"></span> Equipment Booking</span>
                <span><span class="legend-dot legend-lab" style="background:#43a047;"></span> Lab Reservation</span>
                <span><span class="legend-dot legend-maintenance" style="background:#f44336;"></span> Maintenance Due</span>
                <span><span class="legend-dot legend-license" style="background:#ffeb3b;"></span> License Expiry</span>
                <span><span class="legend-dot legend-meeting" style="background:#7c4dff;"></span> Meeting</span>
                <span><span class="legend-dot legend-training" style="background:#ff9800;"></span> Training</span>
                <span><span class="legend-dot legend-other" style="background:#1de9b6;"></span> Other Events</span>
            </div>
            <div id="resourceCalendar"></div>
        </div>
    </div>
</main>
<script>var dashboardCalendarEventsJson = <?= json_encode($calendarEventsJson) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/dashboard.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Real-time Notifications Script -->
<script>
// Simple and reliable real-time notification system
let lastCheckTime = new Date().toISOString().slice(0, 19).replace('T', ' ');
let lastBorrowCount = 0;
let lastReservationCount = 0;

// Initialize counts from the page
document.addEventListener('DOMContentLoaded', function() {
    // Get initial counts from the dashboard cards
    const borrowedCard = document.querySelector('.card.bg-success .card-text');
    const pendingCard = document.querySelector('.card.bg-warning .card-text');
    
    if (borrowedCard) {
        lastBorrowCount = parseInt(borrowedCard.textContent) || 0;
    }
    if (pendingCard) {
        lastReservationCount = parseInt(pendingCard.textContent) || 0;
    }
    
    console.log('Initial counts - Borrowed:', lastBorrowCount, 'Pending:', lastReservationCount);
    console.log('Notification system initialized');
    
    // Start checking for updates every 3 seconds
    setInterval(checkForNewRequests, 3000);
    
    // Also check immediately
    setTimeout(checkForNewRequests, 1000);
});

async function checkForNewRequests() {
    try {
        console.log('Checking for new requests since:', lastCheckTime);
        
        const response = await fetch(`api/notifications_simple.php?lastCheck=${encodeURIComponent(lastCheckTime)}`);
        const data = await response.json();
        
        if (data.success) {
            console.log('Response received:', data);
            console.log('Current counts - Last Borrow:', lastBorrowCount, 'Current Borrow:', data.counts.pendingBorrowRequests);
            console.log('Current counts - Last Reservation:', lastReservationCount, 'Current Reservation:', data.counts.pendingLabReservations);
            
            // Check for new borrow requests
            if (data.counts.pendingBorrowRequests > lastBorrowCount) {
                const newCount = data.counts.pendingBorrowRequests - lastBorrowCount;
                console.log('New borrow request detected! Count:', newCount);
                showNotification(`New Borrow Request${newCount > 1 ? 's' : ''}! ${newCount} new request${newCount > 1 ? 's' : ''} received.`, 'success');
                updateDashboardCounts(data.counts);
            }
            
            // Check for new lab reservations
            if (data.counts.pendingLabReservations > lastReservationCount) {
                const newCount = data.counts.pendingLabReservations - lastReservationCount;
                console.log('New lab reservation detected! Count:', newCount);
                console.log('Showing notification for lab reservation...');
                showNotification(`New Lab Reservation${newCount > 1 ? 's' : ''}! ${newCount} new reservation${newCount > 1 ? 's' : ''} received.`, 'info');
                updateDashboardCounts(data.counts);
            } else {
                console.log('No new lab reservations detected. Current:', data.counts.pendingLabReservations, 'Last:', lastReservationCount);
            }
            
            // Update last check time
            lastCheckTime = data.timestamp;
            
            // Update last counts
            lastBorrowCount = data.counts.pendingBorrowRequests;
            lastReservationCount = data.counts.pendingLabReservations;
        }
    } catch (error) {
        console.error('Error checking for notifications:', error);
    }
}

function showNotification(message, type = 'success') {
    console.log('showNotification called with:', message, type);
    const notificationCounter = document.getElementById('notificationCounter');
    const notificationText = document.getElementById('notificationText');
    
    console.log('notificationCounter element:', notificationCounter);
    console.log('notificationText element:', notificationText);
    
    if (notificationCounter && notificationText) {
        // Update the notification content
        notificationText.textContent = message;
        
        // Update the alert class based on type
        const alertDiv = notificationCounter.querySelector('.alert');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        
        // Show the notification
        notificationCounter.style.display = 'block';
        
        // Play notification sound
        playNotificationSound();
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            notificationCounter.style.display = 'none';
        }, 5000);
        
        console.log('Notification shown successfully:', message);
    } else {
        console.error('Notification elements not found!');
    }
}

function updateDashboardCounts(counts) {
    // Update borrowed items count
    const borrowedItemsElement = document.querySelector('.card.bg-success .card-text');
    if (borrowedItemsElement) {
        borrowedItemsElement.textContent = counts.borrowedItems;
    }
    
    // Update pending reservations count
    const pendingReservationsElement = document.querySelector('.card.bg-warning .card-text');
    if (pendingReservationsElement) {
        pendingReservationsElement.textContent = counts.pendingLabReservations;
    }
    
    console.log('Dashboard counts updated:', counts);
}

function playNotificationSound() {
    try {
        // Create a simple notification sound
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.2);
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch (e) {
        console.log('Audio notification not supported');
    }
}

// Dashboard-specific sidebar fix - ensure sidebar works properly
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard sidebar initialization started');
    
    const sidebar = document.getElementById('mainSidebar');
    const sidebarToggle = document.getElementById('sidebarLogoToggle');
    const mainContent = document.querySelector('.main-content');
    
    // Debug logging
    console.log('Dashboard sidebar elements found:', {
        sidebar: !!sidebar,
        sidebarToggle: !!sidebarToggle,
        mainContent: !!mainContent
    });
    
    // Additional debugging
    if (sidebar) {
        console.log('Sidebar classes:', sidebar.className);
        console.log('Sidebar style:', sidebar.style.cssText);
        console.log('Sidebar computed style:', window.getComputedStyle(sidebar).transform);
    }
    
    if (sidebarToggle) {
        console.log('Toggle button classes:', sidebarToggle.className);
        console.log('Toggle button style:', sidebarToggle.style.cssText);
    }
    
    // Ensure sidebar is properly initialized
    if (sidebar && sidebarToggle && mainContent) {
        // Check if sidebar state is stored in localStorage
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        
        console.log('Dashboard sidebar collapsed from localStorage:', sidebarCollapsed);
        
        // Initialize sidebar state - default to expanded if no preference is stored
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('sidebar-collapsed');
            sidebarToggle.classList.add('sidebar-collapsed');
            console.log('Dashboard sidebar set to collapsed');
        } else {
            // Ensure sidebar is visible by default
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('sidebar-collapsed');
            sidebarToggle.classList.remove('sidebar-collapsed');
            console.log('Dashboard sidebar set to expanded');
        }
        
        // Add click event listener to toggle button
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Dashboard toggle button clicked!');
            console.log('Before toggle - Sidebar collapsed:', sidebar.classList.contains('collapsed'));
            
            // Toggle sidebar state
            if (sidebar.classList.contains('collapsed')) {
                // Open sidebar
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('sidebar-collapsed');
                sidebarToggle.classList.remove('sidebar-collapsed');
                console.log('Opening sidebar');
            } else {
                // Close sidebar
                sidebar.classList.add('collapsed');
                mainContent.classList.add('sidebar-collapsed');
                sidebarToggle.classList.add('sidebar-collapsed');
                console.log('Closing sidebar');
            }
            
            console.log('After toggle - Sidebar collapsed:', sidebar.classList.contains('collapsed'));
            console.log('Sidebar classes after toggle:', sidebar.className);
            console.log('Main content classes after toggle:', mainContent.className);
            console.log('Toggle button classes after toggle:', sidebarToggle.className);
            
            // Store sidebar state
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            console.log('Dashboard sidebar state saved to localStorage:', isCollapsed);
            
            // Trigger calendar resize if it exists
            if (window.calendar) {
                setTimeout(() => {
                    window.calendar.updateSize();
                }, 300);
            }
        });
        
        // Handle calendar resizing when sidebar changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    // Ensure calendar resizes when sidebar toggles
                    if (window.calendar) {
                        setTimeout(() => {
                            window.calendar.updateSize();
                        }, 300);
                    }
                }
            });
        });
        
        observer.observe(sidebar, { attributes: true });
    } else {
        console.error('Dashboard sidebar elements not found!');
    }
    
    // Fallback: Ensure sidebar works even if global script fails
    setTimeout(function() {
        const fallbackSidebar = document.getElementById('mainSidebar');
        const fallbackToggle = document.getElementById('sidebarLogoToggle');
        const fallbackMainContent = document.querySelector('.main-content');
        
        if (fallbackSidebar && fallbackToggle && fallbackMainContent) {
            console.log('Fallback sidebar initialization');
            
            // Check if sidebar is working properly
            const isCollapsed = fallbackSidebar.classList.contains('collapsed');
            const isVisible = fallbackSidebar.style.transform !== 'translateX(-100%)' && 
                             fallbackSidebar.style.display !== 'none' &&
                             fallbackSidebar.style.visibility !== 'hidden';
            
            // If sidebar should be visible but isn't, fix it
            if (!isCollapsed && !isVisible) {
                console.log('Sidebar should be visible but isn\'t, applying fallback fix');
                fallbackSidebar.classList.remove('collapsed');
                fallbackMainContent.classList.remove('sidebar-collapsed');
                fallbackToggle.classList.remove('sidebar-collapsed');
            }
            
            // If sidebar should be hidden but isn't, fix it
            if (isCollapsed && isVisible) {
                console.log('Sidebar should be hidden but isn\'t, applying fallback fix');
                fallbackSidebar.classList.add('collapsed');
                fallbackMainContent.classList.add('sidebar-collapsed');
                fallbackToggle.classList.add('sidebar-collapsed');
            }
        }
    }, 1000);
    
    // Add temporary debug function to window for testing
    window.debugSidebar = function() {
        const sidebar = document.getElementById('mainSidebar');
        const mainContent = document.querySelector('.main-content');
        const toggle = document.getElementById('sidebarLogoToggle');
        
        console.log('=== SIDEBAR DEBUG INFO ===');
        console.log('Sidebar element:', sidebar);
        console.log('Sidebar classes:', sidebar ? sidebar.className : 'Not found');
        console.log('Sidebar style:', sidebar ? sidebar.style.cssText : 'Not found');
        console.log('Sidebar computed transform:', sidebar ? window.getComputedStyle(sidebar).transform : 'Not found');
        console.log('Main content classes:', mainContent ? mainContent.className : 'Not found');
        console.log('Toggle button classes:', toggle ? toggle.className : 'Not found');
        console.log('LocalStorage sidebar state:', localStorage.getItem('sidebarCollapsed'));
        console.log('========================');
    };
    
    console.log('Debug function available: window.debugSidebar()');
});

// Add live updates indicator
document.addEventListener('DOMContentLoaded', function() {
    const indicator = document.createElement('div');
    indicator.className = 'position-fixed';
    indicator.style.cssText = 'top: 20px; right: 20px; z-index: 9998; background: rgba(0, 123, 255, 0.9); color: white; padding: 8px 12px; border-radius: 20px; font-size: 12px; display: flex; align-items: center; gap: 5px;';
    indicator.innerHTML = `
        <div style="width: 8px; height: 8px; background: #fff; border-radius: 50%; animation: pulse 2s infinite;"></div>
        <span>Live Updates</span>
    `;
    
    // Add pulse animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.3; }
            100% { opacity: 1; }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(indicator);
});
</script>
</body>
</html>
