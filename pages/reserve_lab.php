<?php
require_once '../includes/auth.php';
require_once '../classes/Database.php';
require_once '../classes/LabService.php';
require_once __DIR__ . '/../includes/send_mail.php';

$database = new Database();
$pdo = $database->getConnection();

// Get the next control number
$stmt = $pdo->query("SELECT MAX(control_number) AS max_cn FROM lab_reservations");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$next_control_number = ($row['max_cn'] ?? 0) + 1;

// Fetch labs for checkboxes
$labService = new LabService();
$labs = $labService->getAllLabs();

// Fetch offices/departments from borrowers table
$stmt = $pdo->query("SELECT id, name, type, contact_person, email FROM borrowers WHERE status = 'Active' ORDER BY name");
$borrowers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments for faculty
$stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch courses, years, sections for students
$stmt = $pdo->query("SELECT id, name FROM courses ORDER BY name");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, name FROM years ORDER BY name");
$years = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, name FROM sections ORDER BY name");
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['lab_id'])) {
        die("Error: Please select at least one laboratory.");
    }
    $date_requested = $_POST['date_requested'];
    $purpose = $_POST['purpose'];
    $needed_tools = $_POST['needed_tools'];
    $borrower_type = $_POST['borrower_type'];
    $borrower_id = ($borrower_type === 'Faculty' || $borrower_type === 'Student') ? null : $_POST['borrower_id'];
    $requested_by = $_POST['requested_by'];
    $noted_by = $_POST['noted_by'];
    $approved_by = $_POST['approved_by'];
    $borrower_email = $_POST['borrower_email'];
    $contact_person = $_POST['contact_person'];
    $status = 'Pending';
    $lab_id = $_POST['lab_id'];
    $reservation_start = $_POST['reservation_start'] ?? '';
    $reservation_end = $_POST['reservation_end'] ?? '';
    if (!$reservation_start || !$reservation_end) {
        throw new Exception("Reservation start and end date/time are required.");
    }
    // Validation: reservation_end must be after reservation_start
    if (strtotime($reservation_end) <= strtotime($reservation_start)) {
        echo '<div class="alert alert-danger">End date/time must be after start date/time. Please select a different end time.</div>';
        exit;
    }
    
    // Additional validation: minimum 30 minutes difference
    $timeDiff = strtotime($reservation_end) - strtotime($reservation_start);
    if ($timeDiff < 1800) { // 1800 seconds = 30 minutes
        echo '<div class="alert alert-danger">Reservation must be at least 30 minutes long. Please select a longer time slot.</div>';
        exit;
    }
    $date = date('Y-m-d H:i:s'); // Current timestamp when request is created
    $time_start = date('H:i:s', strtotime($reservation_start));
    $time_end = date('H:i:s', strtotime($reservation_end));
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
    try {
        // Prevent double-booking: comprehensive overlap check with approved or pending reservations
        $overlapStmt = $pdo->prepare("
            SELECT COUNT(*) as overlap_count, 
                   GROUP_CONCAT(CONCAT(requested_by, ' (', DATE_FORMAT(reservation_start, '%M %d, %Y %h:%i %p'), ' - ', DATE_FORMAT(reservation_end, '%M %d, %Y %h:%i %p'), ')') SEPARATOR '; ') as conflicting_reservations
            FROM lab_reservations 
            WHERE lab_id = ? 
            AND status IN ('Approved', 'Pending')
            AND (
                (reservation_start < ? AND reservation_end > ?) OR
                (reservation_start < ? AND reservation_end > ?) OR
                (reservation_start >= ? AND reservation_end <= ?) OR
                (reservation_start <= ? AND reservation_end >= ?)
            )
        ");
        $overlapStmt->execute([
            $lab_id, 
            $reservation_end, $reservation_start,    // Existing reservation overlaps with new reservation start
            $reservation_end, $reservation_start,    // Existing reservation overlaps with new reservation end
            $reservation_start, $reservation_end,    // New reservation is completely within existing reservation
            $reservation_start, $reservation_end     // New reservation completely contains existing reservation
        ]);
        $overlapResult = $overlapStmt->fetch(PDO::FETCH_ASSOC);
        $overlapCount = $overlapResult['overlap_count'];
        
        if ($overlapCount > 0) {
            $conflictingDetails = $overlapResult['conflicting_reservations'];
            throw new Exception("This lab is already reserved for the selected time slot. Conflicting reservations: $conflictingDetails. Please choose another time or lab.");
        }
        // Handle file uploads
        $approved_letter_path = null;
        $id_photo_path = null;
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../uploads/lab_reservations/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Process approved letter upload
        if (isset($_FILES['approved_letter']) && $_FILES['approved_letter']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['approved_letter'];
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];
            
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Invalid file type for approved letter. Only PDF, DOC, DOCX, JPG, and PNG files are allowed.');
            }
            
            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception('Approved letter file is too large. Maximum size is 5MB.');
            }
            
            $approved_letter_filename = 'letter_' . time() . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $approved_letter_path = 'uploads/lab_reservations/' . $approved_letter_filename;
            
            if (!move_uploaded_file($file['tmp_name'], '../' . $approved_letter_path)) {
                throw new Exception('Failed to upload approved letter.');
            }
        }
        
        // Process ID photo upload
        if (isset($_FILES['id_photo']) && $_FILES['id_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['id_photo'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
            
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Invalid file type for ID photo. Only JPG and PNG files are allowed.');
            }
            
            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception('ID photo file is too large. Maximum size is 5MB.');
            }
            
            $id_photo_filename = 'idphoto_' . time() . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $id_photo_path = 'uploads/lab_reservations/' . $id_photo_filename;
            
            if (!move_uploaded_file($file['tmp_name'], '../' . $id_photo_path)) {
                throw new Exception('Failed to upload ID photo.');
            }
        }
        
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO lab_reservations 
            (control_number, lab_id, user_id, date_reserved, time_start, time_end, purpose, borrower_type, borrower_id, needed_tools, requested_by, noted_by, approved_by, status, borrower_email, contact_person, tracking_code, reservation_start, reservation_end, approved_letter, id_photo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['control_number'],
            $lab_id,
            $_SESSION['user_id'],
            $date,
            $time_start,
            $time_end,
            $purpose,
            $borrower_type,
            $borrower_id,
            $needed_tools,
            $requested_by,
            $noted_by,
            $approved_by,
            $status,
            $borrower_email,
            $contact_person,
            $tracking_code,
            $reservation_start,
            $reservation_end,
            $approved_letter_path,
            $id_photo_path
        ]);
        $pdo->commit();
        // Send email notification to borrower
        $subject = "Your Lab Reservation Tracking Code";
        $message = "Hello $requested_by,\n\nYour lab reservation has been submitted.\n\nTracking Code: $tracking_code\n\nYou can track your reservation status at: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/borrowers_portal.php\n\nThank you.";
        sendSMTPMail($borrower_email, $subject, $message);
        $redirectUrl = 'reserve_success.php?tracking_code=' . urlencode($tracking_code);
        if (isset($_GET['from']) && $_GET['from'] === 'portal') {
            $redirectUrl .= '&from=portal';
        }
        header("Location: $redirectUrl");
        exit;
    } catch (Exception $e) {
        // What: Error during lab reservation
        // Why: Validation error, DB error, double booking, etc.
        // How: Log error and show user-friendly message
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('[Reserve Lab Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        echo '<div class="alert alert-danger">Error saving reservation: ' . htmlspecialchars($e->getMessage()) . '</div>';
        exit;
    }
}

