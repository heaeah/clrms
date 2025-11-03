<?php
require_once '../includes/auth.php';
require_role(['ICT Staff']);

require_once '../classes/UserService.php';

$userService = new UserService();
$userId = $_SESSION['user_id'] ?? null;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $data = [
            'name' => trim($_POST['name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['mobile_number']) ?: null,
            'role' => 'ICT Staff' // Keep role as ICT Staff
        ];
        
        // Debug: Log the data being sent
        error_log('[ICT Profile Debug] Data being sent: ' . print_r($data, true), 3, __DIR__ . '/../logs/error.log');
        
        // Validate required fields
        if (empty($data['name'])) {
            set_flash('danger', 'Name is required.');
            header('Location: ict_profile.php');
            exit;
        }
        
        if (empty($data['email'])) {
            set_flash('danger', 'Email is required.');
            header('Location: ict_profile.php');
            exit;
        }
        
        if ($userService->updateUser($userId, $data)) {
            $_SESSION['name'] = $data['name'];
            set_flash('success', 'Profile updated successfully.');
        } else {
            set_flash('danger', 'Failed to update profile.');
        }
        header('Location: ict_profile.php');
        exit;
    } catch (Exception $e) {
        error_log('[ICT Profile Update Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header('Location: ict_profile.php');
        exit;
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            set_flash('danger', 'New passwords do not match.');
        } elseif (strlen($newPassword) < 6) {
            set_flash('danger', 'New password must be at least 6 characters long.');
        } else {
            if ($userService->changePassword($userId, $currentPassword, $newPassword, $confirmPassword)) {
                set_flash('success', 'Password changed successfully.');
            } else {
                set_flash('danger', 'Current password is incorrect.');
            }
        }
        header('Location: ict_profile.php');
        exit;
    } catch (Exception $e) {
        error_log('[ICT Password Change Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header('Location: ict_profile.php');
        exit;
    }
}

try {
    // Get user information
    $user = $userService->getUserById($userId);
    
} catch (Exception $e) {
    error_log('[ICT Profile Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $user = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - ICT Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/ict_portal.css" rel="stylesheet">
</head>
<body class="bg-light ict-portal">
    <?php include '../includes/ict_sidebar.php'; ?>
    
    <!-- Mobile Menu Button -->
    <button class="btn btn-primary mobile-menu-btn d-md-none position-fixed" 
            style="top: 1rem; left: 1rem; z-index: 1060;">
        <i class="bi bi-list"></i>
    </button>

    <main class="main-content">
        <div class="container-fluid px-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-person-fill me-2 text-primary"></i>
                        My Profile
                    </h2>
                    <p class="text-muted mb-0">Manage your ICT staff profile and settings</p>
                </div>
            </div>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-person me-2"></i>Profile Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="update_profile" value="1">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="name" class="form-control" 
                                               value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" name="email" id="email" class="form-control" 
                                               value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="mobile_number" class="form-label">Phone Number</label>
                                        <input type="tel" name="mobile_number" id="mobile_number" class="form-control" 
                                               value="<?= htmlspecialchars($user['mobile_number'] ?? '') ?>" 
                                               oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11)"
                                               maxlength="11" pattern="[0-9]{11}" inputmode="numeric">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="role" class="form-label">Role</label>
                                        <input type="text" name="role" id="role" class="form-control" 
                                               value="<?= htmlspecialchars($user['role'] ?? 'ICT Staff') ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" name="username" id="username" class="form-control" 
                                               value="<?= htmlspecialchars($user['username'] ?? '') ?>" readonly>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-1"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Change Password -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-lock me-2"></i>Change Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="change_password" value="1">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                                        <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8">
                                        <div class="password-requirements mt-2">
                                            <small class="text-muted">Password must contain:</small>
                                            <ul class="list-unstyled mt-1 mb-0">
                                                <li><small class="text-muted">• At least 8 characters</small></li>
                                                <li><small class="text-muted">• At least one lowercase letter (a-z)</small></li>
                                                <li><small class="text-muted">• At least one uppercase letter (A-Z)</small></li>
                                                <li><small class="text-muted">• At least one number (0-9)</small></li>
                                                <li><small class="text-muted">• At least one special character (@$!%*?&)</small></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8">
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-key me-1"></i>Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Profile Avatar -->
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <div class="avatar bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px; font-size: 2rem;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <h5 class="mb-1"><?= htmlspecialchars($user['name'] ?? 'ICT Staff') ?></h5>
                            <p class="text-muted mb-2"><?= htmlspecialchars($user['role'] ?? 'ICT Staff') ?></p>
                            <small class="text-muted">Member since <?= date('M Y', strtotime($user['created_at'] ?? 'now')) ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/ict_portal.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('new_password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword.value) {
                if (this.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
        });
    </script>
</body>
</html>
