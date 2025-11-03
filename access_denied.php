<?php
require_once '../includes/auth.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <?php show_flash(); ?>
    <div class="alert alert-danger">
        <h4>Access Denied</h4>
        <p>You do not have permission to view this page.</p>
        <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
