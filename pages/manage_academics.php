<?php
require_once '../includes/auth.php';
require_once '../classes/Database.php';

$database = new Database();
$pdo = $database->getConnection();

$success = '';
$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $type = $_POST['type'];
        $name = trim($_POST['name']);
        if (empty($name)) {
            throw new Exception("Name cannot be empty.");
        }
        // Map form type to DB table
        $tableMap = [
            'course' => 'courses',
            'year' => 'years',
            'section' => 'sections',
            'department' => 'departments'
        ];
        if (!array_key_exists($type, $tableMap)) {
            throw new Exception("Invalid category.");
        }
        $stmt = $pdo->prepare("INSERT INTO {$tableMap[$type]} (name) VALUES (?)");
        $stmt->execute([$name]);
        $pdo->commit();
        $success = ucfirst($type) . " added successfully!";
    } catch (Exception $e) {
        // What: Error during academic info form submission
        // Why: Validation error, DB error, etc.
        // How: Log error and show user-friendly message
        $pdo->rollBack();
        error_log('[Manage Academics Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Academic Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">Manage Courses, Years, Sections & Departments</h2>

    <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Select Type</label>
            <select name="type" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="course">Course</option>
                <option value="year">Year</option>
                <option value="section">Section</option>
                <option value="department">Department</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" placeholder="Enter Name" required>
        </div>
        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary">Add Entry</button>
        </div>
    </form>

    <hr class="my-5">

    <div class="row">
        <?php
        $tables = ['courses' => 'Courses', 'years' => 'Years', 'sections' => 'Sections', 'departments' => 'Departments'];
        foreach ($tables as $table => $label):
            try {
                $stmt = $pdo->query("SELECT * FROM $table ORDER BY name");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // What: Error fetching academic info list
                // Why: DB error, etc.
                // How: Log error and show user-friendly message
                error_log('[Manage Academics Fetch Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
                $rows = [];
            }
            ?>
            <div class="col-md-6 mb-4">
                <h5><?= $label ?></h5>
                <ul class="list-group">
                    <?php foreach ($rows as $row): ?>
                        <li class="list-group-item"><?= htmlspecialchars($row['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>