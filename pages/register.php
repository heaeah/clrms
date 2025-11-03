<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth.php';

// Debug: Check current user session
if (!isset($_SESSION['user_id'])) {
    set_flash('danger', 'You must be logged in to access this page.');
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lab Admin') {
    set_flash('danger', 'Access denied. Only Lab Admins can manage users. Your current role: ' . ($_SESSION['role'] ?? 'Not set'));
    header('Location: dashboard.php');
    exit;
}

// Original role check
require_role(['Lab Admin']); // Only admins can register users
require_once '../classes/User.php';
require_once '../classes/UserService.php';

$userObj = new User();
$userService = new UserService();

// Handle delete user action
if (isset($_GET['delete'])) {
    $userId = intval($_GET['delete']);
    try {
        $userService->deleteUser($userId);
        set_flash('success', 'User deleted successfully.');
    } catch (Exception $e) {
        set_flash('danger', 'Error deleting user: ' . $e->getMessage());
    }
    header("Location: register.php");
    exit;
}

// Handle toggle user status action
if (isset($_GET['toggle'])) {
    $userId = intval($_GET['toggle']);
    $currentStatus = $_GET['status'] ?? '';
    
    try {
        $userService->toggleStatus($userId, $currentStatus);
        set_flash('success', 'User status updated successfully.');
    } catch (Exception $e) {
        set_flash('danger', 'Error updating user status: ' . $e->getMessage());
    }
    header("Location: register.php");
    exit;
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = $_POST['role'];
    $email = trim($_POST['email']);
    $mobile_number = trim($_POST['mobile_number']);

    if ($password !== $confirm_password) {
        set_flash('danger', 'Passwords do not match.');
    } else {
        // Check if username already exists
        if ($userObj->usernameExists($username)) {
            set_flash('danger', 'Username already exists. Please choose a different username.');
        } else {
            // Check if email already exists
            if ($userObj->emailExists($email)) {
                set_flash('danger', 'Email address already registered. Please use a different email.');
            } else {
                // Send verification code instead of directly creating account
                require_once '../classes/EmailVerificationService.php';
                $verificationService = new EmailVerificationService();
                
                // Store user data temporarily
                $userData = [
                    'name' => $name,
                    'username' => $username,
                    'password' => $password, // Will be hashed when account is created
                    'role' => $role,
                    'email' => $email,
                    'mobile_number' => $mobile_number
                ];
                
                // Generate and send verification code
                $code = $verificationService->generateAndSendCode($email, $userData);
                
                if ($code) {
                    // Store email in session for verification page (NOT the code - security!)
                    $_SESSION['verification_email'] = $email;
                    $_SESSION['pending_user_data'] = $userData;
                    
                    set_flash('success', 'Verification code sent to ' . $email . '. Please check your email inbox.');
                    
                    // Redirect to verification page
                    header('Location: verify_email.php');
                    exit;
                } else {
                    set_flash('danger', 'Failed to send verification email. Please try again or contact administrator.');
                }
            }
        }
    }
}


