<?php
// pages/ict_support_form.php
require_once '../includes/auth.php';
require_once '../classes/ICTSupport.php';
require_once '../classes/MasterlistService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ictSupport = new ICTSupport();
        $masterlistService = new MasterlistService();
        
        // Validate department against masterlist
        $department = $_POST['department'] ?? '';
        if (!empty($department) && !$masterlistService->validateMasterlistValue('departments_master', 'name', $department)) {
            $error = 'Invalid department selected. Please choose from the available options.';
        } else {
            // Prepare form data
            $formData = [
                'requester_name' => $_POST['requester_name'] ?? '',
                'department' => $department,
                'request_date' => date('Y-m-d'),
                'request_time' => date('H:i:s'),
                'nature_of_request' => $_POST['purpose'] ?? '',
                'action_taken' => 'Request submitted via form',
                'photo' => null
            ];
        }
        
        if (!isset($error)) {
            // Handle file upload if provided
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/ict_support/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                    $formData['photo'] = 'ict_support/' . $fileName;
                }
            }
            
            // Save the request
            $result = $ictSupport->addRequest($formData);
            
            if ($result) {
                // Redirect to ICT portal with success message
                header("Location: ict_portal.php?success=1");
                exit;
            } else {
                $error = "Failed to submit request. Please try again.";
            }
        }
    } catch (Exception $e) {
        error_log('[ICT Support Form Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        $error = "An error occurred while submitting the request.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request for ICT Support Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/ict_support_form.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="ict-bond-paper">
    <div class="ict-header-box">
        <div class="ict-header-logo">
            <img src="../assets/images/chmsu_logo.jpg" alt="CHMSU Logo" class="ict-logo">
        </div>
        <div class="ict-header-title">Request for ICT Support Services</div>
        <div class="ict-doc-info">
            <table>
                <tr><td><strong>Document Code:</strong></td><td>F.01-ICT-CHMSU</td></tr>
                <tr><td><strong>Revision No.:</strong></td><td>0</td></tr>
                <tr><td><strong>Effective Date:</strong></td><td>April 1, 2024</td></tr>
                <tr><td><strong>Page:</strong></td><td>1 of 1</td></tr>
            </table>
        </div>
    </div>
    <div class="ict-divider"></div>
    <div class="ict-form-section">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center" style="margin: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Requester Information -->
            <div class="ict-form-row">
                <div class="row">
                    <div class="col-md-6">
                        <label class="ict-form-label" for="requester_name">Requester Name:</label>
                        <input type="text" class="ict-form-underline-input" name="requester_name" id="requester_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ict-form-label" for="department">Department:</label>
                        <select class="ict-form-underline-input" name="department" id="department" required>
                            <?php 
                            $masterlistService = new MasterlistService();
                            echo $masterlistService->getDepartmentOptions();
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="ict-form-row">
                <div class="ict-borrow-row">
                    <span class="ict-borrow-num">1.0</span>
                    <span class="ict-borrow-box">BORROW</span>
                </div>
                <label class="ict-form-label" for="purpose">Purpose:</label>
                <input type="text" class="ict-form-underline-input" name="purpose" id="purpose" required>
                <label class="ict-form-label" for="duration">Duration:</label>
                <input type="text" class="ict-form-underline-input" name="duration" id="duration">
                <label class="ict-form-label" for="venue">Venue/Room:</label>
                <input type="text" class="ict-form-underline-input" name="venue" id="venue">
            </div>
            
            <!-- Photo Upload -->
            <div class="ict-form-row">
                <label class="ict-form-label" for="photo">Supporting Photo (Optional):</label>
                <input type="file" class="form-control" name="photo" id="photo" accept="image/*">
            </div>
            <div class="table-responsive">
                <table class="table ict-table" id="ictItemsTable">
                    <thead>
                    <tr>
                        <th colspan="2">Date/Time</th>
                        <th rowspan="2">Quantity</th>
                        <th rowspan="2">Item Details</th>
                        <th rowspan="2">Status/ Remarks</th>
                    </tr>
                    <tr>
                        <th>Borrowed</th>
                        <th>Returned</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><input type="text" class="ict-form-underline-input" name="borrowed[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="returned[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="quantity[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="item_details[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="remarks[]"></td>
                    </tr>
                    <tr>
                        <td><input type="text" class="ict-form-underline-input" name="borrowed[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="returned[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="quantity[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="item_details[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="remarks[]"></td>
                    </tr>
                    <tr>
                        <td><input type="text" class="ict-form-underline-input" name="borrowed[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="returned[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="quantity[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="item_details[]"></td>
                        <td><input type="text" class="ict-form-underline-input" name="remarks[]"></td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="ict-btn-row">
                <button type="button" class="btn btn-secondary cancel-btn" id="cancelBtn">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary add-row-btn" id="addRowBtn">
                    <i class="bi bi-plus-circle"></i> Add Row
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle-fill"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>
<script src="../assets/js/ict_support_form.js"></script>
</body>
</html> 