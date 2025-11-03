<?php
// Include necessary files
require_once '../classes/Equipment.php';
require_once '../classes/Database.php';

// Get equipment details
$equipment = new Equipment();
$equip = $equipment->getEquipmentById($_POST['equipment_id'] ?? 0);

// If equipment does not exist, show an error
if (!$equip) {
    die("Invalid equipment selected.");
}

// Determine user ID: Use 9999 for guest users (Borrowers accessing via QR code)
$user_id = 9999; // Default Guest User ID

// Get form data
$control_number = $_POST['control_number'] ?? '';
$date_requested = $_POST['date_requested'] ?? '';
$borrower_name = $_POST['borrower_name'] ?? '';
$course_year = $_POST['course_year'] ?? '';
$subject = $_POST['subject'] ?? '';
$signature_image = $_POST['signature_image'] ?? '';  // Signature image (base64)
$datetime_needed = $_POST['datetime_needed'] ?? '';
$purpose = $_POST['purpose'] ?? '';
$location_of_use = $_POST['location_of_use'] ?? '';
$released_by = $_POST['released_by'] ?? '';
$return_date = $_POST['return_date'] ?? '';
$quantity = $_POST['quantity'] ?? 1;
$description = $_POST['description'] ?? '';

try {
    // Prepare SQL statement to insert borrow request
    $database = new Database();  // Create an instance of the Database class
    $pdo = $database->getConnection();  // Get the connection

    $query = "
        INSERT INTO borrow_requests 
        (equipment_id, control_number, date_requested, borrower_name, course_year, subject, signature_image, 
        datetime_needed, purpose, location_of_use, released_by, return_date, quantity, description, user_id)
        VALUES 
        (:equipment_id, :control_number, :date_requested, :borrower_name, :course_year, :subject, :signature_image, 
        :datetime_needed, :purpose, :location_of_use, :released_by, :return_date, :quantity, :description, :user_id)
    ";

    $stmt = $pdo->prepare($query);

    // Bind parameters
    $stmt->bindParam(':equipment_id', $equip['id'], PDO::PARAM_INT);
    $stmt->bindParam(':control_number', $control_number, PDO::PARAM_STR);
    $stmt->bindParam(':date_requested', $date_requested, PDO::PARAM_STR);
    $stmt->bindParam(':borrower_name', $borrower_name, PDO::PARAM_STR);
    $stmt->bindParam(':course_year', $course_year, PDO::PARAM_STR);
    $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
    $stmt->bindParam(':signature_image', $signature_image, PDO::PARAM_STR); // base64 encoded image
    $stmt->bindParam(':datetime_needed', $datetime_needed, PDO::PARAM_STR);
    $stmt->bindParam(':purpose', $purpose, PDO::PARAM_STR);
    $stmt->bindParam(':location_of_use', $location_of_use, PDO::PARAM_STR);
    $stmt->bindParam(':released_by', $released_by, PDO::PARAM_STR);
    $stmt->bindParam(':return_date', $return_date, PDO::PARAM_STR);
    $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);  // Use Guest User ID for Borrowers

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to success page or display success message
        echo "Borrow request successfully submitted!";
    } else {
        // Handle errors
        throw new Exception("Error submitting borrow request. Please try again later.");
    }
} catch (Exception $e) {
    // What: Error during borrow request submission
    // Why: DB error, validation error, etc.
    // How: Log error and show user-friendly message
    error_log('[Submit Borrow Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    echo "Error: " . $e->getMessage();
}
?>
