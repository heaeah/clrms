<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class SidebarRenderer {
    public static function render() {
        $role = $_SESSION['role'] ?? '';
        $current = basename($_SERVER['PHP_SELF']);
        include __DIR__ . '/sidebar_content.php';
    }
}

// For backward compatibility, render sidebar as before:
SidebarRenderer::render();
