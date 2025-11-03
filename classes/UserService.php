<?php
require_once 'User.php';
require_once 'PasswordValidator.php';

class UserService {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Register a new user
     * @param array $data
     * @return bool
     * What: Registers a new user
     * Why: Centralizes registration logic
     * How: Calls User::register
     */
    public function handleRegister($data) {
        // Validate password strength
        $passwordValidation = PasswordValidator::validatePassword($data['password']);
        if (!$passwordValidation['is_valid']) {
            throw new Exception(implode('. ', $passwordValidation['errors']));
        }
        
        return $this->userModel->register($data['name'], $data['username'], $data['password'], $data['role'], $data['email'] ?? null, $data['mobile_number'] ?? null);
    }

    /**
     * Get all users
     * @return array
     * What: Fetches all users
     * Why: For listing/overview
     * How: Calls User::getAllUsers
     */
    public function getAllUsers() {
        return $this->userModel->getAllUsers();
    }

    /**
     * Get user by ID
     * @param int $id
     * @return array|null
     * What: Fetches user by ID
     * Why: For details/editing
     * How: Calls User::getUserById
     */
    public function getUserById($id) {
        return $this->userModel->getUserById($id);
    }

    /**
     * Update user
     * @param int $id
     * @param array $data
     * @return bool
     * What: Updates user record
     * Why: For editing/updating
     * How: Calls User::updateUser
     */
    public function updateUser($id, $data) {
        return $this->userModel->updateUser(
            $id, 
            $data['name'], 
            $data['role'], 
            $data['email'] ?? null, 
            $data['phone'] ?? null
        );
    }

    /**
     * Delete user
     * @param int $id
     * @return bool
     * What: Deletes user record
     * Why: For removal
     * How: Calls User::deleteUser
     */
    public function deleteUser($id) {
        return $this->userModel->deleteUser($id);
    }

    /**
     * Toggle user status
     * @param int $id
     * @param string $currentStatus
     * @return bool
     * What: Toggles user status
     * Why: For activation/deactivation
     * How: Calls User::toggleStatus
     */
    public function toggleStatus($id, $currentStatus) {
        return $this->userModel->toggleStatus($id, $currentStatus);
    }

    /**
     * Update user contact info
     * @param int $id
     * @param string $email
     * @param string $mobile_number
     * @return bool
     * What: Updates user contact info
     * Why: For profile management
     * How: Calls User::updateContactInfo
     */
    public function updateContactInfo($id, $email, $mobile_number) {
        return $this->userModel->updateContactInfo($id, $email, $mobile_number);
    }

    /**
     * Change user password
     * @param int $id
     * @param string $oldPassword
     * @param string $newPassword
     * @param string $confirmPassword
     * @return bool
     * What: Changes user password
     * Why: For security
     * How: Calls User::changePassword
     */
    public function changePassword($id, $oldPassword, $newPassword, $confirmPassword) {
        // Validate new password strength
        $passwordValidation = PasswordValidator::validatePassword($newPassword);
        if (!$passwordValidation['is_valid']) {
            throw new Exception(implode('. ', $passwordValidation['errors']));
        }
        
        return $this->userModel->changePassword($id, $oldPassword, $newPassword, $confirmPassword);
    }

    /**
     * Upload user profile picture
     * @param int $id
     * @param array $file
     * @return bool
     * What: Uploads profile picture
     * Why: For user profile
     * How: Calls User::uploadProfilePicture
     */
    public function uploadProfilePicture($id, $file) {
        return $this->userModel->uploadProfilePicture($id, $file);
    }
} 