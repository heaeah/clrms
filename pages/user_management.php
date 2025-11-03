<?php
require_once '../includes/auth.php';
require_role(['Lab Admin']);
require_once '../classes/UserService.php';

// ✅ Only Admins allowed
if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit;
}

$userService = new UserService();

try {
    /**
     * What: Fetch all users
     * Why: For listing in the table
     * How: Uses UserService::getAllUsers
     */
    $users = $userService->getAllUsers();
    
    // Calculate statistics
    $totalUsers = count($users);
    $activeUsers = count(array_filter($users, function($user) { return $user['status'] === 'Active'; }));
    $inactiveUsers = $totalUsers - $activeUsers;
    
    // Role-based statistics
    $labAdminUsers = count(array_filter($users, function($user) { return $user['role'] === 'Lab Admin'; }));
    $studentAssistantUsers = count(array_filter($users, function($user) { return $user['role'] === 'Student Assistant'; }));
    $facultyBorrowerUsers = count(array_filter($users, function($user) { return $user['role'] === 'Faculty Borrower'; }));
    $chairpersonUsers = count(array_filter($users, function($user) { return $user['role'] === 'Chairperson'; }));
    $ictStaffUsers = count(array_filter($users, function($user) { return $user['role'] === 'ICT Staff'; }));
} catch (Exception $e) {
    error_log('[User List Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    $users = [];
    $totalUsers = $activeUsers = $inactiveUsers = $labAdminUsers = $studentAssistantUsers = $facultyBorrowerUsers = $chairpersonUsers = $ictStaffUsers = 0;
}

// Handle delete user
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        if ($userService->deleteUser($id)) {
            set_flash('success', 'User deleted successfully.');
        } else {
            set_flash('danger', 'Failed to delete user.');
        }
    } catch (Exception $e) {
        error_log('[User Delete Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header("Location: user_management.php");
    exit;
}
// Handle toggle user status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $currentStatus = $_GET['status'];
    try {
        if ($userService->toggleStatus($id, $currentStatus)) {
            set_flash('success', 'User status updated successfully.');
        } else {
            set_flash('danger', 'Failed to update status.');
        }
    } catch (Exception $e) {
        error_log('[User Status Toggle Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
    }
    header("Location: user_management.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - CLRMS</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/user_management.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid px-4 mt-4 fade-in">
        <?php show_flash(); ?>

        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-people-fill"></i>
                Manage Users
            </h1>
            <a href="register.php" class="btn btn-primary btn-enhanced">
                <i class="bi bi-person-plus"></i> Add New User
            </a>
        </div>

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
                <div class="role-stat-card faculty-borrower">
                    <div class="role-stat-value"><?= $facultyBorrowerUsers ?></div>
                    <div class="role-stat-label">Faculty Borrower</div>
                    <i class="bi bi-person-badge role-stat-icon"></i>
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
                    <option value="Faculty Borrower">Faculty Borrower</option>
                    <option value="Chairperson">Chairperson</option>
                    <option value="ICT Staff">ICT Staff</option>
                </select>
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="bi bi-people"></i>
                    <h3>No Users Found</h3>
                    <p>There are no users in the system yet. Add your first user to get started.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table" id="usersTable">
                        <thead>
                        <tr>
                            <th>ID</th>
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
                            <tr class="user-row" data-name="<?= strtolower(htmlspecialchars($u['name'])) ?>" data-username="<?= strtolower(htmlspecialchars($u['username'])) ?>" data-role="<?= htmlspecialchars($u['role']) ?>" data-status="<?= htmlspecialchars($u['status']) ?>">
                                <td><strong>#<?= $u['id'] ?></strong></td>
                                <td>
                                    <?php if ($u['profile_picture']): ?>
                                        <img src="../uploads/<?= $u['profile_picture'] ?>" class="profile-picture" alt="Profile">
                                    <?php else: ?>
                                        <img src="../uploads/default.png" class="profile-picture" alt="Default Profile">
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td>
                                    <?php 
                                    $roleClass = '';
                                    switch($u['role']) {
                                        case 'Lab Admin': $roleClass = 'bg-danger text-white'; break;
                                        case 'Student Assistant': $roleClass = 'bg-primary text-white'; break;
                                        case 'Faculty Borrower': $roleClass = 'bg-success text-white'; break;
                                        case 'Chairperson': $roleClass = 'bg-warning text-dark'; break;
                                        case 'ICT Staff': $roleClass = 'bg-info text-white'; break;
                                        default: $roleClass = 'bg-secondary text-white';
                                    }
                                    ?>
                                    <span class="badge <?= $roleClass ?>"><?= htmlspecialchars($u['role']) ?></span>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'Active'): ?>
                                        <span class="status-badge active">
                                            <i class="bi bi-check-circle-fill"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">
                                            <i class="bi bi-x-circle-fill"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn-action btn-edit" title="Edit User">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="user_management.php?delete=<?= $u['id'] ?>"
                                           onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');"
                                           class="btn-action btn-delete" title="Delete User">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                        <?php if ($u['status'] === 'Active'): ?>
                                            <a href="user_management.php?toggle=<?= $u['id'] ?>&status=Active"
                                               onclick="return confirm('Are you sure you want to deactivate this user?');"
                                               class="btn-action btn-toggle-inactive" title="Deactivate User">
                                                <i class="bi bi-slash-circle"></i> Deactivate
                                            </a>
                                        <?php else: ?>
                                            <a href="user_management.php?toggle=<?= $u['id'] ?>&status=Inactive"
                                               onclick="return confirm('Are you sure you want to activate this user?');"
                                               class="btn-action btn-toggle-active" title="Activate User">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </a>
                                        <?php endif; ?>
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
</main>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/user_management.js"></script>
</body>
</html>
