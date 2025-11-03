<?php
require_once '../includes/auth.php';
require_once '../classes/UserService.php';

$userService = new UserService();
try {
    /**
     * What: Fetch current user by ID
     * Why: For profile display
     * How: Uses UserService::getUserById
     */
    $currentUser = $userService->getUserById($_SESSION['user_id']);
} catch (Exception $e) {
    error_log('[Profile Fetch Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    set_flash('danger', 'Error: ' . $e->getMessage());
    $currentUser = [];
}

// Handle password change
if (isset($_POST['change_password'])) {
    try {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        if ($userService->changePassword($_SESSION['user_id'], $old, $new, $confirm)) {
            set_flash('success', 'Password changed successfully.');
        } else {
            set_flash('danger', 'Failed to change password. Please check your old password.');
        }
        header("Location: profile.php");
        exit;
    } catch (Exception $e) {
        // What: Error during password change
        // Why: DB error, validation error, etc.
        // How: Log error and show user-friendly message
        error_log('[Profile Password Change Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header("Location: profile.php");
        exit;
    }
}

// Handle profile picture upload
if (isset($_POST['upload_picture'])) {
    try {
        if ($userService->uploadProfilePicture($_SESSION['user_id'], $_FILES['profile_picture'])) {
            set_flash('success', 'Profile picture updated.');
        } else {
            set_flash('danger', 'Failed to upload picture.');
        }
        header("Location: profile.php");
        exit;
    } catch (Exception $e) {
        // What: Error during profile picture upload
        // Why: File error, DB error, etc.
        // How: Log error and show user-friendly message
        error_log('[Profile Picture Upload Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header("Location: profile.php");
        exit;
    }
}

// Handle contact info update
if (isset($_POST['update_contact'])) {
    try {
        $email = trim($_POST['email']);
        $mobile_number = trim($_POST['mobile_number']);
        if ($userService->updateContactInfo($_SESSION['user_id'], $email, $mobile_number)) {
            set_flash('success', 'Contact information updated.');
        } else {
            set_flash('danger', 'Failed to update contact information.');
        }
        header("Location: profile.php");
        exit;
    } catch (Exception $e) {
        // What: Error during contact info update
        // Why: DB error, validation error, etc.
        // How: Log error and show user-friendly message
        error_log('[Profile Contact Update Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
        set_flash('danger', 'Error: ' . $e->getMessage());
        header("Location: profile.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Management - CLRMS</title>

    <!-- Bootstrap and CSS Links -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/profile.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Sidebar -->
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<main class="main-content">
    <div class="container-fluid px-4 mt-4">
        <!-- Flash Messages -->
        <?php show_flash(); ?>

        <!-- Page Heading -->
        <h1 class="h3 text-primary mb-4 text-center"><i class="bi bi-person-circle"></i> Profile Management</h1>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img src="../uploads/<?= $currentUser['profile_picture'] ? $currentUser['profile_picture'] : 'default.png' ?>"
                                 class="rounded-circle shadow-sm" style="width: 150px; height: 150px; object-fit: cover;" alt="Profile Picture">
                        </div>
                        <h4 class="text-primary mb-1"><?= htmlspecialchars($currentUser['name']) ?></h4>
                        <p class="text-muted mb-3"><?= htmlspecialchars($currentUser['role']) ?></p>
                        <form method="POST" enctype="multipart/form-data" class="mt-3">
                            <div class="mb-3">
                                <input type="file" name="profile_picture" id="profile_picture" class="form-control" accept="image/*" required>
                            </div>
                            <button type="submit" name="upload_picture" class="btn btn-primary w-100">
                                <i class="bi bi-cloud-upload"></i> Upload Picture
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white text-center">
                        <strong><i class="bi bi-person-lines-fill"></i> Contact Information</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3 text-start">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3 text-start">
                                <label for="mobile_number" class="form-label">Mobile Number</label>
                                <input type="text" name="mobile_number" id="mobile_number" class="form-control" value="<?= htmlspecialchars($currentUser['mobile_number'] ?? '') ?>" required>
                            </div>
                            <button type="submit" name="update_contact" class="btn btn-success w-100">
                                <i class="bi bi-save"></i> Update Contact Info
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center">
                        <strong><i class="bi bi-shield-lock"></i> Change Password</strong>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3 text-start">
                                <label for="old_password" class="form-label">Old Password</label>
                                <input type="password" name="old_password" id="old_password" class="form-control" placeholder="Enter old password" required>
                            </div>
                            <div class="mb-3 text-start">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password" required>
                            </div>
                            <div class="mb-3 text-start">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Re-enter new password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-success w-100">
                                <i class="bi bi-save"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/profile.js"></script>
</body>
</html>