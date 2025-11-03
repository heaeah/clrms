<?php
session_start();
require_once '../classes/EmailVerificationService.php';
require_once '../classes/User.php';

// Check if email is set in session
if (!isset($_SESSION['verification_email'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['verification_email'];
$verificationService = new EmailVerificationService();
$error = '';
$success = '';

// Handle verification code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $code = trim($_POST['code']);
        
        if (empty($code)) {
            $error = 'Please enter the verification code.';
        } else {
            // Verify the code
            $userData = $verificationService->verifyCode($email, $code);
            
            if ($userData) {
                // Code is valid, now create the user account
                $userObj = new User();
                
                $registered = $userObj->register(
                    $userData['name'],
                    $userData['username'],
                    $userData['password'],
                    $userData['role'],
                    $userData['email'],
                    $userData['mobile_number']
                );
                
                if ($registered) {
                    // Clear session data
                    unset($_SESSION['verification_email']);
                    unset($_SESSION['pending_user_data']);
                    
                    // Set success message
                    $_SESSION['flash_success'] = 'Email verified successfully! Your account has been created. You can now login.';
                    
                    // Redirect to login page
                    header('Location: login.php');
                    exit;
                } else {
                    $error = 'Verification successful, but failed to create account. Please contact administrator.';
                }
            } else {
                $error = 'Invalid or expired verification code. Please try again or request a new code.';
            }
        }
    } elseif (isset($_POST['resend_code'])) {
        // Resend verification code
        $newCode = $verificationService->resendCode($email);
        
        if ($newCode) {
            $success = 'A new verification code has been sent to your email address.';
        } else {
            $error = 'Failed to resend verification code. Please try again.';
        }
    }
}

// Auto-cleanup expired codes
$verificationService->cleanupExpiredCodes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
        }
        .verification-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        .verification-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        .code-input {
            font-size: 2rem;
            text-align: center;
            letter-spacing: 10px;
            font-weight: bold;
            padding: 15px;
        }
        .btn-verify {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 40px;
            font-size: 1.1rem;
        }
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-card">
            <div class="verification-header">
                <div class="verification-icon">
                    <i class="bi bi-envelope-check"></i>
                </div>
                <h2>Email Verification</h2>
                <p class="mb-0">Check your email for the verification code</p>
            </div>
            
            <div class="p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mb-4">
                    <p class="text-muted">
                        A 6-digit verification code has been sent to:<br>
                        <strong><?= htmlspecialchars($email) ?></strong>
                    </p>
                    
                    <div class="alert alert-warning border-0">
                        <i class="bi bi-envelope-paper"></i>
                        <strong>Check Your Email Inbox</strong><br>
                        <small>Please check your email for the verification code.</small>
                    </div>
                    
                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle"></i>
                        <strong>For XAMPP/Development:</strong><br>
                        Since this is a local development environment, emails are saved to:<br>
                        <code>C:\xampp\htdocs\clrms\logs\emails\</code><br>
                        <small>Open the latest .txt file with Notepad to see the verification code.</small>
                    </div>
                    
                    <small class="text-muted">
                        <i class="bi bi-clock"></i> The code expires in 10 minutes
                    </small>
                </div>
                
                <form method="POST">
                    <div class="mb-4">
                        <label for="code" class="form-label text-center d-block">Enter Verification Code</label>
                        <input type="text" 
                               class="form-control code-input" 
                               id="code" 
                               name="code" 
                               maxlength="6" 
                               pattern="[0-9]{6}" 
                               placeholder="000000"
                               required
                               autofocus>
                        <small class="form-text text-muted text-center d-block mt-2">
                            Enter the 6-digit code from your email
                        </small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="verify_code" class="btn btn-primary btn-verify">
                            <i class="bi bi-check-circle me-2"></i>Verify Email
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <p class="text-muted mb-2">Didn't receive the code?</p>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="resend_code" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-clockwise me-1"></i>Resend Code
                        </button>
                    </form>
                </div>
                
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-format code input (only allow numbers)
        document.getElementById('code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Auto-submit when 6 digits are entered
        document.getElementById('code').addEventListener('input', function(e) {
            if (this.value.length === 6) {
                // Optional: auto-submit after 500ms delay
                // setTimeout(() => this.form.submit(), 500);
            }
        });
    </script>
</body>
</html>

