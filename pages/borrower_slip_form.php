<?php
require_once '../includes/auth.php';
require_once '../classes/Database.php';
require_once '../classes/Equipment.php';
require_once __DIR__ . '/../includes/send_mail.php';

$database = new Database();
$pdo = $database->getConnection();

$equipmentObj = new Equipment();
$equipmentList = $equipmentObj->getAllEquipment();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['equipment_ids'])) {
        die("Error: Please add at least one equipment item.");
    }
    try {
        // Check if all selected equipment are available
        $placeholders = implode(',', array_fill(0, count($_POST['equipment_ids']), '?'));
        $stmt = $pdo->prepare("SELECT id FROM equipment WHERE id IN ($placeholders) AND status = 'Available'");
        $stmt->execute($_POST['equipment_ids']);
        $availableIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($availableIds) !== count($_POST['equipment_ids'])) {
            throw new Exception("One or more selected equipment items are not available for borrowing.");
        }
        $pdo->beginTransaction();
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $control_number = $_POST['control_number'];
        $date_requested = $_POST['date_requested'];
        $borrower_type = $_POST['borrower_type'];
        
        // Handle borrower name based on type
        if ($borrower_type === 'Office/Department') {
            $borrower_name = $_POST['borrower_office_dept'];
            $borrower_email = $_POST['borrower_email'] ?? '';
        } else {
        $borrower_name = $_POST['borrower_name'];
            $borrower_email = $_POST['borrower_email'] ?? '';
        }
        
        if (!$borrower_email || !filter_var($borrower_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("A valid email address is required.");
        }
        $released_by = $_POST['released_by'];
        $purpose = $_POST['purpose'];
        $location_of_use = $_POST['location_of_use'];
        if ($location_of_use === 'Others' && !empty($_POST['location_of_use_other'])) {
            $location_of_use = $_POST['location_of_use_other'];
        }
        $borrow_start = $_POST['borrow_start'] ?? '';
        $borrow_end = $_POST['borrow_end'] ?? '';
        if (!$borrow_start || !$borrow_end) {
            throw new Exception("Borrow start and end date/time are required.");
        }
        $status = 'Pending';
        // Handle ID picture upload
        $id_picture_path = null;
        if (isset($_FILES['id_picture']) && $_FILES['id_picture']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['id_picture']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];
            if (!in_array($ext, $allowed)) {
                throw new Exception("Invalid file type for ID picture. Only JPG, JPEG, PNG allowed.");
            }
            $uploadDir = __DIR__ . '/../uploads/borrower_ids/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $filename = uniqid('idpic_') . '.' . $ext;
            $targetPath = $uploadDir . $filename;
            if (!move_uploaded_file($_FILES['id_picture']['tmp_name'], $targetPath)) {
                throw new Exception("Failed to upload ID picture.");
            }
            $id_picture_path = 'uploads/borrower_ids/' . $filename;
        } else {
            throw new Exception("ID picture is required.");
        }
        // Generate a unique tracking code
        function generateTrackingCode($length = 8) {
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            return $code;
        }
        $tracking_code = generateTrackingCode();
        $stmt = $pdo->prepare("INSERT INTO borrow_requests (user_id, control_number, borrower_name, borrower_email, released_by, id_picture, purpose, location_of_use, status, date_requested, tracking_code, borrow_start, borrow_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $control_number, $borrower_name, $borrower_email, $released_by, $id_picture_path, $purpose, $location_of_use, $status, $date_requested, $tracking_code, $borrow_start, $borrow_end]);
        $request_id = $pdo->lastInsertId();
        foreach ($_POST['equipment_ids'] as $equip_id) {
            $stmtItem = $pdo->prepare("INSERT INTO borrow_request_items (request_id, equipment_id, quantity) VALUES (?, ?, ?)");
            $stmtItem->execute([$request_id, $equip_id, 1]);
        }
        $pdo->commit();
        $redirectUrl = 'borrow_success.php?request_id=' . $request_id . '&tracking_code=' . $tracking_code;
        if (isset($_GET['from']) && $_GET['from'] === 'portal') {
            $redirectUrl .= '&from=portal';
        }
        header("Location: $redirectUrl");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[Borrower Slip Form Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        die("Error saving borrow request: " . $e->getMessage());
    }
}

