<?php
require_once '../config/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../auth/login_admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Brew & Bubble</title>
    <link href="../public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../public/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .stats-card {
            color: white;
            border-radius: 15px;
            padding: 25px;
            height: 100%;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card.orders {
            background: linear-gradient(135deg, #6f42c1 0%, #8B5CF6 100%);
        }
        
        .stats-card.users {
            background: linear-gradient(135deg, #10b981 0%, #34D399 100%);
        }
        
        .stats-card.revenue {
            background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%);
        }
        
        .stats-card i {
            font-size: 2.5rem;
            opacity: 0.9;
        }
        
        .stats-card h3 {
            font-weight: 800;
            margin-bottom: 5px;
        }
        
        .stats-card h6 {
            opacity: 0.9;
            font-weight: 600;
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
        
        /* Status badge colors */
        .bg-primary { background-color: #0d6efd !important; }
        .bg-info { background-color: #0dcaf0 !important; }
        .bg-warning { background-color: #ffc107 !important; }
        .bg-secondary { background-color: #6c757d !important; }
        .bg-success { background-color: #198754 !important; }
        .bg-danger { background-color: #dc3545 !important; }
        
        .admin-welcome {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .admin-welcome h4 {
            margin: 0;
            font-weight: 700;
        }
        
        /* Action buttons */
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            margin-right: 5px;
        }
        
        /* User status badges */
        .badge-active {
            background-color: #198754;
        }
        
        .badge-inactive {
            background-color: #6c757d;
        }
        
        /* Chart container */
        .chart-container {
            position: relative;
            height: 300px;
            margin: 0 auto;
        }
        
        .chart-card {
            height: 100%;
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
                            <a class="nav-link active" href="dashboard.php">
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
                            <a class="nav-link" href="manage_users.php">
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
                <div class="admin-welcome">
                    <h4><i class="fas fa-crown me-2"></i>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h4>
                    <p class="mb-0">Admin Dashboard Overview</p>
                </div>
                
                <div class="row">
                    <!-- Total Orders Card -->
                    <div class="col-md-4 mb-4">
                        <div class="stats-card orders">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Total Orders</h6>
                                    <h3>
                                        <?php
                                        $orders_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
                                        echo $orders_count;
                                        ?>
                                    </h3>
                                </div>
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Users Card -->
                    <div class="col-md-4 mb-4">
                        <div class="stats-card users">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Total Users</h6>
                                    <h3>
                                        <?php
                                        $users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                                        echo $users_count;
                                        ?>
                                    </h3>
                                </div>
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Revenue Card -->
                    <div class="col-md-4 mb-4">
                        <div class="stats-card revenue">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6>Total Revenue</h6>
                                    <h3>
                                        ₱<?php
                                        $revenue = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'Paid'")->fetch_assoc()['total'];
                                        echo number_format($revenue, 2);
                                        ?>
                                    </h3>
                                </div>
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Row -->
                <div class="row">
                    <!-- Order Status Pie Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie me-2"></i>Order Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="orderStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Status Pie Chart -->
                    <div class="col-md-6 mb-4">
                        <div class="card chart-card">
                            <div class="card-header">
                                <h5><i class="fas fa-credit-card me-2"></i>Payment Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="paymentStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-clock me-2"></i>Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recent_orders = $conn->query("
                                        SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name 
                                        FROM orders o 
                                        JOIN users u ON o.user_id = u.user_id 
                                        ORDER BY o.order_datetime DESC 
                                        LIMIT 5
                                    ");
                                    
                                    if ($recent_orders->num_rows > 0) {
                                        while ($order = $recent_orders->fetch_assoc()) {
                                            echo '
                                            <tr>
                                                <td><strong>#' . $order['order_id'] . '</strong></td>
                                                <td>' . htmlspecialchars($order['customer_name']) . '</td>
                                                <td>' . date('M j, Y', strtotime($order['order_datetime'])) . '</td>
                                                <td><strong>₱' . number_format($order['total_amount'], 2) . '</strong></td>
                                                <td>
                                                    <span class="badge ' . getStatusBadgeClass($order['order_status']) . '">
                                                        ' . htmlspecialchars($order['order_status']) . '
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge ' . getPaymentBadgeClass($order['payment_status']) . '">
                                                        ' . htmlspecialchars($order['payment_status']) . '
                                                    </span>
                                                </td>
                                            </tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center">No orders found.</td></tr>';
                                    }
                                    
                                    function getStatusBadgeClass($status) {
                                        switch($status) {
                                            case 'Received': return 'bg-primary';
                                            case 'Preparing': return 'bg-info';
                                            case 'Ready for Pickup': return 'bg-warning';
                                            case 'Out for Delivery': return 'bg-secondary';
                                            case 'Delivered': return 'bg-success';
                                            case 'Completed': return 'bg-success';
                                            case 'Cancelled': return 'bg-danger';
                                            default: return 'bg-secondary';
                                        }
                                    }
                                    
                                    function getPaymentBadgeClass($status) {
                                        switch($status) {
                                            case 'Paid': return 'bg-success';
                                            case 'Pending': return 'bg-warning';
                                            case 'Failed': return 'bg-danger';
                                            case 'Refunded': return 'bg-info';
                                            default: return 'bg-secondary';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Users -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-user-plus me-2"></i>Recent Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>User ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Registered</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recent_users = $conn->query("
                                        SELECT user_id, first_name, last_name, email, phone_number, created_at, is_active 
                                        FROM users 
                                        ORDER BY created_at DESC 
                                        LIMIT 5
                                    ");
                                    
                                    if ($recent_users->num_rows > 0) {
                                        while ($user = $recent_users->fetch_assoc()) {
                                            $status_class = $user['is_active'] ? 'badge-active' : 'badge-inactive';
                                            $status_text = $user['is_active'] ? 'Active' : 'Inactive';
                                            
                                            echo '
                                            <tr>
                                                <td><strong>#' . $user['user_id'] . '</strong></td>
                                                <td>' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</td>
                                                <td>' . htmlspecialchars($user['email']) . '</td>
                                                <td>' . htmlspecialchars($user['phone_number'] ?? 'N/A') . '</td>
                                                <td>' . date('M j, Y', strtotime($user['created_at'])) . '</td>
                                                <td>
                                                    <span class="badge ' . $status_class . '">
                                                        ' . $status_text . '
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="manage_users.php?edit=' . $user['user_id'] . '" class="btn btn-sm btn-primary btn-action">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="manage_users.php?delete=' . $user['user_id'] . '" class="btn btn-sm btn-danger btn-action" onclick="return confirm(\'Are you sure you want to delete this user?\')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="7" class="text-center">No users found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="manage_users.php" class="btn btn-primary">View All Users</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Get order status data from PHP
        <?php
        // Query for order status distribution
        $order_status_data = $conn->query("
            SELECT order_status, COUNT(*) as count 
            FROM orders 
            GROUP BY order_status
        ");
        
        $order_status_labels = [];
        $order_status_counts = [];
        $order_status_colors = [];
        
        while ($row = $order_status_data->fetch_assoc()) {
            $order_status_labels[] = $row['order_status'];
            $order_status_counts[] = $row['count'];
            $order_status_colors[] = getChartColor($row['order_status']);
        }
        
        // Query for payment status distribution
        $payment_status_data = $conn->query("
            SELECT payment_status, COUNT(*) as count 
            FROM orders 
            GROUP BY payment_status
        ");
        
        $payment_status_labels = [];
        $payment_status_counts = [];
        $payment_status_colors = [];
        
        while ($row = $payment_status_data->fetch_assoc()) {
            $payment_status_labels[] = $row['payment_status'];
            $payment_status_counts[] = $row['count'];
            $payment_status_colors[] = getPaymentChartColor($row['payment_status']);
        }
        
        function getChartColor($status) {
            switch($status) {
                case 'Received': return '#0d6efd';
                case 'Preparing': return '#0dcaf0';
                case 'Ready for Pickup': return '#ffc107';
                case 'Out for Delivery': return '#6c757d';
                case 'Delivered': return '#198754';
                case 'Completed': return '#198754';
                case 'Cancelled': return '#dc3545';
                default: return '#6c757d';
            }
        }
        
        function getPaymentChartColor($status) {
            switch($status) {
                case 'Paid': return '#198754';
                case 'Pending': return '#ffc107';
                case 'Failed': return '#dc3545';
                case 'Refunded': return '#0dcaf0';
                default: return '#6c757d';
            }
        }
        ?>
        
        // Initialize Order Status Chart
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        const orderStatusChart = new Chart(orderStatusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($order_status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($order_status_counts); ?>,
                    backgroundColor: <?php echo json_encode($order_status_colors); ?>,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Initialize Payment Status Chart
        const paymentStatusCtx = document.getElementById('paymentStatusChart').getContext('2d');
        const paymentStatusChart = new Chart(paymentStatusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($payment_status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($payment_status_counts); ?>,
                    backgroundColor: <?php echo json_encode($payment_status_colors); ?>,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>