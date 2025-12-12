<?php
require_once '../config/db.php';
session_start(); 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = "Please login to checkout.";
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_query = "
    SELECT c.cart_id, c.quantity, m.item_id, m.name, m.base_price 
    FROM cart c 
    JOIN menu_items m ON c.item_id = m.item_id 
    WHERE c.user_id = ?";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cart_items = [];
$subtotal = 0;

while ($item = $cart_result->fetch_assoc()) {
    $item_total = $item['base_price'] * $item['quantity'];
    $subtotal += $item_total;
    $cart_items[] = $item;
}

$cart_stmt->close();

if (empty($cart_items)) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: ../cart/view_cart.php");
    exit();
}

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_type = sanitize_input($_POST['order_type']);
    $payment_method = sanitize_input($_POST['payment_method']);
    $special_instructions = sanitize_input($_POST['special_instructions']);
    
    // Calculate delivery fee
    $delivery_fee = ($order_type === 'Delivery') ? 50.00 : 0.00;
    $total_amount = $subtotal + $delivery_fee;
    
    // Create order
    $order_stmt = $conn->prepare("
        INSERT INTO orders (user_id, order_type, subtotal, delivery_fee, total_amount, payment_method, special_instructions) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $order_stmt->bind_param("isdddss", $user_id, $order_type, $subtotal, $delivery_fee, $total_amount, $payment_method, $special_instructions);
    
    if ($order_stmt->execute()) {
        $order_id = $conn->insert_id;
        
        // Add order items
        $order_item_stmt = $conn->prepare("
            INSERT INTO order_items (order_id, item_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($cart_items as $item) {
            $order_item_stmt->bind_param("iiid", $order_id, $item['item_id'], $item['quantity'], $item['base_price']);
            $order_item_stmt->execute();
        }
        $order_item_stmt->close();
        
        // Clear cart
        $clear_cart_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear_cart_stmt->bind_param("i", $user_id);
        $clear_cart_stmt->execute();
        $clear_cart_stmt->close();
        
        $order_stmt->close();
        
        $_SESSION['order_id'] = $order_id;
        header("Location: order_success.php");
        exit();
    } else {
        $_SESSION['error'] = "Error creating order. Please try again.";
    }
    $order_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Brew & Bubble</title>
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
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 30px;
            font-weight: 600;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
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
        
        .page-header h2 {
            color: white;
            font-weight: 800;
            margin-bottom: 0;
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
        
        .order-summary {
            background: linear-gradient(135deg, #fff 0%, var(--light-cream) 100%);
            border-left: 4px solid var(--caramel);
        }
        
        .summary-item {
            display: flex;
            justify-content: between;
            margin-bottom: 10px;
        }
        
        .summary-total {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--dark-chocolate);
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
        
        .checkout-icon {
            font-size: 1.2rem;
            color: var(--caramel);
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include '../partials/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-shopping-bag me-3"></i>Checkout</h2>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-circle me-2"></i>Error</h5>
                <p class="mb-0"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>₱<?php echo number_format($item['base_price'], 2); ?></td>
                                            <td><strong>₱<?php echo number_format($item['base_price'] * $item['quantity'], 2); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Checkout Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="order_type" class="form-label"><i class="fas fa-truck checkout-icon"></i>Order Type</label>
                                <select class="form-control" id="order_type" name="order_type" required>
                                    <option value="Pickup">Pickup</option>
                                    <option value="Delivery">Delivery</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="payment_method" class="form-label"><i class="fas fa-credit-card checkout-icon"></i>Payment Method</label>
                                <select class="form-control" id="payment_method" name="payment_method" required>
                                    <option value="COD">Cash on Delivery</option>
                                    <option value="GCash">GCash</option>
                                    <option value="Maya">Maya</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Debit Card">Debit Card</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="special_instructions" class="form-label"><i class="fas fa-sticky-note checkout-icon"></i>Special Instructions (Optional)</label>
                                <textarea class="form-control" id="special_instructions" name="special_instructions" rows="3" placeholder="Any special requests or instructions..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 py-3">
                                <i class="fas fa-check-circle me-2"></i>Place Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card order-summary">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="summary-item d-flex justify-content-between mb-3">
                            <span>Subtotal:</span>
                            <span>₱<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-item d-flex justify-content-between mb-3">
                            <span>Delivery Fee:</span>
                            <span id="delivery_fee">₱0.00</span>
                        </div>
                        <hr>
                        <div class="summary-item d-flex justify-content-between mb-3">
                            <strong class="summary-total">Total:</strong>
                            <strong class="summary-total" id="total_amount">₱<?php echo number_format($subtotal, 2); ?></strong>
                        </div>
                        <div class="mt-4 p-3 bg-light rounded">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Delivery fee of ₱50.00 will be added for delivery orders.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../partials/footer.php'; ?>
    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('order_type').addEventListener('change', function() {
            const deliveryFee = this.value === 'Delivery' ? 50.00 : 0.00;
            const subtotal = <?php echo $subtotal; ?>;
            const total = subtotal + deliveryFee;
            
            document.getElementById('delivery_fee').textContent = '₱' + deliveryFee.toFixed(2);
            document.getElementById('total_amount').textContent = '₱' + total.toFixed(2);
        });
    </script>
</body>
</html>