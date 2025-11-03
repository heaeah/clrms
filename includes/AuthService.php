<?php
// includes/AuthService.php

class FlashService {
    public static function set($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    public static function show() {
        if (isset($_SESSION['flash']) && is_array($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            $type = $flash['type'] ?? 'info';
            $message = $flash['message'] ?? '';
            if (trim($message) !== '') {
                echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">';
                echo htmlspecialchars($message);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
            }
            unset($_SESSION['flash']);
        }
    }
}

class AuthService {
    public static function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            FlashService::set('danger', 'Please login first.');
            header('Location: ../pages/login.php');
            exit;
        }
    }
    public static function requireRole($allowed_roles = []) {
        self::requireLogin();
        $user_role = $_SESSION['role'] ?? null;
        if (!$user_role || !in_array($user_role, $allowed_roles)) {
            FlashService::set('danger', 'Access denied.');
            header('Location: dashboard.php');
            exit;
        }
    }
} 