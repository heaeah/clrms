<?php
require_once '../includes/auth.php';
require_once '../classes/User.php';

// Only Lab Admins can reset passwords
if ($_SESSION['role'] !== 'Lab Admin') {
    header("Location: dashboard.php");
    exit;
}

$user = new User();
$message = '';
$error = '';

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $username = trim($_POST['username']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($username) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } else {
        try {
            // Get user by username
            $userData = $user->getUserByUsername($username);
            if (!$userData) {
                $error = 'User not found.';
            } else {
                // Update password
                $hashedPassword = hash('sha256', $new_password);
                $stmt = $user->getConn()->prepare("UPDATE users SET password = :password WHERE username = :username");
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':username', $username);
                
                if ($stmt->execute()) {
                    $message = "Password reset successfully for user: {$username}";
                    error_log("[Password Reset] User {$username} password reset by {$_SESSION['username']}", 3, __DIR__ . '/../logs/password_reset.log');
                } else {
                    $error = 'Failed to reset password.';
                }
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
            error_log('[Password Reset Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        }
    }
}

// getUserByUsername method is now available in the User class
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .reset-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-card">
            <div class="reset-header">
                <h3 class="mb-0">
                    <i class="bi bi-key me-2"></i>
                    Reset User Password
                </h3>
                <p class="mb-0 mt-2">Reset password for existing user account</p>
            </div>
            <div class="reset-body">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle me-2"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="bi bi-person me-1"></i>Username
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               placeholder="Enter username"
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            <i class="bi bi-lock me-1"></i>New Password
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="new_password" 
                               name="new_password" 
                               placeholder="Enter new password"
                               minlength="4"
                               required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="bi bi-lock-fill me-1"></i>Confirm Password
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Confirm new password"
                               minlength="4"
                               required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="reset_password" class="btn btn-primary btn-reset">
                            <i class="bi bi-key me-2"></i>Reset Password
                        </button>
                        <a href="user_management.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to User Management
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                confirmPassword.dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>