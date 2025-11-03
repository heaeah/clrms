<?php
require_once '../includes/auth.php';
require_once '../classes/User.php';

$user = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($user->login($username, $password)) {
        $role = $_SESSION['role'] ?? '';
        if ($role === 'ICT Staff') {
            header("Location: ict_portal.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    } else {
        set_flash('danger', 'Invalid username, password, or inactive account.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background elements */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .bg-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        .bg-circle:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .bg-circle:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .bg-circle:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 35px 70px rgba(0, 0, 0, 0.2);
        }

        .login-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            padding: 40px 30px 30px;
            text-align: center;
            position: relative;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .logo-container {
            position: relative;
            z-index: 2;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
            color: white;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .login-title {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .login-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
            font-weight: 400;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group.error .form-control {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }

        .form-group.error .input-icon {
            color: #ef4444;
        }

        .password-requirements {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            margin-top: 8px;
        }

        .requirement {
            margin-bottom: 4px;
            font-size: 0.8rem;
            color: #6b7280;
        }

        .requirement.valid {
            color: #059669;
        }

        .requirement.valid i {
            color: #059669;
        }

        .requirement.invalid {
            color: #dc2626;
        }

        .requirement.invalid i {
            color: #dc2626;
        }

        .requirement i {
            margin-right: 6px;
            font-size: 0.7rem;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.9rem;
            display: block;
        }

        .input-group {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            background: #ffffff !important;
            transition: all 0.3s ease;
            color: #000000 !important;
            height: 56px;
            line-height: 1.5;
        }

        .form-control:focus {
            outline: none;
            border-color: #4facfe;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(79, 172, 254, 0.1);
            transform: translateY(-1px);
            color: #000000 !important;
        }

        .form-control:focus::placeholder {
            color: #4facfe;
            opacity: 0.7;
        }

        .form-control::placeholder {
            color: #6b7280;
            font-weight: 400;
            transition: all 0.3s ease;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            z-index: 2;
            pointer-events: none;
        }

        .form-control:focus + .input-icon {
            color: #4facfe;
        }

        .btn-login {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 172, 254, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-footer {
            padding: 20px 30px;
            text-align: center;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
        }

        .footer-text {
            color: #6b7280;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #16a34a;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        /* Loading animation */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }

        .btn-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Responsive design */
        @media (max-width: 576px) {
            .login-container {
                padding: 15px;
            }
            
            .login-card {
                border-radius: 20px;
            }
            
            .login-header {
                padding: 30px 20px 25px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
            
            .login-footer {
                padding: 15px 20px;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }

            .form-control {
                height: 52px;
                font-size: 16px; /* Prevents zoom on iOS */
            }

            .input-icon {
                font-size: 1.1rem;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .login-card {
                background: rgba(17, 24, 39, 0.95);
                border-color: rgba(255, 255, 255, 0.1);
            }
            
            .form-control {
                background: #1f2937 !important;
                border-color: #4b5563;
                color: #ffffff !important;
            }
            
            .form-control:focus {
                background: #1f2937 !important;
                border-color: #4facfe;
                color: #ffffff !important;
            }
            
            .form-label {
                color: #f9fafb;
            }
            
            .login-footer {
                background: #1f2937;
                border-color: #374151;
            }
            
            .footer-text {
                color: #9ca3af;
            }
        }
    </style>
</head>
<body>

<!-- Animated background -->
<div class="bg-animation">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
</div>

<div class="login-container">
    <?php show_flash(); ?>
    
    <div class="login-card">
        <div class="login-header">
            <div class="logo-container">
                <div class="logo-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to your CLRMS account</p>
            </div>
        </div>
        
        <div class="login-body">
            <form method="POST" id="loginForm">
                <div class="form-group" id="usernameGroup">
                    <label class="form-label" for="username">
                        <i class="bi bi-person me-1"></i>Username
                    </label>
                    <div class="input-group">
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               placeholder="Enter your username" 
                               required 
                               autofocus
                               autocomplete="username">
                        <i class="bi bi-person-fill input-icon"></i>
                    </div>
                </div>
                
                <div class="form-group" id="passwordGroup">
                    <label class="form-label" for="password">
                        <i class="bi bi-lock me-1"></i>Password
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter your password" 
                               required
                               minlength="1"
                               autocomplete="current-password">
                        <i class="bi bi-lock-fill input-icon"></i>
                    </div>
                    <div class="password-requirements mt-2" id="passwordRequirements" style="display: none;">
                        <small class="text-muted">
                            <div class="requirement" id="req-length"><i class="bi bi-circle"></i> At least 8 characters</div>
                            <div class="requirement" id="req-lowercase"><i class="bi bi-circle"></i> At least one lowercase letter</div>
                            <div class="requirement" id="req-uppercase"><i class="bi bi-circle"></i> At least one uppercase letter</div>
                            <div class="requirement" id="req-number"><i class="bi bi-circle"></i> At least one number</div>
                            <div class="requirement" id="req-special"><i class="bi bi-circle"></i> At least one special character (@$!%*?&)</div>
                        </small>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Sign In
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            <p class="footer-text">
                <i class="bi bi-shield-check me-1"></i>
                &copy; <?= date('Y') ?> CLRMS - Computer Laboratory Resource Management System
            </p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const inputs = document.querySelectorAll('.form-control');
    
    // Add focus effects and validation
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.parentElement.classList.add('focused');
            this.parentElement.parentElement.classList.remove('error');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.parentElement.classList.remove('focused');
            // Validate on blur
            if (this.value.trim() === '') {
                this.parentElement.parentElement.classList.add('error');
            }
        });

        input.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.parentElement.parentElement.classList.remove('error');
            }
        });
    });

    // Password validation
    const passwordInput = document.getElementById('password');
    const passwordRequirements = document.getElementById('passwordRequirements');
    
    passwordInput.addEventListener('focus', function() {
        passwordRequirements.style.display = 'block';
    });
    
    passwordInput.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            passwordRequirements.style.display = 'none';
        }
    });
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        // Check each requirement
        const requirements = {
            length: password.length >= 8,
            lowercase: /[a-z]/.test(password),
            uppercase: /[A-Z]/.test(password),
            number: /\d/.test(password),
            special: /[@$!%*?&]/.test(password)
        };
        
        // Update visual indicators
        document.getElementById('req-length').className = 'requirement ' + (requirements.length ? 'valid' : 'invalid');
        document.getElementById('req-lowercase').className = 'requirement ' + (requirements.lowercase ? 'valid' : 'invalid');
        document.getElementById('req-uppercase').className = 'requirement ' + (requirements.uppercase ? 'valid' : 'invalid');
        document.getElementById('req-number').className = 'requirement ' + (requirements.number ? 'valid' : 'invalid');
        document.getElementById('req-special').className = 'requirement ' + (requirements.special ? 'valid' : 'invalid');
        
        // Update icons
        document.querySelectorAll('.requirement i').forEach((icon, index) => {
            const reqName = Object.keys(requirements)[index];
            icon.className = requirements[reqName] ? 'bi bi-check-circle-fill' : 'bi bi-circle';
        });
    });
    
    // Form submission with loading state
    loginForm.addEventListener('submit', function(e) {
        loginBtn.classList.add('btn-loading');
        loginBtn.disabled = true;
        
        // Remove loading state after 2 seconds (fallback)
        setTimeout(() => {
            loginBtn.classList.remove('btn-loading');
            loginBtn.disabled = false;
        }, 2000);
    });
    
    // Add subtle animations on page load
    const loginCard = document.querySelector('.login-card');
    loginCard.style.opacity = '0';
    loginCard.style.transform = 'translateY(30px)';
    
    setTimeout(() => {
        loginCard.style.transition = 'all 0.6s ease';
        loginCard.style.opacity = '1';
        loginCard.style.transform = 'translateY(0)';
    }, 100);
    
    // Add floating animation to background circles
    const circles = document.querySelectorAll('.bg-circle');
    circles.forEach((circle, index) => {
        circle.style.animationDelay = `${index * 2}s`;
    });
});
</script>
</body>
</html>
