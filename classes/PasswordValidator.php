<?php

class PasswordValidator {
    
    /**
     * Validate password strength
     * @param string $password
     * @return array
     */
    public static function validatePassword($password) {
        $errors = [];
        
        // Check minimum length
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        // Check for lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        // Check for uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        // Check for number
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        // Check for special character
        if (!preg_match('/[@$!%*?&]/', $password)) {
            $errors[] = 'Password must contain at least one special character (@$!%*?&)';
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get password requirements as HTML
     * @return string
     */
    public static function getRequirementsHtml() {
        return '
        <div class="password-requirements mt-2">
            <small class="text-muted">Password must contain:</small>
            <ul class="list-unstyled mt-1 mb-0">
                <li><small class="text-muted">• At least 8 characters</small></li>
                <li><small class="text-muted">• At least one lowercase letter (a-z)</small></li>
                <li><small class="text-muted">• At least one uppercase letter (A-Z)</small></li>
                <li><small class="text-muted">• At least one number (0-9)</small></li>
                <li><small class="text-muted">• At least one special character (@$!%*?&)</small></li>
            </ul>
        </div>';
    }
    
    /**
     * Get password requirements as text
     * @return string
     */
    public static function getRequirementsText() {
        return 'Password must contain: at least 8 characters, one lowercase letter, one uppercase letter, one number, and one special character (@$!%*?&)';
    }
}

