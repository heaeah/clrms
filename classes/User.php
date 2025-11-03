<?php
// classes/User.php

require_once 'Database.php';
require_once 'BaseModel.php';

class User extends BaseModel {
    public function __construct() {
        $db = new Database();
        parent::__construct($db->getConnection(), 'users');
    }

    // Encapsulation: Getter for conn (if needed)
    public function getConn() {
        return $this->conn;
    }

    // Implement CRUD methods
    public function getAll() {
        return $this->getAllUsers();
    }
    public function getById($id) {
        return $this->getUserById($id);
    }
    public function create($data) {
        return $this->register($data['name'], $data['username'], $data['password'], $data['role'], $data['email'] ?? null, $data['mobile_number'] ?? null);
    }
    public function update($id, $data) {
        return $this->updateUser($id, $data['name'], $data['role']);
    }
    public function delete($id) {
        return $this->deleteUser($id);
    }

    // Check if login credentials are correct
    public function login($username, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->bindParam(":username", $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Log login attempt for debugging
                error_log("[Login Attempt] Username: {$username}, User ID: {$user['id']}, Status: {$user['status']}", 3, __DIR__ . '/../logs/login.log');
                
                // Check if user is active
                if ($user['status'] !== 'Active') {
                    error_log("[Login Failed] User {$username} is inactive", 3, __DIR__ . '/../logs/login.log');
                    return false;
                }
                
                // Hash the input password
                $inputHash = hash('sha256', $password);
                
                // Debug logging (remove in production)
                error_log("[Password Check] Input hash: {$inputHash}, Stored hash: {$user['password']}", 3, __DIR__ . '/../logs/login.log');
                
                // Check password
                if ($inputHash === $user['password']) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    
                    error_log("[Login Success] User {$username} logged in successfully", 3, __DIR__ . '/../logs/login.log');
                    return true;
                } else {
                    error_log("[Login Failed] Password mismatch for user {$username}", 3, __DIR__ . '/../logs/login.log');
                    return false;
                }
            } else {
                error_log("[Login Failed] User {$username} not found", 3, __DIR__ . '/../logs/login.log');
                return false;
            }
        } catch (PDOException $e) {
            // What: Database error during login
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[User Login Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    // Logout user
    public function logout() {
        session_unset();
        session_destroy();
    }

    public function getUserById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // What: Database error during getUserById
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[User getUserById Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function getUserByUsername($username) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // What: Database error during getUserByUsername
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[User getUserByUsername Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function changePassword($id, $oldPassword, $newPassword, $confirmPassword) {
        try {
            $user = $this->getUserById($id);
            if (!$user) return false;
            if (hash('sha256', $oldPassword) !== $user['password']) {
                return false; // Old password incorrect
            }
            if ($newPassword !== $confirmPassword) {
                return false; // Passwords do not match
            }
            $newHash = hash('sha256', $newPassword);
            $stmt = $this->conn->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindParam(":password", $newHash);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            // What: Database error during changePassword
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[User changePassword Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function uploadProfilePicture($id, $file) {
        try {
            if ($file['error'] == 0) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . "." . $ext;
                $destination = __DIR__ . '/../uploads/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $stmt = $this->conn->prepare("UPDATE users SET profile_picture = :filename WHERE id = :id");
                    $stmt->bindParam(":filename", $filename);
                    $stmt->bindParam(":id", $id);
                    return $stmt->execute();
                }
            }
            return false;
        } catch (PDOException $e) {
            // What: File/database error during uploadProfilePicture
            // Why: File move or DB update failed
            // How: Log error and return false
            error_log('[User uploadProfilePicture Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function register($name, $username, $password, $role, $email = null, $mobile_number = null) {
        try {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindParam(":username", $username);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                return false; // Username already taken
            }
            $hashPassword = hash('sha256', $password);
            $status = 'Active'; // Always active when registered
            $stmt = $this->conn->prepare("INSERT INTO users (name, username, password, role, status, email, mobile_number) 
                                  VALUES (:name, :username, :password, :role, :status, :email, :mobile_number)");
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":password", $hashPassword);
            $stmt->bindParam(":role", $role);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":mobile_number", $mobile_number);
            return $stmt->execute();
        } catch (PDOException $e) {
            // What: Database error during register
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[User register Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users ORDER BY id ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // What: Database error during getAllUsers
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[User getAllUsers Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function deleteUser($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            // What: Database error during deleteUser
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[User deleteUser Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function updateUser($id, $name, $role, $email = null, $phone = null) {
        try {
            // Debug: Log the parameters being passed
            error_log('[User updateUser Debug] ID: ' . $id . ', Name: ' . $name . ', Role: ' . $role . ', Email: ' . $email . ', Phone: ' . $phone, 3, __DIR__ . '/../logs/error.log');
            
            $stmt = $this->conn->prepare("UPDATE users SET name = :name, role = :role, email = :email, mobile_number = :phone WHERE id = :id");
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":role", $role);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":phone", $phone);
            $stmt->bindParam(":id", $id);
            
            $result = $stmt->execute();
            
            // Debug: Log the result
            error_log('[User updateUser Debug] Update result: ' . ($result ? 'SUCCESS' : 'FAILED'), 3, __DIR__ . '/../logs/error.log');
            
            return $result;
        } catch (PDOException $e) {
            // What: Database error during updateUser
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[User updateUser Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function toggleStatus($id, $currentStatus) {
        try {
            $newStatus = ($currentStatus === 'Active') ? 'Inactive' : 'Active';
            $stmt = $this->conn->prepare("UPDATE users SET status = :status WHERE id = :id");
            $stmt->bindParam(":status", $newStatus);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            // What: Database error during toggleStatus
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[User toggleStatus Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    public function updateContactInfo($id, $email, $mobile_number) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET email = :email, mobile_number = :mobile_number WHERE id = :id");
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":mobile_number", $mobile_number);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            // What: Database error during updateContactInfo
            // Why: Query failure, connection issue, etc.
            // How: Log error and return false
            error_log('[User updateContactInfo Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    /**
     * Check if username already exists
     * @param string $username
     * @return bool
     */
    public function usernameExists($username) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = :username");
            $stmt->bindParam(":username", $username);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log('[User usernameExists Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }

    /**
     * Check if email already exists
     * @param string $email
     * @return bool
     */
    public function emailExists($email) {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE email = :email");
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log('[User emailExists Error] ' . $e->getMessage(), 3, __DIR__ . '/../logs/error.log');
            return false;
        }
    }
}
?>
