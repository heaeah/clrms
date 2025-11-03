<?php
require_once '../includes/auth.php';
require_role(['Lab Admin', 'Student Assistant']);
require_once '../classes/BorrowRequest.php';
require_once '../classes/Equipment.php';

$borrowObj = new BorrowRequest();
$equipmentObj = new Equipment();

// Handle status update (Approve/Reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $status = $_POST['action'] === 'approve' ? 'Approved' : 'Denied';
    $borrowObj->updateBorrowRequestStatus($_POST['request_id'], $status);
    set_flash('success', "Request successfully {$status}.");
    header("Location: borrow.php");
    exit;
}

// Auto-generate control number
$lastRequest = $borrowObj->getLastBorrowRequest();
$lastId = $lastRequest['last_id'] ?? 0;
$controlNumber = "CONTROL-" . str_pad($lastId + 1, 3, "0", STR_PAD_LEFT);


$borrowRequests = ($_SESSION['role'] === 'Lab Admin' || $_SESSION['role'] === 'Student Assistant')
    ? $borrowObj->getAllBorrowRequests()
    : $borrowObj->getUserBorrowRequests($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrow Equipment - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        #signature-pad {
            border: 2px dashed #ccc;
            border-radius: 5px;
            height: 150px;
            background: #fff;
        }
    </style>
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <?php show_flash(); ?>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 text-primary"><i class="bi bi-box-arrow-in-down"></i> Borrow Equipment</h1>
        </div>

        <!-- Borrow Request Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <strong>Submit Borrow Request</strong>
            </div>
            <div class="card-body">
                <form method="POST" onsubmit="captureSignature()">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Control Number</label>
                            <input type="text" name="control_number" class="form-control" value="<?= $controlNumber ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Request</label>
                            <input type="date" name="date_requested" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Borrower Name</label>
                            <input type="text" name="borrower_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course & Year</label>
                            <input type="text" name="course_year" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teacher Signature</label>
                            <div id="signature-pad"></div>
                            <input type="hidden" name="teacher_signature" id="teacher_signature">
                            <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="clearSignature()">Clear Signature</button>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date & Time Needed</label>
                            <input type="datetime-local" name="datetime_needed" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Released By</label>
                            <input type="text" name="released_by" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Purpose</label>
                            <input type="text" name="purpose" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Location of Use</label>
                            <input type="text" name="location" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Borrow Date</label>
                            <input type="date" name="borrow_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Return Date</label>
                            <input type="date" name="return_date" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Choose Equipment</label>
                            <select name="equipment_id" class="form-select" required>
                                <option value="" disabled selected>Select equipment</option>
                                <?php foreach ($equipmentObj->getAvailableEquipment() as $equip): ?>
                                    <option value="<?= $equip['id'] ?>"><?= htmlspecialchars($equip['name']) ?> (<?= htmlspecialchars($equip['serial_number']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send-check"></i> Submit Request
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Borrow Requests Table -->
        <h2 class="h5 text-primary mb-3"><i class="bi bi-list-check"></i> Borrow Requests</h2>
        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle text-center">
                    <thead class="table-dark">
                    <tr>
                        <th>Control No.</th>
                        <th>Borrower</th>
                        <th>Subject</th>
                        <th>Equipment</th>
                        <th>Date Needed</th>
                        <th>Qty</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($borrowRequests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['control_number']) ?></td>
                            <td><?= htmlspecialchars($req['borrower_name']) ?></td>
                            <td><?= htmlspecialchars($req['subject']) ?></td>
                            <td><?= htmlspecialchars($req['equipment_name']) ?></td>
                            <td><?= htmlspecialchars($req['datetime_needed']) ?></td>
                            <td><?= htmlspecialchars($req['quantity']) ?></td>
                            <td><?= htmlspecialchars($req['purpose']) ?></td>
                            <td>
                                <?php
                                $statusClass = match ($req['status']) {
                                    'Approved' => 'success',
                                    'Denied' => 'danger',
                                    default => 'warning text-dark'
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($req['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($req['status'] === 'Pending'): ?>
                                    <form method="POST" class="d-inline-block">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.6/dist/signature_pad.umd.min.js"></script>
<script>
    const canvas = document.createElement('canvas');
    document.getElementById('signature-pad').appendChild(canvas);
    canvas.width = document.getElementById('signature-pad').offsetWidth;
    canvas.height = 150;

    const signaturePad = new SignaturePad(canvas);

    function captureSignature() {
        if (!signaturePad.isEmpty()) {
            document.getElementById('teacher_signature').value = signaturePad.toDataURL();
        }
    }

    function clearSignature() {
        signaturePad.clear();
    }
</script>
</body>
</html>
