<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = sanitize_input($_POST['first_name']);
    $lastName = sanitize_input($_POST['last_name']);
    $phone = sanitize_input($_POST['phone']);
    
    $errors = [];
    
    if (empty($firstName) || empty($lastName)) {
        $errors[] = "First name and last name are required.";
    }
    
    if (empty($errors)) {
        $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone_number = ? WHERE user_id = ?");
        $update_stmt->bind_param("sssi", $firstName, $lastName, $phone, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();
        } else {
            $errors[] = "Error updating profile. Please try again.";
        }
        $update_stmt->close();
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errors[] = "All password fields are required.";
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = "New passwords do not match.";
    }
    
    if (strlen($newPassword) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    }
    
    if (empty($errors)) {
        // Verify current password
        $check_stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $user_data = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if (password_verify($currentPassword, $user_data['password_hash'])) {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $update_stmt->bind_param("si", $newPasswordHash, $user_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Password changed successfully!";
                header("Location: profile.php");
                exit();
            } else {
                $errors[] = "Error changing password. Please try again.";
            }
            $update_stmt->close();
        } else {
            $errors[] = "Current password is incorrect.";
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Brew & Bubble</title>
    <link href="public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --caramel: #A67C52;
            --milk-tea: #D4B996;
            --boba: #815A3B;
            --coffee: #6F4E37;
            --light-cream: #F7F0E0;
            --dark-chocolate: #3C2A1E;
        }
        
        body {
            background-color: var(--light-cream);
            color: var(--dark-chocolate);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .container {
            padding-top: 30px;
            padding-bottom: 50px;
            flex: 1;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            height: 100%;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
            padding: 20px;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 700;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .btn-primary {
            background-color: var(--boba);
            border-color: var(--boba);
            border-radius: 30px;
            font-weight: 600;
            padding: 10px 25px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--dark-chocolate);
            border-color: var(--dark-chocolate);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid var(--milk-tea);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--caramel);
            box-shadow: 0 0 0 0.2rem rgba(166, 124, 82, 0.25);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-chocolate);
            margin-bottom: 8px;
        }
        
        .alert {
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
            color: #721c24;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-color: #28a745;
            color: #155724;
        }
        
        .page-header h2 {
            color: white;
            font-weight: 800;
            margin-bottom: 0;
        }
        
        /* Ensure header and footer have proper contrast */
        .navbar-dark .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .navbar-dark .navbar-nav .nav-link:hover {
            color: white !important;
        }
        
        footer {
            background: linear-gradient(135deg, var(--dark-chocolate) 0%, var(--coffee) 100%);
            color: white;
        }
        
        footer a {
            color: var(--milk-tea) !important;
        }
        
        footer a:hover {
            color: white !important;
            text-decoration: none;
        }
        
        footer h5 {
            color: white;
            font-weight: 700;
        }
        
        footer .text-light {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .profile-icon {
            font-size: 1.2rem;
            color: var(--caramel);
            margin-right: 10px;
        }
        
        .form-text {
            color: var(--coffee) !important;
        }
    </style>
</head>
<body>
    <?php include 'partials/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-user-circle me-3"></i>User Profile</h2>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_SESSION['errors'])): ?>
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-circle me-2"></i>Error</h5>
                <?php 
                foreach ($_SESSION['errors'] as $error) {
                    echo "<p class='mb-1'>$error</p>";
                }
                unset($_SESSION['errors']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <h5><i class="fas fa-check-circle me-2"></i>Success</h5>
                <p class='mb-0'><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="first_name" class="form-label"><i class="fas fa-user profile-icon"></i>First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="last_name" class="form-label"><i class="fas fa-user profile-icon"></i>Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label"><i class="fas fa-envelope profile-icon"></i>Email Address</label>
                                <input type="email" class="form-control" id="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <div class="form-text">Email cannot be changed.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="phone" class="form-label"><i class="fas fa-phone profile-icon"></i>Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-lock me-2"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="current_password" class="form-label"><i class="fas fa-key profile-icon"></i>Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label"><i class="fas fa-key profile-icon"></i>New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text">Password must be at least 6 characters long.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label"><i class="fas fa-key profile-icon"></i>Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary w-100">
                                <i class="fas fa-lock me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'partials/footer.php'; ?>
    <script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>