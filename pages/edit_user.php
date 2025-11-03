<?php
require_once '../includes/auth.php';
require_once '../classes/UserService.php';

// ✅ Only Lab Admins allowed
if ($_SESSION['role'] !== 'Lab Admin') {
    header("Location: dashboard.php");
    exit;
}

$userService = new UserService();

// Get User ID
if (!isset($_GET['id'])) {
    header("Location: user_management.php");
    exit;
}

$id = intval($_GET['id']);
try {
    /**
     * What: Fetch user by ID
     * Why: For editing
     * How: Uses UserService::getUserById
     */
    $user = $userService->getUserById($id);
    if (!$user) {
        set_flash('danger', 'User not found.');
        header("Location: user_management.php");
        exit;
    }
} catch (Exception $e) {
    error_log('[User Fetch Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    set_flash('danger', 'Error: ' . $e->getMessage());
    header("Location: user_management.php");
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $email = trim($_POST['email']) ?: null;
    $phone = trim($_POST['mobile_number']) ?: null;
    
    try {
        if ($userService->updateUser($id, [
            'name' => $name, 
            'role' => $role, 
            'email' => $email, 
            'phone' => $phone
        ])) {
            set_flash('success', 'User updated successfully.');
            header("Location: user_management.php");
            exit;
        } else {
            set_flash('danger', 'Failed to update user.');
        }
    } catch (Exception $e) {
        error_log('[User Update Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - CLRMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/edit_user.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-light">

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">
                    <i class="bi bi-person-gear me-2 text-primary"></i>
                    Edit User
                </h2>
                <p class="text-muted mb-0">Update user information and permissions</p>
            </div>
            <div>
                <a href="register.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Users
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php show_flash(); ?>

        <!-- Edit User Card -->
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-person-fill me-2"></i>
                            User Information
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" id="editUserForm">
                            <!-- User Avatar Section -->
                            <div class="text-center mb-4">
                                <div class="user-avatar mb-3">
                                    <div class="avatar-circle">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                </div>
                                <h5 class="mb-1"><?= htmlspecialchars($user['name']) ?></h5>
                                <span class="badge bg-<?= match($user['role']) {
                                    'Admin' => 'danger',
                                    'Lab Admin' => 'primary',
                                    'Faculty Borrower' => 'success',
                                    'Chairperson' => 'warning',
                                    'ICT Staff' => 'info',
                                    default => 'secondary'
                                } ?> fs-6">
                                    <?= htmlspecialchars($user['role']) ?>
                                </span>
                            </div>

                            <!-- Form Fields -->
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="name" class="form-label fw-bold">
                                        <i class="bi bi-person me-1"></i>Full Name
                                    </label>
                                    <input type="text" 
                                           name="name" 
                                           id="name" 
                                           value="<?= htmlspecialchars($user['name']) ?>" 
                                           class="form-control form-control-lg" 
                                           required
                                           placeholder="Enter full name">
                                    <div class="form-text">The user's complete name as it should appear in the system</div>
                                </div>

                                <div class="col-12">
                                    <label for="role" class="form-label fw-bold">
                                        <i class="bi bi-shield-check me-1"></i>Role & Permissions
                                    </label>
                                    <select name="role" id="role" class="form-select form-select-lg" required>
                                        <option value="">Select a role</option>
                                        <option value="Admin" <?= $user['role'] == 'Admin' ? 'selected' : '' ?>>
                                            <i class="bi bi-shield-fill-check"></i> Admin - Full system access
                                        </option>
                                        <option value="Lab Admin" <?= $user['role'] == 'Lab Admin' ? 'selected' : '' ?>>
                                            <i class="bi bi-building"></i> Lab Admin - Laboratory management
                                        </option>
                                        <option value="Faculty Borrower" <?= $user['role'] == 'Faculty Borrower' ? 'selected' : '' ?>>
                                            <i class="bi bi-person-badge"></i> Faculty Borrower - Equipment borrowing
                                        </option>
                                        <option value="Chairperson" <?= $user['role'] == 'Chairperson' ? 'selected' : '' ?>>
                                            <i class="bi bi-person-star"></i> Chairperson - Department oversight
                                        </option>
                                        <option value="ICT Staff" <?= $user['role'] == 'ICT Staff' ? 'selected' : '' ?>>
                                            <i class="bi bi-pc-display"></i> ICT Staff - Technical support
                                        </option>
                                    </select>
                                    <div class="form-text">Choose the appropriate role based on the user's responsibilities</div>
                                </div>

                                <!-- User Status -->
                                <div class="col-12">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-toggle-on me-1"></i>Account Status
                                    </label>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-<?= $user['status'] === 'Active' ? 'success' : 'danger' ?> fs-6 me-3">
                                            <i class="bi bi-<?= $user['status'] === 'Active' ? 'check-circle' : 'x-circle' ?> me-1"></i>
                                            <?= htmlspecialchars($user['status']) ?>
                                        </span>
                                        <small class="text-muted">Account status cannot be changed from this page</small>
                                    </div>
                                </div>

                                <!-- Additional Info -->
                                <?php if (isset($user['email']) && $user['email']): ?>
                                <div class="col-12">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-envelope me-1"></i>Email Address
                                    </label>
                                    <div class="form-control-plaintext bg-light p-3 rounded">
                                        <i class="bi bi-envelope me-2"></i>
                                        <?= htmlspecialchars($user['email']) ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (isset($user['mobile_number']) && $user['mobile_number']): ?>
                                <div class="col-12">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-phone me-1"></i>Mobile Number
                                    </label>
                                    <div class="form-control-plaintext bg-light p-3 rounded">
                                        <i class="bi bi-phone me-2"></i>
                                        <?= htmlspecialchars($user['mobile_number']) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-3 mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary btn-lg flex-fill">
                                    <i class="bi bi-check-circle me-2"></i>Update User
                                </button>
                                <a href="register.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-x-circle me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Role Information Card -->
                <div class="card shadow-sm border-0 mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>Role Permissions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="permission-item">
                                    <i class="bi bi-shield-fill-check text-danger me-2"></i>
                                    <strong>Admin:</strong> Full system access, user management, all features
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="permission-item">
                                    <i class="bi bi-building text-primary me-2"></i>
                                    <strong>Lab Admin:</strong> Laboratory management, equipment oversight
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="permission-item">
                                    <i class="bi bi-person-badge text-success me-2"></i>
                                    <strong>Faculty Borrower:</strong> Equipment borrowing, reservations
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="permission-item">
                                    <i class="bi bi-pc-display text-info me-2"></i>
                                    <strong>ICT Staff:</strong> Technical support, maintenance management
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/edit_user.js"></script>
</body>
</html>
