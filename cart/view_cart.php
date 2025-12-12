<?php
require_once '../config/db.php';
session_start(); 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $_SESSION['error'] = "Please login to view your cart.";
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $cart_id => $quantity) {
        $quantity = intval($quantity);
        if ($quantity < 1) {
            $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    $_SESSION['success'] = "Cart updated successfully!";
    header("Location: view_cart.php");
    exit();
}

// Handle item removal
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success'] = "Item removed from cart!";
    header("Location: view_cart.php");
    exit();
}

// Get cart items
$stmt = $conn->prepare("
    SELECT c.cart_id, c.quantity, m.item_id, m.name, m.base_price, m.image_url 
    FROM cart c 
    JOIN menu_items m ON c.item_id = m.item_id 
    WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$subtotal = 0;

while ($row = $result->fetch_assoc()) {
    $row['total'] = $row['base_price'] * $row['quantity'];
    $subtotal += $row['total'];
    $cart_items[] = $row;
}

$stmt->close();

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
    <title>Shopping Cart - Brew & Bubble</title>
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
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-3px);
        }
        
        .btn-outline-danger {
            border-radius: 30px;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border-color: #28a745;
            color: #155724;
            border-radius: 15px;
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
        
        .cart-item {
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .cart-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid var(--milk-tea);
        }
        
        .input-group {
            max-width: 140px;
        }
        
        .input-group .form-control {
            border-radius: 30px 0 0 30px;
        }
        
        .input-group .btn {
            border-radius: 0 30px 30px 0;
        }
        
        .order-summary {
            background: linear-gradient(135deg, #fff 0%, var(--light-cream) 100%);
            border-left: 4px solid var(--caramel);
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
    </style>
</head>
<body>
    <?php include '../partials/header.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h2><i class="fas fa-shopping-cart me-3"></i>Shopping Cart</h2>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="empty-state text-center py-5">
                <i class="fas fa-shopping-cart fa-4x mb-3" style="color: var(--caramel);"></i>
                <h3 class="mb-3">Your Cart is Empty</h3>
                <p class="lead mb-4">Looks like you haven't added any items to your cart yet.</p>
                <a href="../index.php" class="btn btn-primary btn-lg">Start Shopping <i class="fas fa-arrow-right ms-2"></i></a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="cart-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <img src="<?php echo get_image_url($item['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                     class="item-image img-fluid">
                                            </div>
                                            <div class="col-md-4">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                <p class="text-muted mb-0">₱<?php echo number_format($item['base_price'], 2); ?></p>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="input-group">
                                                    <input type="number" name="quantities[<?php echo $item['cart_id']; ?>]" 
                                                           value="<?php echo $item['quantity']; ?>" min="1" max="99" class="form-control">
                                                    <a href="?remove=<?php echo $item['cart_id']; ?>" class="btn btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-end">
                                                <strong>₱<?php echo number_format($item['total'], 2); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <div class="d-flex justify-content-between mt-4">
                                    <a href="../index.php" class="btn btn-outline-primary">
                                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                    </a>
                                    <button type="submit" name="update_cart" class="btn btn-primary">
                                        <i class="fas fa-sync-alt me-2"></i>Update Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card order-summary">
                            <div class="card-body">
                                <h5 class="card-title mb-4"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Delivery Fee:</span>
                                    <span>₱0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-4">
                                    <strong>Total:</strong>
                                    <strong>₱<?php echo number_format($subtotal, 2); ?></strong>
                                </div>
                                <button type="button" class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#checkoutModal">
    <i class="fas fa-check-circle me-2"></i>Proceed to Checkout
</button>

                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php include '../partials/footer.php'; ?>
    <script src="../public/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="checkoutModalLabel"><i class="fas fa-check-circle me-2"></i>Confirm Checkout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h6 class="mb-3">Items in your cart:</h6>
        <div class="list-group mb-3">
          <?php foreach ($cart_items as $item): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <img src="<?php echo get_image_url($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                     style="width:50px;height:50px;object-fit:cover;border-radius:5px;margin-right:10px;">
                <div>
                  <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                  <span class="text-muted"> x <?php echo $item['quantity']; ?></span>
                </div>
              </div>
              <span>₱<?php echo number_format($item['total'], 2); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span>Subtotal:</span>
          <span>₱<?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="d-flex justify-content-between mb-3">
          <span>Delivery Fee:</span>
          <span>₱0.00</span>
        </div>
        <hr>
        <div class="d-flex justify-content-between mb-0">
          <strong>Total:</strong>
          <strong>₱<?php echo number_format($subtotal, 2); ?></strong>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="../orders/checkout.php" class="btn btn-success">Yes, Proceed</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>