<?php
require_once '../config/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Handle user actions (edit, delete, toggle status)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Edit user - fetch user data
    if (isset($_GET['edit'])) {
        $user_id = intval($_GET['edit']);
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_to_edit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
    // Delete user
    if (isset($_GET['delete'])) {
        $user_id = intval($_GET['delete']);
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "User deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting user: " . $stmt->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
        header("Location: manage_users.php");
        exit();
    }
    
    // Toggle user status
    if (isset($_GET['toggle_status'])) {
        $user_id = intval($_GET['toggle_status']);
        $stmt = $conn->prepare("SELECT is_active FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Handle case where is_active might not be set
        $current_status = isset($user['is_active']) ? $user['is_active'] : 1;
        $new_status = $current_status ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $new_status, $user_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "User status updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating user status: " . $stmt->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
        header("Location: manage_users.php");
        exit();
    }
}

// Handle form submission for adding/editing users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $_SESSION['message'] = "Please fill in all required fields!";
        $_SESSION['message_type'] = "danger";
    } else {
        if ($user_id > 0) {
            // Update existing user
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ?, is_active = ? WHERE user_id = ?");
            $stmt->bind_param("ssssii", $first_name, $last_name, $email, $phone_number, $is_active, $user_id);
        } else {
            // Add new user - check if email already exists
            $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $_SESSION['message'] = "Email already exists!";
                $_SESSION['message_type'] = "danger";
            } else {
                // Create a temporary password (in real application, you would generate a secure random password)
                $temp_password = password_hash('temp123', PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone_number, password_hash, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone_number, $temp_password, $is_active);
            }
            $check_stmt->close();
        }
        
        if (isset($stmt) && $stmt->execute()) {
            $_SESSION['message'] = $user_id > 0 ? "User updated successfully!" : "User added successfully!";
            $_SESSION['message_type'] = "success";
        } elseif (!isset($_SESSION['message'])) {
            $_SESSION['message'] = "Error saving user: " . (isset($stmt) ? $stmt->error : "Unknown error");
            $_SESSION['message_type'] = "danger";
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
        
        header("Location: manage_users.php");
        exit();
    }
}

// Fetch all users
$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Brew & Bubble</title>
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
        
        .sidebar {
            background: linear-gradient(135deg, var(--dark-chocolate) 0%, var(--coffee) 100%) !important;
            min-height: calc(100vh - 73px);
            padding-top: 20px;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 15px 20px;
            margin: 5px 10px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 30px;
            background-color: var(--light-cream);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
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
        
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            border: none;
            font-weight: 600;
            padding: 15px;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: rgba(0,0,0,0.05);
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
        }
        
        h2 {
            color: var(--dark-chocolate);
            font-weight: 800;
            margin-bottom: 20px;
        }
        
        /* Status badge colors */
        .bg-success { background-color: #198754 !important; }
        .bg-secondary { background-color: #6c757d !important; }
        .bg-warning { background-color: #ffc107 !important; }
        .bg-danger { background-color: #dc3545 !important; }
        
        .admin-header {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .admin-header h4 {
            margin: 0;
            font-weight: 700;
        }
        
        /* Action buttons */
        .btn-action {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            margin: 2px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        /* Form styles */
        .form-label {
            font-weight: 600;
            color: var(--dark-chocolate);
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid var(--milk-tea);
            padding: 10px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--caramel);
            box-shadow: 0 0 0 0.2rem rgba(166, 124, 82, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--coffee) 0%, var(--dark-chocolate) 100%);
            transform: translateY(-2px);
        }
        
        .btn-outline-secondary {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
        }
        
        /* Toggle switch */
        .form-check-input {
            width: 3em;
            height: 1.5em;
        }
        
        .form-check-input:checked {
            background-color: var(--caramel);
            border-color: var(--caramel);
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .alert {
            border-radius: 15px;
            padding: 15px 20px;
        }
        
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        
        .btn-close {
            filter: invert(1);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--caramel);
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include '../partials/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_menu.php">
                                <i class="fas fa-utensils"></i>
                                Manage Menu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_users.php">
                                <i class="fas fa-users"></i>
                                Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_orders.php">
                                <i class="fas fa-shopping-cart"></i>
                                View Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="admin-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-users me-2"></i>Manage Users</h4>
                    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#userModal">
                        <i class="fas fa-plus me-1"></i> Add New User
                    </button>
                </div>
                
                <!-- Display messages -->
                <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                    <i class="fas <?php echo $_SESSION['message_type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                endif; ?>
                
                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Users</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($users->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Registered</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($user = $users->fetch_assoc()): ?>
                                            <?php 
                                            // Handle case where is_active might not be set
                                            $is_active = isset($user['is_active']) ? $user['is_active'] : 1;
                                            ?>
                                            <tr>
                                                <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-3">
                                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $is_active ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap">
                                                        <a href="?edit=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary btn-action">
                                                            <i class="fas fa-edit me-1"></i> Edit
                                                        </a>
                                                        <a href="?toggle_status=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-warning btn-action">
                                                            <i class="fas fa-power-off me-1"></i> <?php echo $is_active ? 'Deactivate' : 'Activate'; ?>
                                                        </a>
                                                        <a href="?delete=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                            <i class="fas fa-trash me-1"></i> Delete
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <h5>No Users Found</h5>
                                <p class="text-muted">Get started by adding your first user.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                                    <i class="fas fa-plus me-1"></i> Add User
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel"><?php echo isset($user_to_edit) ? 'Edit User' : 'Add New User'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="manage_users.php">
                    <div class="modal-body">
                        <?php if (isset($user_to_edit)): ?>
                            <input type="hidden" name="user_id" value="<?php echo $user_to_edit['user_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required 
                                value="<?php echo isset($user_to_edit) ? htmlspecialchars($user_to_edit['first_name']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required 
                                value="<?php echo isset($user_to_edit) ? htmlspecialchars($user_to_edit['last_name']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                value="<?php echo isset($user_to_edit) ? htmlspecialchars($user_to_edit['email']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                value="<?php echo isset($user_to_edit) ? htmlspecialchars($user_to_edit['phone_number'] ?? '') : ''; ?>">
                        </div>
                        
                        <div class="mb-3 form-check form-switch">
                            <?php 
                            // Handle case where is_active might not be set
                            $is_active_checked = (isset($user_to_edit) && isset($user_to_edit['is_active']) && $user_to_edit['is_active']) || !isset($user_to_edit);
                            ?>
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                <?php echo $is_active_checked ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active User</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?php echo isset($user_to_edit) ? 'Update User' : 'Add User'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isset($_GET['edit'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var userModal = new bootstrap.Modal(document.getElementById('userModal'));
            userModal.show();
        });
    </script>
    <?php endif; ?>
</body>
</html>