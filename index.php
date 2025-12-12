<?php
require_once 'config/db.php';
session_start(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brew & Bubble - Milk Tea Shop</title>
    <link href="public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
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
            line-height: 1.6;
        }
        
        /* Compact Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            padding: 60px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('public/images/background.avif') no-repeat center center;
            background-size: cover;
            opacity: 0.12;
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero-content h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        .hero-content p {
            font-size: 1.1rem;
            margin-bottom: 25px;
        }
        
        /* Compact Cards */
        .card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .category-card {
            background: linear-gradient(135deg, #fff 0%, var(--light-cream) 100%);
            border-left: 3px solid var(--caramel);
            text-align: center;
            padding: 20px;
        }
        
        .category-icon {
            font-size: 2rem;
            color: var(--caramel);
            margin-bottom: 15px;
        }
        
        .card-title {
            color: var(--dark-chocolate);
            fontWeight: 700;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        /* Compact Menu Items */
        .menu-item-card .card-img-top {
            height: 180px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .menu-item-card:hover .card-img-top {
            transform: scale(1.03);
        }
        
        .menu-item-card .card-body {
            padding: 20px;
        }
        
        .price {
            color: var(--coffee);
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--boba);
            border-color: var(--boba);
            border-radius: 25px;
            font-weight: 600;
            padding: 10px 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--dark-chocolate);
            border-color: var(--dark-chocolate);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        
        .btn-light {
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* Badges */
        .badge {
            font-size: 0.65rem;
            padding: 5px 10px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .badge.bg-warning {
            background-color: var(--caramel) !important;
        }
        
        .badge.bg-success {
            background-color: var(--coffee) !important;
        }
        
        /* Section Headings */
        h2 {
            color: var(--dark-chocolate);
            font-weight: 800;
            margin-bottom: 30px;
            font-size: 2rem;
            position: relative;
            display: inline-block;
        }
        
        h2:after {
            content: '';
            position: absolute;
            width: 40%;
            height: 3px;
            background: linear-gradient(to right, var(--caramel), var(--coffee));
            bottom: -10px;
            left: 30%;
            border-radius: 2px;
        }
        
        h3 {
            color: var(--dark-chocolate);
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.5rem;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--caramel);
        }
        
        /* Input Group */
        .input-group {
            max-width: 180px;
        }
        
        .form-control {
            border-radius: 20px 0 0 20px;
            border: 1px solid var(--milk-tea);
        }
        
        /* Sections */
        .section {
            padding: 50px 0;
        }
        
        .menu-section {
            background-color: #fff;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .hero-content h1 {
            animation: fadeInUp 0.8s ease-out;
        }
        
        .hero-content p {
            animation: fadeInUp 0.8s ease-out 0.1s both;
        }
        
        .hero-content a {
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }
        
        /* Toast Notification */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
        
        .toast {
            background: linear-gradient(135deg, var(--caramel) 0%, var(--coffee) 100%);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: none;
        }
        
        .toast-header {
            background: rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.2);
            color: white;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content p {
                font-size: 1rem;
            }
            
            h2 {
                font-size: 1.75rem;
            }
            
            h3 {
                font-size: 1.3rem;
            }
            
            .category-icon {
                font-size: 1.8rem;
            }
            
            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'partials/header.php'; ?>
    
    <!-- Toast Notification Container -->
    <div class="toast-container"></div>
    
    <!-- Hero Section -->
    <section class="hero-section text-white">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>Brew & Bubble</h1>
                    <p>Experience the perfect blend of flavors with our premium milk tea and coffee selections. Crafted with passion and the finest ingredients.</p>
                    <a href="#menu" class="btn btn-light">Explore Menu <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
                <div class="col-lg-6 d-none d-lg-block text-center">
                    <div class="hero-image-container position-relative">
                        <div class="rounded-circle position-absolute" style="width: 80px; height: 80px; background: var(--caramel); top: -15px; left: 20px; opacity: 0.8;"></div>
                        <div class="rounded-circle position-absolute" style="width: 60px; height: 60px; background: var(--milk-tea); bottom: 30px; right: 30px; opacity: 0.7;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Featured Categories -->
    <section class="section">
        <div class="container">
            <h2 class="text-center">Our Categories</h2>
            <div class="row">
                <?php
                $categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order";
                $categories_result = $conn->query($categories_query);
                
                if ($categories_result->num_rows > 0) {
                    while ($category = $categories_result->fetch_assoc()) {
                        echo '
                        <div class="col-md-4 mb-4">
                            <div class="card category-card">
                                <div class="card-body text-center p-3">
                                    <div class="category-icon mb-2">
                                        <i class="fas fa-mug-hot"></i>
                                    </div>
                                    <h5 class="card-title">' . htmlspecialchars($category['name']) . '</h5>
                                    <p class="card-text">' . htmlspecialchars($category['description']) . '</p>
                                    <a href="#category-' . $category['category_id'] . '" class="btn btn-primary btn-sm">View Items</a>
                                </div>
                            </div>
                        </div>';
                    }
                } else {
                    echo '<div class="col-12"><p class="text-center">No categories found.</p></div>';
                }
                ?>
            </div>
        </div>
    </section>
    
    <!-- Menu Section -->
    <section id="menu" class="section menu-section">
        <div class="container">
            <h2 class="text-center">Our Menu</h2>
            
            <?php
            // Reset pointer for categories result
            $categories_result = $conn->query($categories_query);
            
            if ($categories_result->num_rows > 0) {
                while ($category = $categories_result->fetch_assoc()) {
                    echo '
                    <div id="category-' . $category['category_id'] . '" class="mb-4">
                        <h3 class="text-center">' . htmlspecialchars($category['name']) . '</h3>
                        <div class="row">';
                    
                    $items_query = "SELECT * FROM menu_items WHERE category_id = " . $category['category_id'] . " AND is_active = 1 ORDER BY display_order";
                    $items_result = $conn->query($items_query);
                    
                    if ($items_result->num_rows > 0) {
                        while ($item = $items_result->fetch_assoc()) {
                            $badges = '';
                            if ($item['is_best_seller']) {
                                $badges .= '<span class="badge bg-warning me-1">Best Seller</span>';
                            }
                            if ($item['is_new_arrival']) {
                                $badges .= '<span class="badge bg-success me-1">New</span>';
                            }
                            
                            echo '
                            <div class="col-md-4 mb-4">
                                <div class="card menu-item-card">
                                    <div style="overflow: hidden;">
                                        <img src="' . htmlspecialchars($item['image_url'] ?? 'public/images/placeholder.png') . '" class="card-img-top" alt="' . htmlspecialchars($item['name']) . '">
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title">' . htmlspecialchars($item['name']) . '</h5>
                                            <div class="badges">' . $badges . '</div>
                                        </div>
                                        <p class="card-text mb-2">' . htmlspecialchars($item['description']) . '</p>
                                        <p class="price mb-3">₱' . number_format($item['base_price'], 2) . '</p>';
                                        
                                        if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                                            echo '
                                            <form action="cart/add_to_cart.php" method="POST" class="add-to-cart-form" data-item-name="' . htmlspecialchars($item['name']) . '">
                                                <input type="hidden" name="item_id" value="' . $item['item_id'] . '">
                                                <div class="input-group">
                                                    <input type="number" name="quantity" class="form-control" value="1" min="1" max="10">
                                                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                                                </div>
                                            </form>';
                                        } else {
                                            echo '<a href="auth/login.php" class="btn btn-outline-primary btn-sm">Login to Order</a>';
                                        }
                                        
                                        echo '
                                    </div>
                                </div>
                            </div>';
                        }
                    } else {
                        echo '<div class="col-12"><p class="text-center">No items in this category.</p></div>';
                    }
                    
                    echo '</div></div>';
                }
            }
            ?>
        </div>
    </section>
    
    <?php include 'partials/footer.php'; ?>
    <script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add to cart functionality with alert
        document.addEventListener('DOMContentLoaded', function() {
            const addToCartForms = document.querySelectorAll('.add-to-cart-form');
            
            addToCartForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const itemName = this.getAttribute('data-item-name');
                    const quantity = formData.get('quantity');
                    
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
                    submitBtn.disabled = true;
                    
                    // Send AJAX request to add to cart
                    fetch('cart/add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Success!', quantity + ' ' + itemName + ' added to cart!', 'success');
                            
                            // Update cart count in navbar
                            if (data.cart_count !== undefined) {
                                updateCartCount(data.cart_count);
                            }
                        } else {
                            showToast('Error', data.message || 'Failed to add item to cart', 'error');
                        }
                    })
                    .catch(error => {
                        showToast('Error', 'Something went wrong! Please try again.', 'error');
                    })
                    .finally(() => {
                        // Restore button state
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
                });
            });
            
            // Toast notification function
            function showToast(title, message, type = 'success') {
                const toastContainer = document.querySelector('.toast-container');
                const toastId = 'toast-' + Date.now();
                
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.id = toastId;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                
                toast.innerHTML = `
                    <div class="toast-header">
                        <strong class="me-auto">${title}</strong>
                        <small>Just now</small>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                        ${message}
                    </div>
                `;
                
                toastContainer.appendChild(toast);
                
                const bsToast = new bootstrap.Toast(toast, {
                    autohide: true,
                    delay: 3000
                });
                
                bsToast.show();
                
                // Remove toast from DOM after it's hidden
                toast.addEventListener('hidden.bs.toast', function () {
                    toast.remove();
                });
            }
            
            // Update cart count in navbar
            function updateCartCount(count) {
                const cartCountElements = document.querySelectorAll('#cart-count');
                cartCountElements.forEach(element => {
                    element.textContent = count;
                });
            }
            
            // Load initial cart count
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            fetch('cart/get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.count !== undefined) {
                        updateCartCount(data.count);
                    }
                })
                .catch(error => console.error('Error fetching cart count:', error));
            <?php endif; ?>
        });
    </script>
</body>
</html>