// Get all users for listing
try {
    $users = $userService->getAllUsers();
    
    // Calculate statistics
    $totalUsers = count($users);
    $activeUsers = count(array_filter($users, function($user) { return $user['status'] === 'Active'; }));
    $inactiveUsers = $totalUsers - $activeUsers;
    
    // Role-based statistics
    $labAdminUsers = count(array_filter($users, function($user) { return $user['role'] === 'Lab Admin'; }));
    $studentAssistantUsers = count(array_filter($users, function($user) { return $user['role'] === 'Student Assistant'; }));
    // Faculty Borrower role removed
    $chairpersonUsers = count(array_filter($users, function($user) { return $user['role'] === 'Chairperson'; }));
    $ictStaffUsers = count(array_filter($users, function($user) { return $user['role'] === 'ICT Staff'; }));
} catch (Exception $e) {
    error_log('[Register Page Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    set_flash('danger', 'Error loading user data: ' . $e->getMessage());
    $users = [];
    $totalUsers = $activeUsers = $inactiveUsers = $labAdminUsers = $studentAssistantUsers = $facultyBorrowerUsers = $chairpersonUsers = $ictStaffUsers = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage User - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/register.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid fade-in">
        <?php show_flash(); ?>
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-person-gear"></i>
                Manage User
            </h1>
            <p class="page-subtitle">Create and configure user accounts for the system</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="management-tabs">
            <button class="tab-btn active" data-tab="create">
                <i class="bi bi-person-plus-fill"></i>
                Create User
            </button>
            <button class="tab-btn" data-tab="manage">
                <i class="bi bi-people-fill"></i>
                Manage Users
            </button>
        </div>

        <!-- Create User Tab -->
        <div class="tab-content active" id="create-tab">
            <!-- Form Container -->
            <div class="form-container">
                <!-- Form Header -->
                <div class="form-header">
                    <h2>
                        <i class="bi bi-person-plus-fill"></i>
                        Create New User Account
                    </h2>
                    <p>Fill in the information below to create a new user account</p>
                </div>

            <!-- Form Body -->
            <div class="form-body">
                <form method="POST" id="userForm" novalidate>
                    <!-- Personal Information Section -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-person-fill"></i>
                                Full Name <span class="required">*</span>
                            </label>
                            <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                            <div class="valid-feedback">
                                <i class="bi bi-check-circle"></i>
                                Full name looks good!
                            </div>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i>
                                Please provide a valid full name.
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-person-circle"></i>
                                Username <span class="required">*</span>
                            </label>
                            <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                            <div class="valid-feedback">
                                <i class="bi bi-check-circle"></i>
                                Username is available!
                            </div>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i>
                                Username must be at least 3 characters long.
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-envelope-fill"></i>
                                Email Address <span class="required">*</span>
                            </label>
                            <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                            <div class="valid-feedback">
                                <i class="bi bi-check-circle"></i>
                                Email address looks good!
                            </div>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i>
                                Please provide a valid email address.
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-phone-fill"></i>
                                Mobile Number <span class="required">*</span>
                            </label>
                            <input type="tel" name="mobile_number" class="form-control" placeholder="Enter 11-digit mobile number" required maxlength="11" pattern="[0-9]{11}" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 11)">
                            <div class="valid-feedback">
                                <i class="bi bi-check-circle"></i>
                                Mobile number looks good!
                            </div>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i>
                                Please provide a valid 11-digit mobile number.
                            </div>
                        </div>
                    </div>

                    <!-- Password Section -->
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-lock-fill"></i>
                                Password <span class="required">*</span>
                            </label>
                            <input type="password" name="password" class="form-control" placeholder="Enter password" required id="password">
                            <div class="password-strength" id="passwordStrength">
                                <div class="password-strength-bar"></div>
                            </div>
                            <div class="password-requirements">
                                <small>Password requirements:</small>
                                <ul id="passwordRequirements">
                                    <li id="length" class="invalid">At least 8 characters</li>
                                    <li id="uppercase" class="invalid">One uppercase letter</li>
                                    <li id="lowercase" class="invalid">One lowercase letter</li>
                                    <li id="number" class="invalid">One number</li>
                                    <li id="special" class="invalid">One special character (@$!%*?&)</li>
                                </ul>
                            </div>
                            <div class="valid-feedback">
                                <i class="bi bi-check-circle"></i>
                                Password meets all requirements!
                            </div>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i>
                                Password does not meet requirements.
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-lock-fill"></i>
                                Confirm Password <span class="required">*</span>
                            </label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm password" required id="confirmPassword">
                            <div class="valid-feedback">
                                <i class="bi bi-check-circle"></i>
                                Passwords match!
                            </div>
                            <div class="invalid-feedback">
                                <i class="bi bi-exclamation-circle"></i>
                                Passwords do not match.
                            </div>
                        </div>
                    </div>

                    <!-- Role Selection -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-shield-check"></i>
                            User Role <span class="required">*</span>
                        </label>
                        <select name="role" class="form-select" required>
                            <option value="">Select a role...</option>
                            <option value="Student Assistant">Student Assistant</option>
                            <option value="Lab Admin">Lab Admin</option>
                            <option value="Chairperson">Chairperson</option>
                            <option value="ICT Staff">ICT Staff</option>
                        </select>
                        <div class="valid-feedback">
                            <i class="bi bi-check-circle"></i>
                            Role selected successfully!
                        </div>
                        <div class="invalid-feedback">
                            <i class="bi bi-exclamation-circle"></i>
                            Please select a user role.
                        </div>
                    </div>

                    <!-- Submit Section -->
                    <div class="submit-section">
                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="bi bi-check-circle-fill"></i>
                            Create User Account
                        </button>
                        <div style="margin-top: 15px;">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Only Lab Admins can create user accounts
                            </small>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        </div>

        <!-- Manage Users Tab -->
        <div class="tab-content" id="manage-tab">
            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-value"><?= $totalUsers ?></div>
                    <div class="stat-label">Total Users</div>
                    <i class="bi bi-people stat-icon"></i>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?= $activeUsers ?></div>
                    <div class="stat-label">Active Users</div>
                    <i class="bi bi-person-check stat-icon"></i>
                </div>
                <div class="stat-card danger">
                    <div class="stat-value"><?= $inactiveUsers ?></div>
                    <div class="stat-label">Inactive Users</div>
                    <i class="bi bi-person-x stat-icon"></i>
                </div>
            </div>

            <!-- Role-based Statistics -->
            <div class="role-stats-container">
                <h3 class="section-title">
                    <i class="bi bi-diagram-3"></i>
                    Users by Role
                </h3>
                <div class="role-stats-grid">
                    <div class="role-stat-card lab-admin">
                        <div class="role-stat-value"><?= $labAdminUsers ?></div>
                        <div class="role-stat-label">Lab Admin</div>
                        <i class="bi bi-shield-check role-stat-icon"></i>
                    </div>
                    <div class="role-stat-card student-assistant">
                        <div class="role-stat-value"><?= $studentAssistantUsers ?></div>
                        <div class="role-stat-label">Student Assistant</div>
                        <i class="bi bi-person-workspace role-stat-icon"></i>
                    </div>
                    <div class="role-stat-card chairperson">
                        <div class="role-stat-value"><?= $chairpersonUsers ?></div>
                        <div class="role-stat-label">Chairperson</div>
                        <i class="bi bi-person-star role-stat-icon"></i>
                    </div>
                    <div class="role-stat-card ict-staff">
                        <div class="role-stat-value"><?= $ictStaffUsers ?></div>
                        <div class="role-stat-label">ICT Staff</div>
                        <i class="bi bi-pc-display role-stat-icon"></i>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Controls -->
            <div class="controls-section">
                <div class="search-filter-container">
                    <div class="search-group">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search users by name, username, or role...">
                        <i class="bi bi-search search-icon"></i>
                    </div>
                    <select id="roleFilter" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="Lab Admin">Lab Admin</option>
                        <option value="Student Assistant">Student Assistant</option>
                        <option value="Chairperson">Chairperson</option>
                        <option value="ICT Staff">ICT Staff</option>
                    </select>
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                    <button type="button" id="clearFiltersBtn" class="btn btn-outline-secondary" title="Clear all filters">
                        <i class="bi bi-x-circle me-1"></i>Clear Filters
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <i class="bi bi-people"></i>
                        <h3>No Users Found</h3>
                        <p>There are no users in the system yet. Create your first user to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="usersTable">
                            <thead>
                            <tr>
                                <th>Profile</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($users as $u): ?>
                                <?php if (strtolower($u['username']) === 'testuser' || strtolower($u['username']) === 'demouser'): ?>
                                    <?php continue; // Skip testuser and demouser ?>
                                <?php endif; ?>
                                <tr class="user-row" data-name="<?= strtolower(htmlspecialchars($u['name'])) ?>" data-username="<?= strtolower(htmlspecialchars($u['username'])) ?>" data-role="<?= htmlspecialchars($u['role']) ?>" data-status="<?= htmlspecialchars($u['status']) ?>">
                                    <td>
                                        <?php if ($u['profile_picture'] && file_exists("../uploads/profile/" . $u['profile_picture'])): ?>
                                            <img src="../uploads/profile/<?= $u['profile_picture'] ?>" alt="Profile" class="profile-img">
                                        <?php elseif ($u['profile_picture'] && file_exists("../uploads/" . $u['profile_picture'])): ?>
                                            <img src="../uploads/<?= $u['profile_picture'] ?>" alt="Profile" class="profile-img">
                                        <?php else: ?>
                                            <div class="profile-placeholder">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($u['name']) ?></td>
                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                    <td>
                                        <span class="role-badge role-<?= strtolower(str_replace(' ', '-', $u['role'])) ?>">
                                            <?= htmlspecialchars($u['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $u['status'] === 'Active' ? 'status-active' : 'status-inactive' ?>">
                                            <?= $u['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn-action btn-edit" title="Edit User">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="register.php?delete=<?= $u['id'] ?>" 
                                               onclick="return confirm('Are you sure you want to delete this user?');" 
                                               class="btn-action btn-delete" title="Delete User">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <a href="register.php?toggle=<?= $u['id'] ?>&status=<?= $u['status'] ?>" 
                                               onclick="return confirm('Are you sure you want to <?= $u['status'] === 'Active' ? 'deactivate' : 'activate' ?> this user?');" 
                                               class="btn-action btn-toggle" title="<?= $u['status'] === 'Active' ? 'Deactivate' : 'Activate' ?> User">
                                                <i class="bi bi-toggle-<?= $u['status'] === 'Active' ? 'on' : 'off' ?>"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/register.js?v=<?= time() ?>"></script>
</body>
</html>
