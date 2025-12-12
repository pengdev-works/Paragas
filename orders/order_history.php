<?php
require_once '../config/db.php';
session_start(); 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get orders
$orders_query = "
    SELECT o.*, COUNT(oi.order_item_id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.order_id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.order_id 
    ORDER BY o.order_datetime DESC";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders = $orders_result->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Brew & Bubble</title>
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
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }
        
        .btn-primary {
            background-color: var(--boba);
            border-color: var(--boba);
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--dark-chocolate);
            border-color: var(--dark-chocolate);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-outline-primary {
            color: var(--boba);
            border-color: var(--boba);
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--boba);
            border-color: var(--boba);
            color: white;
            transform: translateY(-3px);
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            background-color: white;
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
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(212, 185, 150, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
        }
        
        .alert-info {
            background-color: rgba(212, 185, 150, 0.2);
            border-color: var(--milk-tea);
            color: var(--dark-chocolate);
            border-radius: 15px;
            padding: 20px;
        }
        
        .alert-info a {
            color: var(--boba);
            font-weight: 600;
            text-decoration: none;
        }
        
        .alert-info a:hover {
            text-decoration: underline;
        }
        
        .page-header h2 {
            color: white;
            font-weight: 800;
            margin-bottom: 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 0;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--caramel);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: var(--dark-chocolate);
            margin-bottom: 15px;
        }
        
        .order-type-badge {
            background-color: var(--milk-tea);
            color: var(--dark-chocolate);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        /* Status badge colors */
        .bg-primary { background-color: #0d6efd !important; }
        .bg-info { background-color: #0dcaf0 !important; }
        .bg-warning { background-color: #ffc107 !important; }
        .bg-secondary { background-color: #6c757d !important; }
        .bg-success { background-color: #198754 !important; }
        .bg-danger { background-color: #dc3545 !important; }
        
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
    </style>
</head>
<body>
    <?php 
    // Include header with color variables
    include '../partials/header.php'; 
    ?>
    
    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-history me-3"></i>Order History</h2>
        </div>
    </div>
    
    <div class="container">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>No Orders Yet</h3>
                <p class="lead mb-4">You haven't placed any orders yet. Start exploring our menu and place your first order!</p>
                <a href="../index.php" class="btn btn-primary btn-lg">Start Shopping <i class="fas fa-arrow-right ms-2"></i></a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($order['order_datetime'])); ?></td>
                                <td><span class="order-type-badge"><?php echo htmlspecialchars($order['order_type']); ?></span></td>
                                <td><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] != 1 ? 's' : ''; ?></td>
                                <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                        switch($order['order_status']) {
                                            case 'Received': echo 'bg-primary'; break;
                                            case 'Preparing': echo 'bg-info'; break;
                                            case 'Ready for Pickup': echo 'bg-warning'; break;
                                            case 'Out for Delivery': echo 'bg-secondary'; break;
                                            case 'Delivered': echo 'bg-success'; break;
                                            case 'Completed': echo 'bg-success'; break;
                                            case 'Cancelled': echo 'bg-danger'; break;
                                            default: echo 'bg-secondary';
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                        switch($order['payment_status']) {
                                            case 'Paid': echo 'bg-success'; break;
                                            case 'Pending': echo 'bg-warning'; break;
                                            case 'Failed': echo 'bg-danger'; break;
                                            case 'Refunded': echo 'bg-info'; break;
                                            default: echo 'bg-secondary';
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_details.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i> Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../partials/footer.php'; ?>
    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>