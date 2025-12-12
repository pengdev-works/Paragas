<?php
require_once '../config/db.php';
session_start(); 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION['order_id'])) {
    header("Location: ../index.php");
    exit();
}

$order_id = $_SESSION['order_id'];
unset($_SESSION['order_id']);

// Get order details
$order_query = "
    SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.user_id 
    WHERE o.order_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();
$order_stmt->close();

// Get order items
$items_query = "
    SELECT oi.*, m.name 
    FROM order_items oi 
    JOIN menu_items m ON oi.item_id = m.item_id 
    WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - Brew & Bubble</title>
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
        
        .success-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px;
        }
        
        .card-body {
            padding: 40px;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
            animation: bounce 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-30px);}
            60% {transform: translateY(-15px);}
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            border: none;
            border-radius: 30px;
            font-weight: 600;
            padding: 12px 30px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--coffee) 0%, var(--dark-chocolate) 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-outline-primary {
            color: var(--boba);
            border-color: var(--boba);
            border-radius: 30px;
            font-weight: 600;
            padding: 12px 30px;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--boba);
            border-color: var(--boba);
            color: white;
            transform: translateY(-3px);
        }
        
        .order-details {
            background: linear-gradient(135deg, #fff 0%, var(--light-cream) 100%);
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            border-left: 4px solid var(--caramel);
        }
        
        .order-number {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        h2 {
            color: var(--dark-chocolate);
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        h5 {
            color: var(--dark-chocolate);
            font-weight: 700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--milk-tea);
        }
        
        .detail-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        
        .detail-item strong {
            color: var(--dark-chocolate);
        }
        
        .item-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .item-list li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .item-list li:last-child {
            border-bottom: none;
        }
        
        .thank-you {
            color: var(--caramel);
            font-weight: 600;
            margin-bottom: 30px;
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
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: var(--caramel);
            opacity: 0.7;
            animation: confetti-fall 5s linear forwards;
            z-index: 1000;
        }
        
        @keyframes confetti-fall {
            0% {
                transform: translateY(-100vh) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <?php include '../partials/header.php'; ?>
    
    <div class="container">
        <div class="success-card card">
            <div class="card-body text-center">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h2>Order Placed Successfully!</h2>
                
                <p class="thank-you">Thank you for your order. We're preparing your delicious drinks!</p>
                
                <div class="order-number">
                    Order #<?php echo $order_id; ?>
                </div>
                
                <div class="order-details">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-receipt me-2"></i>Order Details</h5>
                            <div class="detail-item">
                                <span>Order Type:</span>
                                <strong><?php echo htmlspecialchars($order['order_type']); ?></strong>
                            </div>
                            <div class="detail-item">
                                <span>Payment Method:</span>
                                <strong><?php echo htmlspecialchars($order['payment_method']); ?></strong>
                            </div>
                            <div class="detail-item">
                                <span>Total Amount:</span>
                                <strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                            </div>
                            <?php if (!empty($order['special_instructions'])): ?>
                                <div class="detail-item">
                                    <span>Special Instructions:</span>
                                    <strong><?php echo htmlspecialchars($order['special_instructions']); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <h5><i class="fas fa-list me-2"></i>Order Items</h5>
                            <ul class="item-list">
                                <?php foreach ($order_items as $item): ?>
                                    <li>
                                        <div class="d-flex justify-content-between">
                                            <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                                            <strong>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="../index.php" class="btn btn-primary me-3">
                        <i class="fas fa-home me-2"></i>Continue Shopping
                    </a>
                    <a href="order_history.php" class="btn btn-outline-primary">
                        <i class="fas fa-history me-2"></i>View Order History
                    </a>
                </div>
                
                <div class="mt-4 text-muted">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        You will receive an email confirmation shortly. 
                        <?php if ($order['order_type'] === 'Delivery'): ?>
                            Your order will be delivered in approximately 30-45 minutes.
                        <?php else: ?>
                            Your order will be ready for pickup in approximately 15-25 minutes.
                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../partials/footer.php'; ?>
    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple confetti effect
        document.addEventListener('DOMContentLoaded', function() {
            const colors = ['#A67C52', '#D4B996', '#815A3B', '#6F4E37', '#F7F0E0'];
            
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + 'vw';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.width = (5 + Math.random() * 10) + 'px';
                    confetti.style.height = (5 + Math.random() * 10) + 'px';
                    document.body.appendChild(confetti);
                    
                    // Remove confetti after animation
                    setTimeout(() => {
                        confetti.remove();
                    }, 5000);
                }, i * 100);
            }
        });
    </script>
</body>
</html>