// Fetching dropdown data
function fetchAll($pdo, $table) {
    try {
        $stmt = $pdo->query("SELECT * FROM $table ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // What: Error fetching dropdown data
        // Why: DB error, etc.
        // How: Log error and return empty array
        error_log('[Borrower Slip Form Fetch Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        return [];
    }
}

$courses = fetchAll($pdo, 'courses');
$years = fetchAll($pdo, 'years');
$sections = fetchAll($pdo, 'sections');
$departments = fetchAll($pdo, 'departments');

// Fetch borrowers from the borrowers table
$borrowers = fetchAll($pdo, 'borrowers');

// Fetch lab locations from labs table
$stmt = $pdo->query("SELECT id, lab_name, location FROM labs ORDER BY lab_name");
$labLocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT MAX(control_number) AS max_cn FROM borrow_requests");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$next_control_number = ($row['max_cn'] ?? 0) + 1;

$cancelUrl = 'dashboard.php';
if (isset($_GET['from']) && $_GET['from'] === 'portal') {
    $cancelUrl = 'borrowers_portal.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Borrower's Slip Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/borrower_slip_form.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="slip-box bg-white">
    <div class="row slip-header align-items-center g-2 flex-wrap">
        <div class="col-2 d-flex align-items-center">
        <img src="../assets/images/chmsu_logo.jpg" alt="CHMSU Logo" style="height: 60px;">
        </div>
        <div class="col-7 d-flex flex-column justify-content-center align-items-center text-center">
            <div class="slip-title">BORROWER'S SLIP</div>
        </div>
        <div class="col-3 d-flex flex-column justify-content-center align-items-end">
            <div style="border:2px solid #333; border-radius:6px; padding:6px 8px; font-size:0.75em; background:#f8f9fa; min-width:180px; white-space:nowrap;">
                <table style="width:100%; font-size:inherit;">
                    <tr>
                        <td style="font-weight:bold; padding-right:4px;">Document Code:</td>
                        <td style="text-align:right;">F.01-BSIS-TAL</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; padding-right:4px;">Revision No.:</td>
                        <td style="text-align:right;">0</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; padding-right:4px;">Effective Date:</td>
                        <td style="text-align:right;">May 27, 2024</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; padding-right:4px;">Page:</td>
                        <td style="text-align:right;">1 of 1</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div>BSIS LABORATORY COPY</div>
    <br>
    <form method="POST" id="borrowForm" enctype="multipart/form-data">
        <div class="row mb-2 g-2">
            <div class="col-md-4 mb-2">
                <label class="form-label">Control No.</label>
                <input type="text" class="form-control" name="control_number" value="<?= htmlspecialchars($next_control_number) ?>" readonly>
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label">Date and Time of Request</label>
                <input type="datetime-local" name="date_requested" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" readonly required>
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label">Borrower Type</label>
                <select name="borrower_type" id="borrowerType" class="form-select" required>
                    <option value="Faculty">Faculty</option>
                    <option value="Office/Department">Office/Department</option>
                    <option value="Student">Student</option>
                </select>
            </div>
        </div>

        <div class="row mb-2 g-2">
            <!-- Individual borrower fields -->
            <div id="individualFields" class="col-md-8 mb-2">
                <div class="row">
                    <div class="col-md-6 mb-2">
                <label class="form-label">Name</label>
                        <input type="text" name="borrower_name" id="borrowerName" class="form-control">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" name="borrower_email" id="borrowerEmail" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Office/Department borrower fields -->
            <div id="officeDeptFields" class="col-md-8 mb-2" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Office/Department</label>
                        <select name="borrower_office_dept" id="borrowerOfficeDept" class="form-select">
                            <option value="">Select Office/Department</option>
                            <?php foreach ($borrowers as $borrower): ?>
                                <?php if ($borrower['status'] === 'Active'): ?>
                                    <option value="<?= htmlspecialchars($borrower['name']) ?>" 
                                            data-email="<?= htmlspecialchars($borrower['email'] ?? '') ?>"
                                            data-contact="<?= htmlspecialchars($borrower['contact_person'] ?? '') ?>">
                                        <?= htmlspecialchars($borrower['name']) ?> (<?= htmlspecialchars($borrower['type']) ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Contact Person</label>
                        <input type="text" id="contactPerson" class="form-control" readonly>
                    </div>
                </div>
            </div>

            <!-- Student fields (hidden since Student option removed) -->
            <div id="studentFields" class="col-md-8 mb-2" style="display: none;">
                <div class="row">
                    <div class="col-md-4 mb-2">
                <label class="form-label">Course</label>
                        <select name="course" id="courseSelect" class="form-select">
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Year</label>
                        <select name="year" id="yearSelect" class="form-select">
                            <option value="">Select Year</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= $year['id'] ?>"><?= htmlspecialchars($year['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Section</label>
                        <select name="section" id="sectionSelect" class="form-select">
                            <option value="">Select Section</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= $section['id'] ?>"><?= htmlspecialchars($section['name']) ?></option>
                            <?php endforeach; ?>
                </select>
                    </div>
                </div>
            </div>

            <!-- Faculty fields -->
            <div id="facultyFields" class="col-md-8 mb-2" style="display: none;">
                <div class="row">
                    <div class="col-md-12 mb-2">
                        <label class="form-label">Department</label>
                        <select name="department" id="departmentSelect" class="form-select">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                </select>
                    </div>
                    </div>
                </div>
            </div>

            <div class="row mb-2 g-2">
            <div class="col-md-6 mb-2">
                <label class="form-label">Borrow Start Date/Time</label>
                <input type="datetime-local" name="borrow_start" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label">Borrow End Date/Time</label>
                <input type="datetime-local" name="borrow_end" class="form-control" required>
            </div>
        </div>

        <div class="row mb-2 g-2">
            <div class="col-md-6 mb-2">
                <label class="form-label">Upload ID Picture</label>
                <input type="file" name="id_picture" class="form-control" accept="image/*" required>
                <small class="form-text text-muted mt-1">Upload a clear photo of your ID (JPG, PNG).</small>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label">Released By</label>
                <input type="text" name="released_by" class="form-control" required>
            </div>
        </div>

        <div class="row mb-2 g-2">
            <div class="col-md-6 mb-2">
                <label class="form-label">Purpose</label>
                <input type="text" name="purpose" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label">Location of Use</label>
                <select name="location_of_use" id="locationOfUseSelect" class="form-select" required>
                    <option value="">Select Location</option>
                    <?php foreach ($labLocations as $lab): ?>
                        <option value="<?= htmlspecialchars($lab['lab_name']) ?>">
                            <?= htmlspecialchars($lab['lab_name']) ?> - <?= htmlspecialchars($lab['location']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="Others">Others (Specify below)</option>
                </select>
                <input type="text" name="location_of_use_other" id="locationOfUseOther" class="form-control mt-2" placeholder="Please specify location" style="display:none;">
            </div>
        </div>

        <hr>

        <h5>Items Borrowed</h5>
        <div class="row mb-2 g-2">
            <div class="col-md-8 mb-2">
                <select id="equipmentSelect" class="form-select">
                    <option value="">Select Equipment</option>
                    <?php foreach ($equipmentList as $equip): ?>
                        <option value="<?= htmlspecialchars($equip['id']) ?>"
                            <?= $equip['status'] !== 'Available' ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($equip['name']) ?> - <?= htmlspecialchars($equip['location']) ?>
                            <?= $equip['status'] !== 'Available' ? ' (Not Available)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <button type="button" id="addEquipmentBtn" class="btn btn-primary w-100">Add Equipment</button>
        </div>
        </div>

        <div id="selectedEquipment" class="mb-3">
            <!-- Selected equipment will be displayed here -->
        </div>

        <!-- Note and Status Section -->
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="note">
                    <strong>Note:</strong> Received tools and equipment in good conditions. In the event that the borrower will lose or break the items the borrower will replace the items immediately.<br>
                    Accomplished in duplicate copy.
                </div>
            </div>
            <div class="col-md-6 text-end">
                <div class="status-box stamp-box mx-auto my-2">
                    <div class="stamp-status-label">STATUS</div>
                    <!-- Empty area for staff to stamp -->
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-success px-5 py-2">Submit Request</button>
            <a href="<?= $cancelUrl ?>" class="btn btn-secondary px-5 py-2 ms-2">Cancel</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const borrowerType = document.getElementById('borrowerType');
    const individualFields = document.getElementById('individualFields');
    const officeDeptFields = document.getElementById('officeDeptFields');
    const studentFields = document.getElementById('studentFields');
    const facultyFields = document.getElementById('facultyFields');
    const borrowerName = document.getElementById('borrowerName');
    const borrowerEmail = document.getElementById('borrowerEmail');
    const borrowerOfficeDept = document.getElementById('borrowerOfficeDept');
    const contactPerson = document.getElementById('contactPerson');
    const locationOfUseSelect = document.getElementById('locationOfUseSelect');
    const locationOfUseOther = document.getElementById('locationOfUseOther');
    const equipmentSelect = document.getElementById('equipmentSelect');
    const addEquipmentBtn = document.getElementById('addEquipmentBtn');
    const selectedEquipment = document.getElementById('selectedEquipment');
    const selectedEquipmentIds = [];

    // Function to handle borrower type changes
    function handleBorrowerTypeChange() {
        const type = borrowerType.value;
        
        // Show/hide individual vs office/department fields
        if (type === 'Office/Department') {
            individualFields.style.display = 'none';
            officeDeptFields.style.display = 'block';
            studentFields.style.display = 'none';
            facultyFields.style.display = 'none';
            
            // Clear individual fields
            borrowerName.value = '';
            borrowerEmail.value = '';
            
            // Make office/dept fields required
            borrowerOfficeDept.required = true;
            borrowerName.required = false;
            borrowerEmail.required = false;
        } else {
            individualFields.style.display = 'block';
            officeDeptFields.style.display = 'none';
            
            // Clear office/dept fields
            borrowerOfficeDept.value = '';
            contactPerson.value = '';
            
            // Make individual fields required
            borrowerName.required = true;
            borrowerEmail.required = true;
            borrowerOfficeDept.required = false;
            
            // Show faculty fields based on type
            if (type === 'Faculty') {
                studentFields.style.display = 'none';
                facultyFields.style.display = 'block';
            } else {
                studentFields.style.display = 'none';
                facultyFields.style.display = 'none';
            }
        }
    }

    // Borrower type change handler
    borrowerType.addEventListener('change', handleBorrowerTypeChange);
    
    // Initialize form on page load
    handleBorrowerTypeChange();

    // Office/Department selection handler
    borrowerOfficeDept.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const email = selectedOption.getAttribute('data-email');
        const contact = selectedOption.getAttribute('data-contact');
        
        borrowerEmail.value = email || '';
        contactPerson.value = contact || '';
    });

    // Location of use change handler
    locationOfUseSelect.addEventListener('change', function() {
        if (this.value === 'Others') {
            locationOfUseOther.style.display = 'block';
            locationOfUseOther.required = true;
        } else {
            locationOfUseOther.style.display = 'none';
            locationOfUseOther.required = false;
        }
    });

    // Equipment selection
    addEquipmentBtn.addEventListener('click', function() {
        const selectedValue = equipmentSelect.value;
        const selectedText = equipmentSelect.options[equipmentSelect.selectedIndex].text;
        
        if (selectedValue && !selectedEquipmentIds.includes(selectedValue)) {
            selectedEquipmentIds.push(selectedValue);
            
            const equipmentDiv = document.createElement('div');
            equipmentDiv.className = 'alert alert-info d-flex justify-content-between align-items-center';
            equipmentDiv.innerHTML = `
                <span>${selectedText}</span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEquipment('${selectedValue}', this)">
                    <i class="bi bi-trash"></i> Remove
                </button>
                <input type="hidden" name="equipment_ids[]" value="${selectedValue}">
            `;
            
            selectedEquipment.appendChild(equipmentDiv);
            equipmentSelect.value = '';
        }
    });

    // Remove equipment function
    window.removeEquipment = function(id, button) {
        const index = selectedEquipmentIds.indexOf(id);
        if (index > -1) {
            selectedEquipmentIds.splice(index, 1);
        }
        button.parentElement.remove();
    };

    // Form validation
    document.getElementById('borrowForm').addEventListener('submit', function(e) {
        const borrowerType = document.getElementById('borrowerType').value;
        const borrowerName = document.getElementById('borrowerName').value;
        const borrowerEmail = document.getElementById('borrowerEmail').value;
        const borrowerOfficeDept = document.getElementById('borrowerOfficeDept').value;
        
        // Check if equipment is selected
        if (selectedEquipmentIds.length === 0) {
            e.preventDefault();
            alert('Please add at least one equipment item.');
            return false;
        }
        
        // Validate borrower information based on type
        if (borrowerType === 'Faculty') {
            if (!borrowerName.trim()) {
                e.preventDefault();
                alert('Please enter the faculty name.');
                return false;
            }
            if (!borrowerEmail.trim()) {
                e.preventDefault();
                alert('Please enter the faculty email.');
                return false;
            }
        } else if (borrowerType === 'Office/Department') {
            if (!borrowerOfficeDept) {
                e.preventDefault();
                alert('Please select an office/department.');
                return false;
            }
            if (!borrowerEmail.trim()) {
                e.preventDefault();
                alert('Please enter the office/department email.');
                return false;
            }
        }
        
        // If all validations pass, allow form submission
        return true;
    });
});
</script>
</body>
</html>
