<?php
require_once '../includes/auth.php';
require_role(['Lab Admin', 'Student Assistant']);
require_once '../classes/Database.php';

$db = (new Database())->getConnection();

// Handle add lab
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lab'])) {
    $stmt = $db->prepare("INSERT INTO labs (lab_name, location, capacity) VALUES (?, ?, ?)");
    $stmt->execute([
        $_POST['lab_name'],
        $_POST['location'],
        $_POST['capacity']
    ]);
    set_flash('success', 'Lab added successfully.');
    header("Location: lab_details.php");
    exit;
}

// Fetch all labs
$labs = $db->query("SELECT * FROM labs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Helper: Check if a lab is occupied (only if now is between start and end time today)
function is_lab_occupied_now($db, $lab_id) {
    $today = date('Y-m-d');
    $now = date('H:i:s');
    $stmt = $db->prepare("SELECT COUNT(*) FROM lab_reservations WHERE lab_id = ? AND status = 'Approved' AND date_reserved = ? AND time_start <= ? AND time_end > ?");
    $stmt->execute([$lab_id, $today, $now, $now]);
    $count = $stmt->fetchColumn();
    // Debug output
    if (isset($_GET['debug'])) {
        echo "<pre>lab_id: $lab_id\ntoday: $today\nnow: $now\ncount: $count</pre>";
    }
    return $count > 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Labs - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-primary"><i class="bi bi-building-fill-gear"></i> Labs</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLabModal">
                <i class="bi bi-plus-circle"></i> Add Lab
            </button>
        </div>

        <div class="card shadow rounded-3">
            <div class="card-body">
                <?php if (empty($labs)): ?>
                    <p class="text-muted">No labs found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center">
                            <thead class="table-primary">
                            <tr>
                                <th>ID</th>
                                <th>Lab Name</th>
                                <th>Location</th>
                                <th>Capacity</th>
                                <th>Occupancy</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($labs as $lab): ?>
                                <tr>
                                    <td><?= htmlspecialchars($lab['id']) ?></td>
                                    <td><?= htmlspecialchars($lab['lab_name']) ?></td>
                                    <td><?= htmlspecialchars($lab['location']) ?></td>
                                    <td><?= htmlspecialchars($lab['capacity']) ?></td>
                                    <td>
                                        <?php if (is_lab_occupied_now($db, $lab['id'])): ?>
                                            <span class="badge bg-danger">Occupied</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                    <?php
                    // Debug: Show today's reservations for each lab
                    if (isset($_GET['debug'])) {
                        echo '<h5>Today\'s Reservations (Debug)</h5>';
                        foreach ($labs as $lab) {
                            echo '<strong>Lab: ' . htmlspecialchars($lab['lab_name']) . ' (ID: ' . $lab['id'] . ')</strong><br>';
                            $today = date('Y-m-d');
                            $stmt = $db->prepare("SELECT * FROM lab_reservations WHERE lab_id = ? AND date_reserved = ?");
                            $stmt->execute([$lab['id'], $today]);
                            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if (empty($rows)) {
                                echo '<em>No reservations for today.</em><br>';
                            } else {
                                echo '<table class="table table-sm table-bordered"><thead><tr><th>ID</th><th>Status</th><th>Start</th><th>End</th><th>Purpose</th></tr></thead><tbody>';
                                foreach ($rows as $row) {
                                    echo '<tr>';
                                    echo '<td>' . $row['id'] . '</td>';
                                    echo '<td>' . $row['status'] . '</td>';
                                    echo '<td>' . $row['time_start'] . '</td>';
                                    echo '<td>' . $row['time_end'] . '</td>';
                                    echo '<td>' . htmlspecialchars($row['purpose']) . '</td>';
                                    echo '</tr>';
                                }
                                echo '</tbody></table>';
                            }
                            echo '<hr>';
                        }
                    }
                    ?>
            </div>
        </div>
    </div>
</main>

<!-- Add Lab Modal -->
<div class="modal fade" id="addLabModal" tabindex="-1" aria-labelledby="addLabModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content shadow rounded-3">
            <div class="modal-header">
                <h5 class="modal-title" id="addLabModalLabel">Add New Lab</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="add_lab" value="1">
                <div class="mb-3">
                    <label for="lab_name" class="form-label">Lab Name</label>
                    <input type="text" name="lab_name" id="lab_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" name="location" id="location" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="capacity" class="form-label">Capacity</label>
                    <input type="number" name="capacity" id="capacity" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Save Lab</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
