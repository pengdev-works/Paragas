<?php
require_once '../config/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Security token validation failed.";
        header("Location: view_orders.php");
        exit();
    }

    $order_id = intval($_POST['order_id']);
    $order_status = htmlspecialchars(trim($_POST['order_status']));
    $payment_status = htmlspecialchars(trim($_POST['payment_status']));

    $update_stmt = $conn->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE order_id = ?");
    $update_stmt->bind_param("ssi", $order_status, $payment_status, $order_id);

    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Order status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating order status: " . $conn->error;
    }
    $update_stmt->close();

    header("Location: view_orders.php");
    exit();
}

// Get all orders with user info
$orders_stmt = $conn->prepare("
    SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) AS customer_name, u.email, u.phone_number
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    ORDER BY o.order_datetime DESC
");
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

// Helper badge styles
function getStatusBadgeClass($status) {
    switch($status) {
        case 'Received': return 'bg-primary';
        case 'Preparing': return 'bg-info';
        case 'Ready for Pickup': return 'bg-warning';
        case 'Out for Delivery': return 'bg-secondary';
        case 'Delivered':
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - Brew & Bubble</title>
    <link href="../public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../public/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- ✅ Brew & Bubble Design -->
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

        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .btn-close {
            filter: invert(1);
        }

        .form-control {
            border-radius: 10px;
            padding: 10px 15px;
            border: 2px solid var(--milk-tea);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--caramel);
            box-shadow: 0 0 0 0.2rem rgba(166, 124, 82, 0.25);
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

        /* Fix for table responsiveness */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table {
            min-width: 800px; /* Ensure table has minimum width */
            width: 100%;
        }
        
        /* Fix for modal stacking */
        .modal-backdrop {
            z-index: 1040;
        }
        
        .modal {
            z-index: 1050;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .table-responsive {
                border: 1px solid #dee2e6;
                border-radius: 10px;
            }
            
            .btn-sm {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_menu.php"><i class="fas fa-utensils"></i> Manage Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                    <li class="nav-item"><a class="nav-link active" href="view_orders.php"><i class="fas fa-shopping-cart"></i> View Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 main-content">
            <div class="admin-header d-flex justify-content-between align-items-center">
                <h4><i class="fas fa-shopping-cart me-2"></i>View Orders</h4>
            </div>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h5><i class="fas fa-list me-2"></i>All Orders</h5></div>
                <div class="card-body">
                    <?php if($orders_result && $orders_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Order Status</th>
                                        <th>Payment Status</th>
                                        <th>Payment Method</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold">#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($order['order_datetime'])); ?></td>
                                        <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($order['order_type']); ?></span></td>
                                        <td class="fw-bold">₱<?php echo number_format($order['total_amount'],2); ?></td>
                                        <td><span class="badge <?php echo getStatusBadgeClass($order['order_status']); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                                        <td><span class="badge <?php echo getPaymentBadgeClass($order['payment_status']); ?>"><?php echo htmlspecialchars($order['payment_status']); ?></span></td>
                                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $order['order_id']; ?>"><i class="fas fa-eye"></i> View</button>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['order_id']; ?>"><i class="fas fa-edit"></i> Update</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <h5>No Orders Found</h5>
                            <p class="text-muted">There are currently no orders in the system.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALS - Placed outside the table to prevent overlapping issues -->
<?php 
// Reset result pointer to loop again for modals
$orders_result->data_seek(0);
while($order = $orders_result->fetch_assoc()): 
?>
    <!-- ORDER DETAILS MODAL -->
    <div class="modal fade" id="detailsModal<?php echo $order['order_id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details - #<?php echo $order['order_id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold">Customer Information</h6>
                    <p>
                        <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                        <strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?><br>
                        <strong>Phone:</strong> <?php echo htmlspecialchars($order['phone_number']); ?><br>
                        <strong>Type:</strong> <?php echo htmlspecialchars($order['order_type']); ?><br>
                        <strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['order_datetime'])); ?><br>
                        <strong>Instructions:</strong> <?php echo htmlspecialchars($order['special_instructions'] ?? 'None'); ?>
                    </p>
                    <hr>
                    <h6 class="fw-bold mb-3">Ordered Items</h6>
                    <?php
                    $items_stmt = $conn->prepare("
                        SELECT m.name, oi.quantity, oi.price
                        FROM order_items oi
                        JOIN menu_items m ON oi.item_id = m.item_id
                        WHERE oi.order_id = ?
                    ");
                    $items_stmt->bind_param("i", $order['order_id']);
                    $items_stmt->execute();
                    $items_result = $items_stmt->get_result();
                    if($items_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php $total = 0;
                                while($item = $items_result->fetch_assoc()):
                                    $subtotal = $item['quantity'] * $item['price'];
                                    $total += $subtotal; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₱<?php echo number_format($item['price'],2); ?></td>
                                        <td>₱<?php echo number_format($subtotal,2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr><th colspan="3" class="text-end">Total:</th><th>₱<?php echo number_format($total,2); ?></th></tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No items found for this order.</p>
                    <?php endif; $items_stmt->close(); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- UPDATE STATUS MODAL -->
    <div class="modal fade" id="orderModal<?php echo $order['order_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order #<?php echo $order['order_id']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Order Status</label>
                            <select class="form-control" name="order_status" required>
                                <?php
                                $statuses = ['Received','Preparing','Ready for Pickup','Out for Delivery','Delivered','Completed','Cancelled'];
                                foreach($statuses as $status) {
                                    $sel = ($order['order_status'] === $status) ? 'selected' : '';
                                    echo "<option value='$status' $sel>$status</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Payment Status</label>
                            <select class="form-control" name="payment_status" required>
                                <?php
                                $payments = ['Pending','Paid','Failed','Refunded'];
                                foreach($payments as $p) {
                                    $sel = ($order['payment_status'] === $p) ? 'selected' : '';
                                    echo "<option value='$p' $sel>$p</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endwhile; ?>

<script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>