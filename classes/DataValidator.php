<?php
/**
 * DataValidator Class
 * What: Centralized data validation and sanitization for the entire system
 * Why: Ensures clean, secure, and consistent data across all components
 * How: Provides static methods for validation, sanitization, and data integrity checks
 */

class DataValidator {
    
    /**
     * Validate and clean string data
     * @param mixed $value The value to validate
     * @param string $default Default value if validation fails
     * @param int $maxLength Maximum allowed length
     * @param bool $allowHtml Whether to allow HTML tags
     * @return string Cleaned string
     */
    public static function validateString($value, $default = '', $maxLength = 255, $allowHtml = false) {
        if (is_null($value) || $value === '' || $value === false) {
            return $default;
        }
        
        $cleaned = trim($value);
        
        if (!$allowHtml) {
            $cleaned = strip_tags($cleaned);
        }
        
        // Remove null bytes and control characters
        $cleaned = str_replace(["\0", "\x00"], '', $cleaned);
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleaned);
        
        if (strlen($cleaned) > $maxLength) {
            $cleaned = substr($cleaned, 0, $maxLength);
        }
        
        return $cleaned;
    }
    
    /**
     * Validate and clean numeric data
     * @param mixed $value The value to validate
     * @param int $default Default value if validation fails
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @return int Cleaned integer
     */
    public static function validateInteger($value, $default = 0, $min = 0, $max = 999999) {
        if (is_null($value) || $value === '' || !is_numeric($value)) {
            return $default;
        }
        
        $num = (int)$value;
        return max($min, min($max, $num));
    }
    
    /**
     * Validate and clean float data
     * @param mixed $value The value to validate
     * @param float $default Default value if validation fails
     * @param float $min Minimum allowed value
     * @param float $max Maximum allowed value
     * @return float Cleaned float
     */
    public static function validateFloat($value, $default = 0.0, $min = 0.0, $max = 999999.99) {
        if (is_null($value) || $value === '' || !is_numeric($value)) {
            return $default;
        }
        
        $num = (float)$value;
        return max($min, min($max, $num));
    }
    
    /**
     * Validate and clean date data
     * @param mixed $value The value to validate
     * @param string $default Default value if validation fails
     * @param string $format Expected date format
     * @return string Cleaned date string
     */
    public static function validateDate($value, $default = null, $format = 'Y-m-d H:i:s') {
        if (is_null($value) || $value === '' || $value === '0000-00-00 00:00:00' || $value === '1970-01-01 00:00:00') {
            return $default;
        }
        
        try {
            $date = new DateTime($value);
            return $date->format($format);
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * Validate and clean email address
     * @param mixed $value The value to validate
     * @param string $default Default value if validation fails
     * @return string Cleaned email or default
     */
    public static function validateEmail($value, $default = '') {
        if (is_null($value) || $value === '') {
            return $default;
        }
        
        $cleaned = trim(strtolower($value));
        $cleaned = filter_var($cleaned, FILTER_SANITIZE_EMAIL);
        
        if (filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
            return $cleaned;
        }
        
        return $default;
    }
    
    /**
     * Validate and clean phone number
     * @param mixed $value The value to validate
     * @param string $default Default value if validation fails
     * @return string Cleaned phone number
     */
    public static function validatePhone($value, $default = '') {
        if (is_null($value) || $value === '') {
            return $default;
        }
        
        $cleaned = preg_replace('/[^0-9+\-\(\)\s]/', '', $value);
        $cleaned = trim($cleaned);
        
        if (strlen($cleaned) < 7 || strlen($cleaned) > 20) {
            return $default;
        }
        
        return $cleaned;
    }
    
    /**
     * Validate and clean array data
     * @param mixed $array The array to validate
     * @param array $default Default array if validation fails
     * @return array Cleaned array
     */
    public static function validateArray($array, $default = []) {
        if (!is_array($array) || empty($array)) {
            return $default;
        }
        
        return array_filter($array, function($item) {
            return !is_null($item) && $item !== '';
        });
    }
    
    /**
     * Validate and clean file upload data
     * @param array $file The $_FILES array element
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @return array|false Cleaned file data or false if invalid
     */
    public static function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 5242880) {
        if (!isset($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return false;
        }
        
        return [
            'name' => self::validateString($file['name'], 'unknown'),
            'type' => $mimeType,
            'size' => $file['size'],
            'tmp_name' => $file['tmp_name']
        ];
    }
    
    /**
     * Validate database enum values
     * @param mixed $value The value to validate
     * @param array $allowedValues Array of allowed enum values
     * @param string $default Default value if validation fails
     * @return string Validated enum value
     */
    public static function validateEnum($value, $allowedValues, $default = '') {
        if (is_null($value) || $value === '') {
            return $default;
        }
        
        $cleaned = self::validateString($value, $default);
        
        if (in_array($cleaned, $allowedValues)) {
            return $cleaned;
        }
        
        return $default;
    }
    
    /**
     * Sanitize data for database insertion
     * @param array $data The data array to sanitize
     * @param array $rules Validation rules array
     * @return array Sanitized data array
     */
    public static function sanitizeData($data, $rules) {
        $sanitized = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            switch ($rule['type']) {
                case 'string':
                    $sanitized[$field] = self::validateString(
                        $value, 
                        $rule['default'] ?? '', 
                        $rule['maxLength'] ?? 255,
                        $rule['allowHtml'] ?? false
                    );
                    break;
                    
                case 'integer':
                    $sanitized[$field] = self::validateInteger(
                        $value, 
                        $rule['default'] ?? 0, 
                        $rule['min'] ?? 0, 
                        $rule['max'] ?? 999999
                    );
                    break;
                    
                case 'float':
                    $sanitized[$field] = self::validateFloat(
                        $value, 
                        $rule['default'] ?? 0.0, 
                        $rule['min'] ?? 0.0, 
                        $rule['max'] ?? 999999.99
                    );
                    break;
                    
                case 'date':
                    $sanitized[$field] = self::validateDate(
                        $value, 
                        $rule['default'] ?? null, 
                        $rule['format'] ?? 'Y-m-d H:i:s'
                    );
                    break;
                    
                case 'email':
                    $sanitized[$field] = self::validateEmail(
                        $value, 
                        $rule['default'] ?? ''
                    );
                    break;
                    
                case 'phone':
                    $sanitized[$field] = self::validatePhone(
                        $value, 
                        $rule['default'] ?? ''
                    );
                    break;
                    
                case 'enum':
                    $sanitized[$field] = self::validateEnum(
                        $value, 
                        $rule['allowedValues'] ?? [], 
                        $rule['default'] ?? ''
                    );
                    break;
                    
                default:
                    $sanitized[$field] = $value;
                    break;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Perform data integrity check on database
     * @param PDO $pdo Database connection
     * @return array Array of integrity issues found
     */
    public static function performDataIntegrityCheck($pdo) {
        $issues = [];
        
        try {
            // Check for null or empty critical fields
            $checks = [
                'equipment' => [
                    'query' => "SELECT COUNT(*) as count FROM equipment WHERE name IS NULL OR name = '' OR status IS NULL OR status = ''",
                    'message' => 'Equipment records with missing names or status'
                ],
                'borrow_requests' => [
                    'query' => "SELECT COUNT(*) as count FROM borrow_requests WHERE borrower_name IS NULL OR borrower_name = '' OR status IS NULL OR status = ''",
                    'message' => 'Borrow requests with missing borrower names or status'
                ],
                'lab_reservations' => [
                    'query' => "SELECT COUNT(*) as count FROM lab_reservations WHERE purpose IS NULL OR purpose = '' OR status IS NULL OR status = ''",
                    'message' => 'Lab reservations with missing purpose or status'
                ],
                'users' => [
                    'query' => "SELECT COUNT(*) as count FROM users WHERE username IS NULL OR username = '' OR role IS NULL OR role = ''",
                    'message' => 'Users with missing usernames or roles'
                ],
                'invalid_dates' => [
                    'query' => "SELECT COUNT(*) as count FROM borrow_requests WHERE borrow_start = '1970-01-01 00:00:00' OR borrow_end = '1970-01-01 00:00:00'",
                    'message' => 'Borrow requests with invalid default dates'
                ],
                'orphaned_requests' => [
                    'query' => "SELECT COUNT(*) as count FROM borrow_requests br LEFT JOIN equipment e ON br.equipment_id = e.id WHERE br.equipment_id IS NOT NULL AND e.id IS NULL",
                    'message' => 'Borrow requests referencing non-existent equipment'
                ]
            ];
            
            foreach ($checks as $checkName => $check) {
                $stmt = $pdo->prepare($check['query']);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    $issues[] = [
                        'type' => $checkName,
                        'count' => $result['count'],
                        'message' => $check['message']
                    ];
                }
            }
            
        } catch (Exception $e) {
            error_log('[DataValidator] Data integrity check error: ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            $issues[] = [
                'type' => 'error',
                'count' => 1,
                'message' => 'Data integrity check failed: ' . $e->getMessage()
            ];
        }
        
        return $issues;
    }
    
    /**
     * Log data integrity issues
     * @param array $issues Array of integrity issues
     * @param string $context Context where the check was performed
     */
    public static function logIntegrityIssues($issues, $context = 'System') {
        if (!empty($issues)) {
            $logMessage = "[{$context} Data Integrity] Issues found:\n";
            foreach ($issues as $issue) {
                $logMessage .= "- {$issue['message']}: {$issue['count']} records\n";
            }
            error_log($logMessage, 3, __DIR__ . '/../logs/error.log');
        }
    }
}
?>