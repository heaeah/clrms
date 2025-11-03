<?php
/**
 * FormValidator Class
 * What: Centralized form validation for all system forms
 * Why: Ensures consistent validation across all forms and prevents invalid data submission
 * How: Provides static methods for form validation, sanitization, and error handling
 */

require_once 'DataValidator.php';

class FormValidator {
    
    /**
     * Validate equipment form data
     * @param array $data Form data
     * @return array Validation result with success status and errors
     */
    public static function validateEquipmentForm($data) {
        $errors = [];
        $sanitized = [];
        
        // Required fields validation
        $requiredFields = ['name', 'status', 'location'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst($field) . ' is required';
            }
        }
        
        // Sanitize and validate each field
        $sanitized['name'] = DataValidator::validateString($data['name'] ?? '', 'Unknown Equipment', 255);
        $sanitized['serial_number'] = DataValidator::validateString($data['serial_number'] ?? '', '', 100);
        $sanitized['model'] = DataValidator::validateString($data['model'] ?? '', '', 100);
        $sanitized['status'] = DataValidator::validateEnum($data['status'] ?? '', ['Working', 'Maintenance', 'Broken', 'Retired'], 'Working');
        $sanitized['location'] = DataValidator::validateString($data['location'] ?? '', 'Unknown Location', 255);
        $sanitized['remarks'] = DataValidator::validateString($data['remarks'] ?? '', '', 1000);
        $sanitized['date_transferred'] = DataValidator::validateDate($data['date_transferred'] ?? '');
        $sanitized['installation_date'] = DataValidator::validateDate($data['installation_date'] ?? '');
        $sanitized['maintenance_interval_months'] = DataValidator::validateInteger($data['maintenance_interval_months'] ?? 6, 6, 1, 120);
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $sanitized
        ];
    }
    
    /**
     * Validate borrow request form data
     * @param array $data Form data
     * @return array Validation result with success status and errors
     */
    public static function validateBorrowRequestForm($data) {
        $errors = [];
        $sanitized = [];
        
        // Required fields validation
        $requiredFields = ['borrower_name', 'purpose', 'location_of_use'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Email validation if provided
        if (!empty($data['borrower_email']) && !filter_var($data['borrower_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }
        
        // Date validation
        if (!empty($data['borrow_start']) && !empty($data['borrow_end'])) {
            $startDate = new DateTime($data['borrow_start']);
            $endDate = new DateTime($data['borrow_end']);
            if ($endDate <= $startDate) {
                $errors[] = 'End date must be after start date';
            }
        }
        
        // Sanitize and validate each field
        $sanitized['borrower_name'] = DataValidator::validateString($data['borrower_name'] ?? '', 'Unknown Borrower', 100);
        $sanitized['borrower_email'] = DataValidator::validateEmail($data['borrower_email'] ?? '');
        $sanitized['course_year'] = DataValidator::validateString($data['course_year'] ?? '', '', 50);
        $sanitized['subject'] = DataValidator::validateString($data['subject'] ?? '', '', 100);
        $sanitized['purpose'] = DataValidator::validateString($data['purpose'] ?? '', 'No purpose specified', 255);
        $sanitized['location_of_use'] = DataValidator::validateString($data['location_of_use'] ?? '', 'Unknown Location', 255);
        $sanitized['borrow_start'] = DataValidator::validateDate($data['borrow_start'] ?? '');
        $sanitized['borrow_end'] = DataValidator::validateDate($data['borrow_end'] ?? '');
        $sanitized['released_by'] = DataValidator::validateString($data['released_by'] ?? '', '', 100);
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $sanitized
        ];
    }
    
    /**
     * Validate lab reservation form data
     * @param array $data Form data
     * @return array Validation result with success status and errors
     */
    public static function validateLabReservationForm($data) {
        $errors = [];
        $sanitized = [];
        
        // Required fields validation
        $requiredFields = ['lab_id', 'purpose', 'reservation_start', 'reservation_end', 'contact_person', 'contact_email'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Email validation
        if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid contact email address';
        }
        
        // Date validation
        if (!empty($data['reservation_start']) && !empty($data['reservation_end'])) {
            $startDate = new DateTime($data['reservation_start']);
            $endDate = new DateTime($data['reservation_end']);
            if ($endDate <= $startDate) {
                $errors[] = 'End date must be after start date';
            }
            
            // Check if reservation is in the future
            $now = new DateTime();
            if ($startDate <= $now) {
                $errors[] = 'Reservation start date must be in the future';
            }
        }
        
        // Sanitize and validate each field
        $sanitized['lab_id'] = DataValidator::validateInteger($data['lab_id'] ?? 0, 0, 1, 999);
        $sanitized['purpose'] = DataValidator::validateString($data['purpose'] ?? '', 'No purpose specified', 500);
        $sanitized['reservation_start'] = DataValidator::validateDate($data['reservation_start'] ?? '');
        $sanitized['reservation_end'] = DataValidator::validateDate($data['reservation_end'] ?? '');
        $sanitized['contact_person'] = DataValidator::validateString($data['contact_person'] ?? '', 'Unknown Contact', 100);
        $sanitized['contact_email'] = DataValidator::validateEmail($data['contact_email'] ?? '');
        $sanitized['contact_phone'] = DataValidator::validatePhone($data['contact_phone'] ?? '');
        $sanitized['expected_attendees'] = DataValidator::validateInteger($data['expected_attendees'] ?? 1, 1, 1, 1000);
        $sanitized['special_requirements'] = DataValidator::validateString($data['special_requirements'] ?? '', '', 1000);
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $sanitized
        ];
    }
    
    /**
     * Validate user registration form data
     * @param array $data Form data
     * @return array Validation result with success status and errors
     */
    public static function validateUserRegistrationForm($data) {
        $errors = [];
        $sanitized = [];
        
        // Required fields validation
        $requiredFields = ['name', 'username', 'password', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst($field) . ' is required';
            }
        }
        
        // Username validation
        if (!empty($data['username'])) {
            if (strlen($data['username']) < 3) {
                $errors[] = 'Username must be at least 3 characters long';
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                $errors[] = 'Username can only contain letters, numbers, and underscores';
            }
        }
        
        // Password validation
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                $errors[] = 'Password must be at least 6 characters long';
            }
        }
        
        // Email validation if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }
        
        // Role validation
        $allowedRoles = ['Admin', 'Lab Admin', 'Student Assistant'];
        if (!empty($data['role']) && !in_array($data['role'], $allowedRoles)) {
            $errors[] = 'Invalid role selected';
        }
        
        // Sanitize and validate each field
        $sanitized['name'] = DataValidator::validateString($data['name'] ?? '', 'Unknown User', 100);
        $sanitized['username'] = DataValidator::validateString($data['username'] ?? '', '', 50);
        $sanitized['password'] = $data['password'] ?? ''; // Don't sanitize password
        $sanitized['role'] = DataValidator::validateEnum($data['role'] ?? '', $allowedRoles, 'Student Assistant');
        $sanitized['email'] = DataValidator::validateEmail($data['email'] ?? '');
        $sanitized['mobile_number'] = DataValidator::validatePhone($data['mobile_number'] ?? '');
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $sanitized
        ];
    }
    
    /**
     * Validate file upload
     * @param array $file $_FILES array element
     * @param string $type Type of file (image, document, etc.)
     * @return array Validation result with success status and errors
     */
    public static function validateFileUpload($file, $type = 'image') {
        $errors = [];
        
        if (!isset($file) || !is_array($file)) {
            $errors[] = 'No file uploaded';
            return ['success' => false, 'errors' => $errors, 'data' => null];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'File size too large';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'File upload was incomplete';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'No file was uploaded';
                    break;
                default:
                    $errors[] = 'File upload error occurred';
                    break;
            }
            return ['success' => false, 'errors' => $errors, 'data' => null];
        }
        
        // File size validation (5MB max)
        if ($file['size'] > 5242880) {
            $errors[] = 'File size must be less than 5MB';
        }
        
        // File type validation
        $allowedTypes = [];
        switch ($type) {
            case 'image':
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                break;
            case 'document':
                $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                break;
            default:
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                break;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes);
        }
        
        $validatedFile = null;
        if (empty($errors)) {
            $validatedFile = DataValidator::validateFileUpload($file, $allowedTypes, 5242880);
            if (!$validatedFile) {
                $errors[] = 'File validation failed';
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'data' => $validatedFile
        ];
    }
    
    /**
     * Display validation errors in HTML format
     * @param array $errors Array of error messages
     * @return string HTML formatted error messages
     */
    public static function displayErrors($errors) {
        if (empty($errors)) {
            return '';
        }
        
        $html = '<div class="alert alert-danger" role="alert">';
        $html .= '<h6><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following errors:</h6>';
        $html .= '<ul class="mb-0">';
        foreach ($errors as $error) {
            $html .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Display success message in HTML format
     * @param string $message Success message
     * @return string HTML formatted success message
     */
    public static function displaySuccess($message) {
        return '<div class="alert alert-success" role="alert">' . 
               '<i class="bi bi-check-circle-fill me-2"></i>' . 
               htmlspecialchars($message) . 
               '</div>';
    }
}
?>