$cancelUrl = 'dashboard.php';
if (isset($_GET['from']) && $_GET['from'] === 'portal') {
    $cancelUrl = 'borrowers_portal.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Reservation Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/reserve_lab.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="slip-box bg-white">
    <div class="row slip-header align-items-center">
        <div class="col-2 d-flex align-items-center">
            <img src="../assets/images/chmsu_logo.jpg" alt="CHMSU Logo" style="height: 60px;">
        </div>
        <div class="col-7 d-flex flex-column justify-content-center align-items-center text-center">
            <div class="slip-title">REQUEST FORM</div>
            <div style="font-size:1.1rem;">(USE OF COMPUTER LABORATORIES)</div>
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
    <form method="POST" enctype="multipart/form-data">
        <!-- Step 1: Choose Date and Time First -->
        <div class="alert alert-info mb-3">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Step 1:</strong> Please select your desired reservation date and time first. Available labs will be shown based on your selected time slot.
        </div>
        
        <div class="row mb-2">
            <div class="col-md-4 mb-2">
                <label class="form-label">Date and Time of Request</label>
                <input type="datetime-local" name="date_requested" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" readonly required>
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label">Reservation Start Date/Time <span class="text-danger">*</span></label>
                <input type="datetime-local" name="reservation_start" id="reservation_start" class="form-control" required>
                <small class="text-muted">Choose your start time</small>
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label">Reservation End Date/Time <span class="text-danger">*</span></label>
                <input type="datetime-local" name="reservation_end" id="reservation_end" class="form-control" required>
                <small class="text-muted">Min. 30 minutes from start</small>
            </div>
        </div>
        
        <!-- Step 2: Select Laboratory Based on Availability -->
        <div class="alert alert-success mb-3" id="labSelectionAlert" style="display: none;">
            <i class="bi bi-check-circle me-2"></i>
            <strong>Step 2:</strong> Now select an available laboratory from the options below.
        </div>
        
        <div class="row mb-2">
            <div class="col-md-8 lab-checkboxes mb-2">
                <label class="form-label fw-bold">Laboratory Requested <span class="text-danger">*</span></label>
                <small class="text-muted d-block mb-2">Available labs will update based on your selected time slot</small>
                <?php foreach ($labs as $lab): ?>
                    <label style="margin-right:18px;">
                        <input type="radio" name="lab_id" value="<?= (string)$lab['id'] ?>" data-lab-name="<?= htmlspecialchars($lab['lab_name']) ?>" required>
                        <?= htmlspecialchars($lab['lab_name']) ?>
                        <span class="lab-occupied-label text-danger" style="display:none;">(Occupied)</span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label">Control No.</label>
                <input type="text" class="form-control" name="control_number" value="<?= htmlspecialchars($next_control_number) ?>" readonly>
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label">Purpose</label>
            <input type="text" name="purpose" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Needed Tools / Equipment / Facilities / Software</label>
            <textarea name="needed_tools" class="form-control" rows="3"></textarea>
        </div>
        <div class="row mb-2">
            <div class="col-md-12 mb-2">
                <label class="form-label">Borrower Type <span class="text-danger">*</span></label>
                <select name="borrower_type" id="borrowerType" class="form-control" required>
                    <option value="">Select Borrower Type</option>
                    <option value="Faculty">Faculty</option>
                    <option value="Office/Department">Office/Department</option>
                    <option value="Student">Student</option>
                </select>
            </div>
        </div>
        
        <!-- Faculty Fields (initially hidden) -->
        <div id="facultyFields" style="display: none;">
            <div class="row mb-2">
                <div class="col-md-4 mb-2">
                    <label class="form-label">Requested by <span class="text-danger">*</span></label>
                    <input type="text" name="requested_by" class="form-control" placeholder="Enter your full name">
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="borrower_email" class="form-control" placeholder="Enter your email address">
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Department <span class="text-danger">*</span></label>
                    <select name="department" id="departmentSelect" class="form-control">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Office/Department Fields (initially hidden) -->
        <div id="officeDeptFields" style="display: none;">
            <div class="row mb-2">
                <div class="col-md-12 mb-2">
                    <label class="form-label">Office/Department <span class="text-danger">*</span></label>
                    <select name="borrower_id" id="borrowerSelect" class="form-control">
                        <option value="">Select Office/Department</option>
                    </select>
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4 mb-2">
                    <label class="form-label">Requested by</label>
                    <input type="text" name="requested_by" class="form-control" readonly>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Email</label>
                    <input type="email" name="borrower_email" class="form-control" readonly>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" readonly>
                </div>
            </div>
        </div>
        
        <!-- Student Fields (initially hidden) -->
        <div id="studentFields" style="display: none;">
            <div class="row mb-2">
                <div class="col-md-6 mb-2">
                    <label class="form-label">Student Name <span class="text-danger">*</span></label>
                    <input type="text" name="requested_by" class="form-control" placeholder="Enter your full name">
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="borrower_email" class="form-control" placeholder="Enter your email address">
                </div>
            </div>
            <div class="row mb-2">
                <div class="col-md-4 mb-2">
                    <label class="form-label">Course <span class="text-danger">*</span></label>
                    <select name="course" id="courseSelect" class="form-control">
                        <option value="">Select Course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Year <span class="text-danger">*</span></label>
                    <select name="year" id="yearSelect" class="form-control">
                        <option value="">Select Year</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?= $year['id'] ?>"><?= htmlspecialchars($year['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Section <span class="text-danger">*</span></label>
                    <select name="section" id="sectionSelect" class="form-control">
                        <option value="">Select Section</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?= $section['id'] ?>"><?= htmlspecialchars($section['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="row mb-2">
            <div class="col-md-4 mb-2">
                <label class="form-label">Noted by</label>
                <input type="text" name="noted_by" class="form-control">
                <div class="text-center mt-1">BSIS LABORATORY ASSISTANT</div>
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label">Approved by</label>
                <input type="text" name="approved_by" class="form-control">
                <div class="text-center mt-1">BSIS PROGRAM CHAIRPERSON</div>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col-md-6 mb-2">
                <label class="form-label">Approved Letter from Dean/President <span class="text-danger">*</span></label>
                <input type="file" name="approved_letter" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                <small class="text-muted">Upload the approved letter explaining the purpose of the reservation (PDF, DOC, DOCX, JPG, PNG)</small>
            </div>
            <div class="col-md-6 mb-2">
                <label class="form-label">ID Photo of Person In-Charge <span class="text-danger">*</span></label>
                <input type="file" name="id_photo" class="form-control" accept=".jpg,.jpeg,.png" required>
                <small class="text-muted">Upload the ID photo of the person in-charge of the event/reason (JPG, PNG)</small>
            </div>
        </div>
        <div class="row">
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
            <button type="submit" class="btn btn-success px-5 py-2">Submit Reservation</button>
            <a href="<?= $cancelUrl ?>" class="btn btn-secondary px-5 py-2 ms-2">Cancel</a>
        </div>
    </form>
</div>
<script src="../assets/js/reserve_lab.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Borrower data from PHP
const borrowers = <?= json_encode($borrowers) ?>;

document.addEventListener('DOMContentLoaded', function() {
    const borrowerTypeSelect = document.getElementById('borrowerType');
    const borrowerSelect = document.getElementById('borrowerSelect');
    const facultyFields = document.getElementById('facultyFields');
    const officeDeptFields = document.getElementById('officeDeptFields');
    const studentFields = document.getElementById('studentFields');
    
    // Populate borrower select based on type
    borrowerTypeSelect.addEventListener('change', function() {
        const selectedType = this.value;
        borrowerSelect.innerHTML = '<option value="">Select Office/Department</option>';
        
        // Show/hide appropriate fields
        if (selectedType === 'Faculty') {
            facultyFields.style.display = 'block';
            officeDeptFields.style.display = 'none';
            studentFields.style.display = 'none';
            borrowerSelect.required = false;
        } else if (selectedType === 'Office/Department') {
            facultyFields.style.display = 'none';
            officeDeptFields.style.display = 'block';
            studentFields.style.display = 'none';
            borrowerSelect.required = true;
            
            // Populate dropdown with offices/departments
            const filteredBorrowers = borrowers.filter(borrower => borrower.type === 'Office' || borrower.type === 'Department');
            filteredBorrowers.forEach(borrower => {
                const option = document.createElement('option');
                option.value = borrower.id;
                option.textContent = borrower.name;
                borrowerSelect.appendChild(option);
            });
        } else if (selectedType === 'Student') {
            facultyFields.style.display = 'none';
            officeDeptFields.style.display = 'none';
            studentFields.style.display = 'block';
            borrowerSelect.required = false;
        } else {
            facultyFields.style.display = 'none';
            officeDeptFields.style.display = 'none';
            studentFields.style.display = 'none';
            borrowerSelect.required = false;
        }
        
        // Clear all fields when type changes
        clearAllFields();
    });
    
    // Auto-fill fields when borrower is selected
    borrowerSelect.addEventListener('change', function() {
        const selectedBorrowerId = this.value;
        if (selectedBorrowerId) {
            const borrower = borrowers.find(b => b.id == selectedBorrowerId);
            if (borrower) {
                // Auto-fill the fields for office/department
                const requestedByField = document.querySelector('#officeDeptFields input[name="requested_by"]');
                const emailField = document.querySelector('#officeDeptFields input[name="borrower_email"]');
                const contactPersonField = document.querySelector('#officeDeptFields input[name="contact_person"]');
                
                if (requestedByField) requestedByField.value = borrower.contact_person || '';
                if (emailField) emailField.value = borrower.email || '';
                if (contactPersonField) contactPersonField.value = borrower.contact_person || '';
            }
        } else {
            // Clear fields if no borrower selected
            clearOfficeDeptFields();
        }
    });
    
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const borrowerType = borrowerTypeSelect.value;
        const borrowerId = borrowerSelect.value;
        
        if (!borrowerType) {
            e.preventDefault();
            alert('Please select a borrower type.');
            return;
        }
        
        if (borrowerType === 'Office/Department') {
            if (!borrowerId) {
                e.preventDefault();
                alert('Please select an office/department.');
                return;
            }
        }
        
        // Make required fields mandatory based on selected type
        if (borrowerType === 'Faculty') {
            const facultyRequiredFields = document.querySelectorAll('#facultyFields input, #facultyFields select');
            facultyRequiredFields.forEach(field => {
                field.required = true;
            });
            
            // Validate that all faculty fields are filled
            const requestedBy = document.querySelector('#facultyFields input[name="requested_by"]').value;
            const email = document.querySelector('#facultyFields input[name="borrower_email"]').value;
            const department = document.querySelector('#facultyFields select[name="department"]').value;
            
            if (!requestedBy || !email || !department) {
                e.preventDefault();
                alert('Please fill in all required faculty fields (Name, Email, and Department).');
                return;
            }
        } else if (borrowerType === 'Student') {
            const studentRequiredFields = document.querySelectorAll('#studentFields input, #studentFields select');
            studentRequiredFields.forEach(field => {
                field.required = true;
            });
            
            // Validate that all student fields are filled
            const studentName = document.querySelector('#studentFields input[name="requested_by"]').value;
            const studentEmail = document.querySelector('#studentFields input[name="borrower_email"]').value;
            const course = document.querySelector('#studentFields select[name="course"]').value;
            const year = document.querySelector('#studentFields select[name="year"]').value;
            const section = document.querySelector('#studentFields select[name="section"]').value;
            
            if (!studentName || !studentEmail || !course || !year || !section) {
                e.preventDefault();
                alert('Please fill in all required student fields (Name, Email, Course, Year, and Section).');
                return;
            }
        }
    });
    
    // Helper functions
    function clearAllFields() {
        clearFacultyFields();
        clearOfficeDeptFields();
        clearStudentFields();
    }
    
    function clearFacultyFields() {
        const facultyInputs = document.querySelectorAll('#facultyFields input, #facultyFields select');
        facultyInputs.forEach(input => {
            input.value = '';
            input.required = false;
        });
    }
    
    function clearOfficeDeptFields() {
        const officeDeptInputs = document.querySelectorAll('#officeDeptFields input');
        officeDeptInputs.forEach(input => {
            input.value = '';
            input.required = false;
        });
    }
    
    function clearStudentFields() {
        const studentInputs = document.querySelectorAll('#studentFields input, #studentFields select');
        studentInputs.forEach(input => {
            input.value = '';
            input.required = false;
        });
    }
        
        // Time validation
        const startTimeInput = document.getElementById('reservation_start');
        const endTimeInput = document.getElementById('reservation_end');
        
        startTimeInput.addEventListener('change', function() {
            const startTime = new Date(this.value);
            const endTime = new Date(endTimeInput.value);
            
            if (endTimeInput.value && endTime <= startTime) {
                // Set end time to 1 hour after start time
                const newEndTime = new Date(startTime.getTime() + (60 * 60 * 1000)); // 1 hour
                endTimeInput.value = newEndTime.toISOString().slice(0, 16);
            }
            
            // Show Step 2 alert if both times are now set
            if (this.value && endTimeInput.value) {
                const labSelectionAlert = document.getElementById('labSelectionAlert');
                if (labSelectionAlert) {
                    labSelectionAlert.style.display = 'block';
                }
            }
        });
        
        endTimeInput.addEventListener('change', function() {
            const startTime = new Date(startTimeInput.value);
            const endTime = new Date(this.value);
            
            if (startTimeInput.value && endTime <= startTime) {
                alert('End time must be after start time. Please select a later time.');
                this.value = '';
            } else if (startTimeInput.value && this.value) {
                // Show Step 2 alert when both times are selected
                const labSelectionAlert = document.getElementById('labSelectionAlert');
                if (labSelectionAlert) {
                    labSelectionAlert.style.display = 'block';
                    // Smooth scroll to lab selection
                    labSelectionAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        });
        
        // Check for existing reservations when date/time changes
        function checkExistingReservations() {
            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;
            const labId = document.querySelector('input[name="lab_id"]:checked')?.value;
            
            if (startTime && endTime && labId) {
                fetch('api/check_lab_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        lab_id: labId,
                        start_time: startTime,
                        end_time: endTime
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.available) {
                        let message = 'This time slot conflicts with an existing reservation.';
                        if (data.conflicting_reservations) {
                            message += '\n\nConflicting reservations:\n' + data.conflicting_reservations;
                        }
                        message += '\n\nPlease choose a different time.';
                        alert(message);
                        endTimeInput.value = '';
                    }
                })
                .catch(error => {
                    console.error('Error checking availability:', error);
                });
            }
        }
        
        startTimeInput.addEventListener('change', checkExistingReservations);
        endTimeInput.addEventListener('change', checkExistingReservations);
        
        // Check lab availability when form loads
        function checkAllLabAvailability() {
            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;
            
            if (startTime && endTime) {
                const labRadios = document.querySelectorAll('input[name="lab_id"]');
                
                labRadios.forEach(radio => {
                    const labId = radio.value;
                    const labName = radio.getAttribute('data-lab-name');
                    const occupiedLabel = radio.parentElement.querySelector('.lab-occupied-label');
                    
                    fetch('api/check_lab_availability.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            lab_id: labId,
                            start_time: startTime,
                            end_time: endTime
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.available) {
                            radio.disabled = true;
                            occupiedLabel.style.display = 'inline';
                            radio.parentElement.style.opacity = '0.6';
                            // Add tooltip with conflict details
                            if (data.conflicting_reservations) {
                                radio.parentElement.title = 'Conflicts with: ' + data.conflicting_reservations;
                            }
                        } else {
                            radio.disabled = false;
                            occupiedLabel.style.display = 'none';
                            radio.parentElement.style.opacity = '1';
                            radio.parentElement.title = '';
                        }
                    })
                    .catch(error => {
                        console.error('Error checking availability for lab ' + labName + ':', error);
                    });
                });
            }
        }
        
        // Check availability when both start and end times are set
        startTimeInput.addEventListener('change', function() {
            if (endTimeInput.value) {
                setTimeout(checkAllLabAvailability, 100);
            }
        });
        
        endTimeInput.addEventListener('change', function() {
            if (startTimeInput.value) {
                setTimeout(checkAllLabAvailability, 100);
            }
        });
    });
</script>
</body>
</html>