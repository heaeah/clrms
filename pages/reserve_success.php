<?php
require_once '../includes/auth.php';
$fromPortal = isset($_GET['from']) && $_GET['from'] === 'portal';
$trackingCode = isset($_GET['tracking_code']) ? $_GET['tracking_code'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Successful</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="alert alert-success text-center">
            <h2>Reservation Submitted!</h2>
            <p>Your lab reservation has been successfully submitted.</p>
            <?php if ($trackingCode): ?>
                <div class="alert alert-info mt-3">Your Tracking Code: <strong><?= htmlspecialchars($trackingCode) ?></strong></div>
                <p class="text-muted">Please save this code to track your reservation status.</p>
            <?php endif; ?>
            <?php if ($fromPortal): ?>
                <a href="/clrms/pages/borrowers_portal.php" class="btn btn-primary mt-3">Back to Borrowers Portal</a>
            <?php else: ?>
                <a href="/clrms/pages/dashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 