<?php
// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/AuthService.php';

// For backward compatibility, you can alias the old function names to the new class methods if needed:
function set_flash($type, $message) {
    FlashService::set($type, $message);
}
function show_flash() {
    FlashService::show();
}
function require_login() {
    AuthService::requireLogin();
}
function require_role($allowed_roles = []) {
    AuthService::requireRole($allowed_roles);
}

try {
    // Example: Authentication logic
    // $user = $authObj->authenticate($_POST['username'], $_POST['password']);
} catch (Exception $e) {
    // What: Error during authentication
    // Why: DB error, invalid credentials, etc.
    // How: Log error and show user-friendly message
    error_log('[Auth Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    // Optionally show a user-friendly message
}
