<?php
require_once '../config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = "Please login to view your order details.";
    header("Location: ../auth/login.php");
    exit();
}

// Get order ID from URL
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    $_SESSION['error'] = "Invalid order ID.";
    header("Location: orders.php");
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

// Handle cancel order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    // Verify order belongs to user and can be cancelled
    $stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_check = $result->fetch_assoc();
    $stmt->close();
    
    if ($order_check) {
        $cancellable_statuses = ['Received', 'Preparing'];
        
        if (in_array($order_check['order_status'], $cancellable_statuses)) {
            // Update order status to Cancelled
            $stmt = $conn->prepare("UPDATE orders SET order_status = 'Cancelled', cancelled_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("i", $order_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Order #$order_id has been cancelled successfully.";
                // Refresh the page to show updated status
                header("Location: order_details.php?order_id=" . $order_id);
                exit();
            } else {
                $_SESSION['error'] = "Failed to cancel order. Please try again.";
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "This order cannot be cancelled because it's already " . $order_check['order_status'] . ".";
        }
    } else {
        $_SESSION['error'] = "Order not found.";
    }
}

// Fetch order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header("Location: orders.php");
    exit();
}

// Fetch order items
$stmt_items = $conn->prepare("
    SELECT oi.*, m.name, m.base_price, m.image_url
    FROM order_items oi
    JOIN menu_items m ON oi.item_id = m.item_id
    WHERE oi.order_id = ?
");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

$order_items = [];
while ($row = $result_items->fetch_assoc()) {
    $row['total'] = $row['quantity'] * $row['base_price'];
    $order_items[] = $row;
}
$stmt_items->close();

function get_image_url($image_name) {
    $placeholder = '/milktea/public/images/placeholder.png';

    if (!empty($image_name)) {
        return '/milktea/public/image.php?file=' . urlencode($image_name);
    }

    return $placeholder;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Brew & Bubble</title>
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
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .card-body {
            padding: 25px;
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
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
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
        
        .badge {
            font-size: 0.75rem;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: 600;
        }
        
        .page-header h2 {
            color: white;
            font-weight: 800;
            margin-bottom: 0;
        }
        
        h4, h5 {
            color: var(--dark-chocolate);
            font-weight: 700;
        }
        
        /* Status badge colors */
        .bg-primary { background-color: #0d6efd !important; }
        .bg-info { background-color: #0dcaf0 !important; }
        .bg-warning { background-color: #ffc107 !important; }
        .bg-secondary { background-color: #6c757d !important; }
        .bg-success { background-color: #198754 !important; }
        .bg-danger { background-color: #dc3545 !important; }
        
        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
        }
        
        .error-toast {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .success-toast {
            background: linear-gradient(135deg, #198754 0%, #157347 100%);
            color: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3);
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
        
        /* Modal Styles */
        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }
        
        .modal-footer {
            border-top: 1px solid var(--milk-tea);
            border-radius: 0 0 20px 20px;
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
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid var(--milk-tea);
        }
        
        .order-summary {
            background: linear-gradient(135deg, #fff 0%, var(--light-cream) 100%);
            border-left: 4px solid var(--caramel);
        }
        
        .cancelled-order {
            opacity: 0.8;
            background-color: rgba(220, 53, 69, 0.05);
        }
    </style>
</head>
<body>
    <?php include '../partials/header.php'; ?>
    
    <!-- Toast Notifications -->
    <div class="toast-container">
        <?php if (isset($_SESSION['error'])): ?>
            <div id="errorToast" class="toast error-toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div id="successToast" class="toast success-toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?php echo htmlspecialchars($_SESSION['success']); ?>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    </div>

    <!-- Cancel Order Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelOrderModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Cancel Order
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel order <strong>#<?php echo $order_id; ?></strong>?</p>
                    <p class="text-danger mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        This action cannot be undone. If your payment was already processed, it may take 3-5 business days for the refund to appear in your account.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Keep Order
                    </button>
                    <form method="POST" action="order_details.php?order_id=<?php echo $order_id; ?>">
                        <button type="submit" name="cancel_order" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Yes, Cancel Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-receipt me-3"></i>Order Details #<?php echo $order['order_id']; ?></h2>
        </div>
    </div>

    <div class="container">
        <div class="card <?php echo $order['order_status'] === 'Cancelled' ? 'cancelled-order' : ''; ?>">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-calendar-alt me-2"></i>Order Date:</h5>
                        <p><?php echo date("F j, Y, g:i A", strtotime($order['order_datetime'])); ?></p>

                        <h5><i class="fas fa-clock me-2"></i>Scheduled Date:</h5>
                        <p><?php echo $order['scheduled_datetime'] ? date("F j, Y, g:i A", strtotime($order['scheduled_datetime'])) : 'N/A'; ?></p>
                        
                        <?php if ($order['order_status'] === 'Cancelled' && isset($order['cancelled_at'])): ?>
                            <h5><i class="fas fa-times-circle me-2"></i>Cancelled Date:</h5>
                            <p class="text-danger"><?php echo date("F j, Y, g:i A", strtotime($order['cancelled_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-tag me-2"></i>Status:</h5>
                        <p>
                            <?php
                                $status = $order['order_status'];
                                switch($status) {
                                    case 'Received': echo '<span class="badge bg-warning text-dark">Received</span>'; break;
                                    case 'Preparing': echo '<span class="badge bg-primary">Preparing</span>'; break;
                                    case 'Ready for Pickup': echo '<span class="badge bg-info text-dark">Ready for Pickup</span>'; break;
                                    case 'Out for Delivery': echo '<span class="badge bg-secondary">Out for Delivery</span>'; break;
                                    case 'Delivered': echo '<span class="badge bg-success">Delivered</span>'; break;
                                    case 'Completed': echo '<span class="badge bg-success">Completed</span>'; break;
                                    case 'Cancelled': echo '<span class="badge bg-danger">Cancelled</span>'; break;
                                    default: echo '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>';
                                }
                            ?>
                        </p>

                        <h5><i class="fas fa-credit-card me-2"></i>Payment Method:</h5>
                        <p><?php echo htmlspecialchars($order['payment_method']); ?> 
                            (<span class="badge <?php 
                                switch($order['payment_status']) {
                                    case 'Paid': echo 'bg-success'; break;
                                    case 'Pending': echo 'bg-warning text-dark'; break;
                                    case 'Failed': echo 'bg-danger'; break;
                                    case 'Refunded': echo 'bg-info text-dark'; break;
                                    case 'Refund Pending': echo 'bg-warning text-dark'; break;
                                    default: echo 'bg-secondary';
                                }
                            ?>"><?php echo htmlspecialchars($order['payment_status']); ?></span>)
                        </p>
                    </div>
                </div>

                <?php if (!empty($order['special_instructions'])): ?>
                    <h5><i class="fas fa-sticky-note me-2"></i>Special Instructions:</h5>
                    <p class="p-3 bg-light rounded"><?php echo htmlspecialchars($order['special_instructions']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <h4 class="mb-4"><i class="fas fa-list-alt me-2"></i>Order Items</h4>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo get_image_url($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="item-image me-3">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($item['base_price'],2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><strong>₱<?php echo number_format($item['total'],2); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card order-summary mt-4">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($order['subtotal'],2); ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Delivery Fee:</span>
                    <span>₱<?php echo number_format($order['delivery_fee'],2); ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <strong>Total Amount:</strong>
                    <strong>₱<?php echo number_format($order['total_amount'],2); ?></strong>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="order_history.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
            
            <?php 
            $cancellable_statuses = ['Received', 'Preparing'];
            if (in_array($order['order_status'], $cancellable_statuses)): 
            ?>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderModal">
                    <i class="fas fa-times me-2"></i>Cancel Order
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../partials/footer.php'; ?>
    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show toast notifications automatically
        document.addEventListener('DOMContentLoaded', function() {
            // Show error toast
            var errorToast = document.getElementById('errorToast');
            if (errorToast) {
                var toast = new bootstrap.Toast(errorToast);
                toast.show();
                setTimeout(() => toast.hide(), 5000);
            }
            
            // Show success toast
            var successToast = document.getElementById('successToast');
            if (successToast) {
                var toast = new bootstrap.Toast(successToast);
                toast.show();
                setTimeout(() => toast.hide(), 5000);
            }
        });
    </script>
</body>
</html>