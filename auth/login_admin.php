<?php
session_start();
require_once '../config/db.php';

// Clear any existing errors
unset($_SESSION['errors']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    $errors = [];
    
    if (empty($username) || empty($password)) {
        $errors[] = "Username and password are required.";
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT admin_id, username, password_hash FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_loggedin'] = true;
                
                header("Location: ../admin/dashboard.php");
                exit();
            } else {
                $errors[] = "Invalid password.";
            }
        } else {
            $errors[] = "No admin account found with that username.";
        }
        $stmt->close();
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
    <title>Admin Login - Brew & Bubble</title>
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
        
        .admin-login-card {
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
        
        .admin-icon {
            font-size: 3rem;
            color: var(--caramel);
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .admin-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .admin-links a {
            color: var(--boba);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .admin-links a:hover {
            color: var(--dark-chocolate);
            text-decoration: underline;
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
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--caramel);
        }
        
        .admin-badge {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <?php include '../partials/header.php'; ?>
    
    <div class="container">
        <div class="admin-login-card card">
            <div class="card-header">
                <h3><i class="fas fa-crown me-2"></i>Admin Login</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="fas fa-shield-alt admin-icon"></i>
                    <p class="text-muted">Access the admin dashboard</p>
                </div>
                
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
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" id="username" name="username" required
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   placeholder="Admin username">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" required 
                                   placeholder="Admin password">
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-4">
                        <i class="fas fa-sign-in-alt me-2"></i>Login as Admin
                    </button>
                </form>
                
                <div class="admin-links">
                    <p class="mb-0"><a href="login.php"><i class="fas fa-arrow-left me-2"></i>Back to user login</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../partials/footer.php'; ?>
    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = passwordInput.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>