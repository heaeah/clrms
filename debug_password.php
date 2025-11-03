<?php
require_once 'classes/User.php';

// This is a debug script to help identify password issues
// Remove this file after fixing the password issues

$user = new User();

echo "<h2>Password Debug Information</h2>";

// Test with a known user
$testUsername = 'rheign'; // Change this to a username you know exists
$testPassword = 'rheign'; // Change this to the password you're trying

echo "<h3>Testing Login for: {$testUsername}</h3>";

try {
    // Get user data
    $userData = $user->getUserByUsername($testUsername);
    
    if ($userData) {
        echo "<p><strong>User Found:</strong> Yes</p>";
        echo "<p><strong>User ID:</strong> {$userData['id']}</p>";
        echo "<p><strong>Username:</strong> {$userData['username']}</p>";
        echo "<p><strong>Name:</strong> {$userData['name']}</p>";
        echo "<p><strong>Role:</strong> {$userData['role']}</p>";
        echo "<p><strong>Status:</strong> {$userData['status']}</p>";
        
        // Test password hashing
        $inputPassword = $testPassword;
        $inputHash = hash('sha256', $inputPassword);
        $storedHash = $userData['password'];
        
        echo "<h4>Password Analysis:</h4>";
        echo "<p><strong>Input Password:</strong> {$inputPassword}</p>";
        echo "<p><strong>Input Hash (SHA256):</strong> {$inputHash}</p>";
        echo "<p><strong>Stored Hash:</strong> {$storedHash}</p>";
        echo "<p><strong>Hashes Match:</strong> " . ($inputHash === $storedHash ? 'YES' : 'NO') . "</p>";
        
        if ($inputHash === $storedHash) {
            echo "<p style='color: green;'><strong>✅ Password verification should work!</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>❌ Password verification will fail!</strong></p>";
            echo "<p><strong>Possible issues:</strong></p>";
            echo "<ul>";
            echo "<li>Password was hashed differently during registration</li>";
            echo "<li>Password contains special characters that were encoded differently</li>";
            echo "<li>Database stored password was modified</li>";
            echo "</ul>";
        }
        
        // Test login method
        echo "<h4>Login Method Test:</h4>";
        $loginResult = $user->login($testUsername, $testPassword);
        echo "<p><strong>Login Result:</strong> " . ($loginResult ? 'SUCCESS' : 'FAILED') . "</p>";
        
    } else {
        echo "<p style='color: red;'><strong>❌ User not found!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>All Users in Database:</h3>";

try {
    $allUsers = $user->getAllUsers();
    if ($allUsers) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Status</th><th>Password Hash (first 20 chars)</th></tr>";
        foreach ($allUsers as $u) {
            echo "<tr>";
            echo "<td>{$u['id']}</td>";
            echo "<td>{$u['username']}</td>";
            echo "<td>{$u['name']}</td>";
            echo "<td>{$u['role']}</td>";
            echo "<td>{$u['status']}</td>";
            echo "<td>" . substr($u['password'], 0, 20) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in database.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error getting users:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> This is a debug script. Remove it after fixing the password issues.</p>";
echo "<p><a href='pages/login.php'>Go to Login Page</a></p>";
?>