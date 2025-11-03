<?php
require_once '../includes/auth.php';
require_role(['Lab Admin', 'Chairperson']);

require_once '../classes/Database.php';

$db = (new Database())->getConnection();

$filterAction = $_GET['action'] ?? '';
$filterFrom = $_GET['from'] ?? '';
$filterTo = $_GET['to'] ?? '';
$export = isset($_GET['export']);

$query = "
    SELECT logs.*, 
           u.username AS user_name,
           e.name AS equipment_name
    FROM equipment_logs logs
    LEFT JOIN users u ON logs.deleted_by = u.id
    LEFT JOIN equipment e ON logs.equipment_id = e.id
    WHERE 1
";

$params = [];

if ($filterAction !== '') {
    $query .= " AND logs.action = :action";
    $params[':action'] = $filterAction;
}

if ($filterFrom !== '') {
    $query .= " AND DATE(logs.timestamp) >= :from";
    $params[':from'] = $filterFrom;
}

if ($filterTo !== '') {
    $query .= " AND DATE(logs.timestamp) <= :to";
    $params[':to'] = $filterTo;
}

$query .= " ORDER BY logs.timestamp DESC";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // What: Error fetching equipment logs
    // Why: DB error, etc.
    // How: Log error and show user-friendly message
    error_log('[Equipment Logs Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $logs = [];
}

if ($export) {
    try {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="equipment_logs.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Equipment Name', 'Action', 'User', 'From Location', 'To Location',
            'Authorized By', 'Transfer Date', 'Changes', 'Timestamp'
        ]);
        foreach ($logs as $log) {
            $changes = $log['previous_values'] ?? '';
            fputcsv($output, [
                $log['equipment_name'] ?? 'N/A',
                $log['action'],
                $log['user_name'] ?? 'N/A',
                $log['from_location'] ?? '',
                $log['transferred_to'] ?? '',
                $log['authorized_by'] ?? '',
                $log['transfer_date'] ?? '',
                $changes,
                $log['timestamp']
            ]);
        }
        fclose($output);
        exit;
    } catch (Exception $e) {
        // What: Error exporting equipment logs to CSV
        // Why: File system error, etc.
        // How: Log error and show user-friendly message
        error_log('[Equipment Logs Export Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        echo 'Error exporting logs: ' . htmlspecialchars($e->getMessage());
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Equipment Logs - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/equipment_logs.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <h1 class="h3 text-primary mb-4"><i class="bi bi-clock-history"></i> Equipment Logs</h1>

        <form class="row g-3 mb-4" method="GET">
            <div class="col-md-3">
                <label class="form-label">Action</label>
                <select name="action" class="form-select">
                    <option value="">All</option>
                    <option value="Updated" <?= $filterAction == 'Updated' ? 'selected' : '' ?>>Updated</option>
                    <option value="Transferred" <?= $filterAction == 'Transferred' ? 'selected' : '' ?>>Transferred</option>
                    <option value="Archived" <?= $filterAction == 'Archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($filterFrom) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($filterTo) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2"><i class="bi bi-funnel"></i> Filter</button>
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 1])) ?>" class="btn btn-success">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
        </form>

        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead class="table-dark">
                    <tr>
                        <th>Equipment Name</th>
                        <th>Action</th>
                        <th>User</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Authorized By</th>
                        <th>Transfer Date</th>
                        <th>Changes</th>
                        <th>Timestamp</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="9">No logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['equipment_name'] ?? 'N/A') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td><?= htmlspecialchars($log['user_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($log['from_location'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($log['transferred_to'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($log['authorized_by'] ?? '—') ?></td>
                                <td><?= $log['transfer_date'] ? date('Y-m-d', strtotime($log['transfer_date'])) : '—' ?></td>
                                <td>
                                    <?php
                                    if ($log['previous_values']) {
                                        $changes = json_decode($log['previous_values'], true);
                                        if (is_array($changes) && count($changes) > 0) {
                                            echo "<ul class='text-start mb-0'>";
                                            foreach ($changes as $field => $value) {
                                                echo "<li><strong>" . htmlspecialchars($field) . "</strong>: " . htmlspecialchars($value) . "</li>";
                                            }
                                            echo "</ul>";
                                        } else {
                                            echo '—';
                                        }
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td><?= date('Y-m-d H:i:s', strtotime($log['timestamp'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/equipment_logs.js"></script>
</body>
</html>
