<?php

try {
    // Example: Header logic (if any DB or file operations)
    // $user = $userObj->getUserById($_SESSION['user_id']);
} catch (Exception $e) {
    // What: Error during header rendering
    // Why: File/DB error, etc.
    // How: Log error and show user-friendly message
    error_log('[Header Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
    // Optionally show a user-friendly message
}

class HeaderRenderer {
    public static function render() {
        // Place any header logic here if needed in the future
        // For now, just include the header content
        include __DIR__ . '/header_content.php';
    }
}

// For backward compatibility, render header as before:
HeaderRenderer::render();
