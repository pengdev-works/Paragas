<?php
session_start();
require_once '../config/db.php';

// Clear any existing errors
unset($_SESSION['errors']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    $errors = [];
    
    // More specific validation
    if (empty($email) && empty($password)) {
        $errors[] = "Both email and password are required.";
    } elseif (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password_hash FROM users WHERE email = ?");
        if (!$stmt) {
            $errors[] = "Database connection error. Please try again later.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['loggedin'] = true;
                    
                    // Clear any admin session if exists
                    unset($_SESSION['admin_id']);
                    unset($_SESSION['admin_username']);
                    unset($_SESSION['admin_loggedin']);
                    
                    // Debug: Check session
                    error_log("User login successful: " . $user['email']);
                    
                    header("Location: ../index.php");
                    exit();
                } else {
                    $errors[] = "The password you entered is incorrect. Please try again.";
                }
            } else {
                $errors[] = "No account found with this email address. Please check your email or register for a new account.";
            }
            $stmt->close();
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['show_error_toast'] = true;
        $_SESSION['form_data'] = ['email' => $email]; // Preserve email
        header("Location: login.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Brew & Bubble</title>
    <link href="../public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../public/css/style.css" rel="stylesheet">
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            border: none;
            padding: 25px;
            text-align: center;
        }
        
        .card-header h3 {
            margin: 0;
            font-weight: 800;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .btn-primary {
            background-color: var(--boba);
            border-color: var(--boba);
            border-radius: 30px;
            font-weight: 600;
            padding: 12px;
            transition: all 0.3s ease;
            width: 100%;
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
        
        .login-icon {
            font-size: 4rem;
            color: var(--caramel);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .login-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-links a {
            color: var(--boba);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .login-links a:hover {
            color: var(--dark-chocolate);
            text-decoration: underline;
        }
        
        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
        }
        
        .custom-toast {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
            min-width: 350px;
        }
        
        .toast-header {
            background: transparent;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .toast-body {
            padding: 15px 20px;
        }
        
        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        
        /* Error-specific styling */
        .error-item {
            padding: 5px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .error-item:last-child {
            border-bottom: none;
        }
        
        .error-icon {
            margin-right: 8px;
            font-size: 0.9em;
        }
        
        /* Field error highlighting */
        .is-invalid {
            border-color: #dc3545 !important;
            background-color: rgba(220, 53, 69, 0.05);
        }
        
        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
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
        
        .input-icon {
            position: relative;
        }
        
        .input-icon .form-control {
            padding-left: 45px;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--caramel);
            font-size: 1.2rem;
        }
        
        /* Success toast for future use */
        .success-toast {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
    </style>
</head>
<body>
    <?php include '../partials/header.php'; ?>
    
    <!-- Toast Notification -->
    <div class="toast-container">
        <div id="errorToast" class="toast custom-toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong class="me-auto">Login Failed</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php 
                if (isset($_SESSION['errors'])) {
                    foreach ($_SESSION['errors'] as $error) {
                        echo '<div class="error-item">';
                        echo '<i class="fas fa-times-circle error-icon"></i>';
                        echo htmlspecialchars($error);
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="login-card card">
            <div class="card-header">
                <h3><i class="fas fa-user-circle me-2"></i>Login</h3>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                        <div class="mt-3">
                            <?php 
                            foreach ($_SESSION['errors'] as $error) {
                                echo '<div class="error-item">';
                                echo '<i class="fas fa-times-circle error-icon"></i>';
                                echo htmlspecialchars($error);
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="login.php" id="loginForm" novalidate>
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" class="form-control <?php echo (isset($_SESSION['errors']) && (in_array('Email address is required.', $_SESSION['errors']) || in_array('Please enter a valid email address.', $_SESSION['errors']))) ? 'is-invalid' : ''; ?>" 
                                   id="email" name="email" required 
                                   value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''); ?>"
                                   placeholder="Enter your email">
                            <?php if (isset($_SESSION['errors']) && in_array('Email address is required.', $_SESSION['errors'])): ?>
                                <div class="invalid-feedback">Email address is required.</div>
                            <?php elseif (isset($_SESSION['errors']) && in_array('Please enter a valid email address.', $_SESSION['errors'])): ?>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control <?php echo (isset($_SESSION['errors']) && in_array('Password is required.', $_SESSION['errors'])) ? 'is-invalid' : ''; ?>" 
                                   id="password" name="password" required 
                                   placeholder="Enter your password">
                            <?php if (isset($_SESSION['errors']) && in_array('Password is required.', $_SESSION['errors'])): ?>
                                <div class="invalid-feedback">Password is required.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-4">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>
                
                <div class="login-links">
                    <p class="mb-2">Don't have an account? <a href="register.php">Register here</a></p>
                    <p class="mb-0">Are you an admin? <a href="login_admin.php">Admin login</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../partials/footer.php'; ?>
    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show toast notification if there are errors
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['show_error_toast']) && $_SESSION['show_error_toast']): ?>
                var errorToast = document.getElementById('errorToast');
                if (errorToast) {
                    var toast = new bootstrap.Toast(errorToast);
                    toast.show();
                }
                
                // Auto-hide after 7 seconds (longer for multiple errors)
                setTimeout(function() {
                    var toast = bootstrap.Toast.getInstance(errorToast);
                    if (toast) {
                        toast.hide();
                    }
                }, 7000);
                
                // Clear the flags and form data
                <?php 
                unset($_SESSION['show_error_toast']); 
                unset($_SESSION['form_data']);
                ?>
            <?php endif; ?>
            
            // Also clear errors from session after displaying
            <?php unset($_SESSION['errors']); ?>
            
            // Real-time form validation
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            function showFieldError(input, message) {
                input.classList.add('is-invalid');
                let feedback = input.nextElementSibling;
                if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    input.parentNode.appendChild(feedback);
                }
                feedback.textContent = message;
            }
            
            function clearFieldError(input) {
                input.classList.remove('is-invalid');
                let feedback = input.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.remove();
                }
            }
            
            // Real-time validation
            emailInput.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    showFieldError(this, 'Email address is required.');
                } else if (!validateEmail(this.value)) {
                    showFieldError(this, 'Please enter a valid email address.');
                } else {
                    clearFieldError(this);
                }
            });
            
            passwordInput.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    showFieldError(this, 'Password is required.');
                } else {
                    clearFieldError(this);
                }
            });
            
            // Clear errors on input
            emailInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    clearFieldError(this);
                }
            });
            
            passwordInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    clearFieldError(this);
                }
            });
        });
    </script>
</body>
</html>