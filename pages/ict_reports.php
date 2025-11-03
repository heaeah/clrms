<?php
require_once '../includes/auth.php';
require_role(['ICT Staff']);

require_once '../classes/EquipmentService.php';
require_once '../classes/MaintenanceService.php';
require_once '../classes/ICTSupport.php';
require_once '../classes/SoftwareService.php';

$equipmentService = new EquipmentService();
$maintenanceService = new MaintenanceService();
$ictSupport = new ICTSupport();
$softwareService = new SoftwareService();

try {
    // Get statistics for reports
    $totalEquipment = $equipmentService->countEquipment();
    $allEquipmentList = $equipmentService->getAllEquipment();
    $availableEquipment = count($equipmentService->getAvailableEquipment());
    $borrowedEquipment = $totalEquipment - $availableEquipment;
    
    $maintenanceRecords = $maintenanceService->getAllMaintenance();
    $dueMaintenance = $maintenanceService->getDueMaintenance();
    $overdueMaintenance = $maintenanceService->getOverdueMaintenance();
    
    // Get support requests
    $supportRequests = $ictSupport->getAllRequests();
    // Since the table doesn't have a status column, all requests are considered pending
    $activeRequests = count($supportRequests);
    $resolvedRequests = 0;
    
    // Get software list
    $softwareList = $softwareService->getAllSoftware();
    $activeSoftware = count(array_filter($softwareList, fn($s) => $s['status'] === 'Active'));
    $expiringSoftware = count(array_filter($softwareList, function($s) {
        if (!isset($s['expiry_date'])) return false;
        $expiryDate = strtotime($s['expiry_date']);
        $today = strtotime('today');
        $thirtyDays = strtotime('+30 days', $today);
        return $expiryDate > $today && $expiryDate <= $thirtyDays;
    }));
    
} catch (Exception $e) {
    error_log('[ICT Reports Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $totalEquipment = $availableEquipment = $borrowedEquipment = 0;
    $allEquipmentList = [];
    $maintenanceRecords = $dueMaintenance = $overdueMaintenance = [];
    $supportRequests = [];
    $activeRequests = $resolvedRequests = 0;
    $softwareList = [];
    $activeSoftware = $expiringSoftware = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - ICT Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/ict_portal.css" rel="stylesheet">
</head>
<body class="bg-light ict-portal">
    <?php include '../includes/ict_sidebar.php'; ?>
    
    <!-- Mobile Menu Button -->
    <button class="btn btn-primary mobile-menu-btn d-md-none position-fixed" 
            style="top: 1rem; left: 1rem; z-index: 1060;">
        <i class="bi bi-list"></i>
    </button>

    <main class="main-content">
        <div class="container-fluid px-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-graph-up me-2 text-primary"></i>
                        ICT Reports
                    </h2>
                    <p class="text-muted mb-0">Comprehensive reports and analytics for ICT operations</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" onclick="printReport('all')">
                        <i class="bi bi-printer me-2"></i>Print All Reports
                    </button>
                    <button class="btn btn-success" onclick="exportReport('all')">
                        <i class="bi bi-download me-2"></i>Export All Reports
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Equipment</h6>
                                    <h3 class="mb-0"><?= $totalEquipment ?></h3>
                                    <small><?= $availableEquipment ?> available, <?= $borrowedEquipment ?> borrowed</small>
                                </div>
                                <i class="bi bi-laptop fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Maintenance</h6>
                                    <h3 class="mb-0"><?= count($maintenanceRecords) ?></h3>
                                    <small><?= count($dueMaintenance) ?> due, <?= count($overdueMaintenance) ?> overdue</small>
                                </div>
                                <i class="bi bi-tools fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Support Requests</h6>
                                    <h3 class="mb-0"><?= count($supportRequests) ?></h3>
                                    <small><?= $activeRequests ?> active, <?= $resolvedRequests ?> resolved</small>
                                </div>
                                <i class="bi bi-headset fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Software Licenses</h6>
                                    <h3 class="mb-0"><?= count($softwareList) ?></h3>
                                    <small><?= $activeSoftware ?> active, <?= $expiringSoftware ?> expiring</small>
                                </div>
                                <i class="bi bi-software fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Sections -->
            <div class="row">
                <!-- Equipment Report -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-laptop me-2"></i>Equipment Report
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-success"><?= $availableEquipment ?></h4>
                                        <small class="text-muted">Available</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-warning"><?= $borrowedEquipment ?></h4>
                                        <small class="text-muted">Borrowed</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-primary"><?= $totalEquipment ?></h4>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="printReport('equipment')">
                                    <i class="bi bi-printer me-1"></i>Print
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="exportReport('equipment')">
                                    <i class="bi bi-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Report -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-tools me-2"></i>Maintenance Report
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-warning"><?= count($dueMaintenance) ?></h4>
                                        <small class="text-muted">Due</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-danger"><?= count($overdueMaintenance) ?></h4>
                                        <small class="text-muted">Overdue</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-primary"><?= count($maintenanceRecords) ?></h4>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="printReport('maintenance')">
                                    <i class="bi bi-printer me-1"></i>Print
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="exportReport('maintenance')">
                                    <i class="bi bi-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Report -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-headset me-2"></i>Support Requests Report
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-info"><?= $activeRequests ?></h4>
                                        <small class="text-muted">Active</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-success"><?= $resolvedRequests ?></h4>
                                        <small class="text-muted">Resolved</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-primary"><?= count($supportRequests) ?></h4>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="printReport('support')">
                                    <i class="bi bi-printer me-1"></i>Print
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="exportReport('support')">
                                    <i class="bi bi-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Software Report -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-software me-2"></i>Software Licenses Report
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-success"><?= $activeSoftware ?></h4>
                                        <small class="text-muted">Active</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border-end">
                                        <h4 class="text-warning"><?= $expiringSoftware ?></h4>
                                        <small class="text-muted">Expiring</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-primary"><?= count($softwareList) ?></h4>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="printReport('software')">
                                    <i class="bi bi-printer me-1"></i>Print
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="exportReport('software')">
                                    <i class="bi bi-download me-1"></i>Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-activity me-2"></i>Recent Activities Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Recent Maintenance Activities</h6>
                                    <ul class="list-unstyled">
                                        <?php 
                                        $recentMaintenance = array_slice($maintenanceRecords, 0, 5);
                                        foreach ($recentMaintenance as $maintenance): 
                                        ?>
                                            <li class="mb-2">
                                                <i class="bi bi-tools text-primary me-2"></i>
                                                <strong><?= htmlspecialchars($maintenance['equipment_name'] ?? 'Unknown') ?></strong>
                                                <span class="badge bg-<?= match($maintenance['repair_status']) {
                                                    'Completed' => 'success',
                                                    'In Progress' => 'warning',
                                                    'Pending' => 'danger',
                                                    default => 'secondary'
                                                } ?> ms-2">
                                                    <?= htmlspecialchars($maintenance['repair_status']) ?>
                                                </span>
                                                <br><small class="text-muted"><?= date('M d, Y', strtotime($maintenance['maintenance_date'])) ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Recent Support Requests</h6>
                                    <ul class="list-unstyled">
                                        <?php 
                                        $recentSupport = array_slice($supportRequests, 0, 5);
                                        if (empty($recentSupport)): ?>
                                            <li class="text-muted">No recent support requests</li>
                                        <?php else:
                                            foreach ($recentSupport as $request): 
                                        ?>
                                            <li class="mb-2">
                                                <i class="bi bi-headset text-info me-2"></i>
                                                <strong><?= htmlspecialchars($request['nature_of_request'] ?? 'Support Request') ?></strong>
                                                <span class="badge bg-info ms-2">Pending</span>
                                                <br><small class="text-muted"><?= date('M d, Y', strtotime($request['request_date'])) ?></small>
                                            </li>
                                        <?php endforeach; 
                                        endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/ict_portal.js"></script>
    <script>
        function exportReport(type) {
            // Create export URLs based on type
            let exportUrl = '';
            switch(type) {
                case 'equipment':
                    exportUrl = 'export_equipment_report.php';
                    break;
                case 'maintenance':
                    exportUrl = 'export_maintenance_report.php';
                    break;
                case 'support':
                    exportUrl = 'export_support_report.php';
                    break;
                case 'software':
                    exportUrl = 'export_software_report.php';
                    break;
                case 'all':
                    exportUrl = 'export_all_reports.php';
                    break;
                default:
                    alert('Invalid report type');
                    return;
            }
            
            // Open export in new window
            window.open(exportUrl, '_blank');
        }

        function printReport(type) {
            // Build print content based on report type
            let printContent = '';
            let reportTitle = '';
            
            const currentDate = new Date().toLocaleString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit' 
            });

            // Common header for all reports
            const headerHTML = `
                <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #0d6efd;">
                    <h1 style="color: #0d6efd; margin-bottom: 10px;">
                        <i class="bi bi-graph-up"></i> ICT REPORTS
                    </h1>
                    <p style="color: #6c757d; font-size: 16px; margin: 0;">Computer Laboratory Resources Management System</p>
                </div>
            `;

            const footerHTML = `
                <div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #dee2e6; text-align: center; font-size: 12px; color: #6c757d;">
                    <p><strong>CLRMS - Computer Laboratory Resources Management System</strong></p>
                    <p>This is an official ICT report generated for operational review and analysis.</p>
                    <p>Generated on ${currentDate}</p>
                </div>
            `;

            switch(type) {
                case 'equipment':
                    reportTitle = 'Equipment Report';
                    printContent = buildEquipmentReport();
                    break;
                case 'maintenance':
                    reportTitle = 'Maintenance Report';
                    printContent = buildMaintenanceReport();
                    break;
                case 'support':
                    reportTitle = 'Support Requests Report';
                    printContent = buildSupportReport();
                    break;
                case 'software':
                    reportTitle = 'Software Licenses Report';
                    printContent = buildSoftwareReport();
                    break;
                case 'all':
                    reportTitle = 'Complete ICT Report';
                    printContent = buildCompleteReport();
                    break;
                default:
                    alert('Invalid report type');
                    return;
            }

            // Create print window
            const printWindow = window.open('', '_blank', 'width=1200,height=800');
            if (!printWindow) {
                alert('Please allow popups for this site to enable printing.');
                return;
            }

            const fullHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${reportTitle} - ICT Portal</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
                    <style>
                        @page {
                            size: A4;
                            margin: 1.5cm;
                        }
                        body {
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            padding: 30px;
                            background: #fff;
                        }
                        .report-section {
                            margin-bottom: 30px;
                            page-break-inside: avoid;
                        }
                        .report-section h3 {
                            color: #0d6efd;
                            border-bottom: 2px solid #0d6efd;
                            padding-bottom: 10px;
                            margin-bottom: 20px;
                        }
                        .stats-grid {
                            display: grid;
                            grid-template-columns: repeat(3, 1fr);
                            gap: 15px;
                            margin-bottom: 20px;
                        }
                        .stat-card {
                            border: 2px solid #dee2e6;
                            border-radius: 8px;
                            padding: 15px;
                            text-align: center;
                        }
                        .stat-card h4 {
                            font-size: 32px;
                            margin: 10px 0;
                            font-weight: bold;
                        }
                        .stat-card.success { border-color: #198754; color: #198754; }
                        .stat-card.warning { border-color: #ffc107; color: #ffc107; }
                        .stat-card.danger { border-color: #dc3545; color: #dc3545; }
                        .stat-card.primary { border-color: #0d6efd; color: #0d6efd; }
                        .stat-card.info { border-color: #0dcaf0; color: #0dcaf0; }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 15px;
                            border: 1px solid #dee2e6;
                        }
                        th {
                            background: #0d6efd;
                            color: white;
                            padding: 12px 8px;
                            text-align: left;
                            font-size: 12px;
                            border: 1px solid #0d6efd;
                            font-weight: 600;
                        }
                        td {
                            padding: 10px 8px;
                            border: 1px solid #dee2e6;
                            font-size: 11px;
                            vertical-align: top;
                        }
                        tbody tr:nth-child(even) {
                            background: #f8f9fa;
                        }
                        tbody tr:hover {
                            background: #e9ecef;
                        }
                        .badge {
                            display: inline-block;
                            padding: 4px 8px;
                            border-radius: 4px;
                            font-size: 11px;
                            font-weight: bold;
                        }
                        .badge-success { background: #198754; color: white; }
                        .badge-warning { background: #ffc107; color: #000; }
                        .badge-danger { background: #dc3545; color: white; }
                        .badge-info { background: #0dcaf0; color: #000; }
                        .badge-secondary { background: #6c757d; color: white; }
                        @media print {
                            .no-print { display: none !important; }
                            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
                        }
                        .print-button {
                            position: fixed;
                            top: 10px;
                            right: 10px;
                            padding: 10px 20px;
                            background: #0d6efd;
                            color: white;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                            font-size: 14px;
                            z-index: 1000;
                        }
                        .print-button:hover {
                            background: #0b5ed7;
                        }
                    </style>
                </head>
                <body>
                    <button class="print-button no-print" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                    ${headerHTML}
                    ${printContent}
                    ${footerHTML}
                </body>
                </html>
            `;

            printWindow.document.write(fullHTML);
            printWindow.document.close();

            // Auto-print after content loads
            printWindow.onload = function() {
                setTimeout(() => {
                    printWindow.print();
                }, 250);
            };
        }

        function buildEquipmentReport() {
            const totalEquipment = <?= $totalEquipment ?>;
            const availableEquipment = <?= $availableEquipment ?>;
            const borrowedEquipment = <?= $borrowedEquipment ?>;
            const equipmentData = <?= json_encode($allEquipmentList) ?>;

            let tableRows = '';
            equipmentData.forEach((item, index) => {
                const statusBadge = getStatusBadgeClass(item.status);
                tableRows += `
                    <tr>
                        <td style="text-align: center;">${index + 1}</td>
                        <td>${escapeHtml(item.name || 'N/A')}</td>
                        <td>${escapeHtml(item.category || 'N/A')}</td>
                        <td>${escapeHtml(item.serial_number || 'N/A')}</td>
                        <td>${escapeHtml(item.location || 'N/A')}</td>
                        <td><span class="badge badge-${statusBadge}">${escapeHtml(item.status || 'N/A')}</span></td>
                    </tr>
                `;
            });

            return `
                <div class="report-section">
                    <h3><i class="bi bi-laptop"></i> Equipment Report</h3>
                    <div class="stats-grid">
                        <div class="stat-card success">
                            <small>Available</small>
                            <h4>${availableEquipment}</h4>
                        </div>
                        <div class="stat-card warning">
                            <small>Borrowed</small>
                            <h4>${borrowedEquipment}</h4>
                        </div>
                        <div class="stat-card primary">
                            <small>Total Equipment</small>
                            <h4>${totalEquipment}</h4>
                        </div>
                    </div>
                    <p><strong>Summary:</strong> Complete list of all equipment in the Computer Laboratory Resources Management System.</p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 25%;">Equipment Name</th>
                                <th style="width: 15%;">Category</th>
                                <th style="width: 15%;">Serial Number</th>
                                <th style="width: 20%;">Location</th>
                                <th style="width: 15%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows || '<tr><td colspan="6" style="text-align: center;">No equipment found</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function buildMaintenanceReport() {
            const totalMaintenance = <?= count($maintenanceRecords) ?>;
            const dueMaintenance = <?= count($dueMaintenance) ?>;
            const overdueMaintenance = <?= count($overdueMaintenance) ?>;
            const maintenanceData = <?= json_encode($maintenanceRecords) ?>;

            let tableRows = '';
            maintenanceData.forEach((item, index) => {
                const statusBadge = item.repair_status === 'Completed' ? 'success' : 
                                   (item.repair_status === 'In Progress' ? 'warning' : 'danger');
                const maintenanceDate = item.maintenance_date ? formatDate(item.maintenance_date) : 'N/A';
                const dueDate = item.due_date ? formatDate(item.due_date) : 'N/A';
                
                tableRows += `
                    <tr>
                        <td style="text-align: center;">${index + 1}</td>
                        <td>${escapeHtml(item.equipment_name || 'N/A')}</td>
                        <td>${escapeHtml(item.type || 'N/A')}</td>
                        <td>${escapeHtml(item.issue_description ? item.issue_description.substring(0, 50) + '...' : 'N/A')}</td>
                        <td>${maintenanceDate}</td>
                        <td>${dueDate}</td>
                        <td><span class="badge badge-${statusBadge}">${escapeHtml(item.repair_status || 'N/A')}</span></td>
                    </tr>
                `;
            });

            return `
                <div class="report-section">
                    <h3><i class="bi bi-tools"></i> Maintenance Report</h3>
                    <div class="stats-grid">
                        <div class="stat-card warning">
                            <small>Due</small>
                            <h4>${dueMaintenance}</h4>
                        </div>
                        <div class="stat-card danger">
                            <small>Overdue</small>
                            <h4>${overdueMaintenance}</h4>
                        </div>
                        <div class="stat-card primary">
                            <small>Total Records</small>
                            <h4>${totalMaintenance}</h4>
                        </div>
                    </div>
                    <p><strong>Summary:</strong> Complete list of all maintenance and repair activities for ICT equipment.</p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 20%;">Equipment</th>
                                <th style="width: 10%;">Type</th>
                                <th style="width: 25%;">Issue Description</th>
                                <th style="width: 12%;">Maintenance Date</th>
                                <th style="width: 12%;">Due Date</th>
                                <th style="width: 12%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows || '<tr><td colspan="7" style="text-align: center;">No maintenance records found</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function buildSupportReport() {
            const totalSupport = <?= count($supportRequests) ?>;
            const activeRequests = <?= $activeRequests ?>;
            const resolvedRequests = <?= $resolvedRequests ?>;
            const supportData = <?= json_encode($supportRequests) ?>;

            let tableRows = '';
            supportData.forEach((item, index) => {
                const requestDate = item.request_date ? formatDate(item.request_date) : 'N/A';
                const requestTime = item.request_time || 'N/A';
                const natureOfRequest = item.nature_of_request || 'N/A';
                const actionTaken = item.action_taken || 'Not yet processed';
                
                tableRows += `
                    <tr>
                        <td style="text-align: center;">${index + 1}</td>
                        <td>${escapeHtml(item.requester_name || 'N/A')}</td>
                        <td>${escapeHtml(item.department || 'N/A')}</td>
                        <td>${escapeHtml(natureOfRequest.length > 50 ? natureOfRequest.substring(0, 50) + '...' : natureOfRequest)}</td>
                        <td>${escapeHtml(actionTaken.length > 50 ? actionTaken.substring(0, 50) + '...' : actionTaken)}</td>
                        <td>${requestDate}<br><small>${escapeHtml(requestTime)}</small></td>
                        <td><span class="badge badge-info">Pending</span></td>
                    </tr>
                `;
            });

            return `
                <div class="report-section">
                    <h3><i class="bi bi-headset"></i> Support Requests Report</h3>
                    <div class="stats-grid">
                        <div class="stat-card info">
                            <small>Active</small>
                            <h4>${activeRequests}</h4>
                        </div>
                        <div class="stat-card success">
                            <small>Resolved</small>
                            <h4>${resolvedRequests}</h4>
                        </div>
                        <div class="stat-card primary">
                            <small>Total Requests</small>
                            <h4>${totalSupport}</h4>
                        </div>
                    </div>
                    <p><strong>Summary:</strong> Complete list of all ICT support requests submitted by users.</p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 15%;">Requester</th>
                                <th style="width: 12%;">Department</th>
                                <th style="width: 25%;">Nature of Request</th>
                                <th style="width: 25%;">Action Taken</th>
                                <th style="width: 12%;">Request Date/Time</th>
                                <th style="width: 8%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows || '<tr><td colspan="7" style="text-align: center;">No support requests found</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function buildSoftwareReport() {
            const totalSoftware = <?= count($softwareList) ?>;
            const activeSoftware = <?= $activeSoftware ?>;
            const expiringSoftware = <?= $expiringSoftware ?>;
            const softwareData = <?= json_encode($softwareList) ?>;

            let tableRows = '';
            softwareData.forEach((item, index) => {
                const statusBadge = item.status === 'Active' ? 'success' : 
                                   (item.status === 'Expired' ? 'danger' : 'secondary');
                const purchaseDate = item.purchase_date ? formatDate(item.purchase_date) : 'N/A';
                const expiryDate = item.expiry_date ? formatDate(item.expiry_date) : 'N/A';
                
                tableRows += `
                    <tr>
                        <td style="text-align: center;">${index + 1}</td>
                        <td>${escapeHtml(item.software_name || 'N/A')}</td>
                        <td>${escapeHtml(item.license_key ? item.license_key.substring(0, 20) + '...' : 'N/A')}</td>
                        <td>${escapeHtml(item.vendor || 'N/A')}</td>
                        <td>${purchaseDate}</td>
                        <td>${expiryDate}</td>
                        <td><span class="badge badge-${statusBadge}">${escapeHtml(item.status || 'N/A')}</span></td>
                    </tr>
                `;
            });

            return `
                <div class="report-section">
                    <h3><i class="bi bi-software"></i> Software Licenses Report</h3>
                    <div class="stats-grid">
                        <div class="stat-card success">
                            <small>Active</small>
                            <h4>${activeSoftware}</h4>
                        </div>
                        <div class="stat-card warning">
                            <small>Expiring Soon</small>
                            <h4>${expiringSoftware}</h4>
                        </div>
                        <div class="stat-card primary">
                            <small>Total Licenses</small>
                            <h4>${totalSoftware}</h4>
                        </div>
                    </div>
                    <p><strong>Summary:</strong> Complete list of all software licenses managed by the ICT department.</p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 20%;">Software Name</th>
                                <th style="width: 18%;">License Key</th>
                                <th style="width: 15%;">Vendor</th>
                                <th style="width: 12%;">Purchase Date</th>
                                <th style="width: 12%;">Expiry Date</th>
                                <th style="width: 10%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows || '<tr><td colspan="7" style="text-align: center;">No software licenses found</td></tr>'}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function buildCompleteReport() {
            return `
                ${buildEquipmentReport()}
                <div style="page-break-after: always;"></div>
                ${buildMaintenanceReport()}
                <div style="page-break-after: always;"></div>
                ${buildSupportReport()}
                <div style="page-break-after: always;"></div>
                ${buildSoftwareReport()}
            `;
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // Helper function to format dates
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'N/A';
            
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }

        // Helper function to get status badge class
        function getStatusBadgeClass(status) {
            const statusMap = {
                'Available': 'success',
                'Borrowed': 'warning',
                'Maintenance': 'info',
                'Repair': 'danger',
                'Disposed': 'secondary',
                'Retired': 'secondary',
                'Transferred': 'info'
            };
            return statusMap[status] || 'secondary';
        }
    </script>
</body>
</html>
