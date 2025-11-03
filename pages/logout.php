<?php
require_once '../classes/User.php';

try {
    $user = new User();
    $user->logout();
} catch (Exception $e) {
    // What: Error during logout
    // Why: Session error, etc.
    // How: Log error and show user-friendly message
    error_log('[Logout Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
}

header("Location: ../pages/login.php");
exit;
?>
