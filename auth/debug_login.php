<?php
session_start();
require_once '../config/db.php';

echo "<h2>Login Debug</h2>";

// Test with a known user
$test_email = "topelparagas@gmail.com"; // Change this to an actual email in your database
$test_password = "1234567"; // Change this to the actual password

echo "Testing with email: $test_email<br>";
echo "Testing with password: $test_password<br><br>";

// Check if user exists
$stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password_hash FROM users WHERE email = ?");
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo "User found in database:<br>";
    echo "User ID: " . $user['user_id'] . "<br>";
    echo "Name: " . $user['first_name'] . " " . $user['last_name'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Password Hash: " . $user['password_hash'] . "<br><br>";
    
    // Test password verification
    $password_match = password_verify($test_password, $user['password_hash']);
    echo "Password verification: " . ($password_match ? "SUCCESS" : "FAILED") . "<br><br>";
    
    if ($password_match) {
        // Test setting session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['loggedin'] = true;
        
        echo "Session variables set:<br>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        echo "Login should be successful!";
    } else {
        echo "Password does not match. Please check the password.";
    }
} else {
    echo "No user found with email: $test_email<br>";
    echo "Available users in database:<br>";
    
    $all_users = $conn->query("SELECT user_id, first_name, last_name, email FROM users");
    if ($all_users->num_rows > 0) {
        echo "<ul>";
        while ($row = $all_users->fetch_assoc()) {
            echo "<li>" . $row['email'] . " (" . $row['first_name'] . " " . $row['last_name'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "No users found in database.";
    }
}

$stmt->close();
?>