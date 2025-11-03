<?php
require_once 'Database.php';
require_once __DIR__ . '/../includes/send_mail.php';

class EmailVerificationService {
    private $conn;
    
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }
    
    /**
     * Generate and send verification code to email
     * @param string $email
     * @param array $userData - User registration data to store temporarily
     * @return string|false - Returns verification code on success, false on failure
     */
    public function generateAndSendCode($email, $userData = []) {
        // Generate 6-digit code first (outside try-catch)
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        try {
            // Set expiration time (10 minutes from now)
            $createdAt = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Store user data as JSON
            $userDataJson = json_encode($userData);
            
            // Delete any existing verification codes for this email
            $deleteStmt = $this->conn->prepare("DELETE FROM email_verification_codes WHERE email = ?");
            $deleteStmt->execute([$email]);
            
            // Insert new verification code
            $stmt = $this->conn->prepare("
                INSERT INTO email_verification_codes 
                (email, verification_code, created_at, expires_at, user_data) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([$email, $code, $createdAt, $expiresAt, $userDataJson]);
            
            if (!$result) {
                error_log('[Email Verification] Database insert failed', 3, __DIR__ . '/../logs/error.log');
            }
            
        } catch (Exception $e) {
            error_log('[Email Verification DB Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            // Continue anyway - we still want to save to file
        }
        
        // Send email with verification code
        $subject = "CLRMS - Email Verification Code";
        $message = "Hello,\n\n";
        $message .= "Your email verification code is: " . $code . "\n\n";
        $message .= "This code will expire in 10 minutes.\n\n";
        $message .= "If you did not request this code, please ignore this email.\n\n";
        $message .= "Thank you,\nCLRMS Team";
        
        // Try to send email via SMTP
        try {
            $emailSent = sendSMTPMail($email, $subject, $message);
            if ($emailSent) {
                error_log('[Email Verification] Email sent successfully to: ' . $email, 3, __DIR__ . '/../logs/error.log');
            }
        } catch (Exception $e) {
            error_log('[Email Verification SMTP Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            $emailSent = false;
        }
        
        // For XAMPP/local development: Save to file as backup
        // In production, emails should be sent via SMTP only
        $this->saveEmailToFile($email, $subject, $message, $code);
        
        // Log the code generation (but don't expose it in production)
        error_log('[Email Verification] Code generated for: ' . $email . ' (Code saved to logs/emails/)', 3, __DIR__ . '/../logs/error.log');
        
        // Return the code so the system knows it was generated
        return $code;
    }
    
    /**
     * Verify the code entered by user
     * @param string $email
     * @param string $code
     * @return array|false - Returns user data on success, false on failure
     */
    public function verifyCode($email, $code) {
        try {
            // Debug: Log the verification attempt
            error_log('[Email Verification] Verifying code for email: ' . $email . ' | Code: ' . $code, 3, __DIR__ . '/../logs/error.log');
            
            // First check if there's any code for this email
            $checkStmt = $this->conn->prepare("SELECT * FROM email_verification_codes WHERE email = ?");
            $checkStmt->execute([$email]);
            $allCodes = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('[Email Verification] Found ' . count($allCodes) . ' codes for this email', 3, __DIR__ . '/../logs/error.log');
            
            if (count($allCodes) > 0) {
                foreach ($allCodes as $codeData) {
                    error_log('[Email Verification] DB Code: ' . $codeData['verification_code'] . ' | Expires: ' . $codeData['expires_at'] . ' | Verified: ' . $codeData['is_verified'], 3, __DIR__ . '/../logs/error.log');
                }
            }
            
            $stmt = $this->conn->prepare("
                SELECT * FROM email_verification_codes 
                WHERE email = ? 
                AND verification_code = ? 
                AND is_verified = 0 
                AND expires_at > NOW()
            ");
            
            $stmt->execute([$email, $code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                error_log('[Email Verification] Code verified successfully!', 3, __DIR__ . '/../logs/error.log');
                
                // Mark as verified
                $updateStmt = $this->conn->prepare("
                    UPDATE email_verification_codes 
                    SET is_verified = 1 
                    WHERE id = ?
                ");
                $updateStmt->execute([$result['id']]);
                
                // Return the stored user data
                return json_decode($result['user_data'], true);
            }
            
            error_log('[Email Verification] Verification failed - code not found or expired', 3, __DIR__ . '/../logs/error.log');
            return false;
            
        } catch (Exception $e) {
            error_log('[Email Verification Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }
    
    /**
     * Check if email has been verified
     * @param string $email
     * @return bool
     */
    public function isEmailVerified($email) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM email_verification_codes 
                WHERE email = ? 
                AND is_verified = 1
            ");
            
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
            
        } catch (Exception $e) {
            error_log('[Email Verification Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }
    
    /**
     * Clean up expired verification codes
     * @return int - Number of deleted records
     */
    public function cleanupExpiredCodes() {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM email_verification_codes 
                WHERE expires_at < NOW() 
                AND is_verified = 0
            ");
            
            $stmt->execute();
            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log('[Email Verification Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return 0;
        }
    }
    
    /**
     * Resend verification code
     * @param string $email
     * @return string|false
     */
    public function resendCode($email) {
        try {
            // Get existing user data if available
            $stmt = $this->conn->prepare("
                SELECT user_data 
                FROM email_verification_codes 
                WHERE email = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $userData = $result ? json_decode($result['user_data'], true) : [];
            
            // Generate and send new code
            return $this->generateAndSendCode($email, $userData);
            
        } catch (Exception $e) {
            error_log('[Email Verification Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }
    
    /**
     * Save email to file for XAMPP/development
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $code
     * @return void
     */
    private function saveEmailToFile($to, $subject, $body, $code) {
        try {
            // Create emails directory if it doesn't exist
            $emailDir = __DIR__ . '/../logs/emails';
            if (!is_dir($emailDir)) {
                mkdir($emailDir, 0777, true);
            }
            
            // Create filename with timestamp
            $filename = $emailDir . '/verification_' . date('Y-m-d_His') . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $to) . '.txt';
            
            // Format email content
            $content = "===============================================\n";
            $content .= "CLRMS EMAIL VERIFICATION CODE\n";
            $content .= "===============================================\n";
            $content .= "To: " . $to . "\n";
            $content .= "Subject: " . $subject . "\n";
            $content .= "Date: " . date('Y-m-d H:i:s') . "\n";
            $content .= "===============================================\n\n";
            $content .= "VERIFICATION CODE: " . $code . "\n\n";
            $content .= "===============================================\n";
            $content .= "Message:\n";
            $content .= "===============================================\n";
            $content .= $body . "\n";
            $content .= "===============================================\n\n";
            $content .= "This email has been saved to: " . $filename . "\n";
            
            // Save to file
            file_put_contents($filename, $content);
            
            error_log('[Email Verification] Email saved to file: ' . $filename, 3, __DIR__ . '/../logs/error.log');
            
        } catch (Exception $e) {
            error_log('[Email Verification] Failed to save email to file: ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        }
    }
}

