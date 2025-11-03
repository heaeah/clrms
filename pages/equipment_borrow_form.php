<?php
require_once '../classes/Equipment.php';
try {
    $equipment = new Equipment();
    $equip = $equipment->getEquipmentById($_GET['id'] ?? 0);
    if (!$equip) {
        throw new Exception("Invalid equipment selected.");
    }
} catch (Exception $e) {
    // What: Error fetching equipment for borrow form
    // Why: Invalid ID, DB error, etc.
    // How: Log error and show user-friendly message
    error_log('[Equipment Borrow Form Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Borrow Form - <?= htmlspecialchars($equip['name']) ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/equipment_borrow_form.css" rel="stylesheet">

</head>
<body>

<div class="container py-5">
    <div class="card form-card shadow bg-white p-4">
        <div class="form-section-title">
            <i class="bi bi-journal-arrow-up"></i> Equipment Borrowing Form
        </div>
        <h4 class="text-secondary mb-4"><?= htmlspecialchars($equip['name']) ?></h4>

        <form action="submit_borrow.php" method="POST">
            <input type="hidden" name="equipment_id" value="<?= $equip['id'] ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label>Control Number</label>
                    <input type="text" name="control_number" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Date of Request</label>
                    <input type="date" name="date_requested" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Borrower Name</label>
                    <input type="text" name="borrower_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Course & Year</label>
                    <input type="text" name="course_year" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Subject</label>
                    <input type="text" name="subject" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Signature of Subject Teacher</label>
                    <div class="border rounded p-2" style="background: #f9f9f9;">
                        <canvas id="signature-pad" width="500" height="150" style="border: 1px solid #ccc;"></canvas>
                        <input type="hidden" name="signature_image" id="signature_image">
                        <div class="mt-2 text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-signature">Clear</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label>Date & Time Needed</label>
                    <input type="datetime-local" name="datetime_needed" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Purpose</label>
                    <input type="text" name="purpose" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Location of Use</label>
                    <input type="text" name="location_of_use" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Released By</label>
                    <input type="text" name="released_by" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Return Date</label>
                    <input type="date" name="return_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Quantity</label>
                    <input type="number" name="quantity" class="form-control" min="1" required>
                </div>
                <div class="col-12">
                    <label>Equipment Description</label>
                    <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($equip['remarks']) ?></textarea>
                </div>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="btn btn-success w-100 py-2">
                    <i class="bi bi-send-check"></i> Submit Borrow Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script src="../assets/js/equipment_borrow_form.js"></script>
</body>
</html>
