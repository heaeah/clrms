<?php
require_once '../includes/auth.php';
require_role(['ICT Staff']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Accomplishment - ICT Portal</title>
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
                        <i class="bi bi-clipboard-check me-2 text-primary"></i>
                        Job Accomplishment
                    </h2>
                    <p class="text-muted mb-0">Track and manage ICT staff accomplishments</p>
                </div>
            </div>

            <!-- Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm mx-auto" style="max-width: 1100px;">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Job Accomplishment Report</h5>
                        </div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label class="form-label">Completed by</label>
                                    <input type="text" class="form-control" name="completed_by" placeholder="ICT Personnel Assigned to the Job">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date Completed</label>
                                    <input type="date" class="form-control" name="date_completed">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Concern/Diagnose</label>
                                    <input type="text" class="form-control" name="concern_diagnose">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Work Performed</label>
                                    <input type="text" class="form-control" name="work_performed">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Recommendation</label>
                                    <input type="text" class="form-control" name="recommendation">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <input type="text" class="form-control" name="status">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Noted by</label>
                                    <input type="text" class="form-control" value="ENGR. RUSSEL M. DELA TORRE, PhD" readonly>
                                    <small class="text-muted">ICT Director</small>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="ict_portal.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Cancel</a>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Report</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/ict_portal.js"></script>
</body>
</html>
