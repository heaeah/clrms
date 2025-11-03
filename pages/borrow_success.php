<?php
require_once '../includes/auth.php'; // Optional: If user authentication is needed

$controlNumber = $_GET['control'] ?? null;
$fromPortal = isset($_GET['from']) && $_GET['from'] === 'portal';
$trackingCode = $_GET['tracking_code'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrower's Slip Submitted</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/borrow_success.css" rel="stylesheet">
</head>
<body>

<div class="confirmation-box text-center">
    <div class="confirmation-icon mb-3">
        <i class="bi bi-check-circle-fill"></i>
    </div>
    <h2 class="mb-3 text-success">Borrower's Slip Submitted</h2>

    <?php if ($controlNumber): ?>
        <p class="lead">Control Number: <strong><?= htmlspecialchars($controlNumber) ?></strong></p>
    <?php else: ?>
        <p class="lead">Your request has been submitted successfully.</p>
    <?php endif; ?>
    <?php if ($trackingCode): ?>
        <div class="alert alert-info mt-3">
            <strong>Tracking Code:</strong> <span style="font-size:1.2em;letter-spacing:2px;"><?= htmlspecialchars($trackingCode) ?></span><br>
            <small>Save this code to track your request status in the portal.</small>
        </div>
    <?php endif; ?>

    <a href="borrower_slip_form.php<?= $fromPortal ? '?from=portal' : '' ?>" class="btn btn-outline-primary mt-4">Submit Another</a>
    <?php if ($fromPortal): ?>
        <a href="/clrms/pages/borrowers_portal.php" class="btn btn-primary mt-4 ms-2">Back to Borrowers Portal</a>
    <?php else: ?>
        <a href="/clrms/pages/dashboard.php" class="btn btn-primary mt-4 ms-2">Go to Dashboard</a>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="../assets/js/borrow_success.js"></script>
</body>
</html>